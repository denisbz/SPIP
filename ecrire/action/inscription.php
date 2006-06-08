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

include_spip('balise/formulaire_inscription');
include_spip(_DIR_COMPIL . 'assembler'); 
include_spip('inc/lang');
include_spip('inc/headers');

function action_inscription_dist() {

	utiliser_langue_site();
	utiliser_langue_visiteur();
	http_no_cache();
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="', 
  $GLOBALS['spip_lang'],
  '" dir="',
  ($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr'),
  '">
<head><title>',
  _T('pass_vousinscrire'), 
  '</title>
<link rel="stylesheet" type="text/css" href="'.find_in_path('spip_style.css').'" />
</head><body>';

	inclure_balise_dynamique(balise_FORMULAIRE_INSCRIPTION_dyn(_request('mode'), _request('focus'), _request('id_rubrique'))
	);
	echo "</body></html>";
}

?>
