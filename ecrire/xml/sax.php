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

include_spip('inc/filtres');
include_spip('inc/charsets');
include_spip('xml/interfaces');

// http://doc.spip.org/@xml_debutElement
function xml_debutElement($parser, $name, $attrs)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$ouvrant = &$phraseur_xml->ouvrant;
	$reperes = &$phraseur_xml->reperes;

	$t = isset($ouvrant[$depth]) ? $ouvrant[$depth] : ' ';
	// espace initial signifie: deja integree au resultat
	if ($t[0] != ' ')
	  {
	    $phraseur_xml->res .= '<' . $t . '>';
	    $ouvrant[$depth] = ' ' . $t;
	  }
	$t = $contenu[$depth];
	// n'indenter que s'il y a un separateur avant
	$phraseur_xml->res .= ereg_replace("[\n\t ]+$",  "\n$depth", $t);
	$contenu[$depth] = "";
	$att = '';
	$sep = ' ';
	foreach ($attrs as $k => $v) {
	  $delim = strpos($v, "'") === false ? "'" : '"';
	  $val = entites_html($v);
	  $att .= $sep .  $k . "=" . $delim
	    . ($delim !== '"' ? str_replace('&quot;', '"', $val) : $val)
	    . $delim;
	  $sep = "\n $depth";
	}
	$depth .= '  ';
	$contenu[$depth] = "";
	$ouvrant[$depth] = $name . $att;
	$reperes[$depth] = xml_get_current_line_number($parser);
}

// http://doc.spip.org/@xml_finElement
function xml_finElement($parser, $name, $fusion_bal=false)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$ouvrant = &$phraseur_xml->ouvrant;

	$ouv = $ouvrant[$depth];

	if ($ouv[0] != ' ')
	  $ouvrant[$depth] = ' ' . $ouv;
	else $ouv= "";
	$t = $contenu[$depth];
	$depth = substr($depth, 2);
	$t = ereg_replace("[\n\t ]+$", "\n" . $depth, $t);

  // fusion <balise></balise> en <balise />.
  // ATTENTION,  certains clients http croient que fusion ==> pas d'atttributs
  // en particulier pour les balises Script et A.
  // en presence d'attributs ne le faire que si la DTD est dispo et d'accord
  // (param fusion_bal)

	if ($t || (($ouv != $name) AND !$fusion_bal))
	  $phraseur_xml->res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . "</" . $name . ">";
	else
	  $phraseur_xml->res .= ($ouv ? ('<' . $ouv  . ' />') : ("</" .  $name . ">"));
}

// http://doc.spip.org/@xml_textElement
function xml_textElement($parser, $data)
{
	global $phraseur_xml;

	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$contenu[$depth] .= preg_match('/^script/',$phraseur_xml->ouvrant[$depth])
	  ? $data
	  : entites_html($data);
}

// http://doc.spip.org/@xml_PiElement
function xml_PiElement($parser, $target, $data)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	if (strtolower($target) != "php")
	  $contenu[$depth] .= $data;
	else {
		ob_start();
		eval($data);
		$data = ob_get_contents();
		ob_end_clean();
		$contenu[$depth] .= $data;
	}
}


// http://doc.spip.org/@xml_defautElement
function xml_defautElement($parser, $data)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;

	if (!isset($contenu[$depth])) $contenu[$depth]='';
	$contenu[$depth] .= $data;
}

// http://doc.spip.org/@xml_parsestring
function xml_parsestring($phraseur, $data)
{
	global $phraseur_xml;
	$phraseur_xml->contenu[$phraseur_xml->depth] ='';

	if (!xml_parse($phraseur, $data, true)) {
	  // ne pas commencer le message par un "<" (cf xml_sax_dist)
	  $phraseur_xml->err = array(
	    xml_error_string(xml_get_error_code($phraseur)) .
		  coordonnees_erreur($phraseur) . "<br />\n" .
		  (!$phraseur_xml->depth ? '' :
		   (
		    _T('erreur_balise_non_fermee') .
		    " <tt>" .
		    $phraseur_xml->ouvrant[$phraseur_xml->depth] .
		    "</tt> " .
		    _T('ligne') .
		    $phraseur_xml->reperes[$phraseur_xml->depth] .
		    " <br />\n" )));
	}
}

// http://doc.spip.org/@coordonnees_erreur
function coordonnees_erreur($xml_parser)
{
  global $xml_entete_length;
  return
    ' ' .
	xml_get_current_line_number($xml_parser) + $xml_entete_length.
    ' ' .
	xml_get_current_column_number($xml_parser);
}

// http://doc.spip.org/@xml_sax_dist
function xml_sax_dist($page, $apply=false)
{
	global $phraseur_xml;

	// init par defaut si pas fait (espace public)
	if (!isset($GLOBALS['phraseur_xml'])) {
		$indenter_xml = charger_fonction('indenter', 'xml');
		return $indenter_xml($page, $apply);
	}

	$xml_parser = xml_parser_create($GLOBALS['meta']['charset']);

	xml_set_element_handler($xml_parser,
			array($phraseur_xml, "debutElement"),
			array($phraseur_xml, "finElement"));

	xml_set_character_data_handler($xml_parser,
				       array($phraseur_xml, "textElement"));

	xml_set_processing_instruction_handler($xml_parser,
				       array($phraseur_xml, 'PiElement'));

	xml_set_default_handler($xml_parser,
				array($phraseur_xml, "defautElement"));

	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

	unset($GLOBALS['xhtml_error']);

	if ($apply) {
		ob_start();
		if (is_array($apply))
		  $r = call_user_func_array($page, $apply);
		else $r = $page();
		$page = ob_get_contents();
		ob_end_clean();
		// fonction sans aucun "echo", ca doit etre le resultat
		if (!$page) $page = $r;
	}

	// charger la DTD et transcoder les entites,
	// et escamoter le doctype que sax mange en php 5
	list($entete,$page) = sax_bug($page);

	$GLOBALS['xml_entete_length'] = substr_count($entete,"\n");

	$res = $phraseur_xml->phraserTout($xml_parser, $page);

	xml_parser_free($xml_parser);

	if ($res[0] == '<') return $entete . $res;

	$GLOBALS['xhtml_error'] = $res;

	return $entete . $page;
}

// SAX ne dit pas si une Entite est dans un attribut ou non.
// Les eliminer toutes sinon celles des attributs apparaissent en zone texte!
// Celles fondamentales pour la lecture (lt gt quot amp) sont conservees 
// (d'ailleurs SAX ne les considere pas comme des entites dans un attribut)
// Si la DTD est dispo, on va chercher les entites dedans
// sinon on se rabat sur ce qu'en connait SPIP en standard.

// http://doc.spip.org/@sax_bug
function sax_bug($data)
{
	global  $phraseur_xml;

	$r = analyser_doctype($data);

	if (!$r) {
		$data = _MESSAGE_DOCTYPE . _DOCTYPE_ECRIRE
		. preg_replace(_REGEXP_DOCTYPE, '', $data);
		$r =  analyser_doctype($data);
	}

	list($doctype, $topelement, $avail, $grammaire, $rotlvl, $len) = $r;

	$file = _DIR_CACHE_XML . preg_replace('/[^\w.]/','_', $rotlvl) . '.gz';

	if (lire_fichier($file, $r)) {
			$phraseur_xml->dtc = unserialize($r);
	} else {
		include_spip('xml/analyser_dtd');
		$phraseur_xml->dtc = charger_dtd($grammaire, $avail);
		if (($avail == 'PUBLIC' ) AND $phraseur_xml->dtc)
			ecrire_fichier($file, serialize($phraseur_xml->dtc), true);
	}

	// l'entete contient eventuellement < ? xml... ? >, le Doctype, 
	// et des commentaires autour d'eux
	$entete = ltrim(substr($data,0,$len));

	if ($phraseur_xml->dtc) {
		$trans = array();
		
		foreach($phraseur_xml->dtc->entites as $k => $v) {
			if (!strpos(" amp lt gt quot ", $k))
			    $trans["&$k;"] = $v;
		}
		$data = strtr(substr($data,$len), $trans);
	} else {
		$data = html2unicode(substr($data,$len), true);
	}
	return array($entete,unicode2charset($data));
}

// http://doc.spip.org/@analyser_doctype
function analyser_doctype($data)
{

	if (!preg_match(_REGEXP_DOCTYPE, $data, $page))
		return array();

	list($doctype,$pi,$co,$pico, $topelement, $avail,$suite) = $page;

	if (!preg_match('/^"([^"]*)"\s*(.*)$/', $suite, $r))
		if (!preg_match("/^'([^']*)'\s*(.*)$/", $suite, $r))
			return  array();
	list(,$rotlvl, $suite) = $r;

	if (!$suite) {
		if ($avail != 'SYSTEM') return array();
		$grammaire = $rotlvl;
		$rotlvl = '';
	} else {
		if (!preg_match('/^"([^"]*)"\s*$/', $suite, $r))
			if (!preg_match("/^'([^']*)'\s*$/", $suite, $r))
				return array();

		$grammaire = $r[1];
	}

	return array(substr($doctype,strlen($pico)), $topelement, $avail, $grammaire, $rotlvl, strlen($page[0]));
}
?>
