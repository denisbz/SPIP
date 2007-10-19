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

// http://doc.spip.org/@exec_legender_dist
function exec_legender_dist()
{
	$type = _request('type');
	$ancre = _request('ancre');
	$script = _request('script');
	$id = intval(_request('id'));
	$id_document = intval(_request('id_document'));

	if (!autoriser('joindredocument',$type, $id)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('inc/actions');
		$legender = charger_fonction('legender', 'inc');
		ajax_retour($legender($id_document, array(), $script, $type, $id, $ancre));
	}
}
?>
