<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_URL_LOGOUT_collecte;
$balise_URL_LOGOUT_collecte = array();

// filtres[0] = url destination apres logout [(#URL_LOGOUT|url)]
function balise_URL_LOGOUT_stat ($args, $filtres) {
	return array($filtres[0]);
}

function balise_URL_LOGOUT_dyn($cible) {
	if (!$login = $GLOBALS['auteur_session']['login'])
		return '';

	if (!$cible) {
		$link = new Link();
		$cible = $link->getUrl();
	}

	return 'spip_cookie.php3?logout_public=' . $login
		. '&amp;url=' . urlencode($cible);
}

?>
