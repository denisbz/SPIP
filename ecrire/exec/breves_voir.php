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

include_spip('inc/presentation');
include_spip('inc/rubriques');
include_spip('inc/mots');
include_spip('inc/actions');
include_spip('inc/date');
include_spip('base/abstract_sql');
include_spip("inc/indexation");

// http://doc.spip.org/@afficher_breves_voir
function afficher_breves_voir($id_breve, $changer_lang, $cherche_mot, $select_groupe)
{
	global $champs_extra, $options, $connect_statut, $les_notes,$spip_display;
	$result = spip_query("SELECT * FROM spip_breves WHERE id_breve='$id_breve'");


	if ($row = spip_fetch_array($result)) {
		$id_breve=$row['id_breve'];
		$date_heure=$row['date_heure'];
		$titre_breve=$row['titre'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$extra=$row['extra'];
		$lien_titre=$row['lien_titre'];
		$lien_url=$row['lien_url'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
	}

	$flag_editable = (($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique)) OR $statut == 'prop');

	// Est-ce que quelqu'un a deja ouvert la breve en edition ?
	if ($flag_editable
	AND $GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		$modif = mention_qui_edite($id_breve, 'breve');
	} else
		$modif = array();


	pipeline('exec_init',
		array(
			'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
			'data'=>''
		)
	);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("&laquo; $titre_breve &raquo;", "naviguer", "breves", $id_rubrique);
	
	debut_grand_cadre();
	
	echo afficher_hierarchie($id_rubrique);
	
	fin_grand_cadre();
	if (!$row) {echo _T('public:aucune_breve'); exit;}
	debut_gauche();
	
	debut_boite_info();
	
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='1'><B>"._T('info_gauche_numero_breve')."&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='6'><B>$id_breve</B></FONT>";
	echo "</CENTER>";
	
	voir_en_ligne ('breve', $id_breve, $statut);
	
	fin_boite_info();


	//////////////////////////////////////////////////////
	// Logos de la breve
	//

	if (($spip_display != 4) AND $id_breve>0 AND ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique))) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_breve', $id_breve, 'breves_voir'); 
	}

	echo bloc_des_raccourcis(icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui&id_rubrique=$id_rubrique"), "breve-24.gif","creer.gif", false));

	
	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	creer_colonne_droite();

	echo pipeline('affiche_droite',
		array(
		'args'=>array('exec'=>'breves_voir','id_breve'=>$id_breve),
		'data'=>''
		)
	);

	echo meme_rubrique($id_rubrique, $id_breve, 'breve', 'date_heure');

	debut_droite();
	
	debut_cadre_relief("breve-24.gif");
	echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0>";
	echo "<TR><td class='serif'>";

	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'><td width='100%' valign='top'>";
	gros_titre($titre);
	echo "</td>";

	if ($flag_editable) {
		echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
		echo "<td  align='right'>";
		icone(
			// TODO -- _L("Fil a travaille sur cette breve il y a x minutes")
			!$modif ? _T('icone_modifier_breve')
				: _T('texte_travail_article', $modif),
			generer_url_ecrire("breves_edit","id_breve=$id_breve&retour=nav"),
			!$modif ? "breve-24.gif" : "warning-24.gif",
			!$modif ? "edit.gif" : ''
		);
		echo "</td>";
	}
	echo "</tr></table>\n";

	if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {
	
		if ($statut == 'publie') {	
			echo "<p>";
	
			if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
			        $mois = $regs[2];
			        $jour = $regs[3];
			        $annee = $regs[1];
			}
	
	
			debut_cadre_enfonce();
			echo afficher_formulaire_date("breves_voir", "id_breve=$id_breve&options=$options",
						      _T('texte_date_publication_article'), $jour, $mois, $annee);
			fin_cadre_enfonce();	
		}
		else {
			echo "<br /><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE='3'><B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
		}
	}

	$editer_mot = charger_fonction('editer_mot', 'inc');
	echo $editer_mot('breve', $id_breve, $cherche_mot, $select_groupe, $flag_editable);

	//
	// Langue de la breve
	//
	if (($GLOBALS['meta']['multi_articles'] == 'oui') AND ($flag_editable)) {
		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];
	
		if ($changer_lang) {
			if ($changer_lang != "herit")
				spip_query("UPDATE spip_breves SET lang=" . _q($changer_lang) . ", langue_choisie='oui' WHERE id_breve=$id_breve");
			else
				spip_query("UPDATE spip_breves SET lang=" . _q($langue_parent) . ", langue_choisie='non' WHERE id_breve=$id_breve");
			calculer_langues_utilisees();
		}
	
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_breves WHERE id_breve=$id_breve"));
		$langue_breve = $row['lang'];
		$langue_choisie_breve = $row['langue_choisie'];
	
		if ($langue_choisie_breve == 'oui') $herit = false;
		else $herit = true;
	
		debut_cadre_enfonce('langues-24.gif');
	
		echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' class='serif2'>";
		echo bouton_block_invisible('languesbreve');
		echo "<B>";
		echo _T('titre_langue_breve');
		echo "&nbsp; (".traduire_nom_langue($langue_breve).")";
		echo "</B>";
		echo "</TD></TR></TABLE>";
	
		echo debut_block_invisible('languesbreve');
		echo "<center><font face='Verdana,Arial,Sans,sans-serif' size='2'>";
		echo menu_langues('changer_lang', $langue_breve, '', $langue_parent);
		echo "</font></center>\n";
		echo fin_block();
	
		fin_cadre_enfonce();
	}

	echo justifier(propre($texte))."\n";

	$texte_case = ($lien_titre.vider_url($lien_url)) ? "{{"._T('lien_voir_en_ligne')."}} [".$lien_titre."->".$lien_url."]" : '';
	echo propre($texte_case);

	if ($les_notes) {
		echo "<hr width='70%' height=1 align='left'><font size=2>$les_notes</font>\n";
	}

	// afficher les extra
	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "breves");
	}

	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique) AND ($statut=="prop" OR $statut=="prepa")){
		echo "<div align='right'>";
		
		echo "<table>";
		echo "<td  align='right'>";
		icone(_T('icone_publier_breve'), 
		      redirige_action_auteur('editer_breve',"$id_breve-statut-publie","breves_voir","id_breve=$id_breve"), "breve-24.gif", "racine-24.gif");
		echo "</td>";
		
		echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
		echo "<td  align='right'>";
		icone(_T('icone_refuser_breve'), 
		      redirige_action_auteur('editer_breve', "$id_breve-statut-refuse", "breves_voir","id_breve=$id_breve"), "breve-24.gif", "supprimer.gif");
		echo "</td>";
		echo "</table>";	
		
		echo "</div>";
	}	

	echo "</TD></TR></TABLE>";

	fin_cadre_relief();
	
	//////////////////////////////////////////////////////
	// Forums
	//
	
	echo "<br /><br />";
	
	echo "\n<div align='center'>";
	icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_breve&script=breves_voir") . '#formulaire',
	     "forum-interne-24.gif", "creer.gif");
	echo "</div>";
	
	echo "<br />";
	
	echo afficher_forum(spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_breve='$id_breve' AND id_parent=0 ORDER BY date_heure DESC LIMIT 20"), "breves_voir", "id_breve=$id_breve");
	
	echo fin_page();
}

// http://doc.spip.org/@exec_breves_voir_dist
function exec_breves_voir_dist()
{
	global $connect_statut;

	$id_breve = intval(_request('id_breve'));

	if ($row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_breves WHERE id_breve=$id_breve")))
		$id_rubrique = $row['id_rubrique'];
	else
		die ('breve inexistante');

	// TODO: passer ce qui reste de l'update dans action/editer_breve.php
	if (_request('jour') AND $connect_statut == '0minirezo') {
		$annee = _request('annee');
		$mois = _request('mois');
		$jour = _request('jour');
		if ($annee == "0000") $mois = "00";
		if ($mois == "00") $jour = "00";
		spip_query("UPDATE spip_breves SET date_heure='$annee-$mois-$jour' WHERE id_breve=$id_breve");
	}

	afficher_breves_voir($id_breve, _request('changer_lang'), _request('cherche_mot'), _request('select_groupe'));
}
?>
