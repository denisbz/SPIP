<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Le format souhaite : "CACHE/a/bout-d-url.md5(.gz)"
// Attention a modifier simultanement le sanity check de
// la fonction retire_cache()
//
function generer_nom_fichier_cache($contexte='', $fond='') {
	global $flag_gz;

	if (!$contexte) {
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

	// morceau de md5 selon HOST et $fond
	$md_cache = md5($fichier_requete . $GLOBALS['HTTP_HOST'] . $fond);
	$fichier_cache .= '.'.substr($md_cache, 1, 8);

	// Sous-repertoires 0...9a..f/
	$subdir = creer_repertoire(_DIR_CACHE, substr($md_cache, 0, 1));

	include_ecrire('inc_acces');
	verifier_htaccess(_DIR_CACHE);

	$gzip = $flag_gz ? '.gz' : '';

	return _DIR_CACHE . $subdir.$fichier_cache.$gzip;
}

//
// Destruction des fichiers caches invalides
//
// NE PAS appeler ces fonctions depuis l'espace prive 
// car openbase_dir peut leur interdire l'acces au repertoire de cache

// Securite : est sur que c'est un cache
function retire_cache($cache) {
	if (!_DIR_RESTREINT) return;
	if (preg_match('|^' . _DIR_CACHE .
		"([0-9a-f]/)?([0-9]+/)?[^.][\-_\%0-9a-z]+\.[0-9a-f]+(\.gz)?$|i",
		       $cache)) {
		// supprimer le fichier (de facon propre)
		supprimer_fichier($cache);
	} else
		spip_log("Impossible de retirer $cache");
}

// Supprimer les caches marques "x"
function retire_caches($chemin = '') {
	if (!_DIR_RESTREINT) return;

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

//
// Retourne un nombre N:
// < 0 s'il faut calculer la page sans la mettre en cache
// = 0 si on peut utiliser un cache existant
// > 0 s'il faut calculer la page et le mette en cache pendant N secondes
//

function cache_valide($chemin_cache) {
	global $delais;

	if (!isset($delais)) $delais = 3600;

	if (!$delais) return -1;

	if (!file_exists($chemin_cache)) return $delais;

	if ((time() - @filemtime($chemin_cache)) > $delais) return $delais;

	return 0;
}


// retourne le nom du fichier cache, 
// et affecte le param use_cache selon les specs de la fonction cache_valide

function determiner_cache(&$use_cache, $contexte,$fond) {
	global $_SERVER;

	// pour tester si la base est dispo

	include_local(_FILE_CONNECT);

	// cas ignorant le cache car complement dynamique
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$use_cache = -1;
		return "";
	}
	
	$chemin_cache = generer_nom_fichier_cache($contexte, $fond);

	// cas sans jamais de calcul pour raison interne
	if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
		$use_cache = 0;
		return $chemin_cache;
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
	    supprimer_fichier($chemin_cache);
	}

	$use_cache = cache_valide($chemin_cache);

	if (!$use_cache) return $chemin_cache;

	// Si pas valide mais pas de connexion a la base, le garder quand meme

	if (!$GLOBALS['db_ok']) {
		if (file_exists($chemin_cache))
  			$use_cache = 0 ;
		else {
		  // prevenir du pb (une fois, pas pour chaque inclusion)
			if (!spip_interdire_cache) {
				spip_log("Erreur base de donnees & "
				. "impossible utiliser $chemin_cache");
				include_ecrire('inc_minipres');
				install_debut_html(_T('info_travaux_titre'));echo _T('titre_probleme_technique');install_fin_html();
				// continuer quand meme, ca n'ira pas loin.
				// mais ne plus rien signaler
				$GLOBALS['flag_preserver'] = true;
				define ('spip_interdire_cache', true);
			}
		}
	}

	return ($use_cache < 0) ? "" : $chemin_cache;
}

// Passage par reference juste par souci d'economie

function creer_cache(&$page, $chemin_cache, $duree)
{
	// Entrer dans la base les invalideurs calcules par le compilateur
	// (et supprimer les anciens)

	include_ecrire('inc_invalideur');
	maj_invalideurs($chemin_cache, $page['invalideurs'], $duree);

	// Enregistrer le fichier cache

	$r = ecrire_fichier($chemin_cache, 
			"<!-- "
			. str_replace("\n", " ", serialize($page['signal']))
			. " -->\n"
			. $page['texte']);

	// Nouveau cache : creer un invalideur 't' fixant la date
	// d'expiration et la taille du fichier
	if ($r) {
			// Ici on ajoute 3600s pour eviter toute concurrence
			// entre un invalideur et un appel public de page
		$bedtime = time() + $duree + 3600;
		$taille = @filesize($chemin_cache);
		$fichier = addslashes($chemin_cache);
		spip_query("INSERT IGNORE INTO spip_caches (fichier,id,type,taille) VALUES ('$fichier','$bedtime','t','$taille')");
	}
}

// purger un petit cache (tidy ou recherche) qui ne doit pas contenir de
// vieux fichiers
function nettoyer_petit_cache($prefix, $duree = 300) {
	// determiner le repertoire a purger : 'CACHE/rech/'
	$dircache = _DIR_CACHE.creer_repertoire(_DIR_CACHE,$prefix);
	if (spip_touch($dircache.'purger_'.$prefix, $duree, true)) {
		if ($h = @opendir($dircache)) {
			while (($f = @readdir($h)) !== false) {
				if (preg_match(',^'.$prefix.'_,', $f)
				AND time() - filemtime("$dircache$f") > $duree) {
					@unlink("$dircache$f");
					@unlink("$dircache$f.err"); # pour tidy
				}
			}
		}
	}
}

?>
