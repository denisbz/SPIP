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

// http://doc.spip.org/@fichier_admin
function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

// http://doc.spip.org/@debut_admin
function debut_admin($form, $action, $commentaire='') {
	global $connect_login, $connect_statut, $connect_toutes_rubriques;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_refuse'));
	}
	if ($connect_toutes_rubriques) {
		$dir = _DIR_TMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';

	}
	$signal = fichier_admin($action);
	if (@file_exists($dir . $signal)) {
		spip_log ("Action admin: $action");
		return true;
	}
	if ($commentaire) {
		include_spip('inc/texte');
		$commentaire = ("\n<p>".propre($commentaire)."</p>\n");
	}
	include_spip('inc/minipres');
	minipres(_T('info_action', array('action' => $action)),
		  $commentaire
		. $form
		. "\n<p><b>"._T('info_authentification_ftp')."</b>"
		. aide("ftp_auth")
		. "\n<p>"
		. _T('info_creer_repertoire')
		. "\n<p align='center'>\n<INPUT TYPE='text' NAME='fichier' CLASS='fondl' VALUE=\"".
		 $signal
		. "\" size='30'>"
		. "\n<p>"
		. _T('info_creer_repertoire_2',
			array('repertoire' => joli_repertoire($dir)))
		. "\n<p align='right'><INPUT TYPE='submit' VALUE='"
		. _T('bouton_recharger_page')
		. "' CLASS='fondo'>"
		. "</form>");
}

// http://doc.spip.org/@fin_admin
function fin_admin($action) {
	global $connect_login, $connect_toutes_rubriques;
	if ($connect_toutes_rubriques) {
		$dir = _DIR_TMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';
	}
	$signal = fichier_admin($action);
	@unlink($dir . $signal);
	@rmdir($dir . $signal);
}
?>
