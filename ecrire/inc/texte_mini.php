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
include_spip('inc/filtres');
include_spip('inc/lang');

// On initialise la puce pour eviter find_in_path() a chaque rencontre de \n-
// Mais attention elle depend de la direction et de X_fonctions.php, ainsi que
// de l'espace choisi (public/prive)
// http://doc.spip.org/@definir_puce
function definir_puce() {

	// Attention au sens, qui n'est pas defini de la meme facon dans
	// l'espace prive (spip_lang est la langue de l'interface, lang_dir
	// celle du texte) et public (spip_lang est la langue du texte)
	$dir = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);

	$p = 'puce' . (test_espace_prive() ? '_prive' : '');
	if ($dir == 'rtl') $p .= '_rtl';

	if (!isset($GLOBALS[$p])) {
		$img = find_in_path($p.'.gif');
		list(,,,$size) = @getimagesize($img);
		$GLOBALS[$p] = '<img src="'.$img.'" '.$size.' class="puce" alt="-" />';
	}
	return $GLOBALS[$p];
}


// XHTML - Preserver les balises-bloc : on liste ici tous les elements
// dont on souhaite qu'ils provoquent un saut de paragraphe

if (!defined('_BALISES_BLOCS')) define('_BALISES_BLOCS',
	'p|div|pre|ul|ol|li|blockquote|h[1-6r]|'
	.'t(able|[rdh]|head|body|foot|extarea)|'
	.'form|object|center|marquee|address|'
	.'applet|iframe|'
	.'d[ltd]|script|noscript|map|button|fieldset|style');

if (!defined('_BALISES_BLOCS_REGEXP'))
	define('_BALISES_BLOCS_REGEXP',',</?('._BALISES_BLOCS.')[>[:space:]],iS');

//
// Echapper les elements perilleux en les passant en base64
//

// Creer un bloc base64 correspondant a $rempl ; au besoin en marquant
// une $source differente ; le script detecte automagiquement si ce qu'on
// echappe est un div ou un span
// http://doc.spip.org/@code_echappement
function code_echappement($rempl, $source='', $no_transform=false, $mode=NULL) {
	if (!strlen($rempl)) return '';

	// Tester si on echappe en span ou en div
	if (is_null($mode) OR !in_array($mode,array('div','span')))
		$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $rempl) ? 'div' : 'span';

	// Decouper en morceaux, base64 a des probleme selon la taille de la pile
	$taille = 30000;
	for($i = 0; $i < strlen($rempl); $i += $taille) {
		// Convertir en base64 et cacher dans un attribut
		// utiliser les " pour eviter le re-encodage de ' et &#8217
		$base64 = base64_encode(substr($rempl, $i, $taille));
		$return .= "<$mode class=\"base64$source\" title=\"$base64\"></$mode>";
	}

	return $return
		. ((!$no_transform AND $mode == 'div')
			? "\n\n"
			: ''
		);
;
}


// Echapper les <html>...</ html>
// http://doc.spip.org/@traiter_echap_html_dist
function traiter_echap_html_dist($regs) {
	return $regs[3];
}

// Echapper les <code>...</ code>
// http://doc.spip.org/@traiter_echap_code_dist
function traiter_echap_code_dist($regs) {
	list(,,$att,$corps) = $regs;
	$echap = htmlspecialchars($corps); // il ne faut pas passer dans entites_html, ne pas transformer les &#xxx; du code !

	// ne pas mettre le <div...> s'il n'y a qu'une ligne
	if (is_int(strpos($echap,"\n"))) {
		// supprimer les sauts de ligne debut/fin
		// (mais pas les espaces => ascii art).
		$echap = preg_replace("/^[\n\r]+|[\n\r]+$/s", "", $echap);
		$echap = nl2br($echap);
		$echap = "<div style='text-align: left;' "
		. "class='spip_code' dir='ltr'><code$att>"
		.$echap."</code></div>";
	} else {
		$echap = "<code$att class='spip_code' dir='ltr'>".$echap."</code>";
	}

	$echap = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $echap);
	$echap = str_replace("  ", " &nbsp;", $echap);
	return $echap;
}

// Echapper les <cadre>...</ cadre> aka <frame>...</ frame>
// http://doc.spip.org/@traiter_echap_cadre_dist
function traiter_echap_cadre_dist($regs) {
	$echap = trim(entites_html($regs[3]));
	// compter les lignes un peu plus finement qu'avec les \n
	$lignes = explode("\n",trim($echap));
	$n = 0;
	foreach($lignes as $l)
		$n+=floor(strlen($l)/60)+1;
	$n = max($n,2);
	$echap = "\n<textarea readonly='readonly' cols='40' rows='$n' class='spip_cadre' dir='ltr'>$echap</textarea>";
	return $echap;
}
// http://doc.spip.org/@traiter_echap_frame_dist
function traiter_echap_frame_dist($regs) {
	return traiter_echap_cadre_dist($regs);
}

// http://doc.spip.org/@traiter_echap_script_dist
function traiter_echap_script_dist($regs) {
	// rendre joli (et inactif) si c'est un script language=php
	if (preg_match(',<script\b[^>]+php,ims', $regs[0]))
		return highlight_string($regs[0],true);

	// Cas normal : le script passe tel quel
	return $regs[0];
}

define('_PROTEGE_BLOCS', ',<(html|code|cadre|frame|script)(\s[^>]*)?>(.*)</\1>,UimsS');

// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc/filtres
// http://doc.spip.org/@echappe_html
function echappe_html($letexte, $source='', $no_transform=false,
$preg='') {
	if (!is_string($letexte) or !strlen($letexte))
		return $letexte;

	if (($preg OR strpos($letexte,"<")!==false)
	  AND preg_match_all($preg ? $preg : _PROTEGE_BLOCS, $letexte, $matches, PREG_SET_ORDER))
		foreach ($matches as $regs) {
			// echappements tels quels ?
			if ($no_transform) {
				$echap = $regs[0];
			}

			// sinon les traiter selon le cas
			else if (function_exists($f = 'traiter_echap_'.strtolower($regs[1])))
				$echap = $f($regs);
			else if (function_exists($f = $f.'_dist'))
				$echap = $f($regs);

			$letexte = str_replace($regs[0],
				code_echappement($echap, $source, $no_transform),
				$letexte);
		}

	if ($no_transform)
		return $letexte;

	// Gestion du TeX
	if (strpos($letexte, "<math>") !== false) {
		include_spip('inc/math');
		$letexte = traiter_math($letexte, $source);
	}

	// Echapper le php pour faire joli (ici, c'est pas pour la securite)
	if (strpos($letexte,"<"."?")!==false AND preg_match_all(',<[?].*($|[?]>),UisS',
	$letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {
		$letexte = str_replace($regs[0],
			code_echappement(highlight_string($regs[0],true), $source),
			$letexte);
	}

	return $letexte;
}

//
// Traitement final des echappements
// Rq: $source sert a faire des echappements "a soi" qui ne sont pas nettoyes
// par propre() : exemple dans multi et dans typo()
// http://doc.spip.org/@echappe_retour
function echappe_retour($letexte, $source='', $filtre = "") {
	if (strpos($letexte,"base64$source")) {
		# spip_log(htmlspecialchars($letexte));  ## pour les curieux
		if (strpos($letexte,"<")!==false AND
		  preg_match_all(',<(span|div) class=[\'"]base64'.$source.'[\'"]\s(.*)>\s*</\1>,UmsS',
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
				// recherche d'attributs supplementaires
				$at = array();
				foreach(array('lang', 'dir') as $attr) {
					if ($a = extraire_attribut($reg[0], $attr))
						$at[$attr] = $a;
				}
				if ($at) {
					$rempl = '<'.$reg[1].'>'.$rempl.'</'.$reg[1].'>';
					foreach($at as $attr => $a)
						$rempl = inserer_attribut($rempl, $attr, $a);
				}
				if ($filtre) $rempl = $filtre($rempl);
				$letexte = str_replace($reg[0], $rempl, $letexte);
			}
		}
	}
	return $letexte;
}

// Reinserer le javascript de confiance (venant des modeles)

// http://doc.spip.org/@echappe_retour_modeles
function echappe_retour_modeles($letexte, $interdire_scripts=false)
{
	$letexte = echappe_retour($letexte);

	// Dans les appels directs hors squelette, securiser aussi ici
	if ($interdire_scripts)
		$letexte = interdire_scripts($letexte);

	return trim($letexte);
}


// http://doc.spip.org/@couper
function couper($texte, $taille=50, $suite = '&nbsp;(...)') {
	if (!($length=strlen($texte)) OR $taille <= 0) return '';
	$offset = 400 + 2*$taille;
	while ($offset<$length
		AND strlen(preg_replace(",<[^>]+>,Uims","",substr($texte,0,$offset)))<$taille)
		$offset = 2*$offset;
	if (	$offset<$length
			&& ($p_tag_ouvrant = strpos($texte,'<',$offset))!==NULL){
		$p_tag_fermant = strpos($texte,'>',$offset);
		if ($p_tag_fermant<$p_tag_ouvrant)
			$offset = $p_tag_fermant+1; // prolonger la coupe jusqu'au tag fermant suivant eventuel
	}
	$texte = substr($texte, 0, $offset); /* eviter de travailler sur 10ko pour extraire 150 caracteres */

	// on utilise les \r pour passer entre les gouttes
	$texte = str_replace("\r\n", "\n", $texte);
	$texte = str_replace("\r", "\n", $texte);

	// sauts de ligne et paragraphes
	$texte = preg_replace("/\n\n+/", "\r", $texte);
	$texte = preg_replace("/<(p|br)( [^>]*)?".">/", "\r", $texte);

	// supprimer les traits, lignes etc
	$texte = preg_replace("/(^|\r|\n)(-[-#\*]*|_ )/", "\r", $texte);

	// supprimer les tags
	$texte = supprimer_tags($texte);
	$texte = trim(str_replace("\n"," ", $texte));
	$texte .= "\n";	// marquer la fin

	// travailler en accents charset
	$texte = unicode2charset(html2unicode($texte, /* secure */ true));
	if (!function_exists('nettoyer_raccourcis_typo'))
		include_spip('inc/lien');
	$texte = nettoyer_raccourcis_typo($texte);

	// corriger la longueur de coupe
	// en fonction de la presence de caracteres utf
	if ($GLOBALS['meta']['charset']=='utf-8'){
		$long = charset2unicode($texte);
		$long = spip_substr($long, 0, max($taille,1));
		$nbcharutf = preg_match_all('/(&#[0-9]{3,5};)/S', $long, $matches);
		$taille += $nbcharutf;
	}


	// couper au mot precedent
	$long = spip_substr($texte, 0, max($taille-4,1));
	$u = $GLOBALS['meta']['pcre_u'];
	$court = preg_replace("/([^\s][\s]+)[^\s]*\n?$/".$u, "\\1", $long);
	$points = $suite;

	// trop court ? ne pas faire de (...)
	if (spip_strlen($court) < max(0.75 * $taille,2)) {
		$points = '';
		$long = spip_substr($texte, 0, $taille);
		$texte = preg_replace("/([^\s][\s]+)[^\s]*\n?$/".$u, "\\1", $long);
		// encore trop court ? couper au caractere
		if (spip_strlen($texte) < 0.75 * $taille)
			$texte = $long;
	} else
		$texte = $court;

	if (strpos($texte, "\n"))	// la fin est encore la : c'est qu'on n'a pas de texte de suite
		$points = '';

	// remettre les paragraphes
	$texte = preg_replace("/\r+/", "\n\n", $texte);

	// supprimer l'eventuelle entite finale mal coupee
	$texte = preg_replace('/&#?[a-z0-9]*$/S', '', $texte);

	return quote_amp(trim($texte)).$points;
}


// http://doc.spip.org/@protege_js_modeles
function protege_js_modeles($t) {
	if (isset($GLOBALS['visiteur_session'])){
		if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER)){
			if (!defined('_PROTEGE_JS_MODELES')){
				include_spip('inc/acces');
				define('_PROTEGE_JS_MODELES',creer_uniqid());
			}
			foreach ($r as $regs)
				$t = str_replace($regs[0],code_echappement($regs[0],'javascript'._PROTEGE_JS_MODELES),$t);
		}
		if (preg_match_all(',<\?php.*?($|\?'.'>),isS', $t, $r, PREG_SET_ORDER)){
			if (!defined('_PROTEGE_PHP_MODELES')){
				include_spip('inc/acces');
				define('_PROTEGE_PHP_MODELES',creer_uniqid());
			}
			foreach ($r as $regs)
				$t = str_replace($regs[0],code_echappement($regs[0],'php'._PROTEGE_PHP_MODELES),$t);
		}
	}
	return $t;
}


function echapper_faux_tags($letexte){
	if (strpos($letexte,'<')===false)
		return $letexte;
  $textMatches = preg_split (',(</?[a-z!][^<>]*>),', $letexte, null, PREG_SPLIT_DELIM_CAPTURE);

  $letexte = "";
  while (count($textMatches)) {
  	// un texte a echapper
  	$letexte .= str_replace(array("<"),array('&lt;'),array_shift($textMatches));
  	// un tag html qui a servit a faite le split
 		$letexte .= array_shift($textMatches);
  }
  return $letexte;
}

// Securite : utiliser SafeHTML s'il est present dans ecrire/safehtml/
// http://doc.spip.org/@safehtml
function safehtml($t) {
	static $safehtml;

	if (!$t OR !is_string($t))
		return $t;
	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t,'<')===false)
		return str_replace("\x00", '', $t);

	$t = interdire_scripts($t); // jolifier le php
	$t = echappe_js($t);

	if (!isset($safehtml))
		$safehtml = charger_fonction('safehtml', 'inc', true);
	if ($safehtml)
		$t = $safehtml($t);

	return interdire_scripts($t); // interdire le php (2 precautions)
}


// fonction en cas de texte extrait d'un serveur distant:
// on ne sait pas (encore) rapatrier les documents joints
// Sert aussi a nettoyer un texte qu'on veut mettre dans un <a> etc.
// TODO: gerer les modeles ?
// http://doc.spip.org/@supprime_img
function supprime_img($letexte, $message=NULL) {
	if ($message===NULL) $message = '(' . _T('img_indisponible') . ')';
	return preg_replace(',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'\s*/?'.'>,i',
		$message, $letexte);
}