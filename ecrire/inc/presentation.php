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

include_spip('inc/agenda'); // inclut inc/layer, inc/texte, inc/filtre
include_spip('inc/boutons');
include_spip('inc/actions');

// Choix dynamique de la couleur

// http://doc.spip.org/@choix_couleur
function choix_couleur() {
	global $couleurs_spip;
	if ($couleurs_spip) {
		foreach ($couleurs_spip as $key => $val) {
			echo "<a href=\"".parametre_url(self(), 'set_couleur', $key)."\">" .
				http_img_pack("rien.gif", " ", "width='8' height='8' style='margin: 1px; background-color: ".$val['couleur_claire'].";' onmouseover=\"changestyle('bandeauinterface','visibility', 'visible');\""). "</a>";
		}
	}
}

// Faux HR, avec controle de couleur

// http://doc.spip.org/@hr
function hr($color, $retour = false) {
	$ret = "<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";
	
	if ($retour) return $ret;
	else echo $ret;
}


//
// Cadres
//

// http://doc.spip.org/@debut_cadre
function debut_cadre($style, $icone = "", $fonction = "", $titre = "") {
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$style_gauche = " padding-$spip_lang_left: 38px;";
		$style_cadre = " style='margin-top: 14px;'";
	} else $style_cadre = $style_gauche = '';
	
	// accesskey pour accessibilite espace prive
	if ($accesskey <= 122) // z
	{
		$accesskey_c = chr($accesskey++);
		$ret = "<a name='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";
	} else $ret ='';

	if ($style == "e") {
		$ret .= "<div class='cadre-e-noir'$style_cadre><div class='cadre-$style'>";
	}
	else {
		$ret .= "<div class='cadre-$style'$style_cadre>";
	}

	$ret .= "<div style='position: relative;'>";

	if ($spip_display != 1 AND $spip_display != 4 AND strlen($icone) > 1) {
		$ret .= "<div style='position: absolute; top: -16px; $spip_lang_left: 10px;'>";
		if ($fonction) {
			$ret .= "<div " . http_style_background($icone, "no-repeat; padding: 0px; margin: 0px") . ">";
			$ret .= http_img_pack($fonction, "", "");
			$ret .= "</div>";
		}
		else $ret .=  http_img_pack("$icone", "", "");
		$ret .= "</div>";

		$style_cadre = " style='position: relative; top: 15px; margin-bottom: 14px;'";
	}


	if (strlen($titre) > 0) {
		if ($spip_display == 4) {
			$ret .= "<h3 class='cadre-titre'>$titre</h3>";
		} else {
			$ret .= "<div class='cadre-titre' style='margin: 0px;$style_gauche'>$titre</div>";
		}
	}
	
	
	$ret .= "</div>";
	
	$ret .= "<div class='cadre-padding' style='overflow:hidden;'>";


	return $ret;
}

// http://doc.spip.org/@fin_cadre
function fin_cadre($style) {

	$ret = "</div>";
	$ret .= "</div>";
	if ($style == "e") $ret .= "</div>";
	if ($style != "forum" AND $style != "thread-forum") $ret .= "<div style='height: 5px;'></div>";

	return $ret;
}


// http://doc.spip.org/@debut_cadre_relief
function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_relief
function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_enfonce
function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_enfonce
function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_sous_rub
function debut_cadre_sous_rub($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('sous_rub', $icone, $fonction, $titre);
	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_sous_rub
function fin_cadre_sous_rub($return = false){
	$retour_aff = fin_cadre('sous_rub');
	if ($return) return $retour_aff;
	else echo $retour_aff;
}



// http://doc.spip.org/@debut_cadre_forum
function debut_cadre_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_forum
function fin_cadre_forum($return = false){
	$retour_aff = fin_cadre('forum');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_thread_forum
function debut_cadre_thread_forum($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('thread-forum', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_thread_forum
function fin_cadre_thread_forum($return = false){
	$retour_aff = fin_cadre('thread-forum');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_gris_clair
function debut_cadre_gris_clair($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('gris-clair', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_gris_clair
function fin_cadre_gris_clair($return = false){
	$retour_aff = fin_cadre('gris-clair');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur
function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur
function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


// http://doc.spip.org/@debut_cadre_couleur_foncee
function debut_cadre_couleur_foncee($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur-foncee', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_couleur_foncee
function fin_cadre_couleur_foncee($return = false){
	$retour_aff = fin_cadre('couleur-foncee');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@debut_cadre_trait_couleur
function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

// http://doc.spip.org/@fin_cadre_trait_couleur
function fin_cadre_trait_couleur($return = false){
	$retour_aff = fin_cadre('trait-couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}



//
// une boite alerte
//
// http://doc.spip.org/@debut_boite_alerte
function debut_boite_alerte() {
	echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
	echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor='white'>";
}

// http://doc.spip.org/@fin_boite_alerte
function fin_boite_alerte() {
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}


//
// une boite info
//
// http://doc.spip.org/@debut_boite_info
function debut_boite_info($return=false) {
	$r ="<div class='cadre-info verdana1'>";
	if ($return) return $r; else echo $r;
}

// http://doc.spip.org/@fin_boite_info
function fin_boite_info($return=false) {
	//echo "</font></td></tr></table></div>\n\n";
	$r = "</div>";
	if ($return) return $r; else echo $r;
}

//
// une autre boite
//
// http://doc.spip.org/@bandeau_titre_boite2
function bandeau_titre_boite2($titre, $logo="", $fond="white", $texte="black", $echo = true) {
	global $spip_lang_left, $spip_display, $browser_name;
	
	if (strlen($logo) > 0 AND $spip_display != 1 AND $spip_display != 4) {
		$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

		$retour = "<div style='position: relative;$ie_style'>"
		. "<div style='position: absolute; top: -12px; $spip_lang_left: 3px;'>" .
		  http_img_pack("$logo", "", "")
		. "</div>"
		. "<div style='background-color: $fond; color: $texte; padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;' class='verdana2'>$titre</div>"
		 . "</div>";
	} else {
		$retour = "<h3 style='background-color: $fond; color: $texte; padding: 3px; border-bottom: 1px solid #444444; margin: 0px;' class='verdana2'>$titre</h3>";
	}

	if ($echo) echo $retour; return $retour;
}

//
// La boite des raccourcis
// Se place a droite si l'ecran est en mode panoramique.

// http://doc.spip.org/@bloc_des_raccourcis
function bloc_des_raccourcis($bloc) {
	global $spip_display;

	return "<div>&nbsp;</div>"
	. creer_colonne_droite('',true)
	. debut_cadre_enfonce('',true)
	. (($spip_display != 4)
	     ? ("<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"
		._T('titre_cadre_raccourcis')
		."</b><p />")
	       : ( "<h3>"._T('titre_cadre_raccourcis')."</h3><ul>"))
	. $bloc
	. (($spip_display != 4) ? "</font>" :  "</ul>")
	. fin_cadre_enfonce(true);
}

// Afficher un petit "+" pour lien vers autre page

// http://doc.spip.org/@afficher_plus
function afficher_plus($lien) {
	global $options, $spip_lang_right, $spip_display;
	
	if ($options == "avancees" AND $spip_display != 4) {
			return "<div style='float:$spip_lang_right; padding-top: 2px;'><a href='$lien'>" .
			  http_img_pack("plus.gif", "+", "") ."</a></div>";
	}
}



//
// Fonctions d'affichage
//

// http://doc.spip.org/@afficher_liste
function afficher_liste($largeurs, $table, $styles = '') {
	global $spip_display;

	if (!is_array($table)) return "";

	if ($spip_display != 4) {
		$res = '';
		while (list(, $t) = each($table)) {
			$res .= afficher_liste_display_neq4($largeurs, $t, $styles);
		}
	} else {
		$res = "\n<ul style='text-align: $spip_lang_left; background-color: white;'>";
		while (list(, $t) = each($table)) {
			$res .= afficher_liste_display_eq4($largeurs, $t, $styles);
		}
		$res .= "\n</ul>";
	}

	return $res;
}

function afficher_liste_display_neq4($largeurs, $t, $styles = '') {

	global $spip_lang_left;
	$res = '';
	$evt = (eregi("msie", $browser_name) ? " onmouseover=\"changeclass(this,'tr_liste_over');\" onmouseout=\"changeclass(this,'tr_liste');\"" :'');

	$res .= "\n<tr class='tr_liste'$evt>";
	reset($largeurs);
	if ($styles) reset($styles);
	while (list(, $texte) = each($t)) {
		$style = $largeur = "";
		list(, $largeur) = each($largeurs);
		if ($styles) list(, $style) = each($styles);
		if (!trim($texte)) $texte .= "&nbsp;";
		$res .= "\n<td" .
			($largeur ? " width=\"$largeur\"" : '') .
			($style ? " class=\"$style\"" : '') .
			">$texte\n</td>";
	}
	$res .= "\n</tr>";
	return $res;
}

function afficher_liste_display_eq4($largeurs, $t, $styles = '') {
	global $spip_lang_left;

	$res .= "<li>";
	reset($largeurs);
	if ($styles) reset($styles);
	while (list(, $texte) = each($t)) {
		$style = $largeur = "";
		list(, $largeur) = each($largeurs);
		if (!$largeur) $res .= $texte." ";
	}
	$res .= "</li>\n";
	return $res;
}

// http://doc.spip.org/@afficher_tranches_requete
function afficher_tranches_requete($num_rows, $tmp_var, $url='', $nb_aff = 10) {
	static $ancre = 0;
	global $browser_name, $spip_lang_right, $spip_display;

	$deb_aff = intval(_request($tmp_var));
	$ancre++;
	$self = self();
	$ie_style = ($browser_name == "MSIE") ? "height:1%" : '';

	$texte = "\n<div style='position: relative;$ie_style; background-color: #dddddd; border-bottom: 1px solid #444444; padding: 2px;' class='arial1' id='a$ancre'>";
	$on ='';

	for ($i = 0; $i < $num_rows; $i += $nb_aff){
		$deb = $i + 1;
		$fin = $i + $nb_aff;
		if ($fin > $num_rows) $fin = $num_rows;
		if ($deb > 1) $texte .= " |\n";
		if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
			$texte .= "<b>$deb</b>";
		}
		else {
			$script = parametre_url($self, $tmp_var, $deb-1);
			if ($url) {
				$on = "\nonclick=\"return charger_id_url('"
				. $url
				. "&amp;"
				. $tmp_var
				. '='
				. ($deb-1)
				. "','"
				. $tmp_var
				. '\');"';
			}
			$texte .= "<a href=\"$script#a$ancre\"$on>$deb</a>";
		}
	}
	
	$texte .= "<span class=\"arial2\" style='border-bottom: 1px solid #444444; position: absolute; top: 2px; $spip_lang_right: 15px;'>";


	$script = parametre_url($self, $tmp_var, -1);
	if ($url) {
				$on = "\nonclick=\"return charger_id_url('"
				. $url
				. "&amp;"
				. $tmp_var
				. "=-1','"
				. $tmp_var
				. '\');"';
	}
	$l = htmlentities(_T('lien_tout_afficher'));
	$texte .= "<a href=\"$script#a$ancre\"$on><img\nsrc='". _DIR_IMG_PACK . "plus.gif' title=\"$l\" alt=\"$l\" /></a>";
	

	$texte .= "</span></div>\n";

	return $texte;
}


// http://doc.spip.org/@affiche_tranche_bandeau
function affiche_tranche_bandeau($requete, $icone, $fg, $bg, $tmp_var,  $titre, $force, $largeurs, $styles, $skel, $own='')
{
	global $spip_display ;

	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($force OR ($cpt = $cpt['n']))) return  array();

	if ($titre) echo "<div style='height: 12px;'></div>";
	echo "<div class='liste'>";
	echo bandeau_titre_boite2('<b>' . $titre . '</b>', $icone, $fg, $bg, false);
	echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);

	$deb_aff = intval(_request($tmp_var));
	$nb_aff = floor(1.5 * _TRANCHES);

	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		echo afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	$result = spip_query("SELECT " . (isset($requete["SELECT"]) ? $requete["SELECT"] : "*") . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	$tous_id = array();
	while ($row = spip_fetch_array($result)) {
		$table[]= $skel($row, $tous_id, $voir_logo, $own);
	}
	spip_free_result($result);
		
	echo afficher_liste($largeurs, $table, $styles);
	echo "</table>";
	echo "</div>\n";
	return $tous_id;
}


// http://doc.spip.org/@afficher_liste_debut_tableau
function afficher_liste_debut_tableau() {
	global $spip_display;

	if ($spip_display != 4) return "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";
}

// http://doc.spip.org/@afficher_liste_fin_tableau
function afficher_liste_fin_tableau() {
	global $spip_display;
	if ($spip_display != 4) return "</table>";
}


// http://doc.spip.org/@puce_statut_article
function puce_statut_article($id, $statut, $id_rubrique) {
	global $spip_lang_left, $dir_lang, $connect_statut, $options;
	
	switch ($statut) {
	case 'publie':
		$clip = 2;
		$puce = 'verte';
		$title = _T('info_article_publie');
		break;
	case 'prepa':
		$clip = 0;
		$puce = 'blanche';
		$title = _T('info_article_redaction');
		break;
	case 'prop':
		$clip = 1;
		$puce = 'orange';
		$title = _T('info_article_propose');
		break;
	case 'refuse':
		$clip = 3;
		$puce = 'rouge';
		$title = _T('info_article_refuse');
		break;
	case 'poubelle':
		$clip = 4;
		$puce = 'poubelle';
		$title = _T('info_article_supprime');
		break;
	}
	$puce = "puce-$puce.gif";
	
	if ($connect_statut == '0minirezo' AND $options == 'avancees' AND acces_rubrique($id_rubrique)) {
	  // les versions de MSIE ne font pas toutes pareil sur alt/title
	  // la combinaison suivante semble ok pour tout le monde.
	  $titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
	  $action = "onmouseover=\"montrer('statutdecalarticle$id');\"";
	  $inser_puce = "\n<div class='puce_article' id='statut$id'$dir_lang>"
			. "\n<div class='puce_article_fixe' $action>" .
		  http_img_pack("$puce", "", "id='imgstatutarticle$id' style='margin: 1px;'") ."</div>"
			. "\n<div class='puce_article_popup' id='statutdecalarticle$id' onmouseout=\"cacher('statutdecalarticle$id');\" style=' margin-left: -".((11*$clip)+1)."px;'>\n"
			. afficher_script_statut($id, 'article', -1, 'puce-blanche.gif', 'prepa', $titles['blanche'], $action)
			. afficher_script_statut($id, 'article', -12, 'puce-orange.gif', 'prop', $titles['orange'], $action)
			. afficher_script_statut($id, 'article', -23, 'puce-verte.gif', 'publie', $titles['verte'], $action)
			. afficher_script_statut($id, 'article', -34, 'puce-rouge.gif', 'refuse', $titles['rouge'], $action)
			. afficher_script_statut($id, 'article', -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'], $action)
		. "</div></div>";
	} else {
		$inser_puce = http_img_pack("$puce", "", "id='imgstatutarticle$id' style='margin: 1px;'");
	}
	return $inser_puce;
}

// http://doc.spip.org/@puce_statut_breve
function puce_statut_breve($id, $statut, $type, $droit) {
	global $spip_lang_left, $dir_lang;

	$puces = array(
		       0 => 'puce-orange-breve.gif',
		       1 => 'puce-verte-breve.gif',
		       2 => 'puce-rouge-breve.gif',
		       3 => 'puce-blanche-breve.gif');

	switch ($statut) {
			case 'prop':
				$clip = 0;
				$puce = $puces[0];
				$title = _T('titre_breve_proposee');
				break;
			case 'publie':
				$clip = 1;
				$puce = $puces[1];
				$title = _T('titre_breve_publiee');
				break;
			case 'refuse':
				$clip = 2;
				$puce = $puces[2];
				$title = _T('titre_breve_refusee');
				break;
			default:
				$clip = 0;
				$puce = $puces[3];
				$title = '';
	}

	$type1 = "statut$type$id"; 
	$inser_puce = http_img_pack($puce, "", "id='img$type1' style='margin: 1px;'");

	if (!$droit) return $inser_puce;
	
	$type2 = "statutdecal$type$id";
	$action = "onmouseover=\"montrer('$type2');\"\n";

	  // les versions de MSIE ne font pas toutes pareil sur alt/title
	  // la combinaison suivante semble ok pour tout le monde.

	return	"<div class='puce_breve' id='$type1'$dir_lang>"
		. "<div class='puce_breve_fixe' $action>"
		. $inser_puce
		. "</div>"
		. "\n<div class='puce_breve_popup' id='$type2' onmouseout=\"cacher('$type2');\" style=' margin-left: -".((9*$clip)+1)."px;'>\n"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop',_T('texte_statut_propose_evaluation'), $action)
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie',_T('texte_statut_publie'), $action)
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse',_T('texte_statut_refuse'), $action)
		.  "</div></div>";
}

// http://doc.spip.org/@afficher_script_statut
function afficher_script_statut($id, $type, $n, $img, $statut, $title, $act)
{
  return http_href_img("javascript:selec_statut('$id', '$type', -1, '" .
		      http_wrapper($img) .
		      "', '" .
		       generer_action_auteur("instituer_$type","$id-$statut") .
		      "');",
		      $img,
			"title=\"".$title."\"",
			'','','',
		      $act);
}

//
// Afficher tableau d'articles
//
// http://doc.spip.org/@afficher_articles
function afficher_articles($titre, $requete, $formater_article='') {

	if (!isset($requete['FROM'])) $requete['FROM'] = 'spip_articles AS articles';

	if (!isset($requete['SELECT'])) {
		$requete['SELECT'] = "articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date, articles.lang, articles.id_trad, articles.descriptif";
	}
	
	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	// memorisation des arguments pour g�rer l'affichage par tranche
	// et/ou par langues.

	$hash = substr(md5(serialize($requete) . $GLOBALS['meta']['gerer_trad'] . $titre), 0, 31);
	$tmp_var = 't' . substr($hash, 0, 7);

	// le champ id_auteur sert finalement a memoriser le nombre de lignes
	// (a renommer)

	$res_proch = spip_query("SELECT id_ajax_fonc, id_auteur FROM spip_ajax_fonc WHERE hash=0x$hash LIMIT 1");

	if ($row = spip_fetch_array($res_proch)) {
		$id_ajax = $row["id_ajax_fonc"];
		$cpt = $row["id_auteur"];
	} else  {

		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));

		if (!$cpt = $cpt['n']) return '' ;
		
		if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);
		$v = serialize(array($titre, $requete, $tmp_var, $formater_article));

		include_spip ('base/abstract_sql');
		$id_ajax = spip_abstract_insert("spip_ajax_fonc", "(variables, hash, id_auteur, date)", "(" . _q($v) . ", 0x$hash, $cpt, NOW())");
	}

	$nb_aff = floor(1.5 * _TRANCHES);
	$deb_aff = intval(_request($tmp_var));

	$requete['FROM'] = preg_replace("/(spip_articles AS \w*)/", "\\1 LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete['FROM']);

	$requete['SELECT'] .= ", petitions.id_article AS petition ";

	return afficher_articles_trad($titre, $requete, $formater_article, $tmp_var, $id_ajax, $cpt);
}

// http://doc.spip.org/@afficher_articles_trad
function afficher_articles_trad($titre_table, $requete, $formater_article, $tmp_var, $id_ajax, $cpt, $trad=0) {

	global $options, $spip_lang_right;

	if ($trad) {
		$formater_article = 'afficher_articles_trad_boucle';
		$icone = "langues-off-12.gif";
	} else {
		if (!$formater_article)
			$formater_article =  charger_fonction('formater_article', 'inc');
		$icone = 'langues-12.gif';
	}

	$nb_aff = ($cpt  > floor(1.5 * _TRANCHES)) ? _TRANCHES : $cpt ;
	$deb_aff = intval(_request($tmp_var));

	$q = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));
	$t = '';
	while ($r = spip_fetch_array($q)) $t .= $formater_article($r);
	spip_free_result($q);

	$style = "style='visibility: hidden; float: $spip_lang_right'";

	$texte = http_img_pack("searching.gif", "*", $style . " id='img_$tmp_var'");

	if (($GLOBALS['meta']['gerer_trad'] == "oui")) {
		$url = generer_url_ecrire('memoriser',"id_ajax_fonc=$id_ajax&trad=" . (1-$trad));
		$texte .= 
		 "\n<div style='float: $spip_lang_right;'><a href=\"#\"\nonclick=\"return charger_id_url('$url','$tmp_var');\">"
		. "<img\nsrc='". _DIR_IMG_PACK . $icone ."' alt=' ' /></a></div>";
	}
	$texte .=  '<b>' . $titre_table  . '</b>';

	$res =  "\n<div style='height: 12px;'></div>"
	. "\n<div class='liste'>"
	. bandeau_titre_boite2($texte, "article-24.gif", 'white', 'black',false)

	. (($cpt <= $nb_aff) ? ''
	   : afficher_tranches_requete($cpt, $tmp_var, generer_url_ecrire('memoriser', "id_ajax_fonc=$id_ajax&trad=$trad"), $nb_aff))
	. afficher_liste_debut_tableau()
	. $t
	. afficher_liste_fin_tableau()
	. "</div>\n";

	return ajax_action_greffe($tmp_var,$res);
}

// http://doc.spip.org/@afficher_articles_trad_boucle
function afficher_articles_trad_boucle($row)
{
	global $dir_lang,  $spip_lang_right;

	$vals = '';

	$id_article = $row['id_article'];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$id_trad = $row['id_trad'];
	$lang = $row['lang'];

	// La petite puce de changement de statut
	$vals[] = puce_statut_article($id_article, $statut, $id_rubrique);

	// Le titre (et la langue)
	
	$langues_art = "";
	$dates_art = "";
	$l = "";

	$res_trad = spip_query("SELECT id_article, lang, date_modif  FROM spip_articles WHERE id_trad = $id_trad AND id_trad > 0");

	while ($row_trad = spip_fetch_array($res_trad)) {

		$id_article_trad = $row_trad["id_article"];
		$lang_trad = $row_trad["lang"];
		$date_trad = $row_trad["date_modif"];
		$dates_art[$lang_trad] = $date_trad;
		$langues_art[$lang_trad] = $id_article_trad;
		if ($id_article_trad == $id_trad) $date_ref = $date;
	}


	// faudrait sortir ces invariants de boucle

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND (!isset($GLOBALS['id_rubrique']))) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$afficher_langue = true;
			$langue_defaut = isset($GLOBALS['langue_rubrique'])
			  ? $GLOBALS['meta']['langue_site']
			  : $GLOBALS['langue_rubrique'];
	}

	$span_lang = false;

	foreach(explode(',', $GLOBALS['meta']['langues_multilingue']) as $k){
		if ($langues_art[$k]) {
			if ($langues_art[$k] == $id_trad) {
				$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "'><span class='lang_base'>$k</span></a>";
				$l .= $span_lang;
			} else {
				$date = $dates_art[$k];
				if ($date < $date_ref) 
					$l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='claire'>$k</a>";
				else $l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='foncee'>$k</a>";
			}			
		}
#				else $l.= "<span class='creer'>$k</span>";
	}
			
	if (!$span_lang)
		$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'><span class='lang_base'>$lang</span></a>";

	$vals[] = "<div style='text-align: center;'>$span_lang</div>";
			
			
	$s = "<div>";
	$s .= "<div style='float: $spip_lang_right; margin-right: -10px;'>$l</div>";
	
	if (acces_restreint_rubrique($id_rubrique))
		$s .= http_img_pack("admin-12.gif", "", "width='12' height='12'", _T('titre_image_admin_article'));

	$s .= "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'$dir_lang style=\"display:block;\">";
			
			
	if ($id_article == $id_trad) $titre = "<b>$titre</b>";
			
	$s .= typo($titre);

	if ($afficher_langue AND $lang != $langue_defaut)
		$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";

	$s .= "</a>";
	$s .= "</div>";
	
	$vals[] = $s;
	
	$vals[] = "";
	
	$largeurs = array(11, 24, '', '1');
	$styles = array('', 'arial1', 'arial1', '');

	return ($spip_display != 4)
	? afficher_liste_display_neq4($largeurs, $vals, $styles)
	: afficher_liste_display_eq4($largeurs, $vals, $styles);
}

//
// Afficher tableau de breves
//

// http://doc.spip.org/@afficher_breves
function afficher_breves($titre_table, $requete, $affrub=false) {
	global  $couleur_foncee, $options;	
 
	if (($GLOBALS['meta']['multi_rubriques'] == 'oui'
	     AND (!isset($GLOBALS['id_rubrique'])))
	OR $GLOBALS['meta']['multi_articles'] == 'oui') {
		$afficher_langue = true;

		if (isset($GLOBALS['langue_rubrique'])) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = $GLOBALS['meta']['langue_site'];
	} else $afficher_langue = $langue_defaut = '';


	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);

	if ($options == "avancees") {
		if ($affrub) $largeurs = array('7', '', '188', '38');
		else $largeurs = array('7','', '100', '38');
		$styles = array('', 'arial11', 'arial1', 'arial1');
	} else {
		if ($affrub) $largeurs = array('7','', '188');
		else  $largeurs = array('7','', '100');
		$styles = array('','arial11', 'arial1');
	}

	return affiche_tranche_bandeau($requete, "breve-24.gif", $couleur_foncee, "white", $tmp_var, $titre_table, false, $largeurs, $styles, 'afficher_breves_boucle', array( $afficher_langue, $affrub, $langue_defaut));

}

// http://doc.spip.org/@afficher_breves_boucle
function afficher_breves_boucle($row, &$tous_id,  $voir_logo, $own)
{
  global  $dir_lang, $options, $connect_statut, $spip_lang_right;
	$droit = ($connect_statut == '0minirezo' && $options == 'avancees');
	list($afficher_langue, $affrub, $langue_defaut) = $own;
	$vals = '';

	$id_breve = $row['id_breve'];
	$tous_id[] = $id_breve;
	$date_heure = $row['date_heure'];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$statut = $row['statut'];
	if (isset($row['lang']))
	  changer_typo($lang = $row['lang']);
	else $lang = $langue_defaut;
	$id_rubrique = $row['id_rubrique'];
			
	$vals[] = puce_statut_breve($id_breve, $statut, 'breve', ($droit && acces_rubrique($id_rubrique)), $id_rubrique);

	$s = "<div>";
	$s .= "<a href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "' style=\"display:block;\">";

	if ($voir_logo) {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($logo = $logo_f($id_breve, 'id_breve', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			$logo = ratio_image($fid, $nom, $format, 26, 20, "alt=''");
			if ($logo)
				$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</div>";
		}
	}

	$s .= typo($titre);
	if ($afficher_langue AND $lang != $langue_defaut)
		$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";
	$s .= "</a>";

	$s .= "</div>";
	$vals[] = $s;

	$s = "";
	if ($affrub) {
		$rub = spip_fetch_array(spip_query("SELECT id_rubrique, titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$id_rubrique = $rub['id_rubrique'];
		$s .= "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style=\"display:block;\">".typo($rub['titre'])."</a>";
	} else if ($statut != "prop")
			$s = affdate_jourcourt($date_heure);
		else
			$s .= _T('info_a_valider');
	$vals[] = $s;
			
	if ($options == "avancees") {
		$vals[] = "<b>"._T('info_numero_abbreviation')."$id_breve</b>";
	}
			
	return $vals;
}


//
// Afficher tableau de rubriques
//

// http://doc.spip.org/@afficher_rubriques
function afficher_rubriques($titre_table, $requete) {
	global $options;

        $tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);
	$largeurs = array('12','', '');
	$styles = array('', 'arial2', 'arial11');
	return affiche_tranche_bandeau($requete, "rubrique-24.gif", "#999999", "white", $tmp_var, $titre_table, false, $largeurs, $styles, 'afficher_rubriques_boucle');
}

// http://doc.spip.org/@afficher_rubriques_boucle
function afficher_rubriques_boucle($row, &$tous_id)
{
	$vals = '';

	$id_rubrique = $row['id_rubrique'];
	$id_parent = $row['id_parent'];
	$tous_id[] = $id_rubrique;
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$lang = traduire_nom_langue($row['lang']);
	$langue_choisie = $row['langue_choisie'];
	
	if ($langue_choisie == "oui") $lang = "<b>$lang</b>";
	else $lang = "($lang)";
	
	if ($id_parent == 0) $puce = "secteur-12.gif";
	else $puce = "rubrique-12.gif";
	
	$s = http_img_pack($puce, '- ', "");
	$vals[] = $s;
	
	$s = "<b><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>";
	$s .= typo($titre);
	$s .= "</A></b>";
	$vals[] = $s;
	
	$s = "<div align=\"right\">";
	if  ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
				$s .= ($lang);
	}
	$s .= "</div>";
	$vals[] = $s;
	return $vals;
}

//
// Afficher des auteurs sur requete SQL
//
// http://doc.spip.org/@bonhomme_statut
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "0minirezo":
			return http_img_pack("admin-12.gif", "", "",
					_T('titre_image_administrateur'));
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
			  return http_img_pack("visit-12.gif",'', "", _T('titre_image_redacteur'));
			else
			  return http_img_pack("redac-12.gif",'', "", _T('titre_image_redacteur_02'));
			break;
		case "5poubelle":
		  return http_img_pack("poubelle.gif", '', "",_T('titre_image_auteur_supprime'));
			break;
		case "6forum":
		  return http_img_pack("visit-12.gif", '', "",_T('titre_image_visiteur'));
			break;
		case "nouveau":
		default:
			return '';
			break;
	}
}

// La couleur du statut
// http://doc.spip.org/@puce_statut
function puce_statut($statut, $type='article') {
	switch ($statut) {
		case 'publie':
			return 'verte';
		case 'prepa':
			return 'blanche';
		case 'prop':
			return 'orange';
		case 'refuse':
			return 'rouge';
		case 'poubelle':
			return 'poubelle';
	}
}


// http://doc.spip.org/@afficher_auteurs
function afficher_auteurs ($titre_table, $requete) {

	if (!$requete['SELECT']) $requete['SELECT'] = '*' ;

	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($cpt = $cpt['n'])) return $tous_id ;
	if ($requete['LIMIT']) $cpt = min($requete['LIMIT'], $cpt);

	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);
	$nb_aff = floor(1.5 * _TRANCHES);
	$deb_aff = intval(_request($tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	debut_cadre_relief("auteur-24.gif");

	if ($titre_table) {
			echo "<p><table width='100%' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr><td width='100%'>";
			echo "<table width='100%' cellpadding='3' cellspacing='0' border='0'>";
			echo "<tr bgcolor='#333333'><td width='100%' colspan='5'><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
	else {
			echo "<p><table width='100%' cellpadding='3' cellspacing='0' border='0'>";
		}

	echo $tranches;

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$tous_id[] = $row['id_auteur'];
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$table[]= $formater_auteur($row['id_auteur']);
	}
	spip_free_result($result);
	$largeurs = array(20, 20, 200, 20, 50);
	$styles = array('','','arial2','arial1','arial1');
	echo afficher_liste($largeurs, $table, $styles);

	if ($titre_table) echo "</table></td></tr>";
	echo "</table>";
	fin_cadre_relief();

	return $tous_id;
}

//
// Afficher les forums
//

// http://doc.spip.org/@afficher_forum
function afficher_forum($request, $retour, $arg, $controle_id_article = false) {
	global $spip_display;
	static $compteur_forum = 0;
	static $nb_forum = array();
	static $thread = array();

	$compteur_forum++;
	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$thread[$compteur_forum] = 1;
	
	$res = '';

	if ($spip_display == 4) $res .= "<ul>";
 
 	while($row = spip_fetch_array($request)) {
		$statut=$row['statut'];
		if ($compteur_forum==1) $res .= "\n<br />";
		if (($controle_id_article) ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			 OR ($statut=="publie" AND $id_parent > 0))) {

			$res .= afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $thread, $retour, $arg)
			. afficher_forum(spip_query("SELECT * FROM spip_forum WHERE id_parent='" . $row['id_forum'] . "'" . ($controle_id_article ? " AND statut<>'off'" : '') . " ORDER BY date_heure"), $retour, $arg, $controle_id_article);
		}
		$thread[$compteur_forum]++;
	}
	if ($spip_display == 4) $res .= "</ul>";
	spip_free_result($request);
	$compteur_forum--;
	return $res;
}

// http://doc.spip.org/@afficher_forum_thread
function afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $i, $retour, $arg) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right, $spip_display;
	static $voir_logo = array(); // pour ne calculer qu'une fois

	if (is_array($voir_logo)) {
		$voir_logo = (($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non") ? 
		      "position: absolute; $spip_lang_right: 0px; margin: 0px; margin-top: -3px; margin-$spip_lang_right: 0px;" 
		      : '');
	}

	$id_forum=$row['id_forum'];
	$id_parent=$row['id_parent'];
	$id_rubrique=$row['id_rubrique'];
	$id_article=$row['id_article'];
	$id_breve=$row['id_breve'];
	$id_message=$row['id_message'];
	$id_syndic=$row['id_syndic'];
	$date_heure=$row['date_heure'];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$auteur= extraire_multi($row['auteur']);
	$email_auteur=$row['email_auteur'];
	$nom_site=$row['nom_site'];
	$url_site=$row['url_site'];
	$statut=$row['statut'];
	$ip=$row["ip"];
	$id_auteur=$row["id_auteur"];
	
	$res = "<a id='id$id_forum'></a>";

	if ($spip_display == 4) {
		$res .= "<li>".typo($titre)."<br />";
	} else {

		$titre_boite = '';
		if ($id_auteur AND $voir_logo) {
			$logo_f = charger_fonction('chercher_logo', 'inc');
			if ($logo = $logo_f($id_auteur, 'id_auteur', 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				$logo = ratio_image($fid, $nom, $format, 48, 48, "alt=''");
				if ($logo)
					$titre_boite = "<div style='$voir_logo'>$logo</div>" ;
			}
		} 

		$titre_boite .= typo($titre);

		$res .= "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr>";
		$res .= afficher_forum_4($compteur_forum, $nb_forum, $i);

		if ($compteur_forum == 1) 
			$res .= afficher_forum_logo($statut, $titre_boite);
		else $res .= debut_cadre_thread_forum("", true, "", $titre_boite);
	}
			
	// Si refuse, cadre rouge
	if ($statut=="off") {
		$res .= "<div style='border: 2px dashed red; padding: 5px;'>";
	}
	// Si propose, cadre jaune
	else if ($statut=="prop") {
		$res .= "<div style='border: 1px solid yellow; padding: 5px;'>";
	}
		
	$res .= "<span class='arial2'>". date_interface($date_heure) . "</span>&nbsp;&nbsp;";

	if ($id_auteur) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res .= join(' ',$formater_auteur($id_auteur));
	} else {
		if ($email_auteur) {
			if (email_valide($email_auteur))
				$email_auteur = "<a href='mailto:"
				.htmlspecialchars($email_auteur)
				."?subject=".rawurlencode($titre)."'>".$email_auteur
				."</a>";
			$auteur .= " &mdash; $email_auteur";
		}
		$res .= safehtml("<span class='arial2'> / <b>$auteur</b></span>");
	}

	// boutons de moderation
	if ($controle_id_article)
		$res .= boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$id_article", $ip);

	$res .= safehtml(justifier(propre($texte)));

	if ($nom_site) {
		if (strlen($url_site) > 10)
			$res .= "<div align='left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>";
		else $res .= "<b>$nom_site</b>";
	}

	if (!$controle_id_article) {
	  	$tm = rawurlencode($titre);
		$res .= "<div align='right' class='verdana1'>"
		. "<b><a href='"
		  . generer_url_ecrire("forum_envoi", "id_parent=$id_forum&titre_message=$tm&script=$retour") . '#formulaire'
		. "'>"
		. _T('lien_repondre_message')
		. "</a></b></div>";
	}

	if ($GLOBALS['meta']["mots_cles_forums"] == "oui")
		$res .= afficher_forum_mots($id_forum);
	
	if ($statut == "off" OR $statut == "prop") $res .= "</div>";

	if ($spip_display != 4) {
		if ($compteur_forum == 1) $res .= fin_cadre_forum(true);
		else $res .= fin_cadre_thread_forum(true);
		$res .= "</td></tr></table>\n";
	}
	return $res;
}


// http://doc.spip.org/@afficher_forum_logo
function afficher_forum_logo($statut, $titre_boite)
{
	if ($statut == "prive") $logo = "forum-interne-24.gif";
	else if ($statut == "privadm") $logo = "forum-admin-24.gif";
	else if ($statut == "privrac") $logo = "forum-interne-24.gif";
	else $logo = "forum-public-24.gif";
	return debut_cadre_forum($logo, true, "", $titre_boite);
}

// http://doc.spip.org/@afficher_forum_mots
function afficher_forum_mots($id_forum)
{
	$result = spip_query("SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot");

	$res = '<ul>';
	while ($row = spip_fetch_array($result)) {
		$res .= "<li> <b>"
		. propre($row['titre'])
		. " :</b> "
		.  propre($row['type'])
		.  "<li>";
	}
	$res .= '</ul>';
	return $res;
}

// affiche les traits de liaisons entre les reponses

// http://doc.spip.org/@afficher_forum_4
function afficher_forum_4($compteur_forum, $nb_forum, $thread)
{
	global $spip_lang_rtl;
	$fleche='rien.gif';
	$res = '';
	for ($j=2;$j<=$compteur_forum AND $j<20;$j++){
		$fond[$j]=_DIR_IMG_PACK . 'rien.gif';
		if ($thread[$j]!=$nb_forum[$j]){
			$fond[$j]=_DIR_IMG_PACK . 'forum-vert.gif';
		}
		if ($j==$compteur_forum){
			$fleche="forum-droite$spip_lang_rtl.gif";
		}
		$res .= "<td width='10' valign='top' style='background-color: "
		.  $fond[$j]
		.  "'>"
		. http_img_pack($fleche, " ", "width='10' height='13'")
		. "</td>\n";
	}
	return $res . "\n<td width='100%' valign='top'>";
}


// http://doc.spip.org/@envoi_link
function envoi_link($nom_site_spip) {
	global $connect_statut, $connect_toutes_rubriques, $spip_display;
	global $spip_lang, $couleur_claire, $couleur_foncee;

	$args = "couleur_claire=" .
		substr($couleur_claire,1) .
		'&couleur_foncee=' .
		substr($couleur_foncee,1) .
		'&ltr=' . 
		$GLOBALS['spip_lang_left'];

	// CSS de secours en cas de non fonct de la suivante
	$res = '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('style_prive_defaut.css')
	. '" />'  . "\n"
	
	// CSS espace prive : la vraie
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $args) .'" />' . "\n"

	// CSS calendrier
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('agenda.css') .'" />' . "\n"

	// CSS imprimante (masque des trucs, a completer)
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('spip_style_print.css')
	. '" media="print" />' . "\n"

	// CSS "visible au chargement" differente selon js actif ou non
	. '<link rel="stylesheet" type="text/css" href="'
	. find_in_path('spip_style_'
		. (($_COOKIE['spip_accepte_ajax'] != -1) ? 'invisible' : 'visible')
		. '.css')
	.' " />' . "\n"

	// favicon.ico
	. '<link rel="shortcut icon" href="'
	. url_absolue(find_in_path('favicon.ico'))
	. "\" />\n";
	$js = debut_javascript($connect_toutes_rubriques,
			($GLOBALS['meta']["activer_statistiques"] != 'non'));

	if ($spip_display == 4) return $res . $js;

	$nom = entites_html($nom_site_spip);

	$res .= "<link rel='alternate' type='application/rss+xml' title=\"$nom\" href='"
			. generer_url_public('backend') . "' />\n";
	$res .= "<link rel='help' type='text/html' title=\""._T('icone_aide_ligne') . 
			"\" href='"
			. generer_url_ecrire('aide_index',"var_lang=$spip_lang")
			."' />\n";
	if ($GLOBALS['meta']["activer_breves"] != "non")
		$res .= "<link rel='alternate' type='application/rss+xml' title=\""
			. $nom
			. " ("._T("info_breves_03")
			. ")\" href='" . generer_url_public('backend-breves') . "' />\n";

	return $res . $js;
}

// http://doc.spip.org/@debut_javascript
function debut_javascript($admin, $stat)
{
	global $spip_lang_left, $browser_name, $browser_version;
	include_spip('inc/charsets');


	// tester les capacites JS :

	// On envoie un script ajah ; si le script reussit le cookie passera a +1
	// on installe egalement un <noscript></noscript> qui charge une image qui
	// pose un cookie valant -1
	$tester_javascript =  ($_COOKIE['spip_accepte_ajax'] >= 1) ? '' : (
 "if (a = createXmlHttp()) {
	a.open('GET', '" . generer_url_ecrire('test_ajax', 'js=1', '&') .
		  "', true) ;
	a.send(null);
}");

	if ($_COOKIE['spip_accepte_ajax'] != -1) {
		define('_TESTER_NOSCRIPT',
			"<noscript><div style='display:none;'><img src='".generer_url_ecrire('test_ajax', 'js=-1')."' width='1' height='1' alt='' /></div></noscript>\n"); // pour le pied de page
	}

	return 
	// envoi le fichier JS de config si browser ok.
		$GLOBALS['browser_layer'] .
	 	http_script(
			$tester_javascript . 
			"\nvar ajax_image_searching = '<div style=\"float: ".$GLOBALS['spip_lang_right'].";\"><img src=\"".url_absolue(_DIR_IMG_PACK."searching.gif")."\" /></div>';" .
			"\nvar admin = " . ($admin ? 1 : 0) .
			"\nvar stat = " . ($stat ? 1 : 0) .
			"\nvar largeur_icone = " .
			largeur_icone_bandeau_principal(_T('icone_a_suivre')) .
			"\nvar  bug_offsetwidth = " .
// uniquement affichage ltr: bug Mozilla dans offsetWidth quand ecran inverse!
			((($spip_lang_left == "left") &&
			  (($browser_name != "MSIE") ||
			   ($browser_version >= 6))) ? 1 : 0) .
			"\nvar confirm_changer_statut = '" .
			unicode_to_javascript(addslashes(html2unicode(_T("confirm_changer_statut")))) . 
			"';\n") .
		http_script('',_DIR_JAVASCRIPT . 'presentation.js');
}

// Fonctions onglets

// http://doc.spip.org/@onglet_relief_inter
function onglet_relief_inter(){
	
	echo "<td>&nbsp;</td>";
	
}

// http://doc.spip.org/@debut_onglet
function debut_onglet(){

	echo "\n\n";
	echo "<div style='padding: 7px;'><table cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
}

// http://doc.spip.org/@fin_onglet
function fin_onglet(){
	echo "</tr>";
	echo "</table></div>\n\n";
}

// http://doc.spip.org/@onglet
function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	global $spip_display, $spip_lang_left ;


	echo "<td>";
	
	if ($onglet != $onglet_ref) {
		echo "<div style='position: relative;'>";
		if ($spip_display != 1) {
			if (strlen($icone) > 0) {
				echo "<div style='z-index: 2; position: absolute; top: 0px; $spip_lang_left: 5px;'>" .
				  http_img_pack("$icone", "", "") . "</div>";
				$style = " top: 7px; padding-$spip_lang_left: 32px; z-index: 1;";
			} else {
				$style = " top: 7px;";
			}
		}
		
		echo "<div onmouseover=\"changeclass(this, 'onglet_on');\" onmouseout=\"changeclass(this, 'onglet');\" class='onglet' style='position: relative;$style'><a href='$lien'>$texte</a></div>";
		
		
		echo "</div>";
	} else {
		echo "<div style='position: relative;'>";
		if ($spip_display != 1) {
			if (strlen($icone) > 0) {
				echo "<div style='z-index: 2; position: absolute; top: 0px; $spip_lang_left: 5px;'>" .
				  http_img_pack("$icone", "", "") . "</div>";
				$style = " top: 7px; padding-$spip_lang_left: 32px; z-index: 1;";
			} else {
				$style = " top: 7px;";
			}
		}
		
		echo "<div class='onglet_off' style='position: relative;$style'>$texte</div>";
		
		
		echo "</div>";
	}
	echo "</td>";
}

// http://doc.spip.org/@barre_onglets
function barre_onglets($rubrique, $ongletCourant){
	$onglets= definir_barre_onglets($rubrique);
	if(count($onglets)==0) return;

	debut_onglet();

	foreach($onglets as $exec => $onglet) {
		$url= $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		onglet(_T($onglet->libelle), $url,
 			$exec, $ongletCourant, $onglet->icone);
	}
	fin_onglet();
}

// http://doc.spip.org/@largeur_icone_bandeau_principal
function largeur_icone_bandeau_principal($texte) {
	global $spip_display, $spip_ecran ;
	global $connect_statut, $connect_toutes_rubriques;

	if ($spip_display == 1){
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$largeur = 60;
	}
	else {
		if (count(explode(" ", $texte)) > 1) $largeur = 84;
		else $largeur = 80;
	}
	if ($spip_ecran == "large") $largeur = $largeur + 30;

	if (!$connect_toutes_rubriques) {
		$largeur = $largeur + 30;
	}


	return $largeur;
}

// http://doc.spip.org/@bandeau_principal
function bandeau_principal($rubrique, $sous_rubrique, $largeur)
{
	$res = '';
	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if ($page=='espacement') {
			$res .= "<td> &nbsp; </td>";
		} else {
			if ($detail->url)
				$lien_noscript = $detail->url;
			else
				$lien_noscript = generer_url_ecrire($page);

			if ($detail->url2)
				$lien = $detail->url2;
			else
				$lien = $lien_noscript;

			$res .= icone_bandeau_principal(
					_T($detail->libelle),
					$lien,
					$detail->icone,
					$page,
					$rubrique,
					$lien_noscript,
					$page,
					$sous_rubrique);
		}
	}

	return "<div class='bandeau-icones'>\n<table width='$largeur' cellpadding='0' cellspacing='0' border='0' align='center'><tr>\n$res</tr></table></div>\n";
}


// http://doc.spip.org/@icone_bandeau_principal
function icone_bandeau_principal($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique = "", $lien_noscript = "", $sous_rubrique_icone = "", $sous_rubrique = ""){
	global $spip_display, $menu_accesskey, $compteur_survol;

	$largeur = largeur_icone_bandeau_principal($texte);

	$alt = '';
	$title = '';
	if ($spip_display == 1){
	}
	else if ($spip_display == 3){
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		$alt = ' ';
	}
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}

	$class_select = ($sous_rubrique_icone == $sous_rubrique) ? " class='selection'" : '';

	if (eregi("^javascript:",$lien)) {
		$a_href = "\nonclick=\"$lien; return false;\" href='$lien_noscript' target='spip_aide'$class_select";
	}
	else {
		$a_href = "\nhref=\"$lien\"$class_select";
	}

	$compteur_survol ++;

	if ($spip_display != 1 AND $spip_display != 4) {
		$class ='cellule48';
		$texte = http_img_pack($fond, $alt, "$title width='48' height='48'")
		. ($spip_display == 3 ? '' :  "<span>$texte</span>");
	} else {
		$class = 'cellule-texte';
	}  
		
	return "<td class='$class' onmouseover=\"changestyle('bandeau$rubrique_icone', 'visibility', 'visible');\" width='$largeur'><a$accesskey$a_href>$texte</a></td>\n";
}




// http://doc.spip.org/@icone_bandeau_secondaire
function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;
	global $menu_accesskey, $compteur_survol;

	$alt = '';
	$title = '';
	$accesskey = '';
	if ($spip_display == 1) {
		//$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		//$hauteur = 26;
		$largeur = 40;
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		//$hauteur = 68;
		if (count(explode(" ", $texte)) > 1) $largeur = 80;
		else $largeur = 70;
		$alt = " ";
	}
	if ($aide AND $spip_display != 3) {
		$largeur += 50;
		//$texte .= aide($aide);
	}
	if ($spip_display != 3 AND strlen($texte)>16) $largeur += 20;
	
	if (!$menu_accesskey = intval($menu_accesskey)) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}
	if ($spip_display == 3) $accesskey_icone = $accesskey;

	$class_select =  ($rubrique_icone != $rubrique) ? '' : " class='selection'";
	$compteur_survol ++;

	$a_href = "<a$accesskey href=\"$lien\"$class_select>";

	if ($spip_display != 1) {
		echo "<td class='cellule36' style='width: ".$largeur."px;'>";
		echo "$a_href" .
		  http_img_pack("$fond", $alt, "$title");
		if ($aide AND $spip_display != 3) echo aide($aide)." ";
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' width='$largeur'>$a_href".$texte;
	echo "</a>";	
	echo "</td>\n";
	return $largeur;
}



// http://doc.spip.org/@icone
function icone($texte, $lien, $fond, $fonction="", $align="", $afficher='oui'){
	global $spip_display;

	if ($fonction == "supprimer.gif") {
		$style = '-danger';
	} else {
		$style = '';
		if (strlen($fonction) < 3) $fonction = "rien.gif";
	}

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 100;
		$title = $alt = "";
	}
	else if ($spip_display == 3){
		$hauteur = 30;
		$largeur = 30;
		$title = "title=\"$texte\"";
		$alt = $texte;
	}
	else {
		$hauteur = 70;
		$largeur = 100;
		$title = '';
		$alt = $texte;
	}

	if ($spip_display != 1 AND $spip_display != 4){
		if ($fonction != "rien.gif"){
		  $icone = http_img_pack($fonction, $alt, "$title width='24' height='24'" .
					  http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone = http_img_pack($fond, $alt, "$title width='24' height='24'");
		}
	} else $icone = '';

	if ($spip_display != 3){
		$icone .= "<span>$texte</span>";
	}

	// cas d'ajax_action_auteur: faut defaire le boulot 
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r))
	  list($x,$lien,$atts,$texte)= $r;
	else $atts = '';
	$lien = "\nhref='$lien'$atts";

	$icone = "\n<table cellpadding='0' class='pointeur' cellspacing='0' border='0' width='$largeur'"
	. ((strlen($align) > 2) ? " align='$align' " : '')
	. ">\n<tr><td class='icone36$style' style='text-align:center;'><a"
	. $lien
	. '>'
	. $icone
	. "</a></td></tr></table>\n";

	if ($afficher == 'oui')	echo $icone; else return $icone;
}

// http://doc.spip.org/@icone_horizontale
function icone_horizontale($texte, $lien, $fond = "", $fonction = "", $echo = true, $javascript='') {
	global $spip_display;

	$retour = '';

	// cas d'ajax_action_auteur: faut defaire le boulot 
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a href='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r))
	  list($x,$lien,$atts,$texte)= $r;
	else $atts = '';
	$lien = "\nhref='$lien'$atts";

	if ($spip_display != 4) {
	
		if ($spip_display != 1) {
			$retour .= "\n<table class='cellule-h-table' cellpadding='0' style='vertical-align: middle'>"
			. "\n<tr><td><a $javascript$lien class='cellule-h'>"
			. "<span class='cell-i'>" ;
			if ($fonction){
				$retour .= http_img_pack($fonction, "", http_style_background($fond, "center center no-repeat"));
			}
			else {
				$retour .= http_img_pack($fond, "", "");
			}
			$retour .= "</span></a></td>"
			. "\n<td class='cellule-h-lien'><a $javascript$lien class='cellule-h'>"
			. $texte
			. "</a></td></tr></table>\n";
		}
		else {
			$retour .= "<div><a class='cellule-h-texte' $javascript$lien>$texte</a></div>\n";
		}
		if ($fonction == "supprimer.gif")
			$retour = "\n<div class='danger'>$retour</div>";
	} else {
		$retour = "<li><a$lien>$texte</a></li>";
	}

	if ($echo) echo $retour; else return $retour;
}


// http://doc.spip.org/@bandeau_barre_verticale
function bandeau_barre_verticale(){
	echo "<td class='separateur'></td>\n";
}


// lien changement de couleur
// http://doc.spip.org/@lien_change_var
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien = parametre_url($lien, $set, $couleur);
	return "\n<area shape='rect' href='$lien' coords='$coords' title=\"$titre\" alt=' ' $mouseOver />";
}

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@debut_page
function debut_page($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $onLoad = "", $id_rubrique = "") {

	include_spip('inc/headers');
	http_no_cache();
	echo init_entete($titre, $id_rubrique);
	init_body($rubrique, $sous_rubrique, $onLoad, $id_rubrique);

	echo "<center onmouseover='recherche_desesperement()'>", // ????
		avertissement_messagerie(),
		(($rubrique == "messagerie")
		 OR (_request('changer_config')!="oui")) 
		? auteurs_recemment_connectes() : '';
}

// envoi du doctype et du <head><title>...</head> 
// http://doc.spip.org/@init_entete
function init_entete($titre='', $id_rubrique=0) {
  include_spip('inc/gadgets');

	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
		$nom_site_spip=  _T('info_mon_site_spip');

	$head = "<title>["
		. $nom_site_spip
		. "] " . textebrut(typo($titre)) . "</title>\n"
		. "<meta http-equiv='Content-Type' content='text/html"
		. (($c = $GLOBALS['meta']['charset']) ?
			"; charset=$c" : '')
		. "' />\n"
		. envoi_link($nom_site_spip);

	// anciennement verifForm
	$head .= '
	<script type="text/javascript"><!--
	$(document).ready(function(){
		verifForm();
	'
	.
	repercuter_gadgets($id_rubrique)
	.'
	});
	// --></script>
	';

	return _DOCTYPE_ECRIRE
	. html_lang_attributes()
	. "<head>\n"
	. pipeline('header_prive', $head)
	. "</head>\n";
}

// fonction envoyant la double serie d'icones de redac
// http://doc.spip.org/@init_body
function init_body($rubrique='accueil', $sous_rubrique='accueil', $load='', $id_rubrique='') {
	global $couleur_foncee, $couleur_claire;
	global $connect_id_auteur, $connect_toutes_rubriques;
	global $auth_can_disconnect;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	definir_barre_boutons();
	if ($load)
		$load = " onload=\"$load\"";

	echo pipeline('body_prive',"<body ". _ATTRIBUTES_BODY
		.$load
		. '>');

	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;

	echo "\n<map id='map_layout'>";
	echo lien_change_var (self(), 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var (self(), 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo lien_change_var (self(), 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
	echo "\n</map>";

	if ($spip_display == "4") {
		echo "<ul>";
		echo "<li><a href='./'>"._T('icone_a_suivre')."</a></li>";
		echo "<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a></li>";
		echo "<li><a href='" . generer_url_ecrire("forum"). "'>"._T('titre_forum')."</a></li>";
		echo "<li><a href='" . generer_url_ecrire("auteurs") . "'>"._T('icone_auteurs')."</a></li>";
		echo "<li><a href=\"".url_de_base()."\">"._T('icone_visiter_site')."</a></li>";
		echo "</ul>";

		return;
	}

	// Lien oo
	echo "<div class='invisible_au_chargement' style='position: absolute; height: 0px; visibility: hidden;'><a href='oo'>"._T("access_mode_texte")."</a></div>";
	
	echo "<div id='haut-page'>";
	echo "<div class='bandeau-principal' align='center'>\n";
	echo bandeau_principal($rubrique, $sous_rubrique, $largeur);

	echo "<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>";
	echo "<div style='text-align: $spip_lang_left; width: ".$largeur."px; position: relative; z-index: 2000;'>";

	// Icones secondaires

	global $browser_name;
	$coeff_decalage = 0;
	if ($browser_name=="MSIE")
		$coeff_decalage = 1.0;
	$decal=0;
	$largitem_moy = 85;
	$largeur_maxi_menu = $largeur-100;

	foreach($GLOBALS['boutons_admin'] as $page => $detail) {
		if (($rubrique == $page) AND ($_COOKIE['spip_accepte_ajax']==-1)) {
			$class = "visible_au_chargement";
		} else {
			$class = "invisible_au_chargement";
		}

		$sousmenu= $detail->sousmenu;
		if($sousmenu) {
			$offset = (int)round($decal-$coeff_decalage*max(0,($decal+count($sousmenu)*$largitem_moy-$largeur_maxi_menu)));
			if ($offset<0){	$offset = 0; }
			echo "<div class='$class' id='bandeau$page' style='position: absolute; $spip_lang_left: ".$offset."px;'><div class='bandeau_sec'><table class='gauche'><tr>\n";
			$width=0;
			foreach($sousmenu as $souspage => $sousdetail) {
				if ($width+1.25*$largitem_moy>$largeur_maxi_menu){echo "</tr><tr>\n";$width=0;}
				if($souspage=='espacement') {
					if ($width>0){
						echo "<td class='separateur'></td>\n";
						$largitem = 0;
					}
				} else {
					$largitem = icone_bandeau_secondaire (_T($sousdetail->libelle), generer_url_ecrire($sousdetail->url?$sousdetail->url:$souspage, $sousdetail->urlArg), $sousdetail->icone, $souspage, $sous_rubrique);
				}
				$width+=$largitem+10;
			}
			echo "</tr></table></div></div>";
		}
		
		$decal += largeur_icone_bandeau_principal(_T($detail->libelle));
	}

	echo "</div>";
	
	echo "</td></tr></table>";
	
	echo "</div>\n"; // referme: <div class='bandeau-principal' align='center'>"

	//
	// Bandeau colore
	//

if (true /*$bandeau_colore*/) {
	if ($rubrique == "administration") {
		$style = "background: url(" . _DIR_IMG_PACK . "rayures-danger.png); background-color: $couleur_foncee";
		echo "<style>a.icone26 { color: white; }</style>";
	}
	else {
		$style = "background-color: $couleur_claire";
	}

	echo "\n<div style=\"max-height: 40px; width: 100%; border-bottom: solid 1px white;$style\">";
	echo "<table align='center' cellpadding='0' style='background: none;' width='$largeur'><tr>";

	echo "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";

	echo "<a href='" . generer_url_ecrire("articles_tous") . "' class='icone26' onmouseover=\"changestyle('bandeautoutsite','visibility','visible');\">",
		  http_img_pack("tout-site.png", "", "width='26' height='20'") . "</a>";
		if ($id_rubrique > 0) echo "<a href='" . generer_url_ecrire("brouteur","id_rubrique=$id_rubrique") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\">" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20'") ."</a>";
		else echo "<a href='" . generer_url_ecrire("brouteur") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\" >" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20'") . "</a>";

		echo "<a href='" . generer_url_ecrire("recherche") . "' class='icone26' onmouseover=\"changestyle('bandeaurecherche','visibility','visible'); findObj('form_recherche').focus();\" >" .
		  http_img_pack("loupe.png", "", "width='26' height='20'") ."</a>";

		echo http_img_pack("rien.gif", " ", "width='10'");

		echo "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='icone26' onmouseover=\"changestyle('bandeauagenda','visibility','visible');\">" .
		  http_img_pack("cal-rv.png", "", "width='26' height='20'") ."</a>";
		echo "<a href='" . generer_url_ecrire("messagerie") . "' class='icone26' onmouseover=\"changestyle('bandeaumessagerie','visibility','visible');\">" .
		  http_img_pack("cal-messagerie.png", "", "width='26' height='20'") ."</a>";
		echo "<a href='" . generer_url_ecrire("synchro") . "' class='icone26' onmouseover=\"changestyle('bandeausynchro','visibility','visible');\">" .
		  http_img_pack("cal-suivi.png", "", "width='26' height='20'") . "</a>";
		

		if (!($connect_toutes_rubriques)) {
			echo http_img_pack("rien.gif", " ", "width='10'");
			echo "<a href='" . generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur&initial=-1") . "' class='icone26' onmouseover=\"changestyle('bandeauinfoperso','visibility','visible');\">" .
			  http_img_pack("fiche-perso.png", "", "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
			echo "</a>";
		}
		
	echo "</td>";
	echo "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";
		// overflow pour masquer les noms tres longs (et eviter debords, notamment en ecran etroit)
		if ($spip_ecran == "large") $largeur_nom = 300;
		else $largeur_nom= 110;
		echo "<div style='width: ".$largeur_nom."px; height: 14px; overflow: hidden;'>";
		// Redacteur connecte
		echo typo($GLOBALS['auteur_session']['nom']);
		echo "</div>";
	
	echo "</td>";

	echo "<td> &nbsp; </td>";


	echo "<td class='bandeau_couleur' style='text-align: $spip_lang_right;' valign='middle'>";

			// Choix display
		//	echo"<img src=_DIR_IMG_PACK . 'rien.gif' width='10' />";
			if ($options != "avancees") {
				$lien = parametre_url(self(), 'set_options', 'avancees');
				$icone = "interface-display-comp.png";
			} else {
				$lien = parametre_url(self(), 'set_options', 'basiques');
				$icone = "interface-display.png";
			}
			echo "<a href='$lien' class='icone26' onmouseover=\"changestyle('bandeaudisplay','visibility', 'visible');\">" .
			  http_img_pack("$icone", "", "width='26' height='20'")."</a>";

			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			echo http_img_pack("choix-layout$spip_lang_rtl".($spip_lang=='he'?'_he':'').".gif", "abc", "class='format_png' style='vertical-align: middle' width='59' height='15' usemap='#map_layout'");


			echo http_img_pack("rien.gif", " ", "width='10' height='1'");
			// grand ecran
			if ($spip_ecran == "large") {
				$i = _T('info_petit_ecran');
				echo "<a href='". parametre_url(self(),'set_ecran', 'etroit') ."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran-etroit.png", $i, "width='26' height='20'") . "</a>";
				$ecran = "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
			}
			else {
				$i = _T('info_grand_ecran');
				echo "<a href='".parametre_url(self(),'set_ecran', 'large')."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran.png", $i, "width='26' height='20'") ."</a>";
				$ecran = "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
			}

		echo "</td>";
		
		echo "<td class='bandeau_couleur' style='width: 60px; text-align:$spip_lang_left;' valign='middle'>";
		choix_couleur();
		
		echo "</td>";
	//
	// choix de la langue
	//
	if ($GLOBALS['all_langs']) {
		echo "<td class='bandeau_couleur' style='width: 100px; text-align: $spip_lang_right;' valign='middle'>";
		echo menu_langues('var_lang_ecrire');
		echo "</td>";
	}

		echo "<td class='bandeau_couleur' style='text-align: $spip_lang_right; width: 28px;' valign='middle'>";

		if ($auth_can_disconnect) {
			echo "<a href='",
			  generer_url_action("logout","logout=prive"),
			  "' class='icone26' onmouseover=\"changestyle('bandeaudeconnecter','visibility', 'visible');\">",
			  http_img_pack("deconnecter-24.gif", "", ""),
			  "</a>";
			}
		echo "</td>";
	
	
	echo "</tr></table>";

} // fin bandeau colore

	// <div> pour la barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)

	echo bandeau_gadgets($largeur, $options, $id_rubrique);
	echo "</div>";
	echo "</div>";

	if ($options != "avancees") echo "<div style='height: 18px;'>&nbsp;</div>";
}


// http://doc.spip.org/@avertissement_messagerie
function avertissement_messagerie() {
	global $couleur_foncee;
	global $connect_id_auteur;

	$result_messages = spip_query("SELECT lien.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
	$total_messages = @spip_num_rows($result_messages);
	if ($total_messages == 1) {
		$row = @spip_fetch_array($result_messages);
		$ze_message=$row['id_message'];
		return "<div class='messages'><a href='" . generer_url_ecrire("message","id_message=$ze_message") . "'><font color='$couleur_foncee'>"._T('info_nouveau_message')."</font></a></div>";
	} elseif ($total_messages > 1)
		return "<div class='messages'><a href='" . generer_url_ecrire("messagerie") . "'><font color='$couleur_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a></div>";
	else return '';
}


// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes()
{	
	global $connect_id_auteur;
	$res = '';
	$result_auteurs = spip_query("SELECT id_auteur FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND statut IN ('0minirezo','1comite')");

	if (spip_num_rows($result_auteurs)) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res = "<b>"._T('info_en_ligne'). "&nbsp;</b>";
		while ($row = spip_fetch_array($result_auteurs)) {
			list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);
			$res .= "$mail&nbsp;$nom, ";
		}
		$res = substr($res,0,-2);
	}

	return "<div class='messages' style='color: #666666;'>$res</div>";
}


// http://doc.spip.org/@gros_titre
function gros_titre($titre, $ze_logo='', $aff=true){
	global $couleur_foncee, $spip_display;
	if ($spip_display == 4) {
		$res = "\n<h1>".typo($titre)."</h1>&nbsp;\n";
	}
	else {
		$res = "<div class='verdana2' style='font-size: 18px; color: $couleur_foncee; font-weight: bold;'>" .
		  (strlen($ze_logo) <= 3 ? '':  (http_img_pack($ze_logo, "", "align='middle'") . " &nbsp; ")) .
		  typo($titre) .
		  "</div>\n";
	}
	if ($aff) echo $res; else return $res;
}


//
// Cadre centre (haut de page)
//

// http://doc.spip.org/@debut_grand_cadre
function debut_grand_cadre($return=false){
	global $spip_ecran;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	$res =  "\n<br /><br /><table width='$largeur' cellpadding='0' cellspacing='0' border='0'>\n<tr><td width='$largeur' class='serif'>";
	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@fin_grand_cadre
function fin_grand_cadre($return=false){
	$res = "\n</td></tr></table>";
	if ($return) return $res; else echo $res;
}

// Cadre formulaires

// http://doc.spip.org/@debut_cadre_formulaire
function debut_cadre_formulaire($style='', $return=false){
	$x = "\n<div class='cadre-formulaire'" .
	  (!$style ? "" : " style='$style'") .
	   ">";
	if ($return) return  $x; else echo $x;
}

// http://doc.spip.org/@fin_cadre_formulaire
function fin_cadre_formulaire($return=false){
	if ($return) return  "</div>\n"; else echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

// http://doc.spip.org/@debut_gauche
function debut_gauche($rubrique = "accueil", $return=false) {
	global $connect_statut;
	global $options, $spip_display;
	global $connect_id_auteur;
	global $spip_ecran;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl;

	$flag_3_colonnes = false;
	$largeur = 200;

	// Ecran panoramique ?
	if ($spip_ecran == "large") {
		$largeur_ecran = 974;
		
		// Si edition de texte, formulaires larges
		if (ereg('((articles|breves|rubriques)_edit|forum_envoi)', $GLOBALS['REQUEST_URI'])) {
			$flag_centre_large = true;
		}
		
		$flag_3_colonnes = true;
		$rspan = " rowspan='2'";

	}
	else {
		$largeur_ecran = 750;
		$rspan = '';
	}

	$res = "<br /><table width='$largeur_ecran' cellpadding='0' cellspacing='0' border='0'>
		<tr>\n<td width='$largeur' class='colonne_etroite serif' valign='top' $rspan>
		<div style='width: ${largeur}px; overflow:hidden;'>
\n";
		
	if ($spip_display == 4) $res .= "<!-- ";

	if ($return) return $res; else echo $res;
}


//
// Presentation de l''interface privee, marge de droite
//

// http://doc.spip.org/@creer_colonne_droite
function creer_colonne_droite($rubrique="", $return= false){
	static $deja_colonne_droite;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl, $spip_lang_left;

	if (!$flag_3_colonnes OR $deja_colonne_droite) return '';
	$deja_colonne_droite = true;

	if ($flag_centre_large) {
			$espacement = 17;
			$largeur = 140;
	} else {
			$espacement = 37;
			$largeur = 200;
	}

	$res = "\n<td width='"
	.  $espacement
	.  "' rowspan='2' class='colonne_etroite'>&nbsp;</td>"
	. "\n<td rowspan='1' class='colonne_etroite'></td>"
	. "\n<td width='"
	.  $espacement
	.  "'rowspan='2' class='colonne_etroite'>&nbsp;</td>"
	. "\n<td width='"
	. $largeur 
	. "' rowspan='2' align='"
	. $spip_lang_left
	. "' valign='top' class='colonne_etroite'><p />";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@debut_droite
function debut_droite($rubrique="", $return= false) {
	global $options, $spip_ecran, $spip_display;
	global $spip_lang_left, $couleur_foncee, $couleur_claire;
	global $flag_3_colonnes, $flag_centre_large;

	$res = '';

	if ($spip_display == 4) $res .= " -->";

	$res .= "</div>\n"; # largeur fixe, cf. debut_gauche

	if ($options == "avancees") {

		$res .= liste_articles_bloques();
	}

	$res .= "<div>&nbsp;</div></td>";

	if (!$flag_3_colonnes) {
		$res .= "<td width='50'>&nbsp;</td>";
	}
	else {
		$res .= creer_colonne_droite($rubrique, true)
		. "</td></tr><tr>";
	}

	if ($spip_ecran == 'large' AND $flag_centre_large)
		$largeur = 600;
	else
		$largeur = 500;

	$res .= '<td width="'.$largeur.'" valign="top" align="'.$spip_lang_left.'" rowspan="1" class="serif">';

	// touche d'acces rapide au debut du contenu
	$res .= "\n<a name='saut' href='#saut' accesskey='s'></a>\n";

	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@liste_articles_bloques
function liste_articles_bloques()
{
	global $connect_id_auteur, $couleur_foncee;

	$res = '';
	if ($GLOBALS['meta']["articles_modif"] != "non") {
		include_spip('inc/drapeau_edition');
		$articles_ouverts = liste_drapeau_edition ($connect_id_auteur, 'article');
		if (count($articles_ouverts)) {
			$res .= "<div>&nbsp;</div>"
			. "<div class='bandeau_rubriques' style='z-index: 1;'>"
			. bandeau_titre_boite2('<b>' . _T('info_cours_edition')  . '</b>', "article-24.gif", $couleur_foncee, 'white', false)
			. "<div class='plan-articles-bloques'>";

			foreach ($articles_ouverts as $row) {
				$ze_article = $row['id_article'];
				$ze_titre = $row['titre'];
				$statut = $row["statut"];
					
				$res .= "<div class='$statut'><a style='font-size: 10px;' href='" 
				. generer_url_ecrire("articles","id_article=$ze_article")
				. "'>$ze_titre</a>"
				. "<div style='text-align:right; font-size: 9px;'>"
				. debloquer_article($ze_article,_T('lien_liberer'))
				. "</div>"
				. "</div>";
			}
			$res .= "</div></div>";
		}
	}
	return $res;
}
	
//
// Fin de page de l'interface privee. 
// Elle comporte une image invisble declenchant une tache de fond

// http://doc.spip.org/@fin_page
function fin_page() {
	global $spip_display;

	return "</td></tr></table>"
	. debut_grand_cadre(true)

	. (($spip_display == 4)
		? ("<div><a href='./?set_disp=2'>"
		.  _T("access_interface_graphique")
		. "</a></div>")
		: ('<div style="text-align: right; font-family: Verdana; font-size: 8pt">'
		. info_copyright()
		. "<br />"
		. _T('info_copyright_doc')
		. '</div>'))

	. fin_grand_cadre(true)
	. "</center>"
	. $GLOBALS['rejoue_session']
	. generer_spip_cron()
	. (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '')
	. "</body></html>\n";
}

// http://doc.spip.org/@debloquer_article
function debloquer_article($arg, $texte) {
	$lien = parametre_url(self(), 'debloquer_article', $arg, '&');
	return "<a href='" .
	  generer_action_auteur('instituer_collaboration',$arg, _DIR_RESTREINT_ABS . $lien) .
	  "' title=\"" .
	  entites_html($texte) .
	  "\">$texte&nbsp;" .
	  http_img_pack("croix-rouge.gif", ($arg=='tous' ? "" : "X"),
			"width='7' height='7' align='baseline'") .
	  "</a>";
}

// http://doc.spip.org/@meme_rubrique
function meme_rubrique($id_rubrique, $id, $type, $order='date', $limit=30)
{
	global $spip_lang_right, $spip_lang_left, $options;

	if ($options != "avancees") return '';

	$table = $type . 's';
	$key = 'id_' . $type;

	$voss = spip_query("SELECT $key AS id, titre, statut FROM spip_$table WHERE id_rubrique=$id_rubrique AND (statut = 'publie' OR statut = 'prop') AND ($key != $id) ORDER BY $order DESC LIMIT $limit");

	if (!spip_num_rows($voss)) return '';

	$numero = _T('info_numero_abbreviation');
	$style = "float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;";
	$retour = '';

	while($row = spip_fetch_array($voss)) {
		$ze = $row['id'];
		$retour .= "<a class='"
		. $row['statut']
		. "' style='font-size: 10px;' href='"
		  . generer_url_ecrire($table,"$key=$ze")
		. "'>"
		. "<span class='arial1' style='$style'><b>$numero$ze</b></span>"
		. typo($row['titre'])
		. "</a>";
	}

	return "<div>&nbsp;</div>"
	. "<div class='bandeau_rubriques' style='z-index: 1;'>"
	. bandeau_titre_boite2('<b>' . _T('info_meme_rubrique')  . '</b>', "article-24.gif",'','',false)
	. "<div class='plan-articles'>"
	. $retour
	. "</div></div>";
}

//
// Afficher la hierarchie des rubriques
//

// http://doc.spip.org/@afficher_hierarchie
function afficher_hierarchie($id_rubrique) {
	global $spip_lang_left;

	$parents = '';
	$style1 = "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px";
	$style2 = "margin-$spip_lang_left: 15px;";

	while ($id_rubrique) {

		$res = spip_fetch_array(spip_query("SELECT id_parent, titre, lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

		if (!$res) break; // rubrique inexistante

		$id_parent = $res['id_parent'];
		changer_typo($res['lang']);

		$logo = (!$id_parent) ? "secteur-12.gif"
		: (acces_restreint_rubrique($id_rubrique)
		? "admin-12.gif" : "rubrique-12.gif");

		$parents = "<div class='verdana3' "
		. http_style_background($logo, $style1)
		. "><a href='"
		. generer_url_ecrire("naviguer","id_rubrique=$id_rubrique")
		. "'>"
		. typo(sinon($res['titre'], _T('ecrire:info_sans_titre')))
		. "</a></div>\n<div style='$style2'>"
		. $parents
		. "</div>";

		$id_rubrique = $id_parent;
	}

	return "<div class='verdana3' " .
		  http_style_background("racine-site-12.gif", $style1). 
		  "><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'><b>"._T('lien_racine_site')."</b></a>".aide ("rubhier")."</div>\n<div style='$style2'>".$parents."</div>";
}

// Pour construire des menu avec SELECTED
// http://doc.spip.org/@mySel
function mySel($varaut,$variable, $option = NULL) {
	$res = ' value="'.$varaut.'"' . (($variable==$varaut) ? ' selected="selected"' : '');

	return  (!isset($option) ? $res : "<option$res>$option</option>\n");
}


// Voir en ligne, ou apercu, ou rien (renvoie tout le bloc)
// http://doc.spip.org/@voir_en_ligne
function voir_en_ligne ($type, $id, $statut=false, $image='racine-24.gif', $echo = true) {
	global $connect_statut;

	$en_ligne = $message = '';
	switch ($type) {
		case 'article':
			if ($statut == "publie" AND $GLOBALS['meta']["post_dates"] == 'non') {
				$n = spip_fetch_array(spip_query("SELECT id_article FROM spip_articles WHERE id_article=$id AND date<=NOW()"));
				if (!$n) $statut = 'prop';
			}
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
		case 'rubrique':
			if ($id > 0)
				if ($statut == 'publie')
					$en_ligne = 'calcul';
				else
					$en_ligne = 'preview';
			break;
		case 'breve':
		case 'auteur':
		case 'site':
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
		case 'mot':
			$en_ligne = 'calcul';
			break;
	}

	if ($en_ligne == 'calcul')
		$message = _T('icone_voir_en_ligne');
	else if ($en_ligne == 'preview') {
		// est-ce autorise ?
		if (($GLOBALS['meta']['preview'] == 'oui' AND $connect_statut=='0minirezo')
			OR ($GLOBALS['meta']['preview'] == '1comite'))
			$message = _T('previsualiser');
		else
			$message = '';
	}

	if ($message)
	  return icone_horizontale($message, generer_url_action('redirect', "id_$type=$id&var_mode=$en_ligne"), $image, "rien.gif", $echo);

}

//
// Creer un bouton qui renvoie vers la bonne url spip_rss
// http://doc.spip.org/@bouton_spip_rss
function bouton_spip_rss($op, $args, $fmt='rss') {

	include_spip('inc/acces');
	$a = '';
	if (is_array($args))
		foreach ($args as $val => $var)
			if ($var) $a .= ':' . $val.'-'.$var;
	$a = substr($a,1);

	$url = generer_url_action('rss', "op=$op" 
			    . (!$a ? "" : "&args=$a")
			    . ('&id=' . $GLOBALS['connect_id_auteur'])
			    . ('&cle=' . afficher_low_sec($GLOBALS['connect_id_auteur'], "rss $op $a"))
			    . ('&lang=' . $GLOBALS['spip_lang']));

	switch($fmt) {
		case 'ical':
			$url = preg_replace(',^.*?://,', 'webcal://', $url)
			  . "&amp;fmt=ical";
			$button = 'iCal';
			break;
		case 'atom':
			$button = 'atom';
			break;
		case 'rss':
		default:
		  
			$button = 'RSS';
			break;
	}

	return "<a href='"
	. $url
	. "'>"
	. http_img_pack('feed.png', $button, 'RSS', 'RSS')
	. "</a>";
}


// Fonction pour la messagerie et la page d'accueil

// http://doc.spip.org/@http_calendrier_rv
function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$total = '';
	if (!$messages) return $total;
	foreach ($messages as $row) {
		if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
			$url = quote_amp($match[1]);
		else
			$url = generer_url_ecrire("message", "id_message=".$row['id_message']);

		$rv = ($row['rv'] == 'oui');
		$date = $row['date_heure'];
		$date_fin = $row['date_fin'];
		if ($row['type']=="pb") $bouton = "pense-bete";
		else if ($row['type']=="affich") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div  class='calendrier-arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
			$date_rv = $date_jour;
			$rv =
		((affdate($date) == affdate($date_fin)) ?
		 ("<div class='calendrier-arial9 fond-agenda'>"
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div class='calendrier-arial9 fond-agenda' style='text-align: center;'>"
		  . heures($date).":".minutes($date)."<br />...</div>" ));
		}

		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		  http_href($url,
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;'")) : 
				      http_img_pack($bouton.".gif", $bouton, "")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		$rv .
		"<div><b>" .
		  http_href($url, typo($row['titre']), '', '', 'calendrier-verdana10') .
		"</b></div>" .
		"</td>" .
		"</tr>\n";
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "<table width='100%' border='0' cellpadding='0' cellspacing='2'>" .
	  $total .
	  "</table>" .
	  fin_cadre_enfonce(true);
}

?>
