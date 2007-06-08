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

function inc_puce_statut_dist($id_objet, $statut, $id_rubrique, $type)
{
  // le function_exists n'est utile qu'au greffons 
	if (function_exists($f = "puce_statut_$type"))
		return $f($id_objet, $statut, $id_rubrique, $type);
	else return "<img src='"._DIR_IMG_PACK.	"$type-24.gif" . "' />";
}


function puce_statut_auteur($id, $statut, $id_rubrique, $type) {
  
	return bonhomme_statut(array('statut' => $statut));
}

// http://doc.spip.org/@bonhomme_statut
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "nouveau":
			return '';
			break;
		case "0minirezo":
			return http_img_pack("admin-12.gif", _T('titre_image_administrateur'), "",
					_T('titre_image_administrateur'));
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
			  return http_img_pack("visit-12.gif",_T('titre_image_redacteur'), "", _T('titre_image_redacteur'));
			else
			  return http_img_pack("redac-12.gif",_T('titre_image_redacteur'), "", _T('titre_image_redacteur_02'));
			break;
		case "5poubelle":
			return http_img_pack("poubelle.gif", _T('titre_image_auteur_supprime'), "",_T('titre_image_auteur_supprime'));
			break;
		default:
		  return http_img_pack("visit-12.gif", _T('titre_image_visiteur'), "",_T('titre_image_visiteur'));
			break;
	}
}


function puce_statut_mot($id, $statut, $id_rubrique, $type) {

	return "<img src='"._DIR_IMG_PACK. 'petite-cle.gif' . "' />";
}

function puce_statut_rubrique($id, $statut, $id_rubrique, $type) {

	return "<img src='"._DIR_IMG_PACK. 'rubrique-12.gif' . "' />";
}

// http://doc.spip.org/@puce_statut_article
function puce_statut_article($id, $statut, $id_rubrique, $type='article', $ajax = false) {
	global $lang_objet;
	
	$lang_dir = lang_dir($lang_objet);
	if (!$id) {
	  $id = $id_rubrique;
	  $ajax_node ='';
	} else	$ajax_node = " id='imgstatut$type$id'";

	switch ($statut) {
	case 'publie':
		$clip = 2;
		$puce = 'puce-verte.gif';
		$title = _T('info_article_publie');
		break;
	case 'prepa':
		$clip = 0;
		$puce = 'puce-blanche.gif';
		$title = _T('info_article_redaction');
		break;
	case 'prop':
		$clip = 1;
		$puce = 'puce-orange.gif';
		$title = _T('info_article_propose');
		break;
	case 'refuse':
		$clip = 3;
		$puce = 'puce-rouge.gif';
		$title = _T('info_article_refuse');
		break;
	case 'poubelle':
		$clip = 4;
		$puce = 'puce-poubelle.gif';
		$title = _T('info_article_supprime');
		break;
	}

	$inser_puce = http_img_pack($puce, $title, " style='margin: 1px;'$ajax_node");

	if (!autoriser('publierdans', 'rubrique', $id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$titles = array(
			  "blanche" => _T('texte_statut_en_cours_redaction'),
			  "orange" => _T('texte_statut_propose_evaluation'),
			  "verte" => _T('texte_statut_publie'),
			  "rouge" => _T('texte_statut_refuse'),
			  "poubelle" => _T('texte_statut_poubelle'));
	if ($ajax){
		$action = "\nonmouseover=\"montrer('statutdecal$type$id');\"";
		return 	"<span class='puce_article_fixe'\n$action>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_article_popup' id='statutdecal$type$id'\nonmouseout=\"cacher('statutdecal$type$id');\" style='margin-left: -".((11*$clip)+1)."px;'>"
		  . afficher_script_statut($id, $type, -1, 'puce-blanche.gif', 'prepa', $titles['blanche'], $action)
		  . afficher_script_statut($id, $type, -12, 'puce-orange.gif', 'prop', $titles['orange'], $action)
		  . afficher_script_statut($id, $type, -23, 'puce-verte.gif', 'publie', $titles['verte'], $action)
		  . afficher_script_statut($id, $type, -34, 'puce-rouge.gif', 'refuse', $titles['rouge'], $action)
		  . afficher_script_statut($id, $type, -45, 'puce-poubelle.gif', 'poubelle', $titles['poubelle'], $action)
		  . "</span>";
	}

	$nom = "puce_statut_";

	if ((! _SPIP_AJAX) AND $type != 'article') 
	  $over ='';
	else {

	  $action = generer_url_ecrire('puce_statut_article',"",true);
	  $action = "if (!this.puce_loaded) { this.puce_loaded = true; prepare_selec_statut('$nom', '$type', $id, '$action'); }";
	  $over = "\nonmouseover=\"$action\"";
	}

	return 	"<span class='puce_article' id='$nom$type$id' dir='$lang_dir'$over>"
	. $inser_puce
	. '</span>';
}

// http://doc.spip.org/@puce_statut_breve
function puce_statut_breve($id, $statut, $id_rubrique, $type) {
	global $lang_objet;

	$lang_dir = lang_dir($lang_objet);
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
	$inser_puce = http_img_pack($puce, $title, "id='img$type1' style='margin: 1px;'");

	if (!autoriser('publierdans','rubrique',$id_rubrique)
	OR !_ACTIVER_PUCE_RAPIDE)
		return $inser_puce;

	$type2 = "statutdecal$type$id";
	$action = "\nonmouseover=\"montrer('$type2');\"";

	return	"<span class='puce_breve' id='$type1' dir='$lang_dir'>"
		. "<span class='puce_breve_fixe' $action>"
		. $inser_puce
		. "</span>"
		. "<span class='puce_breve_popup' id='$type2'\nonmouseout=\"cacher('$type2');\" style='margin-left: -".((9*$clip)+1)."px;'>\n"
		. afficher_script_statut($id, $type, -1, $puces[0], 'prop',_T('texte_statut_propose_evaluation'), $action)
		. afficher_script_statut($id, $type, -10, $puces[1], 'publie',_T('texte_statut_publie'), $action)
	  	. afficher_script_statut($id, $type, -19, $puces[2], 'refuse',_T('texte_statut_refuse'), $action)
		.  "</span></span>";
}

// http://doc.spip.org/@puce_statut_site
function puce_statut_site($id_site, $statut, $id_rubrique, $type){
	switch ($statut) {
		case 'publie':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte-breve.gif';
				$title = _T('info_site_reference');
				break;
			case 'prop':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-orange-anim.gif';
				else
					$puce='puce-orange-breve.gif';
				$title = _T('info_site_attente');
				break;
			case 'refuse':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle-breve.gif';
				$title = _T('info_site_refuse');
				break;
	}
	return http_img_pack($puce, $statut, "class='puce'",$title);
}

// http://doc.spip.org/@puce_statut_syndic_article
function puce_statut_syndic_article($id_syndic, $statut, $id_rubrique, $type){
		if ($statut=='publie') {
				$puce='puce-verte.gif';
		}
		else if ($statut == "refuse") {
				$puce = 'puce-poubelle.gif';
		}
	
		else if ($statut == "dispo") { // moderation : a valider
				$puce = 'puce-rouge.gif';
		}
	
		else if ($statut == "off") { // feed d'un site en mode "miroir"
				$puce = 'puce-rouge-anim.gif';
		}
	
		return http_img_pack($puce, $statut, "class='puce'");
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

// http://doc.spip.org/@afficher_script_statut
function afficher_script_statut($id, $type, $n, $img, $statut, $titre, $act)
{
  $i = http_wrapper($img);
  $h = generer_action_auteur("instituer_$type","$id-$statut");
  $h = "javascript:selec_statut('$id', '$type', $n, '$i', '$h');";
  $t = supprimer_tags($titre);
  return "<a href=\"$h\"\ntitle=\"$t\"$act><img src='$i' alt=' '/></a>";
}

?>
