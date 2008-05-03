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
include_spip('inc/presentation');

// http://doc.spip.org/@exec_sites_edit_dist
function exec_sites_edit_dist()
{
	global $connect_statut, $connect_id_rubrique, $spip_lang_right;

	$id_syndic = intval(_request('id_syndic'));
	$result = sql_select("*", "spip_syndic", "id_syndic=$id_syndic");

	if ($row = sql_fetch($result)) {
		$id_syndic = $row["id_syndic"];
		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$url_site = $row["url_site"];
		$url_syndic = $row["url_syndic"];
		$descriptif = $row["descriptif"];
		$syndication = $row["syndication"];
		$extra = $row["extra"];
		$new = false;
	} else {
		$id_rubrique = intval(_request('id_rubrique'));
		$syndication = 'non';
		$new = 'oui';
		$descriptif = $nom_site = $url_site = $url_syndic = '';
		if (!$id_rubrique) {
			$in = !$connect_id_rubrique ? ''
			  : (' id_rubrique IN (' . join(',', $connect_id_rubrique) . ')');
			$id_rubrique = sql_getfetsel('id_rubrique', 'spip_rubriques', $in, '',  'id_rubrique DESC',  1);
		}
		if (!autoriser('creersitedans','rubrique',$id_rubrique )){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
			while (!autoriser('creersitedans','rubrique',$id_rubrique ) && $t = sql_fetch($res)){
				$id_rubrique = $t['id_rubrique'];
			}
		}
	}

	if ( ($new!='oui' AND (!autoriser('voir','site',$id_syndic) OR !autoriser('modifier','site',$id_syndic)))
	  OR ($new=='oui' AND !autoriser('creersitedans','rubrique',$id_rubrique)) ){
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$commencer_page = charger_fonction('commencer_page', 'inc');
	pipeline('exec_init',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

	echo $commencer_page(_T('info_site_reference_2'), "naviguer", "sites", $id_rubrique);

	echo debut_grand_cadre(true);

	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);

	echo debut_gauche('', true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));	  
	echo debut_droite('', true);
	echo debut_cadre_formulaire("", true);

	$contexte = array(
	'icone_retour'=>$new=='oui'?'':icone_inline(_T('icone_retour'), generer_url_ecrire("sites","id_syndic=$id_syndic"), "site-24.gif", "rien.gif",$GLOBALS['spip_lang_right']),
	'redirect'=>generer_url_ecrire("sites"),
	'titre'=>$nom_site,
	'new'=>$new == "oui"?$new:$id_syndic,
	'id_rubrique'=>$id_rubrique,
	'config_fonc'=>'sites_edit_config'
	);
	$page = evaluer_fond("prive/editer/site", $contexte, $connect);
	echo $page['texte'];

/*
	if ($new != 'oui') {
		echo icone_inline(_T('icone_retour'), generer_url_ecrire("sites","id_syndic=$id_syndic"), 'site-24.gif', "rien.gif", $spip_lang_right);
	}
	echo _T('titre_referencer_site');
	echo gros_titre($nom_site,'',false);
	echo "<div class='nettoyeur'></div>";

	$form = '';

	$url_syndic = entites_html($url_syndic);
	$nom_site = entites_html($nom_site);
	$url_site = entites_html($url_site);

	// url vide => proposer 'http://' a titre d'aide
	if (strlen($url_site) == 0) $url_site="http://";

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	else {
		$t = sql_fetsel('id_parent', 'spip_rubriques', "id_rubrique=$id_rubrique");
		$parent_parent=$t['id_parent'];
		if ($parent_parent == 0) $logo = "secteur-24.gif";
		else $logo = "rubrique-24.gif";
	}

	// selecteur de rubriques
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form .= "<label for='nom_site'>" . _T('info_nom_site_2') . "</label>"
	. "<br />\n<input type='text' class='formo' name='nom_site' id='nom_site' value=\""
	. $nom_site
	. "\" size='40' />\n<br />"
	. "<label for='url_site'>"
	. _T('entree_adresse_site')
	. "</label>"
	. "<br />\n<input type='text' class='formo' name='url_site' id='url_site' value=\""
	. $url_site
	. "\" size='40' /><br />\n"
	. debut_cadre_couleur($logo, true, "", _T('entree_interieur_rubrique'))
	. $chercher_rubrique($id_rubrique, 'site', false)
	. fin_cadre_couleur(true)
	. "\n<br />"
	."<b>"
	. "<label for='descriptif'>"
	. _T('entree_description_site')
	. "</label></b><br />\n"
	. "<textarea name='descriptif' id='descriptif' rows='8' class='forml' cols='40' >"
	. entites_html($descriptif)
	. "</textarea>"
	. "\n<input type='hidden' name='syndication_old' value=\""
	. $syndication
	. "\" />";

	if ($GLOBALS['meta']["activer_syndic"]!= "non") {
		$form .= debut_cadre_enfonce('feed.png', true);
		if ($syndication == "non") {
			$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non' checked='checked' />";
		} else {
			$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non' />";
		}
		$form .= "\n<b><label for='syndication_non'>"
		. _T('bouton_radio_non_syndication')
		. "</label></b><br />\n";

		if ($syndication == "non") {
			$form .= "<input type='radio' name='syndication' value='oui' id='syndication_oui' />";
		} else {
			$form .= "<input type='radio' name='syndication' value='oui' id='syndication_oui' checked='checked' />";
		}
		$form .= "\n<b><label for='syndication_oui'>"
		. _T('bouton_radio_syndication')
		. "</label></b>"
		. aide("rubsyn")
		. "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n<tr><td style='width: 10px;'>&nbsp;</td>\n<td>"
		. "<label for='url_syndic'>"
		. _T('entree_adresse_fichier_syndication')
		. "</label><br />\n";

		if (strlen($url_syndic) < 8) $url_syndic = "http://";

	// cas d'une liste de flux detectee par feedfinder : menu
		if (preg_match(',^select: (.+),', $url_syndic, $regs)) {
			$feeds = explode(' ',$regs[1]);
			$form .= "<select name='url_syndic' id='url_syndic'>\n";
			foreach ($feeds as $feed) {
				$form .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
			}
			$form .= "</select>\n";
		} else {
			$form .= "<input type='text' class='formo' name='url_syndic' id='url_syndic' value=\"$url_syndic\" size='40' />\n";
		}
		$form .= "</td></tr></table>";
		$form .= fin_cadre_enfonce(true);
	}

	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'sites', intval($id_rubrique));
	}

	// Ajouter le controles md5
	if ($row) {
		include_spip('inc/editer');
		$form .= controles_md5($row);
	}


	if ($new == 'oui'
	AND ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0)){
		$form_auto = "<span class='verdana1 spip_small'>"
		. "<label for='url_auto'>"
		. _T('texte_referencement_automatique')
		. "</label></span>"
		. "\n<div style='text-align: right'><input type=\"text\" name=\"url_auto\" id=\"url_auto\" class='fondl' size='40' value=\"http://\" />\n"
		. "<input type=\"submit\" value=\""
		. _T('bouton_ajouter')
		. "\" class='fondo' />\n"
		. '</div>';

		$form = debut_cadre_relief("site-24.gif", true)
		. $form_auto
		. fin_cadre_relief(true)
		. "\n<p><b>"
		. _T('texte_non_fonction_referencement')
		. "</b></p>\n"
		. debut_cadre_enfonce("site-24.gif", true)
		. $form
		. fin_cadre_enfonce(true);

	}

	$form .= "\n<div style='text-align: right'><input type='submit' value='"
	. _T('bouton_enregistrer')
	. "' class='fondo' /></div>";

	echo generer_action_auteur('editer_site',
				      ($new == 'oui') ? $new : $id_syndic,
				      generer_url_ecrire('sites'),
				      $form,
				      " method='post'"
				      );*/

	echo fin_cadre_formulaire(true);
	
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

	echo fin_gauche(), fin_page();
	}
}
?>
