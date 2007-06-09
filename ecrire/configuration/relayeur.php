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

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_relayeur_dist()
{
	global $spip_lang_right, $spip_lang_left;

	$res = $submit = '';

	if ($http_proxy = $GLOBALS['meta']["http_proxy"]) {
		include_spip('inc/distant');
		// Masquer un eventuel password authentifiant
		$http_proxy=entites_html(no_password_proxy_url($http_proxy));
		if ($http_proxy) {
			$res = "\n<p style='text-align: $spip_lang_left;' class='ligne_noire verdana1 spip_small'>"
			. _T('texte_test_proxy')
			. "</p>"
			. "\n<p>"
			. "<input type='text' name='test_proxy' value='http://www.spip.net/' size='40' class='forml' />"
			. "</p>";

			// ce retour_proxy provient soit de l'url, si on est en ajax,
			// soit des globales si on est en mode sans javascript. Joli :(
			if($retour_proxy = _request('retour_proxy')
			OR $retour_proxy = $GLOBALS['retour_proxy']) {
				$res .= debut_boite_info(true)
				. $retour_proxy
				. fin_boite_info(true);
			}
			$submit = array('valider_proxy' => _T('bouton_valider'),
					'tester_proxy' => _T('bouton_test_proxy'));
		}
	}

	$res = "\n<div class='verdana2'>"
	. propre(_T('texte_proxy'))
	. "</div>"
	. "\n<div class='verdana2'>"
	. "<input type='text' name='http_proxy' size='40' class='forml' value='$http_proxy' />"
	. $res
	. "</div>";

	$res = debut_cadre_trait_couleur("base-24.gif", true, "", _T('info_sites_proxy').aide ("confhttpproxy"))
	.  ajax_action_post('configurer', 'relayeur', 'config_fonctions', '', $res, $submit)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-relayeur", '', $res);
}
?>
