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

  // Script pour appeler un squelette apres s'etre authentifie

include_once 'inc_version.php';

include_spip('inc/cookie');

$auth = charger_fonction('auth', 'inc');
$var_auth = $auth();

if ($var_auth !== '') {
	if ($var_auth===-1) exit();

	include_spip('inc/headers');
	// si l'authentifie' n'a pas acces a l'espace de redac
	// c'est qu'on voulait forcer sa reconnaissance en tant que visiteur.
	// On reexecute pour deboucher sur le include public.
	if (!autoriser('ecrire')) {
		$redir = '../?' . $_SERVER['QUERY_STRING'];
		spip_setcookie('spip_session', $_COOKIE['spip_session'], time() + 3600 * 24 * 14);
	} else {
	// autrement on insiste
		$redir = generer_url_public('login',
			"url=" . 
			rawurlencode(str_replace('/./', '/',
				(_DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS)
						 . str_replace('&amp;', '&', self()))), true);
	}
	redirige_par_entete($redir);
}

// En somme, est prive' ce qui est publiquement nomme'...

include_once 'public.php';
?>
