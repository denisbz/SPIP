<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@insere_1_init
function insere_1_init($request) {

  //  preparation de la table des translations
	$spip_translate = array(
		"type"		=> "VARCHAR(16) NOT NULL",
		"ajout"		=> "ENUM('0', '1')",
		"titre"		=> "text NOT NULL",
                "id_old"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
                "id_new"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

	$spip_translate_key = array(
                "PRIMARY KEY"	=> "id_old, id_new, type",
                "KEY id_old"	=> "id_old");

	spip_mysql_create('spip_translate', $spip_translate, $spip_translate_key, true);
	// au cas ou la derniere fois ce serait terminee anormalement
	spip_query("DELETE FROM spip_translate");
	return insere_1bis_init($request);
}

// http://doc.spip.org/@insere_1bis_init
function insere_1bis_init($request) {

	// l'insertion porte sur les tables principales ...
	$t = array_keys($GLOBALS['tables_principales']);
	// ... mais pas cette table a cause de la duplication des login 
	unset($t[array_search('spip_auteurs', $t)]);
	return $t;
}

// En passe 2, relire les tables principales et les tables auxiliaires 
// sur les mots et les documents car on sait les identifier

// http://doc.spip.org/@insere_2_init
function insere_2_init($request) {
	$t = insere_1bis_init($request);

	// ne pas importer cette table, son homologue est prioritaire
	unset($t[array_search('spip_types_documents', $t)]);

	$t[]= 'spip_mots_articles';
	$t[]= 'spip_mots_breves';
	$t[]= 'spip_mots_rubriques';
	$t[]= 'spip_mots_syndic';
	$t[]= 'spip_mots_forum';
	$t[]= 'spip_mots_documents';
	$t[]= 'spip_documents_articles';
	$t[]= 'spip_documents_rubriques';

	return $t;
}

//   construire le tableau PHP de la table spip_translate
// (mis en table pour pouvoir reprendre apres interruption)

// http://doc.spip.org/@translate_init
function translate_init($request) {

	include_spip('inc/texte'); // pour les Regexp des raccourcis
	include_spip('inc/chercher_logo'); // pour les noms des logos
	include_spip('inc/distant'); // pour recuperer les logos

	$q = spip_query("SELECT * FROM spip_translate");
	$trans = array();
	while ($r = sql_fetch($q)) {
		$trans[$r['type']][$r['id_old']] = array($r['id_new'], $r['titre'], intval($r['ajout']));
	}
	return $trans;
}


// http://doc.spip.org/@import_insere
function import_insere($values, $table, $desc, $request, $atts) {

	$type_id = $desc['key']["PRIMARY KEY"];
	// reserver une place dans les tables principales si nouveau
	$ajout = 0;
	if ((!function_exists($f = 'import_identifie_' . $type_id))
	OR (!($n = $f($values, $table, $desc, $request)))) {
	  // pas d'importation de types_doc (a revoir)
		if ($table == 'spip_types_documents') return;
		$n = sql_insert($table, '', '()');
		if (!$n) {
			$GLOBALS['erreur_restauration'] = sql_error();
			return;
		}
		$ajout = 1;
	}

	if (is_array($n))
		list($id, $titre) = $n; 
	else {$id = $n; $titre = "";}
	sql_insert('spip_translate',
				"(id_old, id_new, titre, type, ajout)",
				     "(". $values[$type_id] .",$id, " . _q($titre) . ", '$type_id', '$ajout')");
}

// Renumerotation des entites collectees
// Appelle la fonction specifique a la table, ou a defaut la std.
// Le tableau de correspondance est global, et permet qu'un numero
// d'une entite soit calcule une seule fois, a sa premiere occurrence.
// (Mais des requetes avec jointures eviteraient sa construction. A voir)

// http://doc.spip.org/@import_translate
function import_translate($values, $table, $desc, $request, $atts) {

	if (!function_exists($f = 'import_translate_' . $table))
	  $f = 'import_translate_std';
	$f($values, $table, $desc, $request, $atts);
}

// La fonction d'insertion apres renumerotation.
// Afin qu'inserer une 2e fois la meme sauvegarde ne change pas la base,
// chaque entree de la sauvegarde est ignoree s'il existe une entree
// de meme titre avec le meme contexte (parent etc) dans la base installee.
// Une synchronisation plus fine serait preferable, cf [8004]

// http://doc.spip.org/@import_inserer_translate
function import_inserer_translate($values, $table, $desc, $request, $vals, $atts) {
	global $trans;
	$p = $desc['key']["PRIMARY KEY"];
	$v = $values[$p];

	if (!isset($trans[$p]) OR !isset($trans[$p][$v]) OR $trans[$p][$v][2]){
		spip_query("REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .substr($vals,1) . ')');

		$on = isset($atts['on']) ? ($atts['on']) : '';
		$off = isset($atts['off']) ? ($atts['off']) : '';
		if ($on OR $off) {
			$t = type_du_logo($p);		  
			$url = $request['url_site'];
			if (!$url) $url = $atts['adresse_site'];
			if (substr($url,-1) !='/') $url .='/';
			$url .= $atts['dir_logos'];
			$new = $trans[$p][$v][0];
			if ($on) {
			  if ($logo = recuperer_page($url . $t . "on$v." . $on))
			    ecrire_fichier(_DIR_LOGOS. $t . "on$new." . $on, $logo);
			}
			if ($off) {
			  if ($logo = recuperer_page($url . $t . "off$v." . $off))
			    ecrire_fichier(_DIR_LOGOS. $t . "off$new." . $off, $logo);
			}
		}
	}
}

// Insertion avec renumerotation, y compris des raccourcis.
// http://doc.spip.org/@import_translate_std
function import_translate_std($values, $table, $desc, $request, $atts) {

	$vals = '';

	foreach ($values as $k => $v) {
		if ($k=='id_parent' OR $k=='id_secteur')
				$k = 'id_rubrique';
		else  if (($k=='chapo') AND ($v[0]=='=') AND preg_match(_RACCOURCI_CHAPO, substr($v,1), $m))
			$v = '=[->' . substr($v,1) . ']';

		$v = importe_raccourci($k,importe_translate_maj($k, $v));

		$vals .= "," . _q($v);
	}
	import_inserer_translate($values, $table, $desc, $request, $vals, $atts);
}

// http://doc.spip.org/@import_translate_spip_documents
function import_translate_spip_documents($values, $table, $desc, $request, $atts) {

	$url = $request['url_site'];
	if (!$url) $url = $atts['adresse_site'];
	if (substr($url,-1) !='/') $url .='/';
#	$url .= $atts['dir_img']; // deja dans la BD importee
	$values['distant']= 'oui';

	$vals = '';
	foreach ($values as $k => $v) {
	  if ($k=='fichier')
	    $v = $url .$v;
	  else $v = importe_raccourci($k,importe_translate_maj($k, $v));
	  $vals .= "," . _q($v);
	}
	import_inserer_translate($values, $table, $desc, $request, $vals, $atts);
}

// Fonction de renumerotation, par delegation aux fonction specialisees
// Si une allocation est finalement necessaire, celles-ci doivent repercuter
// la renumerotation sur la table SQL temporaire pour qu'en cas de reprise
// sur Time-Out il n'y ait pas reallocation.
// En l'absence d'allocation, cet acces SQL peut etre omis, quitte a 
// recalculer le nouveau numero  si une autre occurrence est rencontree
// a la reprise. Pas dramatique.

// http://doc.spip.org/@importe_translate_maj
function importe_translate_maj($k, $v)
{
	global $trans;
	if (!(isset($trans[$k]) AND isset($trans[$k][$v]))) return $v;
	list($g, $titre, $ajout) = $trans[$k][$v];
	if ($g <= 0) {
		$f = 'import_identifie_parent_' . $k;
		$g = $f($g, $titre, $v);
		if ($g > 0)
			  // memoriser qu'on insere
			$trans[$k][$v][2]=1;
		else $g = (0-$g);
		$trans[$k][$v][0] = $g;
	}
	return $g;
}

define('_RACCOURCI_MODELE_ALL', '@' . _RACCOURCI_MODELE .'@isS');

// http://doc.spip.org/@importe_raccourci
function importe_raccourci($k, $v)
{

	if (preg_match_all(_RACCOURCI_LIEN, $v, $m, PREG_SET_ORDER)) {
		foreach ($m as $regs) {
		  // supprimer 'http://' ou 'mailto:'
		  	$lien = vider_url($regs[3]);
			if ($match = typer_raccourci($lien)) {
				list($f,$objet,$id,$params,$ancre) = $match;
				$k = 'id_' . $f;
				$g = importe_translate_maj($k, $id);
				if ($g != $id) {

				  $rac = '[' . $regs[1] . '->' . $reg[2] . $objet . $g . $params . $ancre .']';
				  $v = str_replace($regs[0], $rac, $v);
				}
			}
		}
	}

	if (preg_match_all(_RACCOURCI_MODELE_ALL, $v, $m, PREG_SET_ORDER)) {
		foreach ($m as $regs) {
			$g = importe_translate_maj('id_document', $regs[3]);
			if ($g != $regs[3]) {
				$rac = '<' . $regs[2] . $g . $regs[4] . '>' . $regs[5];
				$v = str_replace($regs[0], $rac, $v);
			}
		}
	}
	return $v;
}

// un document importe est considere comme identique a un document present
// s'ils ont meme taille et meme nom
// http://doc.spip.org/@import_identifie_id_document
function import_identifie_id_document($values, $table, $desc, $request) {
	$t = $values['taille'];
	$f = $values['fichier'];
	$h = $request['url_site'] . $f;
	$r = sql_fetch(spip_query("SELECT id_document AS id, fichier AS titre FROM spip_documents WHERE taille=" . _q($t) . " AND (fichier=" . _q($f) . " OR fichier= " . _q($h) . ')'));
	return array($r['id'], $r['titre']);
}

// un type de document importe est considere comme identique a un type present
// s'ils ont meme extension et meme titre
// Sinon il ne sera PAS importe
// http://doc.spip.org/@import_identifie_id_type
function import_identifie_id_type($values, $table, $desc, $request) {
	$e = $values['extension'];
	$t = $values['titre'];
	$r = sql_fetch(spip_query("SELECT id_type AS id, titre FROM spip_types_documents WHERE extension=" . _q($e) . " AND titre=" . _q($t)));
	return array($r['id'], $r['titre']);
}

// deux groupes de mots ne peuvent avoir le meme titre ==> identification
// http://doc.spip.org/@import_identifie_id_groupe
function import_identifie_id_groupe($values, $table, $desc, $request)  {
	$r = sql_fetch(spip_query("SELECT id_groupe AS id, titre FROM spip_groupes_mots WHERE titre=" . _q($values['titre'])));
	return array($r['id'], $r['titre']);
}

// pour un mot le titre est insuffisant, il faut aussi l'identite du groupe.
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
// http://doc.spip.org/@import_identifie_id_mot
function import_identifie_id_mot($values, $table, $desc, $request) {
	return array((0 - $values['id_groupe']), $values['titre']);
}

// Passe 2: mot de meme titre et de meme groupe ==> identification
// http://doc.spip.org/@import_identifie_parent_id_mot
function import_identifie_parent_id_mot($id_groupe, $titre, $v)
{
	global $trans;
	$titre = _q($titre);
	$id_groupe = 0-$id_groupe;
	if (isset($trans['id_groupe'])
	AND isset($trans['id_groupe'][$id_groupe])) {
		$new = $trans['id_groupe'][$id_groupe][0];
		$r = sql_fetch(spip_query("SELECT id_mot FROM spip_mots WHERE titre=$titre AND id_groupe=$new" ));
		if ($r) return  (0 - $r['id_mot']);
	}
	$r = sql_insert('spip_mots', '', '()');
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_mot',1)");
	return $r;
}

// idem pour les articles
// http://doc.spip.org/@import_identifie_id_article
function import_identifie_id_article($values, $table, $desc, $request) {
	return array((0 - $values['id_rubrique']), $values['titre']);
}

// Passe 2 des articles comme pour les mots

// http://doc.spip.org/@import_identifie_parent_id_article
function import_identifie_parent_id_article($id_parent, $titre, $v)
{
	$id_parent = importe_translate_maj('id_rubrique', (0 - $id_parent));

	$titre = _q($titre);
	$r = sql_fetch(spip_query("SELECT id_article FROM spip_articles WHERE titre=$titre AND id_rubrique=$id_parent AND statut<>'poubelle'" ));
	if ($r) return (0 - $r['id_article']);

	$r = sql_insert('spip_articles', '', '()');
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_article',1)");
	return $r;
}

// idem pour les breves
// http://doc.spip.org/@import_identifie_id_breve
function import_identifie_id_breve($values, $table, $desc, $request) {
	return array((0 - $values['id_rubrique']), $values['titre']);
}

// Passe 2 des breves comme pour les mots

// http://doc.spip.org/@import_identifie_parent_id_breve
function import_identifie_parent_id_breve($id_parent, $titre, $v)
{
	$id_parent = importe_translate_maj('id_rubrique', (0 - $id_parent));

	$titre = _q($titre);
	$r = sql_fetch(spip_query("SELECT id_breve FROM spip_breves WHERE titre=$titre AND id_rubrique=$id_parent AND statut<>'refuse'" ));
	if ($r) return (0 - $r['id_breve']);

	$r = sql_insert('spip_breves', '', '()');
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_breve',1)");
	return $r;
}


// pour une rubrique le titre est insuffisant, il faut l'identite du parent
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
// http://doc.spip.org/@import_identifie_id_rubrique
function import_identifie_id_rubrique($values, $table, $desc, $request) {
	return array((0 - $values['id_parent']), $values['titre']);
}

// Passe 2 des rubriques, renumerotation en cascade. 
// rubrique de meme titre et de meme parent ==> identification
// http://doc.spip.org/@import_identifie_parent_id_rubrique
function import_identifie_parent_id_rubrique($id_parent, $titre, $v)
{
	global $trans;
	if (isset($trans['id_rubrique'])) {
		if ($id_parent < 0) {
			$id_parent = (0 - $id_parent);
			$gparent = $trans['id_rubrique'][$id_parent][0];
			// parent deja renumerote depuis le debut la passe 2
			if ($gparent >= 0)
			  $id_parent = $gparent;
			else {
			  // premiere occurrence du parent
				$pitre = $trans['id_rubrique'][$id_parent][1];
				$n = import_identifie_parent_id_rubrique($gparent, $pitre, $id_parent);
				$trans['id_rubrique'][$id_parent][0] = ($n>0) ? $n: (0-$n);
				// parent tout neuf,
				// pas la peine de chercher un titre homonyme
				if ($n > 0) {
					$trans['id_rubrique'][$id_parent][2]=1; // nouvelle rub.
					return import_alloue_id_rubrique($n, $titre, $v);
				} else $id_parent = (0 - $n);
			}
		}

		$r = sql_fetch(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE titre=" . _q($titre) . " AND id_parent=" . intval($id_parent)));
		if ($r)  {
		  return (0 - $r['id_rubrique']);
		}
		return import_alloue_id_rubrique($id_parent, $titre, $v);
	}
}

// reserver la place en mettant titre et parent tout de suite
// pour que le SELECT ci-dessus fonctionne a la prochaine occurrence

// http://doc.spip.org/@import_alloue_id_rubrique
function import_alloue_id_rubrique($id_parent, $titre, $v) {
	$titre = _q($titre);
	$r = sql_insert('spip_rubriques', '(titre, id_parent)', "($titre,$id_parent)");
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_rubrique',1)");
	return $r;
}
?>
