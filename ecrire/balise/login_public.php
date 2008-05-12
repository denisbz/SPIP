<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');
spip_connect();

// http://doc.spip.org/@balise_LOGIN_PUBLIC
function balise_LOGIN_PUBLIC ($p, $nom='LOGIN_PUBLIC') {
	return calculer_balise_dynamique($p, $nom, array('url'));
}

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PUBLIC{#LOGIN})]

// http://doc.spip.org/@balise_LOGIN_PUBLIC_stat
function balise_LOGIN_PUBLIC_stat ($args, $filtres) {
	return array($filtres[0] ? $filtres[0] : $args[0], $args[1], $args[2]);
}

// http://doc.spip.org/@balise_LOGIN_PUBLIC_dyn
function balise_LOGIN_PUBLIC_dyn($url, $login) {
	include_spip('balise/formulaire_');
	if (!$url 		# pas d'url passee en filtre ou dans le contexte
	AND !$url = _request('url') # ni d'url passee par l'utilisateur
	)
		$url = parametre_url(self(), '', '', '&');
	return balise_FORMULAIRE__dyn('login',$url,$login,false);
}

?>