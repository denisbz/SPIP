<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/serial');

// http://doc.spip.org/@supprime_invalideurs
function supprime_invalideurs() {
	spip_query("DELETE FROM spip_caches");
}

//
// Calcul des pages : noter dans la base les liens d'invalidation
//
// http://doc.spip.org/@maj_invalideurs
function maj_invalideurs ($fichier, &$page) {
	// ne pas noter les POST et les delais=0
	if ($fichier == '') return;

	// Supprimer les anciens invalideurs
	$f = str_replace('.gz', '', $fichier);
	spip_query("DELETE FROM spip_caches WHERE fichier='$f' OR fichier='$f.gz'");

	// Creer un invalideur 't' nous informant de la date d'expiration
	// et de la taille du fichier cache
	# Note : on ajoute 3600s pour eviter toute concurrence
	# entre un invalideur et un appel public de page
	$bedtime = time() + $page['entetes']['X-Spip-Cache'] + 3600;
	$taille = @filesize(_DIR_CACHE . $fichier);
	spip_query("INSERT IGNORE INTO spip_caches (fichier,id,type,taille) VALUES (" . spip_abstract_quote($fichier) . ",'$bedtime','t','$taille')");

	// invalidations
	insere_invalideur($page['invalideurs'], $fichier);
}

// pour les forums l'invalideur est : 'id_forum/a23'
// pour les petitions et autres, l'invalideur est par exemple :
// 'varia/pet60'
// http://doc.spip.org/@insere_invalideur
function insere_invalideur($inval, $fichier) {
	if ($inval)
	foreach ($inval as $type => $a) {
		if (is_array($a)) {
			$values = array();
			foreach($a as $k => $v)
				$values[] = "('$fichier', '$type/$k')";
			spip_query("INSERT IGNORE INTO spip_caches (fichier, id) VALUES " . join(", ", $values));
		}
	}
}


//
// Invalider les caches lies a telle condition
// on en profite pour noter la date de mise a jour dans les metas
//
// http://doc.spip.org/@suivre_invalideur
function suivre_invalideur($cond, $modif=true) {
	include_spip('inc/meta');
	if ($modif) {
		ecrire_meta('derniere_modif', time());
		ecrire_metas();
	}
	$result = spip_query("SELECT DISTINCT fichier FROM spip_caches WHERE $cond");
	$tous = array();
	while ($row = spip_fetch_array($result))
		$tous[] = $row['fichier'];

	spip_log("suivre $cond dans " . count($tous) . " caches");
	applique_invalideur($tous);
}


//
// Supprimer les vieux caches
//
// http://doc.spip.org/@retire_vieux_caches
function retire_vieux_caches() {
	$condition = "type='t' AND id<".time();
	suivre_invalideur($condition);
}


//
// Marquer les fichiers caches invalides comme etant a supprimer
//
// http://doc.spip.org/@applique_invalideur
function applique_invalideur($depart) {

	if ($depart) {
		$tous = "'".join("', '", $depart)."'";
		spip_log("applique $tous");

		include_spip('base/abstract_sql'); # pour calcul_mysql_in
		spip_query("UPDATE spip_caches SET type='x' WHERE " . calcul_mysql_in('fichier', $tous));

		// Demander a inc-public de retirer les caches
		// invalides ;
		// - le signal (meta='invalider') indique
		// qu'il faut faire attention ;
		// - le signal (meta='invalider_caches') indique qu'on
		// peut effacer 100 caches invalides
		// (Signaux differents pour eviter de la concurrence entre
		// les processus d'invalidation)
		ecrire_meta('invalider', 'oui'); // se verifier soi-meme
		ecrire_meta('invalider_caches', 'oui'); // supprimer les autres
		ecrire_metas();
	}
}


// Utilisee pour vider le cache depuis l'espace prive
// (ou juste les squelettes si un changement de config le necessite)
// http://doc.spip.org/@purger_repertoire
function purger_repertoire($dir, $age='ignore', $regexp = '') {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin))
			@unlink($chemin);
		else if (is_dir($chemin))
			if ($fichier != 'CVS')
				purger_repertoire($chemin);
	}
	closedir($handle);
}

// Fonctions pour le cache des images (vues reduites)


// http://doc.spip.org/@calculer_taille_dossier
function calculer_taille_dossier ($dir) {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		if (is_file("$dir/$fichier")) {
			$taille += filesize("$dir/$fichier");
		}
	}
	closedir($handle);
	return $taille;
}


// http://doc.spip.org/@cron_invalideur
function cron_invalideur($t) {
	//
	// menage des vieux fichiers du cache
	// marques par l'invalideur 't' = date de fin de fichier
	//

	retire_vieux_caches();

	// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux

	// A revoir: il semble y avoir une desynchro ici.
	$t = spip_fetch_array(spip_query("SELECT SUM(taille) AS n FROM spip_caches WHERE type IN ('t', 'x')"));
	$total_cache = $t['n'];
	spip_log("Taille du CACHE: $total_cache octets");

	global $quota_cache;
	$total_cache -= $quota_cache*1024*1024;
	if ($quota_cache > 0 AND $total_cache > 0) {
		$taille_supprimee = 0;
		$q = spip_query("SELECT id, taille FROM spip_caches WHERE type IN ('t', 'x') ORDER BY id");
		while ($r = spip_fetch_array($q)
		AND ($total_cache > $taille_supprimee)) {
			$date_limite = $r['id'];
			$taille_supprimee += $r['taille'];
		}
		spip_log ("Quota cache: efface $taille_supprimee octets");
		suivre_invalideur("id <= $date_limite AND type in ('t', 'x')", false);
	}
	return 1;
}

//
// Destruction des fichiers caches invalides
//

// Securite : est sur que c'est un cache
// http://doc.spip.org/@retire_cache
function retire_cache($cache) {

	if (preg_match(
	"|^([0-9a-f]/)?([0-9]+/)?[^.][\-_\%0-9a-z]+\.[0-9a-f]+(\.gz)?$|i",
	$cache)) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier(_DIR_CACHE . $cache);
	} else
		spip_log("Impossible de retirer $cache");
}

// Supprimer les caches marques "x"
// http://doc.spip.org/@retire_caches
function retire_caches($chemin = '') {
	include_spip('base/abstract_sql');
	include_spip('inc/meta');
	lire_metas();
	// recuperer la liste des caches voues a la suppression
	$suppr = array();

	// En priorite le cache qu'on appelle maintenant
	if ($chemin) {
		$f = spip_abstract_fetsel(array("fichier"),
			array("spip_caches"),
			array("fichier = " . spip_abstract_quote($chemin) . " ",
				"type='x'"),
			"",
			array(),
			1);
		if ($f['fichier']) $suppr[$f['fichier']] = true;
	}

	// Et puis une centaine d'autres
	$compte = 0;
	if (isset($GLOBALS['meta']['invalider_caches'])) {
		$compte = 1;
		effacer_meta('invalider_caches'); # concurrence
		ecrire_metas();

		$q = spip_abstract_select(array("fichier"),
				array("spip_caches"),
				array("type='x'"),
				"",
				array(),
				100);
		while ($r = spip_abstract_fetch($q)) {
			$compte ++;	# compte le nombre de resultats vus (y compris doublons)
			$suppr[$r['fichier']] = true;
		}
	}

	if ($n = count($suppr)) {
		spip_log ("Retire $n caches");
		foreach ($suppr as $cache => $ignore)
			retire_cache($cache);
		spip_query("DELETE FROM spip_caches WHERE " . calcul_mysql_in('fichier', "'".join("','",array_keys($suppr))."'") );
	}

	// Si on a regarde (compte > 0), signaler s'il reste des caches invalides
	if ($compte > 0) {
		if ($compte > 100) # s'il y en a 101 c'est qu'on n'a pas fini
			ecrire_meta('invalider_caches', 'oui');
		else
			effacer_meta('invalider');
		ecrire_metas();
	}
}


// Pour que le compilo ajoute un invalideur a la balise #PARAMETRES_FORUM
// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
// http://doc.spip.org/@code_invalideur_forums
function code_invalideur_forums($p, $code) {
	$type = 'id_forum';
	$valeur = "\n\t\tcalcul_index_forum("
		// Retournera 4 [$SP] mais force la demande du champ SQL
		. champ_sql('id_article', $p) . ','
		. champ_sql('id_breve', $p) .  ','
		. champ_sql('id_rubrique', $p) .','
		. champ_sql('id_syndic', $p) .  ")\n\t";

	return '
	// invalideur '.$type.'
	(!($Cache[\''.$type.'\']['.$valeur."]=1) ? '':\n\t" . $code .")\n";
}


// Fonction permettant au compilo de calculer les invalideurs d'une page
// http://doc.spip.org/@calcul_invalideurs
function calcul_invalideurs($corps, $primary, &$boucles, $id_boucle) {
	if ($primary == 'id_forum'
	OR in_array($primary, explode(',', $GLOBALS['invalider_caches']))) {
		$corps .= "\n\t\t\$Cache['$primary'][intval(" .
		  (($primary != 'id_forum')  ? 
		   index_pile($id_boucle, $primary, $boucles) :
		   ("calcul_index_forum(" . 
		// Retournera 4 [$SP] mais force la demande du champ a MySQL
		    index_pile($id_boucle, 'id_article', $boucles) . ',' .
		    index_pile($id_boucle, 'id_breve', $boucles) .  ',' .
		    index_pile($id_boucle, 'id_rubrique', $boucles) .',' .
		    index_pile($id_boucle, 'id_syndic', $boucles) .
		    ")")) .
		  ")] = 1; // invalideurs\n";
	}
	return $corps;
}

?>
