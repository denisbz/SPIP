<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Evaluer la page produite par un squelette
 * pour la transformer en texte statique
 * Elle peut contenir un < ?xml a securiser avant eval
 * ou du php d'origine inconnue
 *
 * Attention cette partie eval() doit imperativement
 * etre declenchee dans l'espace des globales (donc pas
 * dans une fonction).
 *
 * @param array $page
 * @return bool
 */

$res = true;
// Cas d'une page contenant du PHP :
if ($page['process_ins'] != 'html') {

	// restaurer l'etat des notes avant calcul
	if (isset($page['notes'])
		AND $page['notes']
		AND $notes = charger_fonction("notes","inc",true)){
		$notes($page['notes'],'restaurer_etat');
	}
	ob_start();
	if (strpos($page['texte'],'?xml')!==false)
		$page['texte'] = str_replace('<'.'?xml', "<\1?xml", $page['texte']);

	$res = eval('?' . '>' . $page['texte']);
	$page['texte'] = ob_get_contents();
	ob_end_clean();

	$page['process_ins'] = 'html';

	if (strpos($page['texte'],'?xml')!==false)
		$page['texte'] = str_replace("<\1?xml", '<'.'?xml', $page['texte']);
}

page_base_href($page['texte']);