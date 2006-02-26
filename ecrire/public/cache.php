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

//
// Le format souhaite : "a/bout-d-url.md5(.gz)"
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache()
//
function generer_nom_fichier_cache($contexte, $fond) {

	if ($contexte === NULL) {
		$fichier_requete = nettoyer_uri();
	} else {
		$fichier_requete = $fond;
		foreach ($contexte as $var=>$val)
			$fichier_requete .= "&$var=$val";
	}

	$fichier_cache = ereg_replace('^/+', '', $fichier_requete);
	$fichier_cache = ereg_replace('\.[a-zA-Z0-9]*', '', $fichier_cache);
	$fichier_cache = ereg_replace('&[^&]+=([^&]+)', '&\1', $fichier_cache);
	$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
	if (strlen($fichier_cache) > 24)
		$fichier_cache = substr(ereg_replace('([a-zA-Z]{1,3})[^-]*-',
		'\1-', $fichier_cache), -22);

	// Pour la page d'accueil
	if (!$fichier_cache)
		$fichier_cache = 'INDEX-';

	// morceau de md5 selon HOST, $dossier_squelettes & $fond
	$md_cache = md5($fichier_requete . $_SERVER['HTTP_HOST']
		. $fond . $GLOBALS['dossier_squelettes']);
	$fichier_cache .= '.'.substr($md_cache, 1, 8);

	// Sous-repertoires 0...9a..f ; ne pas prendre la base _DIR_CACHE
	$subdir = sous_repertoire(_DIR_CACHE, substr($md_cache, 0, 1), true);

	include_ecrire('inc_acces');
	verifier_htaccess(_DIR_CACHE);

	return $subdir.$fichier_cache;
}

// Faut-il compresser ce cache ? A partir de 16ko ca vaut le coup
function cache_gz($page) {
	if ($GLOBALS['flag_gz'] AND strlen($page['texte']) > 16*1024)
		return '.gz';
	else
		return '';
}

//
// Destruction des fichiers caches invalides
//

// Securite : est sur que c'est un cache
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
function retire_caches($chemin = '') {

	// recuperer la liste des caches voues a la suppression
	$suppr = array();

	// En priorite le cache qu'on appelle maintenant
	if ($chemin) {
		$q = spip_query("SELECT fichier FROM spip_caches
		WHERE fichier = '".addslashes($chemin)."' AND type='x' LIMIT 1");
		if ($r = spip_fetch_array($q))
			$suppr[$r['fichier']] = true;
	}

	// Et puis une centaine d'autres
	if ($GLOBALS['meta']['invalider_caches']) {
		$compte = 1;
		effacer_meta('invalider_caches'); # concurrence
		ecrire_metas();

		$q = spip_query("SELECT fichier FROM spip_caches
		WHERE type='x' LIMIT 100");
		while ($r = spip_fetch_array($q)) {
			$compte ++;	# compte le nombre de resultats vus (y compris doublons)
			$suppr[$r['fichier']] = true;
		}
	}

	if ($n = count($suppr)) {
		spip_log ("Retire $n caches");
		foreach ($suppr as $cache => $ignore)
			retire_cache($cache);
		spip_query("DELETE FROM spip_caches WHERE "
		.calcul_mysql_in('fichier', "'".join("','",array_keys($suppr))."'") );
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

// gestion des delais par specification a l'exterieur du squelette

function cache_valide($chemin_cache, $date) {

	tester_variable('delais', 3600);

	if (!$GLOBALS['delais']) return -1;

	if ((time() - $date) > $GLOBALS['delais']) return $GLOBALS['delais'];

	return 0;
}

// gestion des delais par specification a l'interieur du squelette

function cache_valide_autodetermine($chemin_cache, $page, $date) {

	if (!$page) return 1;

	if (strlen($duree = $page['entetes']['X-Spip-Cache']))
		return ($date + intval($duree) > time()) ? 0 : $t;

	// squelette ancienne maniere, on se rabat sur le vieux modele
	return cache_valide($chemin_cache, $date);
}


// retourne le nom du fichier cache, 
// et affecte le param use_cache avec un nombre N:
// < 0 s'il faut calculer la page sans la mettre en cache
// = 0 si on peut utiliser un cache existant
// > 0 s'il faut calculer la page et le mette en cache pendant N secondes
//

function determiner_cache(&$use_cache, $contexte, $fond) {

	// pour tester si la base est dispo
	include_local(_FILE_CONNECT);

	// cas ignorant le cache car complement dynamique
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$use_cache = -1;
		return array('','',0);
	}

	$chemin_cache = generer_nom_fichier_cache($contexte, $fond);
	if ($GLOBALS['flag_gz'] AND @file_exists(_DIR_CACHE.$chemin_cache.'.gz'))
		$chemin_cache .= '.gz';

	// HEAD : cas sans jamais de calcul pour raisons de performance
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
		$use_cache = 0;
		return array($chemin_cache, array(),
			@filemtime(_DIR_CACHE . $chemin_cache));
	}

	// Faut-il effacer des pages invalidees (en particulier ce cache-ci) ?
	if ($GLOBALS['meta']['invalider'] AND $GLOBALS['db_ok']) {
		include_ecrire('inc_meta');
		lire_metas();
		retire_caches($chemin_cache);
	}

	// cas sans jamais de cache pour raison interne
	if ($GLOBALS['var_mode'] &&
		($GLOBALS['_COOKIE']['spip_session']
		 || $GLOBALS['_COOKIE']['spip_admin']
		 || @file_exists(_ACCESS_FILE_NAME))) {
			supprimer_fichier(_DIR_CACHE . $chemin_cache);
	}

	$ok = lire_fichier(_DIR_CACHE . $chemin_cache, $page);
	$time = @filemtime(_DIR_CACHE . $chemin_cache);
	$page = restaurer_meta_donnees ($page);
	$use_cache = cache_valide_autodetermine($chemin_cache, $page, $time);

	if (!$use_cache AND $ok) return array($chemin_cache, $page, $time);

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!$GLOBALS['db_ok']) {
		if (file_exists(_DIR_CACHE . $chemin_cache))
			$use_cache = 0 ;
		else {
			spip_log("Erreur base de donnees, impossible utiliser $chemin_cache");
			include_ecrire('inc_minipres');
			minipres(_T('info_travaux_titre'),  _T('titre_probleme_technique'));
		}
	}

	return array((($use_cache < 0) ? "" : $chemin_cache), $page, $time);
}

// Creer le fichier cache
# Passage par reference de $page par souci d'economie
function creer_cache(&$page, &$chemin_cache, $duree) {
	// Entrer dans la base les invalideurs calcules par le compilateur
	// (et supprimer les anciens)

	// arbitrage entre ancien et nouveau modele de delai:
	// primaute a la duree de vie de la page donnee a l'interieur de la page 
	if (strlen($t = $page['entetes']['X-Spip-Cache']))
		$duree = intval($t);

	// Enregistrer le fichier cache qui contient
	// 1) la carte d'identite de la page (ses "globals", genre id_article=7)
	// 2) son contenu
	$page['signal']['process_ins'] = $page['process_ins'];
	$page['signal']['entetes'] = $page['entetes'];

	// Normaliser le chemin et supprimer l'eventuelle contrepartie -gz du cache
	$chemin_cache = str_replace('.gz', '', $chemin_cache);
	$gz = cache_gz($page);
	supprimer_fichier(_DIR_CACHE . $chemin_cache . ($gz ? '' : '.gz'));

	// l'enregistrer, compresse ou non...
	$chemin_cache .= $gz;
	ecrire_fichier(_DIR_CACHE . $chemin_cache,
		"<!-- "
		. str_replace("\n", " ", serialize($page['signal']))
		. " -->\n"
		. $page['texte']);

	// Inserer ses invalideurs
	include_ecrire('inc_invalideur');
	maj_invalideurs($chemin_cache, $page, $duree);

}

function restaurer_meta_donnees ($contenu) {

	if (preg_match("/^<!-- ([^\n]*) -->\n/ms", $contenu, $match)) {
		$meta_donnees = unserialize($match[1]);
		if (is_array($meta_donnees)) {
			foreach ($meta_donnees as $var=>$val) {
				$page[$var] = $val;
			}
		}

	}

	$page['texte'] = substr($contenu, strlen($match[0]));
	return $page;
}


// purger un petit cache (tidy ou recherche) qui ne doit pas contenir de
// vieux fichiers
function nettoyer_petit_cache($prefix, $duree = 300) {
	// determiner le repertoire a purger : 'CACHE/rech/'
	$dircache = sous_repertoire(_DIR_CACHE,$prefix);
	if (spip_touch($dircache.'purger_'.$prefix, $duree, true)) {
		foreach (preg_files("$dircache$prefix") as $f) {
			if (time() - filemtime($f) > $duree) {
				@unlink($f);
				@unlink("$f.err"); # pour tidy
			}
		}
	}
}

?>
