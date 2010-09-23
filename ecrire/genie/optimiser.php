<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/abstract_sql');

// http://doc.spip.org/@genie_optimiser_dist
function genie_optimiser_dist($t) {

	optimiser_base_une_table();
	optimiser_base();

	// la date souhaitee pour le tour suivant = apres-demain a 4h du mat ;
	// sachant qu'on a un delai de 48h, on renvoie aujourd'hui a 4h du mat
	// avec une periode de flou entre 2h et 6h pour ne pas saturer un hebergeur
	// qui aurait beaucoup de sites SPIP
	return -(mktime(2,0,0) + rand(0, 3600*4));
}

// heure de reference pour le garbage collector = 24h auparavant
// http://doc.spip.org/@optimiser_base
function optimiser_base($attente = 86400) {
	optimiser_base_disparus($attente);
}


// http://doc.spip.org/@optimiser_base_une_table
function optimiser_base_une_table() {

	$tables = array();
	$result = sql_showbase();

	// on n'optimise qu'une seule table a chaque fois,
	// pour ne pas vautrer le systeme
	// lire http://dev.mysql.com/doc/refman/5.0/fr/optimize-table.html
	while ($row = sql_fetch($result))
		$tables[] = array_shift($row);

	if ($tables) {
		$table_op = intval($GLOBALS['meta']['optimiser_table']+1) % sizeof($tables);
		ecrire_meta('optimiser_table', $table_op);
		$q = $tables[$table_op];
		spip_log("debut d'optimisation de la table $q");
		if (sql_optimize($q))
			spip_log("fin d'optimisation de la table $q");
		else spip_log("Pas d'optimiseur necessaire");
	}
}

// mysql < 4.0 refuse les requetes DELETE multi table 
// et elles ont une syntaxe differente entre 4.0 et 4.1
// On passe donc par un SELECT puis DELETE avec IN 

// Utilitaire exploitant le SELECT et appliquant DELETE
// L'index du SELECT doit s'appeler "id"

// http://doc.spip.org/@optimiser_sansref
function optimiser_sansref($table, $id, $sel)
{
	$in = array();
	while ($row = sql_fetch($sel)) $in[$row['id']]=true;

	if ($in) {
		$in = join(',', array_keys($in));
		sql_delete($table,  sql_in($id,$in));
		spip_log("Numeros des entrees $id supprimees dans la table $table: $in");
	}
	return count($in);
}

// Nomenclature des liens morts entre les tables,
// suite a la suppresion d'articles, d'auteurs etc
// Maintenant que MySQL 5 a des Cascades on pourrait faire autrement
// mais on garde la compatibilite avec les versions precedentes.

// http://doc.spip.org/@optimiser_base_disparus
function optimiser_base_disparus($attente = 86400) {

	# format = 20060610110141, si on veut forcer une optimisation tout de suite
	$mydate = date("YmdHis", time() - $attente);

	$n = 0;

	//
	// Rubriques 
	//

	# les articles qui sont dans une id_rubrique inexistante
	# attention on controle id_rubrique>0 pour ne pas tuer les articles
	# specialement affectes a une rubrique non-existante (plugin,
	# cf. http://trac.rezo.net/trac/spip/ticket/1549 )
	$res = sql_select("A.id_article AS id",
		        "spip_articles AS A
		        LEFT JOIN spip_rubriques AS R
		          ON A.id_rubrique=R.id_rubrique",
			 "A.id_rubrique > 0
			 AND R.id_rubrique IS NULL
		         AND A.maj < $mydate");

	$n+= optimiser_sansref('spip_articles', 'id_article', $res);

	# les breves qui sont dans une id_rubrique inexistante
	$res = sql_select("B.id_breve AS id",
		        "spip_breves AS B
		        LEFT JOIN spip_rubriques AS R
		          ON B.id_rubrique=R.id_rubrique",
			"R.id_rubrique IS NULL
		         AND B.maj < $mydate");

	$n+= optimiser_sansref('spip_breves', 'id_breve', $res);

	# les droits d'auteurs sur une id_rubrique inexistante
	# (plusieurs entrees seront eventuellement detruites pour chaque rub)
	$res = sql_select("A.id_rubrique AS id",
	 	        "spip_auteurs_rubriques AS A
		        LEFT JOIN spip_rubriques AS R
		          ON A.id_rubrique=R.id_rubrique",
			"R.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_auteurs_rubriques', 'id_rubrique', $res);

	//
	// Articles
	//

	sql_delete("spip_articles", "statut='poubelle' AND maj < $mydate");

	# les liens d'auteurs d'articles effaces
	$res = sql_select("L.id_article AS id",
		      "spip_auteurs_articles AS L
		        LEFT JOIN spip_articles AS A
		          ON L.id_article=A.id_article",
			"A.id_article IS NULL");

	$n+= optimiser_sansref('spip_auteurs_articles', 'id_article', $res);


	//
	// Breves
	//

	sql_delete("spip_breves", "statut='refuse' AND maj < $mydate");


	//
	// Sites
	//

	sql_delete("spip_syndic", "maj < $mydate AND statut = 'refuse'");


	# les articles syndiques appartenant a des sites effaces
	$res = sql_select("S.id_syndic AS id",
		      "spip_syndic_articles AS S
		        LEFT JOIN spip_syndic AS syndic
		          ON S.id_syndic=syndic.id_syndic",
			"syndic.id_syndic IS NULL");

	$n+= optimiser_sansref('spip_syndic_articles', 'id_syndic', $res);


	//
	// Auteurs
	//

	# les liens d'articles sur des auteurs effaces
	$res = sql_select("L.id_auteur AS id",
		      "spip_auteurs_articles AS L
		        LEFT JOIN spip_auteurs AS A
		          ON L.id_auteur=A.id_auteur",
			"A.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_auteurs_articles', 'id_auteur', $res);

	# les liens de messages sur des auteurs effaces
	$res = sql_select("M.id_auteur AS id",
		      "spip_auteurs_messages AS M
		        LEFT JOIN spip_auteurs AS A
		          ON M.id_auteur=A.id_auteur",
			"A.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_auteurs_messages', 'id_auteur', $res);

	# les liens de rubriques sur des auteurs effaces
	$res = sql_select("A.id_rubrique AS id",
		      "spip_auteurs_rubriques AS A
		        LEFT JOIN spip_rubriques AS R
		          ON A.id_rubrique=R.id_rubrique",
			"R.id_rubrique IS NULL");

	$n+= optimiser_sansref('spip_auteurs_rubriques', 'id_rubrique', $res);

	# effacer les auteurs poubelle qui ne sont lies a aucun article
	$res = sql_select("A.id_auteur AS id",
		      	"spip_auteurs AS A
		      	LEFT JOIN spip_auteurs_articles AS L
		          ON L.id_auteur=A.id_auteur",
			"L.id_auteur IS NULL
		       	AND A.statut='5poubelle' AND A.maj < $mydate");

	$n+= optimiser_sansref('spip_auteurs', 'id_auteur', $res);

	# supprimer les auteurs 'nouveau' qui n'ont jamais donne suite
	# au mail de confirmation (45 jours pour repondre, ca devrait suffire)
	sql_delete("spip_auteurs", "statut='nouveau' AND maj < ". sql_quote(date('Y-m-d', time()-45*24*3600)));


	//
	// Documents
	//
	
	# les liens des documents qui sont lies a un objet inexistant
	$r = sql_select("DISTINCT objet","spip_documents_liens");
	while ($t = sql_fetch($r)){
		$type = $t['objet'];
		$spip_table_objet = table_objet_sql($type);
		$id_table_objet = id_table_objet($type);
		$res = sql_select("L.id_document AS id,id_objet",
			      "spip_documents_liens AS L
			        LEFT JOIN $spip_table_objet AS O
			          ON O.$id_table_objet=L.id_objet AND L.objet=".sql_quote($type),
				"O.$id_table_objet IS NULL");
		// sur une cle primaire composee, pas d'autres solutions que de virer un a un
		while ($row = sql_fetch($sel)){
			sql_delete("spip_documents_liens", array("id_document=".$row['id'],"id_objet=".$row['id_objet'],"objet=".sql_quote($type)));
			spip_log("Entree ".$row['id']."/".$row['id_objet']."/$type supprimee dans la table spip_documents_liens");
		}
	}
	
	// on ne nettoie volontairement pas automatiquement les documents orphelins
	
	//
	// Messages prives
	//

	# supprimer les messages lies a un auteur disparu
	$res = sql_select("M.id_message AS id",
		      "spip_messages AS M
		        LEFT JOIN spip_auteurs AS A
		          ON A.id_auteur=M.id_auteur",
			"A.id_auteur IS NULL");

	$n+= optimiser_sansref('spip_messages', 'id_message', $res);


	$n = pipeline('optimiser_base_disparus', array(
			'args'=>array(
				'attente' => $attente,
				'date' => $mydate),
			'data'=>$n
	));
	
	if (!$n) spip_log("Optimisation des tables: aucun lien mort");
}
?>
