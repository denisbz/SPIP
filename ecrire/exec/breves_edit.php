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
include_spip('inc/documents');
include_spip ('inc/barre');

// http://doc.spip.org/@exec_breves_edit_dist
function exec_breves_edit_dist()
{
	global $connect_id_rubrique;
	$id_breve = intval(_request('id_breve'));
	$id_rubrique  = intval(_request('id_rubrique'));
	$new = _request('new');

	// appel du script a la racine, faut choisir 
	// on prend le dernier secteur cree
	// dans une liste restreinte si admin restreint

	if (!$id_rubrique) {
		$in = !$connect_id_rubrique ? ''
		  : (' AND id_rubrique IN (' . join(',', $connect_id_rubrique) . ')');
		$id_rubrique = sql_getfetsel('id_rubrique','spip_rubriques', "id_parent=0$in",'',  "id_rubrique DESC", 1);		

		if (!autoriser('creerbrevedans','rubrique',$id_rubrique )){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
			while (!autoriser('creerbrevedans','rubrique',$id_rubrique ) && $row_rub = sql_fetch($res)){
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}
	

	$row = false;
	if (!( ($new!='oui' AND (!autoriser('voir','breve',$id_breve) OR !autoriser('modifier','breve', $id_breve)))
	       OR ($new=='oui' AND !autoriser('creerbrevedans','rubrique',$id_rubrique)) )) {
		if ($new != "oui") 
			$row = sql_fetsel("*", "spip_breves", "id_breve=$id_breve");
		else $row = true;
	}
	if (!$row) {
		include_spip('inc/minipres');
		echo minipres();
	} else  breves_edit_ok($row, $id_breve, $id_rubrique, $new);
}

function breves_edit_ok($row, $id_breve, $id_rubrique, $new)
{
	global  $connect_statut, $spip_lang_right;

	if ($new != 'oui') {
		$id_breve=$row['id_breve'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$lien_titre=$row['lien_titre'];
		$lien_url=$row['lien_url'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
		$extra = $row['extra'];
		$onfocus = '';
	} else {
		$titre = filtrer_entites(_T('titre_nouvelle_breve'));
		$texte = "";
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$lien_titre='';
		$lien_url='';
		$statut = "prop";
		$row = sql_fetsel("id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
		$id_rubrique = $row['id_secteur'];
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	pipeline('exec_init',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));

	echo $commencer_page(_T('titre_page_breves_edit', array('titre' => $titre)), "naviguer", "breves", $id_rubrique);


	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_rubrique);

	echo fin_grand_cadre(true);
	echo debut_gauche('', true);
	if ($new != 'oui' AND ($connect_statut=="0minirezo" OR $statut=="prop")) {
	# affichage sur le cote des images, en reperant les inserees
	# note : traiter_modeles($texte, true) repere les doublons
	# aussi efficacement que propre(), mais beaucoup plus rapidement
		traiter_modeles("$titre$texte", true);
		echo afficher_documents_colonne($id_breve, "breve");
	}
echo pipeline('affiche_gauche',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
echo creer_colonne_droite('', true);
echo pipeline('affiche_droite',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
echo debut_droite('', true);
echo debut_cadre_formulaire("", true);


if ($new != "oui") {
	echo icone_inline(_T('icone_retour'), generer_url_ecrire("breves_voir","id_breve=$id_breve"), "breve-24.gif", "rien.gif",$spip_lang_right);
	echo _T('info_modifier_breve');
	echo gros_titre($titre,'', false);
	echo "<br class='nettoyeur' />";
}

if ($connect_statut=="0minirezo" OR $statut=="prop" OR $new == "oui") {
	if ($id_breve) $lien = "id_breve=$id_breve";

	$titre = entites_html($titre);
	$lien_titre = entites_html($lien_titre);

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$result=sql_select("id_parent", "spip_rubriques", "id_rubrique=$id_rubrique");

		while($row=sql_fetch($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	// selecteur de rubrique (en general pas d'ajax car toujours racine)
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form = "<label for='sel_lang'>" . _T('entree_titre_obligatoire') . "</label>"
	. "<input type='text' class='formo' name='titre' id='titre' value=\"$titre\" size='40' $onfocus />"
	 . "<input type='hidden' name='id_rubrique_old' value=\"$id_rubrique\" /><br />"
	. debut_cadre_couleur($logo_parent, true, "",_T('entree_interieur_rubrique').aide ("brevesrub"))
	. $chercher_rubrique($id_rubrique, 'breve', ($statut == 'publie')) 
	. fin_cadre_couleur(true)
	 . pipeline('affiche_gauche',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''))
	. "<br /><b>"._T('entree_texte_breve')."</b><br />\n"
	. afficher_textarea_barre($texte)
	. "<br />\n"
	. _T('entree_liens_sites')
	. aide ("breveslien")
	. "<br />\n"
	. "<label for='lien_titre'>" . _T('info_titre')."</label><br />\n"
	. "<input type='text' class='forml' name='lien_titre' id='lien_titre' value=\"$lien_titre\" size='40' /><br />\n"
	. "<label for='lien_url'>" . _T('info_url')."</label><br />\n"
	. "<input type='text' class='forml' name='lien_url' id='lien_url' value=\"$lien_url\" size='40' /><br />";

	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'breves', $id_rubrique);
	}

	if (autoriser('publierdans','rubrique',$id_rubrique)) {
		$form .= debut_cadre_relief('', true)
		. "<b><label for='statut'>"._T('entree_breve_publiee')."</label></b>\n"
		. "<select name='statut' id='statut' size='1' class='fondl'>\n"
		. "<option".mySel("prop",$statut)." style='background-color: white'>"._T('item_breve_proposee')."</option>\n"
		. "<option".mySel("refuse",$statut). " class='danger'>"._T('item_breve_refusee')."</option>\n"
		. "<option".mySel("publie",$statut)." style='background-color: #B4E8C5'>"._T('item_breve_validee')."</option>\n"
		. "</select>".aide ("brevesstatut")."<br />\n"
		. fin_cadre_relief(true);
	}
	$form .= "<p align='right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></p>";

	echo generer_action_auteur('editer_breve',
		$new ? $new : $id_breve,
		generer_url_ecrire('breves_voir'),
		$form,
		" method='post' name='formulaire'"
	);

}
else
	echo "<h2>"._T('info_page_interdite')."</h2>";

echo fin_cadre_formulaire(true);
echo fin_gauche(), fin_page();

}

?>
