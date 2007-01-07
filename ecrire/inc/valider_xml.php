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

include_spip('inc/sax');

define('_REGEXP_DOCTYPE',
	'/^\s*(<[?][^>]*>\s*)?<!DOCTYPE\s+(\w+)\s+(\w+)\s*([^>]*)>/');

define('_REGEXP_ID', '/^[A-Za-z_][\w_:.-]*$/');

// Document Type Compilation

class DTC {
	var	$macros = array();
	var 	$elements = array();
	var 	$peres = array();
	var 	$attributs = array();
	var	$entites = array();
	var	$regles = array();
}

// http://doc.spip.org/@analyser_doctype
function analyser_doctype($data)
{
	if (!preg_match(_REGEXP_DOCTYPE, $data, $r))
		return array();

	list(,,$topelement, $avail,$suite) = $r;

	if (!preg_match('/^"([^"]*)"\s*(.*)$/', $suite, $r))
		if (!preg_match("/^'([^']*)'\s*(.*)$/", $suite, $r))
			return  array();
	list(,$rotlvl, $suite) = $r;

	if (!$suite) {
		$grammaire = $rotlvl;
		$rotlvl = '';
	} else {
		if (!preg_match('/^"([^"]*)"\s*$/', $suite, $r))
			if (!preg_match("/^'([^']*)'\s*$/", $suite, $r))
				return array();
		$grammaire = $r[1];
	}
	return array($topelement, $avail, $grammaire, $rotlvl);
}

// http://doc.spip.org/@charger_dtd
function charger_dtd($data)
{
	$r = analyser_doctype($data);
	if (!$r) return array();

	list ($topelement, $avail, $grammaire, $rotlvl) = $r;
	spip_log("Racine $topelement dans $grammaire ($rotlvl)");
	$dtc = new DTC;
	analyser_dtd($grammaire, $avail, $dtc);
$r = $dtc->regles; ksort($r);foreach($r as $l => $v) echo "$l '$v'<br />\n";
	return $dtc;
}

// Compiler une regle de production en une Regexp qu'on appliquera sur la
// suite des noms de balises separes par des espaces. Du coup:
// supprimer #PCDATA etc, ca ne sert pas pour le controle des balises;
// supprimer les virgules (les sequences sont implicites dans une Regexp)
// conserver | + * ? ( ) qui ont la meme signification en DTD et en Regexp;
// faire suivre chaque nom d'un espace (et supprimer les autres) ...
// et parentheser le tout pour que  | + * ? s'applique dessus.

function compilerRegle($val)
{
	$x = str_replace('()','',
		preg_replace('/\s*,\s*/','',
		preg_replace('/(\w+)\s*/','(\1 )',
		preg_replace('/\s*\|\s*/','|',
		preg_replace('/#\w+\s*[,|]?\s*/','', $val)))));
	return $x;
}


// http://doc.spip.org/@analyser_dtd
function analyser_dtd($grammaire, $avail, &$dtc)
{
	static $trace = array(); // pour debug

	$dtd = '';
	if ($avail == 'SYSTEM')
	  $file = $grammaire;
	else
	  $file = sous_repertoire(_DIR_DTD);
	  $file .= preg_replace('/[^\w.]/','_', $grammaire);

	if (@is_readable($file)) {
		lire_fichier($file, $dtd);
	} else {
		if ($avail == 'PUBLIC') {
			include_spip('inc/distant');
			if ($dtd = recuperer_page($grammaire))
				ecrire_fichier($file, $dtd); 
		}
	}
	if (!$dtd) {
		spip_log("DTD $grammaire inaccessible");
		return array();
	}

	// ejecter les commentaires, surtout quand ils contiennent du code.
	// Option /s car sur plusieurs lignes parfois

	$dtd = preg_replace('/<!--.*?-->/s','',$dtd);

	if (preg_match_all('/<!ENTITY\s+(%?)\s*([\w;.-]+)\s+(PUBLIC|SYSTEM)?\s*"([^"]*)"\s*("([^"]*)")?\s*>/', $dtd, $r, PREG_SET_ORDER)) {
		foreach($r as $m) {
		  list($t, $term, $nom, $type, $val, $q, $alt) = $m;
		  if ($type AND $alt) {
		    // valeur par defaut de $alt obscure. A etudier.
		    if (strpos($alt, '/') === false)
			$alt = preg_replace(',/[^/]+$,', '/', $grammaire)
			. ($alt ? $alt : "loose.dtd")  ;
		    // en cas d'inclusion, l'espace de nom est le meme
		    analyser_dtd($alt, $type, $dtc);
		  }
		  elseif (!$term) {
		    $dtc->entites[$nom] = $val;
		  }
		  else {
		    $dtc->macros[$nom] = expanserEntite($val, $dtc->macros) ;
		  }
		}
	}

	// Dresser le tableau des filles potentielles de l'element
	// pour traquer tres vite les illegitimes.
	// Si la regle a au moins une sequence (i.e. une virgule)
	// ou n'est pas une it�ration (i.e. se termine par * ou +)
	// en faire une RegExp qu'on appliquera aux balises rencontrees.
	// Sinon, conserver seulement le type de l'iteration car la traque
	// aura fait l'essentiel du controle sans memorisation des balises.
	// Fin du controle en finElement
	if (preg_match_all('/<!ELEMENT\s+(\S+)\s+([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
		foreach($r as $m) {
			list(,$nom, $val) = $m;
			$nom = expanserEntite($nom, $dtc->macros);
			$val = compilerRegle(expanserEntite($val, $dtc->macros));
			if (isset($dtc->elements[$nom])) {
			  spip_log("double definition de $nom dans la DTD");
			  return;
			}
			$filles = array();
			if ($val == '(EMPTY )')
				$dtc->regles[$nom] = 'EMPTY';
			elseif  ($val == '(ANY )') 
				$dtc->regles[$nom] = 'ANY';
			else {
				$last = substr($val,-1);
				if (preg_match('/ \w/', $val)
				OR strpos('*+', $last) === false)
					$dtc->regles[$nom] = "/^$val$/";
				else
					$dtc->regles[$nom] = $last;
				$filles = array_values(preg_split('/\W+/', $val,-1, PREG_SPLIT_NO_EMPTY));

				foreach ($filles as $k) {
					if ((!isset($dtc->peres[$k]))
					OR !in_array($nom, $dtc->peres[$k]))
						$dtc->peres[$k][]= $nom;
				}
			}
			$dtc->elements[$nom]= $filles;
		}
		// tri pour presenter les suggestions de corrections
		foreach ($dtc->peres as $k => $v) {
			asort($v);
			$dtc->peres[$k] = $v;
		} 
	}

	if (preg_match_all('/<!ATTLIST\s+(\S+)\s+([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    $nom = expanserEntite($nom, $dtc->macros);
	    $val = expanserEntite($val, $dtc->macros);
	    $att = array();

	    if (preg_match_all("/\s*(\S+)\s+(([(][^)]*[)])|(\S+))\s+(\S+)(\s*'[^']*')?/", $val, $r2, PREG_SET_ORDER)) {
		foreach($r2 as $m2) {
			$v = preg_match('/^\w+$/', $m2[2]) ? $m2[2]
			  : ('/^' . preg_replace('/\s+/', '', $m2[2]) . '$/');
			$m21 = expanserEntite($m2[1], $dtc->macros);
			$m25 = expanserEntite($m2[5], $dtc->macros);
			$trace[$v] = 1;
			$att[$m21] = array($v, $m25);
		}
	    }
	    $dtc->attributs[$nom] = $att;
	  }
	}

	spip_log("DTD $avail $grammaire ". strlen($dtd) . ' octets ' . count($dtc->macros)  . ' macros, ' . count($dtc->elements)  . ' elements, ' . count($trace) . " types diff�rents d'attributs " . count($dtc->entites) . " entites");
}


// http://doc.spip.org/@expanserEntite
function expanserEntite($val, $macros)
{
	if (preg_match_all('/%([.\w]+);/', $val, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
		  $ent = $m[1];
		  // il peut valoir ""
			if (isset($macros[$ent]))
				$val = str_replace($m[0], $macros[$ent], $val);
	  }
	}
	return trim(preg_replace('/\s+/', ' ', $val));
}


// http://doc.spip.org/@validerElement
function validerElement($phraseur, $name, $attrs)
{
	global $phraseur_xml;

	if (!isset($phraseur_xml->dtc->elements[$name]))

		$phraseur_xml->err[]= " <b>$name</b>"
		. _L(' balise inconnue ')
		.  coordonnees_erreur($phraseur);
	else {
	// controler les filles illegitimes, ca suffut 
	  $depth = $phraseur_xml->depth;
	  $ouvrant = $phraseur_xml->ouvrant;
	  if (isset($ouvrant[$depth])) {
	    if (preg_match('/^\s*(\w+)/', $ouvrant[$depth], $r)) {
	      $pere = $r[1];
	      if (isset($phraseur_xml->dtc->elements[$pere]))
		if (!@in_array($name, $phraseur_xml->dtc->elements[$pere])) {
	          $bons_peres = @join ('</b>, <b>', $phraseur_xml->dtc->peres[$name]);
	          $phraseur_xml->err[]= " <b>$name</b>"
	            . _L(" n'est pas un fils de ")
	            . '<b>'
	            .  $pere
	            . '</b>'
	            . (!$bons_peres ? ''
	               : (_L( '<p style="font-size: 80%"> mais de <b>') . $bons_peres . '</b></p>'))
		    .  coordonnees_erreur($phraseur);
		} else if ($phraseur_xml->dtc->regles[$pere][0]=='/') {
		  $phraseur_xml->fratrie[substr($depth,2)].= "$name ";
		}
	    }
	  }
	  // Init de la suite des balises a memoriser si regle difficile
	  if ($phraseur_xml->dtc->regles[$name][0]=='/')
	    $phraseur_xml->fratrie[$depth]='';
	  if (isset($phraseur_xml->dtc->attributs[$name])) {
		  foreach ($phraseur_xml->dtc->attributs[$name] as $n => $v)
		    { if (($v[1] == '#REQUIRED') AND (!isset($attrs[$n])))
			$phraseur_xml->err[]= " <b>$n</b>"
			  . '&nbsp;:&nbsp;'
			  . _L(" attribut obligatoire mais absent dans ")
			  . "<b>$name</b>"
			  .  coordonnees_erreur($phraseur);
		    }
	  }
	}
}


// http://doc.spip.org/@validerAttribut
function validerAttribut($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	// Si la balise est inconnue, eviter d'insister
	if (!isset($phraseur_xml->dtc->attributs[$bal]))
		return ;
		
	$a = $phraseur_xml->dtc->attributs[$bal];
	if (!isset($a[$name])) {
		$bons = join(', ',array_keys($a));
		if ($bons)
		  $bons = " title=' " .
		    _L('attributs connus: ') .
		    $bons .
		    "'";
		$bons .= " style='font-weight: bold'";
		$phraseur_xml->err[]= " <b>$name</b>"
		. _L(' attribut inconnu de ')
		. "<a$bons>$bal</a>"
		. _L(" (survoler pour voir les corrects)")
		.  coordonnees_erreur($phraseur);
	} else{
		$type =  $a[$name][0];
		if (!preg_match('/^\w+$/', $type))
			valider_motif($phraseur, $name, $val, $bal, $type);
		else if (function_exists($f = 'validerAttribut_' . $type))
			$f($phraseur, $name, $val, $bal);
	}
}

// http://doc.spip.org/@validerAttribut_ID
function validerAttribut_ID($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	if (isset($phraseur_xml->ids[$val])) {
		list($l,$c) = $phraseur_xml->ids[$val];
		$phraseur_xml->err[]= " <p><b>$val</b>"
		      . _L(" valeur de l'attribut ")
		      . "<b>$name</b>"
		      . _L(' de ')
		      . "<b>$bal</b>"
		      . _L(" vu auparavant ")
		      . "(L$l,C$c)"
		      .  coordonnees_erreur($phraseur);
	} else {
		valider_motif($phraseur, $name, $val, $bal, _REGEXP_ID);
		$phraseur_xml->ids[$val] = array(xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
	}
}

// http://doc.spip.org/@validerAttribut_IDREF
function validerAttribut_IDREF($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;
	$phraseur_xml->idrefs[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@validerAttribut_IDREFS
function validerAttribut_IDREFS($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	$phraseur_xml->idrefss[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@valider_motif
function valider_motif($phraseur, $name, $val, $bal, $motif)
{
	global $phraseur_xml;

	if (!preg_match($motif, $val)) {
		$phraseur_xml->err[]= " <p><b>$val</b>"
		. _L(" valeur de l'attribut ")
		. "<b>$name</b>"
		. _L(' de ')
		. "<b>$bal</b>"
		. _L(" n'est pas conforme au motif</p><p>")
		. "<b>" . $motif . "</b></p>"
		.  coordonnees_erreur($phraseur);
	}
}

// http://doc.spip.org/@valider_idref
function valider_idref(&$own, $nom, $ligne, $col)
{
	if (!isset($own->ids[$nom]))
		$own->err[]= " <p><b>$nom</b>"
		. _L(" ID inconnu ")
		. $ligne
		. " "
		. $col;
}

// http://doc.spip.org/@inc_valider_passe2_dist
function inc_valider_passe2_dist(&$own)
{
	if (!$own->err) {
		foreach ($own->idrefs as $idref) {
			list($nom, $ligne, $col) = $idref;
			valider_idref($own, $nom, $ligne, $col);
		}
		foreach ($own->idrefss as $idref) {
			list($noms, $ligne, $col) = $idref;
			foreach(preg_split('/\s+/', $noms) as $nom)
				valider_idref($own, $nom, $ligne, $col);
		}
	}
}

class ValidateurXML {

// http://doc.spip.org/@debutElement
function debutElement($phraseur, $name, $attrs)
{ 
	global $phraseur_xml;

	if ($phraseur_xml->dtc->elements)
		validerElement($phraseur, $name, $attrs);

	xml_debutElement($phraseur, $name, $attrs);
	$depth = &$phraseur_xml->depth;
	$phraseur_xml->debuts[$depth] =  strlen($phraseur_xml->res);
	foreach ($attrs as $k => $v) {
		validerAttribut($phraseur, $k, $v, $name);
	}
}

// http://doc.spip.org/@finElement
function finElement($phraseur, $name)
{
	global $phraseur_xml;

	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$ouvrant = &$phraseur_xml->ouvrant;

	$ouv = $ouvrant[$depth];
	if ($ouv[0] != ' ')
	  $ouvrant[$depth] = ' ' . $ouv;
	else $ouv= "";
	$n = strlen($phraseur_xml->res);
	$c = strlen(trim($contenu[$depth]));
	$k = $phraseur_xml->debuts[$depth];

	$regle = $phraseur_xml->dtc->regles[$name];
	$vide = ($regle  == 'EMPTY');
	// controler que les balises devant etre vides le sont 
	if ($vide) {
	  if ($n <> ($k + $c))
			$phraseur_xml->err[]= " <p><b>$name</b>"
			.  _L(' balise non vide')
			.  coordonnees_erreur($phraseur);
	// pour les regles PCDATA ou iteration de disjonction, tout est fait
	} elseif ($regle AND ($regle != '*')) {
		if ($regle == '+') {
		    // iteration de disjonction non vide: 1 balise au -
			if ($n == $k) {
				$phraseur_xml->err[]= " <p>\n<b>$name</b>"
				  .  _L(' balise vide')
				  .  coordonnees_erreur($phraseur);
			}
		} else {
			$f = $phraseur_xml->fratrie[substr($depth,2)];
			if (!preg_match($regle, $f))
				$phraseur_xml->err[]= " <p>\n<b>$name</b>"
				  .  _L(' succession des fils incorrecte : <b>')
				  . $f
				  . '</b>'
				  .  coordonnees_erreur($phraseur);
		}

	}
	xml_finElement($phraseur, $name, $vide);
}

// http://doc.spip.org/@textElement
function textElement($phraseur, $data)
{	xml_textElement($phraseur, $data);}

// http://doc.spip.org/@PiElement
function PiElement($phraseur, $target, $data)
{	xml_PiElement($phraseur, $target, $data);}

// http://doc.spip.org/@defautElement
function defautElement($phraseur, $data)
{	
	global $phraseur_xml;

	if (!preg_match('/^<!--/', $data)
	AND (preg_match_all('/&([^;]*)?/', $data, $r, PREG_SET_ORDER)))
		foreach ($r as $m) {
			list($t,$e) = $m;
			if (!isset($phraseur_xml->dtc->entites[$e]))
				$phraseur_xml->err[]= " <b>$e</b>"
				  . _L(' entite inconnue ')
				  .  coordonnees_erreur($phraseur);
		}
	xml_defautElement($phraseur, $data);
}

// http://doc.spip.org/@phraserTout
function phraserTout($phraseur, $data)
{ 
	$this->dtc = charger_dtd($data);

  // bug de SAX qui ne dit pas si une Entite est dans un attribut ou non
  // ==> eliminer toutes les entites

	$data = unicode2charset(html2unicode($data, true));

	xml_parsestring($phraseur, $data);

	$valider_passe2 = charger_fonction('valider_passe2', 'inc');
	$valider_passe2($this);

	return !$this->err ?  $this->res : join('<br />', $this->err) . '<br />';
}

 var $depth = "";
 var $res = "";
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();

 var $dtc = NULL;
 var $err = array();
 var $ids = array();
 var $idrefs = array();
 var $idrefss = array();
 var $debuts = array();
 var $fratrie = array();
}

// http://doc.spip.org/@inc_valider_xml_dist
function inc_valider_xml_dist($page, $apply=false)
{
	$sax = charger_fonction('sax', 'inc');
	return $sax($page, $apply, $GLOBALS['phraseur_xml'] = new ValidateurXML());

}
?>
