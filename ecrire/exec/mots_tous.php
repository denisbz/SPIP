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

include_spip('inc/presentation');
include_spip('inc/actions');

// http://doc.spip.org/@exec_mots_tous_dist
function exec_mots_tous_dist()
{
	global $conf_mot, $spip_lang, $spip_lang_right, $son_groupe;

	$conf_mot = intval($conf_mot);

	pipeline('exec_init',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_mots_tous'), "naviguer", "mots");
	debut_gauche();


	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_tous'),'data'=>''));

	if (autoriser('creer','groupemots')  AND !$conf_mot){
		$res = icone_horizontale(_T('icone_creation_groupe_mots'), generer_url_ecrire("mots_type","new=oui"), "groupe-mot-24.gif", "creer.gif",false);

		echo bloc_des_raccourcis($res);
	}


	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
	debut_droite();

	gros_titre(_T('titre_mots_tous'));
	if (autoriser('creer','groupemots')) {
	  echo typo(_T('info_creation_mots_cles')) . aide ("mots") ;
	}
	echo "<br /><br />";

//
// On boucle d'abord sur les groupes de mots
//

	$result_groupes = spip_query($q="SELECT *, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_groupes_mots ORDER BY multi");

	while ($row_groupes = spip_fetch_array($result_groupes)) {
		$id_groupe = $row_groupes['id_groupe'];
		$titre_groupe = typo($row_groupes['titre']);
		$descriptif = $row_groupes['descriptif'];
		$texte = $row_groupes['texte'];
		$unseul = $row_groupes['unseul'];
		$obligatoire = $row_groupes['obligatoire'];
		$articles = $row_groupes['articles'];
		$breves = $row_groupes['breves'];
		$rubriques = $row_groupes['rubriques'];
		$syndic = $row_groupes['syndic'];
		$acces_minirezo = $row_groupes['minirezo'];
		$acces_comite = $row_groupes['comite'];
		$acces_forum = $row_groupes['forum'];

		// Afficher le titre du groupe
		echo "<a id='mots_tous-$id_groupe'></a>";

		debut_cadre_enfonce("groupe-mot-24.gif", false, '', $titre_groupe);
		// Affichage des options du groupe (types d'elements, permissions...)
		$res = '';
		if ($articles == "oui") $res .= "> "._T('info_articles_2')." &nbsp;&nbsp;";
		if ($breves == "oui") $res .= "> "._T('info_breves_02')." &nbsp;&nbsp;";
		if ($rubriques == "oui") $res .= "> "._T('info_rubriques')." &nbsp;&nbsp;";
		if ($syndic == "oui") $res .= "> "._T('icone_sites_references')." &nbsp;&nbsp;";

		if ($unseul == "oui" OR $obligatoire == "oui") $res .= "<br />";
		if ($unseul == "oui") $res .= "> "._T('info_un_mot')." &nbsp;&nbsp;";
		if ($obligatoire == "oui") $res .= "> "._T('info_groupe_important')." &nbsp;&nbsp;";

		$res .= "<br />";
		if ($acces_minirezo == "oui") $res .= "> "._T('info_administrateurs')." &nbsp;&nbsp;";
		if ($acces_comite == "oui") $res .= "> "._T('info_redacteurs')." &nbsp;&nbsp;";
		if ($acces_forum == "oui") $res .= "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

 		echo "<span class='verdana1 spip_x-small'>", $res, "</span>";
		if ($descriptif) {
			echo "<div style='border: 1px dashed #aaaaaa; ' class='verdana1 spip_small'>", "<b>",_T('info_descriptif'),"</b> ", propre($descriptif), "&nbsp; </div>";
		}

		if (strlen($texte)>0){
			echo "<span class='verdana1 spip_small'>", propre($texte), "</span>";
		}

		//
		// Afficher les mots-cles du groupe
		//

		$groupe = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots WHERE id_groupe=$id_groupe"));
		$groupe = $groupe['n'];

		echo "<div\nid='editer_mot-$id_groupe' style='position: relative;'>";

		// Preliminaire: confirmation de suppression d'un mot lie � qqch
		// (cf fin de afficher_groupe_mots_boucle executee a l'appel precedent)
		if ($conf_mot  AND $son_groupe==$id_groupe) {
			include_spip('inc/grouper_mots');
			echo confirmer_mot($conf_mot, $id_groupe, $groupe);
		}
		if ($groupe) {
		  	$grouper_mots = charger_fonction('grouper_mots', 'inc');
			echo $grouper_mots($id_groupe, $groupe);
		}

		echo "</div>";

		if (autoriser('modifier','groupemots',$id_groupe)){
			echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
			echo "<tr>";
			echo "<td>";
			icone(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif");
			echo "</td>";
			echo "\n<td id='editer_mot-$id_groupe-supprimer'",
			  (!$groupe ? '' : " style='visibility: hidden'"),
			  ">";
			icone(_T('icone_supprimer_groupe_mots'), redirige_action_auteur('instituer_groupe_mots', "-$id_groupe", "mots_tous"), "groupe-mot-24.gif", "supprimer.gif");
			echo "</td>";
			echo "<td>";
			echo "<div align='$spip_lang_right'>";
			icone(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit","new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous', "#mots_tous-$id_groupe")), "mot-cle-24.gif", "creer.gif");
			echo "</div>";
			echo "</td></tr></table>";
		}	

		fin_cadre_enfonce();
	}

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_tous'),'data'=>''));


	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@confirmer_mot
function confirmer_mot ($conf_mot, $son_groupe, $total)
{
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_mots WHERE id_mot=$conf_mot"));
	if (!$row) return ""; // deja detruit (acces concurrent etc)

	$id_mot = $row['id_mot'];
	$titre_mot = typo($row['titre']);
	$type_mot = typo($row['type']);

	if (($na = intval(_request('na'))) == 1) {
		$texte_lie = _T('info_un_article')." ";
	} else if ($na > 1) {
		$texte_lie = _T('info_nombre_articles', array('nb_articles' => $na)) ." ";
	}
	if (($nb = intval(_request('nb'))) == 1) {
		$texte_lie .= _T('info_une_breve')." ";
	} else if ($nb > 1) {
		$texte_lie .= _T('info_nombre_breves', array('nb_breves' => $nb))." ";
	}
	if (($ns = intval(_request('ns'))) == 1) {
		$texte_lie .= _T('info_un_site')." ";
	} else if ($ns > 1) {
		$texte_lie .= _T('info_nombre_sites', array('nb_sites' => $ns))." ";
	}
	if (($nr = intval(_request('nr'))) == 1) {
		$texte_lie .= _T('info_une_rubrique')." ";
	} else if ($nr > 1) {
		$texte_lie .= _T('info_nombre_rubriques', array('nb_rubriques' => $nr))." ";
	}

	return debut_boite_info(true)
	. "<div class='serif'>"
	. _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie))
	. "<p style='text-align: right'>"
	. generer_supprimer_mot($id_mot, $son_groupe, ("<b>" . _T('item_oui') . "</b>"), $total)
	. "<br />\n"
	.  _T('info_oui_suppression_mot_cle')
	. '</p>'
	  /* troublant. A refaire avec une visibility
	 . "<li><b><a href='" 
	. generer_url_ecrire("mots_tous")
	. "#editer_mot-$son_groupe"
	. "'>"
	. _T('item_non')
	. "</a>,</b> "
	. _T('info_non_suppression_mot_cle')
	. "</ul>" */
	. "</div>"
	. fin_boite_info(true);
}
?>
