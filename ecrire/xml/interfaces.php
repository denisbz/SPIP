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

if (!defined("_ECRIRE_INC_VERSION")) return;

define('_REGEXP_DOCTYPE',
	'/^((<\001?[?][^>]*>)*\s*(<!--.*?-->)*)*<!DOCTYPE\s+(\w+)\s+(\w+)\s*([^>]*)>\s*/');

define('_MESSAGE_DOCTYPE', '<!-- SPIP CORRIGE -->');

define('_SUB_REGEXP_SYMBOL', '[A-Za-z_][\w_:.-]*');

define('_REGEXP_ID', '/^'  . _SUB_REGEXP_SYMBOL . '$/');

define('_REGEXP_ENTITY_USE', '/%('  . _SUB_REGEXP_SYMBOL . ');/');
define('_REGEXP_ENTITY_DEF', '/^%('  . _SUB_REGEXP_SYMBOL . ');/');
define('_REGEXP_TYPE_XML', 'PUBLIC|SYSTEM|INCLUDE|IGNORE|CDATA');
define('_REGEXP_ENTITY_DECL', '/^<!ENTITY\s+(%?)\s*(' .
		_SUB_REGEXP_SYMBOL .
		';?)\s+(' .
		_REGEXP_TYPE_XML .
		')?\s*(' .
		"('([^']*)')" .
		'|("([^"]*)")' .
                '|\s*(%' . _SUB_REGEXP_SYMBOL . ';)\s*' .
       		')\s*(--.*?--)?("([^"]*)")?\s*>\s*(.*)$/s');

define('_REGEXP_INCLUDE_USE', '/^<!\[\s*%\s*([^;]*);\s*\[\s*(.*)$/s');

// Document Type Compilation

class DTC {
	var	$macros = array();
	var 	$elements = array();
	var 	$peres = array();
	var 	$attributs = array();
	var	$entites = array();
	var	$regles = array();
	var	$pcdata = array();
}
?>
