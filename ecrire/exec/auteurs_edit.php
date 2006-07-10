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
include_spip('inc/logos');
include_spip('inc/auteur_voir');
include_spip('inc/message_select');

function exec_auteurs_edit_dist()
{
  global $connect_id_auteur, $spip_display;

	$id_auteur = intval(_request('id_auteur'));
	pipeline('exec_init',array('args'=>array('exec'=>'auteurs_edit','$id_auteur'=>$id_auteur),'data'=>''));

	$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'");

	if (!$auteur = spip_fetch_array($result)) die('erreur');

	modifier_statut_auteur($auteur, $_POST['statut'], $_POST['id_parent']);

	debut_page($auteur['nom'],  "auteurs",
		   (($connect_id_auteur == $id_auteur) ? "perso" : "redacteurs"));

	echo "<br><br><br>";
	
	debut_gauche();

	cadre_auteur_infos($id_auteur, $auteur);

	if (statut_modifiable_auteur($id_auteur, $auteur) AND ($spip_display != 4)) {
		afficher_boite_logo('id_auteur', $id_auteur,
				    _T('logo_auteur').aide ("logoart"), _T('logo_survol'), 'auteurs_edit');
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'auteurs_edit','id_auteur'=>$id_auteur),'data'=>''));
	table_auteurs_edit($auteur);
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'auteurs_edit','id_auteur'=>$id_auteur),'data'=>''));

	fin_page();
}

function table_auteurs_edit($auteur)
{
	global $connect_statut, $connect_id_auteur, $champs_extra, $options  ;

	$id_auteur=$auteur['id_auteur'];
	$nom=$auteur['nom'];
	$bio=$auteur['bio'];
	$email=$auteur['email'];
	$nom_site_auteur=$auteur['nom_site'];
	$url_site=$auteur['url_site'];
	$statut=$auteur['statut'];
	$pgp=$auteur["pgp"];
	$extra = $auteur["extra"];

	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'auteurs_edit','id_auteur'=>$id_auteur),'data'=>''));
	debut_droite();

	debut_cadre_relief("redacteurs-24.gif");
	
	
	echo "<table width='100%' cellpadding='0' border='0' cellspacing='0'>";
	
	echo "<tr>";

	echo "<td valign='top' width='100%'>";	


	gros_titre($nom);

	echo "<div>&nbsp;</div>";

	if (strlen($email) > 2) echo "<div>"._T('email_2')." <B><A HREF='mailto:$email'>$email</A></B></div>";
	$texte_case = ($nom_site_auteur.$url_site) ? ""._T('info_site_2')." [{{".$nom_site_auteur."}}->".$url_site."]" : '';
	echo propre($texte_case);

		
	echo "</td>";
	
	echo "<td>";
	
	if (statut_modifiable_auteur($id_auteur, $auteur)) {
		icone (_T("admin_modifier_auteur"), generer_url_ecrire("auteur_infos","id_auteur=$id_auteur"), "redacteurs-24.gif", "edit.gif");
	}
	echo "</td></tr></table>";

	if (strlen($bio) > 0) { echo "<div>".propre("<quote>".$bio."</quote>")."</div>"; }
	if (strlen($pgp) > 0) { echo "<div>".propre("PGP:<cadre>".$pgp."</cadre>")."</div>"; }

	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		extra_affichage($extra, "auteurs");
	}

	// Afficher le formulaire de changement de statut (cf. inc_acces)
	if ($options == 'avancees')
	  afficher_formulaire_statut_auteur ($id_auteur, $auteur['statut'], "auteurs_edit");

	fin_cadre_relief();

	echo "<div>&nbsp;</div>";

	if ($connect_statut == "0minirezo") $aff_art = "'prepa','prop','publie','refuse'";
	else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
	else $aff_art = "'prop','publie'";

	afficher_articles(_T('info_articles_auteur'),  array('FROM' => "spip_articles AS articles, spip_auteurs_articles AS lien",  "WHERE" => "lien.id_auteur='$id_auteur' AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art)",  'ORDER BY' => "articles.date DESC"), true);

	if ($id_auteur != $connect_id_auteur
	    AND ($statut == '0minirezo' OR $statut == '1comite')) {
		echo "<div>&nbsp;</div>";
		debut_cadre_couleur();

		$vus = array();
	
		afficher_messages(_T('info_discussion_cours'), ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		afficher_messages(_T('info_vos_rendez_vous'), ", spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2", "lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND date_fin > NOW() AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message", $vus, false, false);
	
		icone_horizontale(_T('info_envoyer_message_prive'), generer_url_ecrire("message_edit", "new=oui&type=normal&dest=$id_auteur"),
				  "message.gif");
		fin_cadre_couleur();
	}
}
?>
