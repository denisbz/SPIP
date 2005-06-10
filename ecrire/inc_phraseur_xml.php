<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("INC_PHRASEUR_XML")) return;
define("INC__PHRASEUR_XML", "1");


class PhraseurXML {

function debutElement($parser, $name, $attrs)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $ouvrant = &$phraseur_xml->ouvrant;
  $reperes = &$phraseur_xml->reperes;
  $res = &$phraseur_xml->res;

  $t = $ouvrant[$depth];
  if ($t[0] != ' ')
    {
      $res .= '<' . $t . '>';
      $ouvrant[$depth] = ' ' . $t;
    }
  $t = $contenu[$depth];
  $res .= ereg_replace("[\n\t ]+$",  "\n$depth", $t);
  $contenu[$depth] = "";
  $att = '';
  $sep = ' ';
  foreach ($attrs as $k => $v) {
	$delim = strpos($v, "'") === false ? "'" : '"';
	$att .= $sep .  $k . "=" . $delim . 
	  $phraseur_xml->translate_entities($v)
	  . $delim;
	$sep = "\n $depth";
    }
  $depth .= '  ';
  $contenu[$depth] = "";
  $ouvrant[$depth] = $name . $att;
  $reperes[$depth] = xml_get_current_line_number($parser);
}

function finElement($parser, $name)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $ouvrant = &$phraseur_xml->ouvrant;
  $res = &$phraseur_xml->res;

  $ouv = $ouvrant[$depth];
  if ($ouv[0] != ' ')
      $ouvrant[$depth] = ' ' . $ouv;
  else $ouv= "";
  $t = $contenu[$depth];
  $depth = substr($depth, 2);
  $t = ereg_replace("[\n\t ]+$", "\n" . $depth, $t);
  if ($t)
    $res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . "</" . $name . ">";
  else
    $res .= ($ouv ? ('<' . $ouv  . ' />') : ("</" .  $name . ">"));
}

function textElement($parser, $data)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $contenu[$depth] .= $phraseur_xml->translate_entities($data);
}

function PiElement($parser, $target, $data)
{
  global $phraseur_xml, $xml_parser;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  if (strtolower($target) != "php")
    $contenu[$depth] .= $data;
  else {
	  ob_start();
	  eval($data);
	  $data = ob_get_contents();
	  ob_end_clean();
	  //	  xml_parse($xml_parser, $data); // pas si simple
	  $contenu[$depth] .= $data;
  }
}


function defautElement($parser, $data)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;

  $contenu[$depth] .= $data;
}

 function translate_entities($data)
 {
   return
    str_replace('<', '&lt;', 
		str_replace('>', '&gt;', 
			    ereg_replace('[&]([A-Za-z0-9]*[^A-Za-z0-9;])',
					 "&amp;\\1",
					 $data)));
 }

function xml_parsefile($xml_parser, $file)
{
  if (!($fp = fopen($file, "r"))) {
   die("Impossible d'ouvrir le fichier XML");
  }
  while ($data = fread($fp, 4096)) {
    if (!xml_parse($xml_parser, str_replace('&#8217;',"'",$data), feof($fp))) {
      return (sprintf("erreur XML : %s ligne %d",
		      xml_error_string(xml_get_error_code($xml_parser)),
		      xml_get_current_line_number($xml_parser)));
    }
  }
  return "";
}

function xml_parsestring($xml_parser, $data)
{
	global $phraseur_xml;
	$r = "";
	if (!xml_parse($xml_parser, $data, true)) {
	  // ne pas commencer le message par un "<" (cf inc_debug)
	  $r = xml_error_string(xml_get_error_code($xml_parser)) .
	    _L(" ligne ") .
	    xml_get_current_line_number($xml_parser) .
	    _L(" colonne ") .
	    xml_get_current_column_number($xml_parser) .
	    '<br />' .
	    _L("derni&egrave;re balise non referm&eacute;e&nbsp;: ") .
	    "<tt>" .
	    $phraseur_xml->ouvrant[$phraseur_xml->depth] .
	    "</tt>" .
	    _L(" ligne ") .
	    $phraseur_xml->reperes[$phraseur_xml->depth];

	} else $r = $phraseur_xml->res;

	xml_parser_free($xml_parser);
	return $r;
}

var $depth = "";
var $res = "";
var $contenu = array();
var $ouvrant = array();
var $reperes = array();
}

// xml_set_objet a utiliser a terme
global $phraseur_xml, $xml_parser;
$phraseur_xml = new PhraseurXML();

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser,
			array($phraseur_xml, "debutElement"),
			array($phraseur_xml, "finElement"));
xml_set_character_data_handler($xml_parser, array($phraseur_xml, "textElement"));
xml_set_processing_instruction_handler($xml_parser, array($phraseur_xml, 'PiElement'));
xml_set_default_handler($xml_parser, array($phraseur_xml, "defautElement"));
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

// exemple d'appels
#$error = $phraseur_xml->xml_parsefile($xml_parser, $_SERVER['argv'][1]);
#$error = $phraseur_xml->xml_parsestring($xml_parser, "<html></html>");

#echo $error ? $error : $phraseur_xml->res;

?>
