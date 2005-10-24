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

  // 28 paremetres, qui dit mieux ?
  // moi ! elle en avait 61 en premiere approche

function affiche_articles_dist($id_article, $ajout_auteur, $articles_mots, $articles_redac, $articles_versions, $change_accepter_forum, $change_petition, $changer_virtuel, $cherche_auteur, $cherche_mot, $debut, $dir_lang, $email_unique, $flag_auteur, $flag_editable, $langue_article, $message, $nom_select, $nouv_auteur, $nouv_mot, $rubrique_article, $site_obli, $site_unique, $supp_auteur, $supp_mot, $texte_petition, $titre_article, $lier_trad)
{
 global $options, $spip_display, $spip_lang_left, $spip_lang_right;

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_article = $row["id_article"];
	$surtitre = $row["surtitre"];
	$titre = $row["titre"];
	$soustitre = $row["soustitre"];
	$id_rubrique = $row["id_rubrique"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$id_version = $row["id_version"];
}

// pour l'affichage du virtuel
unset($virtuel);
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date_redac, $regs)) {
	$annee_redac = $regs[1];
	$mois_redac = $regs[2];
	$jour_redac = $regs[3];
	$heure_redac = $regs[4];
	$minute_redac = $regs[5];
	if ($annee_redac > 4000) $annee_redac -= 9000;
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date, $regs)) {
	$annee = $regs[1];
	$mois = $regs[2];
	$jour = $regs[3];
	$heure = $regs[4];
	$minute = $regs[5];
 }



debut_page("&laquo; $titre_article &raquo;", "documents", "articles");

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();

//
// Affichage de la colonne de gauche
//

debut_gauche();

boite_info_articles($id_article, $statut_article, $visites, $articles_versions, $id_version);

//
// Logos de l'article et Boites de configuration avancee
//

boites_de_config_articles($id_article, $flag_editable,
			  $change_accepter_forum, $change_petition,
			  $email_unique, $site_obli, $site_unique,
			  $message, $texte_petition,
			  $changer_virtuel, $virtuel);
 
//
// Affichage de la colonne de droite
//

debut_droite();

changer_typo('','article'.$id_article);

debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

$modif = titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article);


echo "<div class='serif' align='$spip_lang_left'>";

dates_articles($id_article, $flag_editable, $statut_article,  $articles_redac, $date,$annee, $mois, $jour, $heure, $minute,  $annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac);

//
// Liste des auteurs de l'article
//

echo "<a name='auteurs'></a>";

if ($flag_editable AND $options == 'avancees') {
	$bouton = bouton_block_invisible("auteursarticle");
}

debut_cadre_enfonce("auteur-24.gif", false, "", $bouton._T('texte_auteurs').aide ("artauteurs"));

//
// Recherche d'auteur
//

$supprimer_bouton_creer_auteur = rechercher_auteurs_articles($cherche_auteur, $id_article, $ajout_auteur, $flag_editable, $nouv_auteur, $supp_auteur);

//
// Afficher les auteurs
//

$les_auteurs = afficher_auteurs_articles($id_article, $flag_editable);

//
// Ajouter un auteur
//

ajouter_auteurs_articles($id_article, $les_auteurs, $flag_editable, $rubrique_article, $supprimer_bouton_creer_auteur);

fin_cadre_enfonce(false);

//
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $articles_mots != 'non') {
	formulaire_mots('articles', $id_article, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

 langues_articles($id_article, $langue_article, $flag_editable, $id_rubrique, $id_trad, $dir_lang, $nom_select, $lier_trad);


afficher_statut_articles($id_article, $rubrique_article, $statut_article);


afficher_corps_articles($virtuel, $chapo, $texte, $ps);

if ($flag_editable) {
	echo "\n\n<div align='$spip_lang_right'><br />";
	bouton_modifier_articles($id_article, $modif,_T('texte_travail_article', $modif), "warning-24.gif", "");
	echo "</div>";
}

//
// Documents associes a l'article
//

if ($spip_display != 4) afficher_documents_non_inclus($id_article, "article", $flag_editable);

//
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo "<center>";
	echo "<B>"._T('texte_proposer_publication')."</B>";
	echo aide ("artprop");
	bouton(_T('bouton_demande_publication'), "articles.php3?id_article=$id_article&statut_nouv=prop");
	echo "</center>";
	fin_cadre_relief();
}

echo "</div>";

echo "</div>";
fin_cadre_relief();

affiche_forums_article($id_article, $titre, $debut);

fin_page();

}

function boite_info_articles($id_article, $statut_article, $visites, $articles_versions, $id_version)
{
	global $connect_statut, $options;

	debut_boite_info();
 
	echo "<div align='center'>\n";

	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('info_numero_article')."</b></font>\n";
	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_article</b></font>\n";

	voir_en_ligne ('article', $id_article, $statut_article);


	$activer_statistiques = lire_meta("activer_statistiques");

	if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $activer_statistiques != "non" AND $options == "avancees"){
	icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), "statistiques_visites.php3?id_article=$id_article", "statistiques-24.gif","rien.gif");
}

	if ($articles_versions AND $id_version>1 AND $options == "avancees") {
	icone_horizontale(_T('info_historique_lien'), "articles_versions.php3?id_article=$id_article", "historique-24.gif", "rien.gif");
}

	// Correction orthographique
	if (lire_meta('articles_ortho') == 'oui') {
		$js_ortho = "onclick=\"window.open(this.href, 'spip_ortho', 'scrollbars=yes, resizable=yes, width=740, height=580'); return false;\"";
		icone_horizontale(_T('ortho_verifier'), "articles_ortho.php?id_article=$id_article", "ortho-24.gif", "rien.gif", 'echo', $js_ortho);
	}

	echo "</div>\n";
	
	fin_boite_info();
}

function boites_de_config_articles($id_article, $flag_editable,
				   $change_accepter_forum, $change_petition,
				   $email_unique, $site_obli, $site_unique,
				   $message, $texte_petition,
				   $changer_virtuel, $virtuel)
{
  global $connect_statut, $options, $spip_lang_right;

// Logos de l'article

	if ($id_article AND $flag_editable)
	  afficher_boite_logo('art', 'id_article', $id_article,
			      _T('logo_article').aide ("logoart"), _T('logo_survol'));


//
// Boites de configuration avancee
//

	if ($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable) {
	  echo "<p>";
	  debut_cadre_relief("forum-interne-24.gif");


	list($nb_forums) = spip_fetch_array(spip_query(
		"SELECT count(*) AS count FROM spip_forum
		WHERE id_article=$id_article
		AND statut IN ('publie', 'off', 'prop')"));

	list($nb_signatures) = spip_fetch_array(spip_query(
		"SELECT COUNT(*) AS count FROM spip_signatures
		WHERE id_article=$id_article
		AND statut IN ('publie', 'poubelle')"));


	$visible = $change_accepter_forum || $change_petition
		|| $nb_forums || $nb_signatures;

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("forumpetition");
	else
		echo bouton_block_invisible("forumpetition");
	echo _T('bouton_forum_petition') .aide('confforums');
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("forumpetition");
	else
		echo debut_block_invisible("forumpetition");


	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'>\n";


	// Forums

	if ($nb_forums) {
		echo "<br />\n";
		icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)),
		"articles_forum.php3?id_article=$id_article", "suivi-forum-24.gif", "");
	}

	// Reglage existant
	$forums_publics = get_forums_publics($id_article);

	// Modification du reglage ?
	if (isset($change_accepter_forum)
	AND $change_accepter_forum <> $forums_publics) {
		$forums_publics = $change_accepter_forum;
		modifier_forums_publics($id_article, $forums_publics);
	}

	// Afficher le formulaire de modification du reglage
	echo formulaire_modification_forums_publics($id_article, $forums_publics);


	// Petitions

	if ($change_petition) {
		if ($change_petition == "on") {
			if (!$email_unique) $email_unique = "non";
			if (!$site_obli) $site_obli = "non";
			if (!$site_unique) $site_unique = "non";
			if (!$message) $message = "non";

			$texte_petition = addslashes($texte_petition);

			$query_pet = "REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) ".
				"VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', '$texte_petition')";
			$result_pet = spip_query($query_pet);
		}
		else if ($change_petition == "off") {
			$query_pet = "DELETE FROM spip_petitions WHERE id_article=$id_article";
			$result_pet = spip_query($query_pet);
		}
	}

	$petition = spip_fetch_array(spip_query(
		"SELECT * FROM spip_petitions WHERE id_article=$id_article"));

	$email_unique=$petition["email_unique"];
	$site_obli=$petition["site_obli"];
	$site_unique=$petition["site_unique"];
	$message=$petition["message"];
	$texte_petition=$petition["texte"];

	echo "\n<form action='".$GLOBALS['clean_link']->getUrl()
		."' method='POST'>";
	echo "\n<input type='hidden' name='id_article' value='$id_article'>";

	echo "<select name='change_petition'
		class='fondl' style='font-size:10px;'
		onChange=\"setvisibility('valider_petition', 'visible');\"
		>\n";

	if ($petition) {
		$menu = array(
			'on' => _T('bouton_radio_petition_activee'),
			'off'=> _T('bouton_radio_supprimer_petition')
		);
		$val_menu = 'on';
	} else {
		$menu = array(
			'off'=> _T('bouton_radio_pas_petition'),
			'on' => _T('bouton_radio_activer_petition')
		);
		$val_menu = 'off';
	}


	foreach ($menu as $val => $desc) {
		echo "<option";
		if ($val_menu == $val)
			echo " selected";
		echo " value='$val'>".$desc."</option>\n";
	}
	echo "</select>\n";

	if ($petition) {
		if ($nb_signatures) {
			echo "<br />\n";
			icone_horizontale($nb_signatures.'&nbsp;'. _T('info_signatures'),
			"controle_petition.php3?id_article=$id_article", "suivi-petition-24.gif", "");
		}

		echo "<br />\n";

		if ($email_unique=="oui")
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><BR>";
		if ($site_obli=="oui")
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><BR>";
		if ($site_unique=="oui")
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><BR>";
		if ($message=="oui")
			echo "<input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		echo "<P>"._T('texte_descriptif_petition')."&nbsp;:<BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo $texte_petition;
		echo "</TEXTAREA><P>\n";

		echo "<P align='$spip_lang_right'>";
	}

	if (!$petition) echo "<span class='visible_au_chargement' id='valider_petition'>";
	echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'>";
	if (!$petition)  echo "</span>";
	echo "</FORM>";

	echo "</font>";
	echo fin_block();

	fin_cadre_relief();



	// Redirection (article virtuel)
	debut_cadre_relief("site-24.gif");
	$visible = ($changer_virtuel || $virtuel);

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("redirection");
	else
		echo bouton_block_invisible("redirection");
	echo _T('bouton_redirection');
	echo aide ("artvirt");
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("redirection");
	else
		echo debut_block_invisible("redirection");

	echo "<form action='articles.php3?id_article=$id_article' method='post'>";
	echo "\n<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";
	echo "\n<INPUT TYPE='hidden' NAME='changer_virtuel' VALUE='oui'>";
	$virtuelhttp = ($virtuel ? "" : "http://");

	echo "<INPUT TYPE='text' NAME='virtuel' CLASS='formo' style='font-size:9px;' VALUE=\"$virtuelhttp$virtuel\" SIZE='40'><br>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
	echo "(<b>"._T('texte_article_virtuel')."&nbsp;:</b> "._T('texte_reference_mais_redirige').")";
	echo "</font>";
	echo "<div align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'></div>";
	echo "</form>";
	echo fin_block();

	fin_cadre_relief();
 }

//
// Articles dans la meme rubrique
//

meme_rubrique_articles($id_rubrique, $id_article, $options);

}


function changer_statut_articles($id_article, $statut)
{
	spip_log("arti $id_article, $statut");
	$result = spip_query("SELECT statut FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
			$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_articles SET statut='$statut', date=NOW() WHERE id_article=$id_article");			
		include_ecrire("inc_rubriques.php3");
		include_ecrire('inc_lang.php3');
		include_ecrire('inc_filtres.php3');
		include_ecrire('inc_texte.php3');
		calculer_rubriques();

		cron_articles($id_article, $statut, $statut_ancien);
	}
}

function cron_articles($id_article, $statut, $statut_ancien)
{
	global $invalider_caches;

	calculer_rubriques();

	if ($statut == 'publie') {
		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
			marquer_indexer('article', $id_article);
		}
		include_ecrire("inc_mail.php3");
		envoyer_mail_publication($id_article);
	}

	if ($statut_ancien == 'publie' AND $invalider_caches) {
	  	include_ecrire ("inc_invalideur.php3");
		suivre_invalideur("id='id_article/$id_article'");
	}

	if ($statut == "prop" AND $statut_ancien != 'publie') {
		include_ecrire("inc_mail.php3");
		envoyer_mail_proposition($id_article);
	}
}

function meme_rubrique_articles($id_rubrique, $id_article, $options, $order='articles.date', $limit=30)
{
	global $spip_lang_right, $spip_lang_left;

	$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.id_rubrique='$id_rubrique' AND (articles.statut = 'publie' OR articles.statut = 'prop') AND articles.id_article != '$id_article' ORDER BY $order DESC LIMIT $limit");
	if (spip_num_rows($vos_articles) > 0) {
			echo "<div>&nbsp;</div>";
			echo "<div class='bandeau_rubriques' style='z-index: 1;'>";
			bandeau_titre_boite2(_T('info_meme_rubrique'), "article-24.gif");
			echo "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$ze_article = $row['id_article'];
				$ze_titre = typo($row['titre']);
				$ze_statut = $row['statut'];
				
				if ($options == "avancees") {
					$numero = "<div class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>"._T('info_numero_abbreviation')."$ze_article</b></div>";
				}
				echo "<a class='$ze_statut' style='font-size: 10px;' href='articles.php3?id_article=$ze_article'>$numero$ze_titre</a>";
			}
			echo "</div>";
			echo "</div>";
		}
}

function bouton_modifier_articles($id_article, $flag_modif, $mode, $ip, $im)
{
	if ($flag_modif) {
	  icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", $ip, $im);
		echo "<font face='arial,helvetica,sans-serif' size='2'>$mode</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), "articles_edit.php3?id_article=$id_article", "article-24.gif", "edit.gif");
	}

}

function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article)
{
	global  $dir_lang, $spip_lang_left, $connect_id_auteur;

	$logo_statut = "puce-".puce_statut($statut_article).".gif";
	
	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'><td width='100%' valign='top'>";
	
	if ($surtitre) {
		echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
		echo typo($surtitre);
		echo "</b></font></span>\n";
	 }
	 
	gros_titre($titre, $logo_statut);
	
	if ($soustitre) {
		echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
		echo typo($soustitre);
		echo "</b></font></span>\n";
	}
	
	
	if ($descriptif OR $url_site OR $nom_site) {
		echo "<p><div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
		echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
		$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
		echo propre($texte_case);
		echo "</font>";
		echo "</div>";
	}
	
	
	if ($statut_article == 'prop') {
		echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='red'><B>"._T('text_article_propose_publication')."</B></FONT></P>";
	}
	
	echo "</td>";
	
	$flag_modif = false;
	
	if ($flag_editable) {
		echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
		echo "<td align='center'>";
	
		// Recuperer les donnees de l'article
		if (lire_meta('articles_modif') != 'non') {
			$query = "SELECT auteur_modif, UNIX_TIMESTAMP(date_modif) AS modification, UNIX_TIMESTAMP(NOW()) AS maintenant FROM spip_articles WHERE id_article='$id_article'";
			$result = spip_query($query);
	
			if ($row = spip_fetch_array($result)) {
				$auteur_modif = $row["auteur_modif"];
				$modification = $row["modification"];
				$maintenant = $row["maintenant"];
	
				$date_diff = floor(($maintenant - $modification)/60);
	
				if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
					$query_auteur = "SELECT nom FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
					$result_auteur = spip_query($query_auteur);
					if ($row_auteur = spip_fetch_array($result_auteur)) {
						$nom_auteur_modif = typo($row_auteur["nom"]);
					}
					$modif = array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff);
				}
			}
		}
		bouton_modifier_articles($id_article, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif");
	
		echo "</td>";
	 }
	echo "</tr></table>\n";
	echo "<div>&nbsp;</div>";
	return $modif;
}


function dates_articles($id_article, $flag_editable, $statut_article,  $articles_redac, $date,$annee, $mois, $jour, $heure, $minute,  $annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac)
{

  global $spip_lang_left, $spip_lang_right, $options;

  if ($flag_editable AND $options == 'avancees') {
	debut_cadre_couleur();

	echo "<FORM ACTION='articles.php3' METHOD='GET' style='margin: 0px; padding: 0px;'>";
	echo "<INPUT TYPE='hidden' NAME='id_article' VALUE='$id_article'>";

	if ($statut_article == 'publie') {

		echo "<div><b>";
		echo bouton_block_invisible("datepub");
		echo "<span class='verdana1'>"._T('texte_date_publication_article').'</span> ';
		echo majuscules(affdate($date)),
			"</b>".aide('artdate')."</div>";

		echo debut_block_invisible("datepub"),
		  "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>",
		  afficher_jour($jour, "name='jour' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\"", true),
		  afficher_mois($mois, "name='mois' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\"", true),
		  afficher_annee($annee, "name='annee' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  ' - ',
		  afficher_heure($heure, "name='heure' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  afficher_minute($minute, "name='minute' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  "<span class='visible_au_chargement' id='valider_date'>",
		  " &nbsp; <INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'>",
		  "</span>",
		  "</div>",
		  fin_block();
	}
	else {
		echo "<div><b> <span class='verdana1'>"._T('texte_date_creation_article').'</span> ';
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";
	}

	$possedeDateRedac=($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00');
	if (($options == 'avancees' AND $articles_redac != 'non')
	OR $possedeDateRedac) {
		if ($possedeDateRedac)
			$date_affichee = majuscules(affdate($date_redac))
#			." " ._T('date_fmt_heures_minutes', array('h' =>$heure_redac, 'm'=>$minute_redac))
			;
		else
			$date_affichee = majuscules(_T('jour_non_connu_nc'));

		echo "<div><b>";
		echo bouton_block_invisible('dateredac');
		echo "<span class='verdana1'>"
			. majuscules(_T('texte_date_publication_anterieure'))
			.'</span> '. $date_affichee ." ".aide('artdate_redac')."</b></div>";

		echo debut_block_invisible('dateredac');
		echo "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>";
		echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		echo '<tr><td align="$spip_lang_left">';
		echo '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"';
		if (!$possedeDateRedac) echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_on">'._T('texte_date_publication_anterieure_nonaffichee').'</label>';
		echo '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"';
		if ($possedeDateRedac) echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_off">'._T('bouton_radio_afficher').' :</label> ',
		afficher_jour($jour_redac, "name='jour_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		afficher_mois($mois_redac, "name='mois_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true);
		echo "<input type='text' name='annee_redac' class='fondl' value='".$annee_redac."' size='5' maxlength='4' onClick=\"setvisibility('valider_date_prec', 'visible')\"/>";

		echo '<div align="center">',
		afficher_heure($heure_redac, "name='heure_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		afficher_minute($minute_redac, "name='minute_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		"</div>\n";

		echo '</td><td align="$spip_lang_right">';
		echo "<span class='visible_au_chargement' id='valider_date_prec'>";
		echo '<input type="submit" name="Changer" class="fondo" value="'._T('bouton_changer').'" />';
		echo "</span>";
		echo '</td></tr>';
		echo '</table>';
		echo "</div>";
		echo fin_block();
	}

	echo "</FORM>";
	fin_cadre_couleur();
 }
else {
	if ($statut_article == 'publie') $texte_date = _T('texte_date_publication_article');
	else $texte_date = _T('texte_date_creation_article');

	debut_cadre_couleur();
		echo "<div style='text-align:center;'><b> <span class='verdana1'>$texte_date</span> ";
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";


		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') {
			$date_affichee = ' : '.majuscules(affdate($date_redac));		
			echo "<div style='text-align:center;'><b> <span class='verdana1'>"._T(texte_date_publication_anterieure)."</span> ";
			echo $date_affichee."</b>".aide('artdate_redac')."</div>";
		}

	fin_cadre_couleur();
 }
}


function langues_articles($id_article, $langue_article, $flag_editable, $id_rubrique, $id_trad, $dir_lang, $nom_select, $lier_trad)
{

  global $connect_statut, $couleur_claire, $options, $connect_toutes_rubriques;

  if ((lire_meta('multi_articles') == 'oui')
	OR ((lire_meta('multi_rubriques') == 'oui') AND (lire_meta('gerer_trad') == 'oui'))) {

	$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
	$langue_article = $row['lang'];
	$langue_choisie_article = $row['langue_choisie'];

	if (lire_meta('gerer_trad') == 'oui')
		$titre_barre = _T('titre_langue_trad_article');
	else
		$titre_barre = _T('titre_langue_article');

	$titre_barre .= "&nbsp; (".traduire_nom_langue($langue_article).")";

	debut_cadre_enfonce('langues-24.gif', false, "", bouton_block_invisible('languesarticle,ne_plus_lier,lier_traductions').$titre_barre);


	// Choix langue article
	if (lire_meta('multi_articles') == 'oui' AND $flag_editable) {
		echo debut_block_invisible('languesarticle');

		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];

		if ($langue_choisie_article == 'oui') $herit = false;
		else $herit = true;

		debut_cadre_couleur();
		echo "<div style='text-align: center;'>";
		echo menu_langues('changer_lang', $langue_article, _T('info_multi_cet_article').' ', $langue_parent);
		echo "</div>\n";
		fin_cadre_couleur();

		echo fin_block();
	}


	// Gerer les groupes de traductions
	if (lire_meta('gerer_trad') == 'oui') {
		if ($flag_editable AND $supp_trad == 'oui') { // Ne plus lier a un groupe de trad
			spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_article = $id_article");

			// Verifier si l'ancien groupe ne comporte plus qu'un seul article. Alors mettre a zero.
			$result_autres_trad= spip_query("SELECT COUNT(id_article) AS total FROM spip_articles WHERE id_trad = $id_trad");
			if ($row = spip_fetch_array($result_autres_trad))
				$nombre_autres_trad = $row["total"];
			if ($nombre_autres_trad == 1)
				spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_trad = $id_trad");

			$id_trad = 0;
		}

		// Changer article de reference de la trad
		if ($id_trad_new = intval($id_trad_new)
		AND $id_trad_old = intval($id_trad_old)
		AND $connect_statut=='0minirezo'
		AND $connect_toutes_rubriques) { 
			spip_query("UPDATE spip_articles SET id_trad = $id_trad_new WHERE id_trad = $id_trad_old");
			$id_trad = $id_trad_new;
		}

		if ($flag_editable AND $lier_trad > 0) { // Lier a un groupe de trad
			$query_lier = "SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad";
			$result_lier = spip_query($query_lier);
			if ($row = spip_fetch_array($result_lier)) {
				$id_lier = $row['id_trad'];

				if ($id_lier == 0) { // Si l'article vise n'a pas deja de traduction, creer nouveau id_trad
					$nouveau_trad = $lier_trad;
				}
				else {
					if ($id_lier == $id_trad) $err = "<div>"._T('trad_deja_traduit')."</div>";
					$nouveau_trad = $id_lier;
				}

				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $lier_trad");
				if ($id_lier > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_lier");
				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $id_article");
				if ($id_trad > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_trad");

				$id_trad = $nouveau_trad;
			}
			else
				$err .= "<div>"._T('trad_article_inexistant')."</div>";

			if ($err) echo "<font color='red' size=2' face='verdana,arial,helvetica,sans-serif'>$err</font>";
		}


		// Afficher la liste des traductions
		if ($id_trad != 0) {
			$query_trad = "SELECT id_article, titre, lang, statut FROM spip_articles WHERE id_trad = $id_trad";
			$result_trad = spip_query($query_trad);
			
			
			$table='';
			while ($row = spip_fetch_array($result_trad)) {
				$vals = '';
				$id_article_trad = $row["id_article"];
				$titre_trad = $row["titre"];
				$lang_trad = $row["lang"];
				$statut_trad = $row["statut"];

				changer_typo($lang_trad);
				$titre_trad = "<span $dir_lang>$titre_trad</span>";

				if ($ifond == 1) {
					$ifond = 0;
					$bgcolor = "white";
				} else {
					$ifond = 1;
					$bgcolor = $couleur_claire;
				}


				$vals[] = http_img_pack("puce-".puce_statut($statut_trad).'.gif', "", "width='7' height='7' border='0' NAME='statut'");
				
				if ($id_article_trad == $id_trad) {
				  $vals[] = http_img_pack('langues-12.gif', "", "width='12' height='12' border='0'");
					$titre_trad = "<b>$titre_trad</b>";
				} else {
				  if ($connect_statut=='0minirezo'
				  AND $connect_toutes_rubriques)
				  	$vals[] = "<a href='articles.php3?id_article=$id_article&id_trad_old=$id_trad&id_trad_new=$id_article_trad'>". 
				    http_img_pack('langues-off-12.gif', _T('trad_reference'), "width='12' height='12' border='0'", _T('trad_reference')) . "</a>";
				  else $vals[] = http_img_pack('langues-off-12.gif', "", "width='12' height='12' border='0'");
				}

				$ret .= "</td>";

				$s = typo($titre_trad);
				if ($id_article_trad != $id_article) 
					$s = "<a href='articles.php3?id_article=$id_article_trad'>$s</a>";
				if ($id_article_trad == $id_trad)
					$s .= " "._T('trad_reference');

				$vals[] = $s;
				$vals[] = traduire_nom_langue($lang_trad);
				$table[] = $vals;
			}

			// changer_typo($spip_lang); (probleme d'affichage rtl?)

			// bloc traductions
			if (count($vals) > 0) {

				echo "<div class='liste'>";
				bandeau_titre_boite2(_T('trad_article_traduction'),'');
				echo "<table width='100%' cellspacing='0' border='0' cellpadding='2'>";
				//echo "<tr bgcolor='#eeeecc'><td colspan='4' class='serif2'><b>"._T('trad_article_traduction')."</b></td></tr>";

				$largeurs = array(7, 12, '', 100);
				$styles = array('', '', 'arial2', 'arial2');
				afficher_liste($largeurs, $table, $styles);

				echo "</table>";
				echo "</div>";

			}

			changer_typo($langue_article);
		}

		echo debut_block_invisible('lier_traductions');

		echo "<table width='100%'><tr>";
		if ($flag_editable AND $options == "avancees" AND !$ret) {
			// Formulaire pour lier a un article
			echo "<td class='arial2' width='60%'>";
			$lien = $GLOBALS['clean_link'];
			$lien->delVar($nom_select);
			$lien = $lien->getUrl();

			echo "<form action='$lien' method='post' style='margin:0px; padding:0px;'>";
			echo _T('trad_lier');
			echo "<div align='$spip_lang_right'><input type='text' class='fondl' name='lier_trad' size='5'> <INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondl'></div>";
			echo "</form>";
			echo "</td>\n";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
		}
		echo "<td>";
		icone_horizontale(_T('trad_new'), "articles_edit.php3?new=oui&lier_trad=$id_article&id_rubrique=$id_rubrique", "traductions-24.gif", "creer.gif");
		echo "</td>";
		if ($flag_editable AND $options == "avancees" AND $ret) {
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td>";
			icone_horizontale(_T('trad_delier'), "articles.php3?id_article=$id_article&supp_trad=oui", "traductions-24.gif", "supprimer.gif");
			echo "</td>\n";
		}

		echo "</tr></table>";

		echo fin_block();
	}

	fin_cadre_enfonce();
  }
}



function rechercher_auteurs_articles($cherche_auteur, $id_article, $ajout_auteur, $flag_editable, $nouv_auteur, $supp_auteur)
{
  global $spip_lang_left;

  $supprimer_bouton_creer_auteur = false;

  if ($cherche_auteur) {
	echo "<P ALIGN='$spip_lang_left'>";
	$query = "SELECT id_auteur, nom FROM spip_auteurs";
	$result = spip_query($query);
	$table_auteurs = array();
	$table_ids = array();
	while ($row = spip_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR>";
	}
	else if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>"._T('texte_ajout_auteur')."</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = spip_query($query);
		echo "<UL>";
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];

			echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($nom_auteur)."</FONT></B>";
			echo "</FONT>\n";
		}
		echo "</UL>";
	}
	else if (count($resultat) < 16) {
		reset($resultat);
		$les_auteurs = array();
		while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<B>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = spip_query($query);
			echo "<UL class='verdana1'>";
			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				echo "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A HREF=\"articles.php3?id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur#auteurs\">"._T('lien_ajouter_auteur')."</A>";

				if (trim($bio_auteur)) {
					echo "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				echo "</li>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
	}

	if ($GLOBALS['connect_statut'] == '0minirezo') {
		echo "<div style='width: 200px;'>";
		$retour = urlencode($GLOBALS['clean_link']->getUrl());
		$titre = urlencode($cherche_auteur);
		icone_horizontale(_T('icone_creer_auteur'), "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&titre=$titre&redirect=$retour", "redacteurs-24.gif", "creer.gif");
		echo "</div> ";

		// message pour ne pas afficher le second bouton "creer un auteur"
		$supprimer_bouton_creer_auteur = true;
	}

	fin_boite_info();
	echo "<P>";

  }

//
// Appliquer les modifications sur les auteurs
//

  if ($ajout_auteur && $flag_editable) {
	if ($nouv_auteur > 0) {
		$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$nouv_auteur' AND id_article='$id_article'";
		$result=spip_query($query);
		$query="INSERT INTO spip_auteurs_articles (id_auteur,id_article) VALUES ('$nouv_auteur','$id_article')";
		$result=spip_query($query);
	}

	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		marquer_indexer('article', $id_article);
	}
  }


  if ($supp_auteur && $flag_editable) {
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$supp_auteur' AND id_article='$id_article'";
	$result=spip_query($query);
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		marquer_indexer('article', $id_article);
	}
  }

  return $supprimer_bouton_creer_auteur;
}

function afficher_auteurs_articles($id_article, $flag_editable)
{
	global $connect_statut, $options,$connect_id_auteur;

	$les_auteurs = array();

	$query = "SELECT * FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	"WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=$id_article ".
	"GROUP BY auteurs.id_auteur ORDER BY auteurs.nom";
	$result = spip_query($query);

	if (spip_num_rows($result)) {
		echo "<div class='liste'>";
		echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>";
		$table = array();
		while ($row = spip_fetch_array($result)) {
			$vals = array();
			$id_auteur = $row["id_auteur"];
			$nom_auteur = $row["nom"];
			$email_auteur = $row["email"];
			if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			  $bio_auteur = " TITLE=\"$bio_auteur\"";
			$url_site_auteur = $row["url_site"];
			$statut_auteur = $row["statut"];
			if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';
			
			$les_auteurs[] = $id_auteur;

		if ($connect_statut == "0minirezo") $aff_articles = "('prepa', 'prop', 'publie', 'refuse')";
		else $aff_articles = "('prop', 'publie')";

		$query2 = "SELECT COUNT(articles.id_article) AS compteur ".
			"FROM spip_auteurs_articles AS lien, spip_articles AS articles ".
			"WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article ".
			"AND articles.statut IN $aff_articles GROUP BY lien.id_auteur";
		$result2 = spip_query($query2);
		if ($result2) list($nombre_articles) = spip_fetch_array($result2);
		else $nombre_articles = 0;

		$url_auteur = "auteurs_edit.php3?id_auteur=$id_auteur";

		$vals[] = bonhomme_statut($row);

		$vals[] = "<A HREF=\"$url_auteur\"$bio_auteur>".typo($nom_auteur)."</A>";

		$vals[] = bouton_imessage($id_auteur);

		
		
		if ($email_auteur) $vals[] =  "<A HREF='mailto:$email_auteur'>"._T('email')."</A>";
		else $vals[] =  "&nbsp;";

		if ($url_site_auteur) $vals[] =  "<A HREF='$url_site_auteur'>"._T('info_site_min')."</A>";
		else $vals[] =  "&nbsp;";

		if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
		else if ($nombre_articles == 1) $vals[] =  _T('info_1_article');
		else $vals[] =  "&nbsp;";

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
		  $vals[] =  "<A HREF='articles.php3?id_article=$id_article&supp_auteur=$id_auteur#auteurs'>"._T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") . "</A>";
		} else {
			$vals[] = "";
		}
		
		$table[] = $vals;
	}
	
	
	$largeurs = array('14', '', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial11', 'arial1');
	afficher_liste($largeurs, $table, $styles);

	
	echo "</table></div>\n";

	$les_auteurs = join(',', $les_auteurs);
	}
	return $les_auteurs ;
}


function ajouter_auteurs_articles($id_article, $les_auteurs, $flag_editable, $rubrique_article, $supprimer_bouton_creer_auteur)
{

	global $connect_statut, $options,$connect_id_auteur, $couleur_claire ;

	if (!($flag_editable AND $options == 'avancees')) return;

	echo debut_block_invisible("auteursarticle");

	$query = "SELECT * FROM spip_auteurs WHERE ";
	if ($les_auteurs) $query .= "id_auteur NOT IN ($les_auteurs) AND ";
	$query .= "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY statut, nom";
	$result = spip_query($query);
	
	echo "<table width='100%'>";
	echo "<tr>";

	if ($connect_statut == '0minirezo'
	    AND acces_rubrique($rubrique_article)
	    AND $options == "avancees"
	    AND !$supprimer_bouton_creer_auteur) {
	echo "<td width='200'>";
	$retour = urlencode($GLOBALS['clean_link']->getUrl());
	icone_horizontale(_T('icone_creer_auteur'), "auteur_infos.php3?new=oui&ajouter_id_article=$id_article&redirect=$retour", "redacteurs-24.gif", "creer.gif");
	echo "</td>";
	echo "<td width='20'>&nbsp;</td>";
	}

	echo "<td>";


	if (spip_num_rows($result) > 0) {
		echo "<FORM ACTION='articles.php3?id_article=$id_article#auteurs' METHOD='post'>";
		echo "<span class='verdana1'><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></span>\n";
		echo "<DIV><INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (spip_num_rows($result) > 200) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' onClick=\"setvisibility('valider_ajouter_auteur','visible');\" CLASS='fondl' VALUE='' SIZE='20'>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo'>";
			echo "</span>";
		} else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='width:150px;' CLASS='fondl' onChange=\"setvisibility('valider_ajouter_auteur','visible');\">";
			$group = false;
			$group2 = false;

			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row["id_auteur"];
				$nom = $row["nom"];
				$email = $row["email"];
				$statut = $row["statut"];

				$statut=str_replace("0minirezo", _T('info_administrateurs'), $statut);
				$statut=str_replace("1comite", _T('info_redacteurs'), $statut);
				$statut=str_replace("6visiteur", _T('info_visiteurs'), $statut);
				
				$premiere = strtoupper(substr(trim($nom), 0, 1));

				if ($connect_statut != '0minirezo')
					if ($p = strpos($email, '@'))
					  $email = substr($email, 0, $p).'@...';
				if ($email)
					$email = " ($email)";

				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\" style='background-color: $couleur_claire;'> $statut";
				}

				if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old)) {
				  echo "\n<OPTION VALUE=\"x\">";
				}

				$texte_option = supprimer_tags(couper(typo("$nom$email"), 40));
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}
			
			echo "</SELECT>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Ajouter' VALUE="._T('bouton_ajouter')." CLASS='fondo'>";
			echo "</span>";
		}
		echo "</div></FORM>";
	}
	
	echo "</td></tr></table>";

	echo fin_block();
}

function afficher_corps_articles($virtuel, $chapo, $texte, $ps)
{
  global $revision_nbsp, $activer_revision_nbsp, $champs_extra, $extra, $les_notes, $dir_lang;

	echo "\n\n<div align='justify' style='padding: 10px;'>";

	if ($virtuel) {
		debut_boite_info();
		echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
		fin_boite_info();
	} else {
		$revision_nbsp = $activer_revision_nbsp;

		if (strlen($chapo) > 0) {
			echo "<div $dir_lang style='font-size: small;'><b>";
			echo propre($chapo);
			echo "</b></div>\n\n";
		}

		echo "<div $dir_lang style='font-size: small;'>";
#	echo reduire_image(propre($texte), 500,10000);
		echo propre($texte);
		echo "<br clear='both' />";
		echo "</div>";

		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div $dir_lang><font size=2 face='Verdana,Arial,Sans,sans-serif'>";
			echo justifier("<b>"._T('info_ps')."</b> ".propre($ps));
			echo "</font></div>";
			echo fin_cadre_enfonce();
		}
		$revision_nbsp = false;

		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang class='arial11'>";
			echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
			echo "</div>";
			echo fin_cadre_relief();
		}
		
		if ($champs_extra AND $extra) {
			include_ecrire("inc_extra.php3");
			extra_affichage($extra, "articles");
		}
	}
}

function affiche_forums_article($id_article, $titre, $debut, $mute=false)
{
  global $spip_lang_left;

  echo "<BR><BR>";

  $forum_retour = urlencode("articles.php3?id_article=$id_article");
  
  if (!$mute) {
    echo "\n<div align='center'>";
    icone(_T('icone_poster_message'), "forum_envoi.php3?statut=prive&adresse_retour=".$forum_retour."&id_article=$id_article&titre_message=".urlencode($titre), "forum-interne-24.gif", "creer.gif");
    echo "</div>";
  }

  echo "<P align='$spip_lang_left'>";

  $query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0";
  $result_forum = spip_query($query_forum);
  $total = 0;
  if ($row = spip_fetch_array($result_forum)) $total = $row["cnt"];

  if (!$debut) $debut = 0;
  $total_afficher = 8;
  if ($total > $total_afficher) {
	echo "<div class='serif2' align='center'>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	}
	echo "</div>";
}

	$query_forum = "SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC LIMIT $total_afficher OFFSET $debut";
	$result_forum = spip_query($query_forum);
	afficher_forum($result_forum, $forum_retour, $mute);

	if (!$debut) $debut = 0;
	$total_afficher = 8;
	if ($total > $total_afficher) {
	  echo "<div class='serif2' align='center'>";
	  for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A HREF='articles.php3?id_article=$id_article&debut=$i'>$i-$y</A>] ";
	  }
	  echo "</div>";
	}

	echo "</div>\n";
}

function afficher_statut_articles($id_article, $rubrique_article, $statut_article)
{
  global $connect_statut;

  if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article)) {
	echo "<FORM ACTION='articles.php3' METHOD='get'>";
	debut_cadre_relief("racine-site-24.gif");
	echo "<CENTER>";
	
	echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

	echo "<B>"._T('texte_article_statut')."</B> ";

	$statut_url_javascript="'" . _DIR_IMG_PACK . "' + puce_statut(options[selectedIndex].value);";
	echo "<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl' onChange=\"document.statut.src=$statut_url_javascript; setvisibility('valider_statut', 'visible');\">";
	echo "<OPTION" . mySel("prepa", $statut_article) ." style='background-color: white'>"._T('texte_statut_en_cours_redaction')."\n";
	echo "<OPTION" . mySel("prop", $statut_article) . " style='background-color: #FFF1C6'>"._T('texte_statut_propose_evaluation')."\n";
	echo "<OPTION" . mySel("publie", $statut_article) . " style='background-color: #B4E8C5'>"._T('texte_statut_publie')."\n";
	echo "<OPTION" . mySel("poubelle", $statut_article)
	  . http_style_background('rayures-sup.gif') . '>' ._T('texte_statut_poubelle')."\n";
	echo "<OPTION" . mySel("refuse", $statut_article) . " style='background-color: #FFA4A4'>"._T('texte_statut_refuse')."\n";
	echo "</SELECT>";

	echo " &nbsp; ". http_img_pack("puce-".puce_statut($statut_article).'.gif', "", "border='0' NAME='statut'") . "  &nbsp; ";

	// echo "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></noscript>";
	echo "<span class='visible_au_chargement' id='valider_statut'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</span>";
	echo aide ("artstatut");
	echo "</CENTER>";
	fin_cadre_relief();
	echo "</FORM>";
 }
}
?>