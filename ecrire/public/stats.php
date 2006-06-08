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

function ecrire_stats() {public_decompter_dist();}

function public_decompter_dist() {

	// Rejet des robots (qui sont pourtant des humains comme les autres)
	if (preg_match(
	',google|yahoo|msnbot|crawl|lycos|voila|slurp|jeeves|teoma,i',
	$_SERVER['HTTP_USER_AGENT']))
		return;

	// Ne pas compter les visiteurs sur les flux rss (qui sont pourtant
	// des pages web comme les autres) [hack pourri en attendant de trouver
	// une meilleure idee ?]
	if (preg_match(',^backend,', $GLOBALS['fond']))
		return;


	// Identification de l'element
	// Attention il s'agit bien des $GLOBALS, regles (dans le cas des urls
	// personnalises), par la carte d'identite de la page... ne pas utiliser
	// _request() ici !
	if ($log_id_num = intval($GLOBALS['id_article']))
		$log_type = "article";
	else if ($log_id_num = intval($GLOBALS['id_breve']))
		$log_type = "breve";
	else if ($log_id_num = intval($GLOBALS['id_rubrique']))
		$log_type = "rubrique";
	else
		$log_type = "autre";

	// Identification du client
	$client_id = substr(md5(
		$GLOBALS['ip'] . $_SERVER['HTTP_USER_AGENT']
		. $_SERVER['HTTP_ACCEPT'] . $_SERVER['HTTP_ACCEPT_LANGUAGE']
		. $_SERVER['HTTP_ACCEPT_ENCODING']
	), 0,10);

	// Analyse du referer
	if ($log_referer = $_SERVER['HTTP_REFERER']) {
		$url_site_spip = preg_replace(',^((https?|ftp)://)?(www\.)?,i', '',
			$GLOBALS['meta']['adresse_site']);
		if (($url_site_spip<>'')
		AND strpos('-'.strtolower($log_referer), strtolower($url_site_spip))
		AND !$_GET['var_recherche'])
			$log_referer = '';
		else
			$referer_md5 = '0x'.substr(md5($log_referer), 0, 15);
	}

	//
	// stockage sous forme de fichier ecrire/data/stats/client_id
	//

	// 1. Chercher s'il existe deja une session pour ce numero IP.
	$content = array();
	$session = sous_repertoire(_DIR_SESSIONS, 'visites') . $client_id;
	if (lire_fichier($session, $content))
		$content = @unserialize($content);

	// 2. Plafonner le nombre de hits pris en compte pour un IP (robots etc.)
	// et ecrire la session
	if (count($content) < 200) {
		$entree = trim("$log_type\t$log_id_num\t$log_referer");
		$content[$entree] ++;
		ecrire_fichier($session, serialize($content));
	}
}

?>
