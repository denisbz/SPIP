<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");


if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
  
}

debut_page(_T('titre_page_config_fonctions'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_config_fonctions'));
barre_onglets("configuration", "fonctions");

debut_gauche();
debut_droite();

lire_metas();

echo "<form action='config-fonctions.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Activer/desactiver la creation automatique de vignettes
//



function afficher_choix_vignette($process) {
	//global $taille_preview;
	$taille_preview = 120;

	if ($process == lire_meta('image_process'))
		$border = 2;
	else
		$border=0;

	echo "<td  width='".($taille_preview+4)."'><div align='center' valign='bottom' width='".($taille_preview+4)."'><a href='config-fonctions.php3?image_process=$process'><img src='../spip_image.php3?test_vignette=$process' border='$border' /></a><br />";
	if ($border) echo "<b>$process</b>";
	else echo "$process";
	echo "</div></td>\n";
}


// Si Imagick est present, alors c'est imagick automatiquement


if ($flag_gd OR $flag_imagick OR $convert_command)
	debut_cadre_trait_couleur("image-24.gif");

if ($flag_imagick) {
		$image_process = "imagick";
		ecrire_meta('image_process', 'imagick');
		$formats_graphiques = "gif,jpg,png";
		ecrire_meta('formats_graphiques', 'gif,jpg,png');
		ecrire_metas();
}
else {
	if ($flag_gd OR $convert_command) {
		$formats_graphiques = lire_meta("formats_graphiques");

		debut_cadre_relief("", false, "", _T("info_image_process_titre"));

		echo "<p class='verdana2'>";
		echo _T('info_image_process');
		echo "</p>";

		// application du choix de vignette
		if ($image_process) {
			ecrire_meta('image_process', $image_process);
			ecrire_metas(); // Puisque le switch se fait par lire_meta.
						
			// mettre a jour les formats graphiques lisibles
			switch (lire_meta('image_process')) {
				case 'gd1':
					$formats_graphiques = lire_meta('gd_formats');
					break;
				case 'gd2':
					$formats_graphiques = lire_meta('gd_formats');
					break;
				case 'netpbm':
					$formats_graphiques = lire_meta('netpbm_formats');
					break;
				case 'convert':
					$formats_graphiques = 'gif,jpg,png';
					break;
				case 'imagick':
					$formats_graphiques = 'gif,jpg,png';
					break;
			}
			ecrire_meta('formats_graphiques', $formats_graphiques);
			ecrire_metas();
		}

			echo "<table width='100%' align='center'><tr>";

			// Tester les formats
			if ($flag_gd) {
				$nb_process ++;
				afficher_choix_vignette($p = 'gd1');

				if ($flag_ImageCreateTrueColor) {
					afficher_choix_vignette($p = 'gd2');
					$nb_process ++;
				}
			}

			afficher_choix_vignette($p = 'netpbm');
			$nb_process ++;


			if ($convert_command) {
				afficher_choix_vignette($p = 'convert');
				$nb_process ++;
			}
			
			

			echo "</tr></table>\n";
	
		echo "<p class='verdana2'>";
		echo _T('info_image_process2');
		echo "</p>";
	
	
		fin_cadre_relief();
	}
}


	//
	// Une fois le process choisi, proposer vignettes
	//
	
	$creer_preview = lire_meta("creer_preview");
	$taille_preview = lire_meta("taille_preview");
	if ($taille_preview < 10) $taille_preview = 120;

	if (strlen($formats_graphiques) > 0) {
		debut_cadre_trait_couleur("", false, "", _T('info_generation_miniatures_images'));
		
		echo "<p class='verdana2'>";
		echo _T('info_ajout_image');
		echo "</p>\n";
		echo "<p class='verdana2'>";


		$block = "'block', 'none'"; 
		echo bouton_radio("creer_preview", "oui", _T('item_choix_generation_miniature'), $creer_preview == "oui", "changeVisible(this.checked, 'config-preview', $block);");

		if ($creer_preview == "oui") $style = "display: block;";
		else $style = "display: none;";
	
			echo "<div id='config-preview' class='verdana2' style='$style margin-$spip_lang_left: 40px;'>"._T('info_taille_maximale_vignette');
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
			echo " "._T('info_pixels').'<br /><br /></div>';
			
		$block= "'none', 'block'";
		echo bouton_radio("creer_preview", "non", _T('item_choix_non_generation_miniature'), $creer_preview != "oui", "changeVisible(this.checked, 'config-preview', $block);");
	



/*		afficher_choix('creer_preview', $creer_preview,
			array('non' => _T('item_choix_non_generation_miniature'),
				'oui' => _T('item_choix_generation_miniature')));
		echo "</p>\n";
		*/


		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
		
		fin_cadre_trait_couleur();
	}


if ($flag_gd OR $flag_imagick OR $convert_command)
		fin_cadre_trait_couleur();

echo "<p>";




//
// Indexation pour moteur de recherche
//

debut_cadre_trait_couleur("racine-site-24.gif", false, "", _T('info_moteur_recherche').aide ("confmoteur"));


$activer_moteur = lire_meta("activer_moteur");

	echo "<div class='verdana2'>";
		echo _T('info_question_utilisation_moteur_recherche');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('activer_moteur', $activer_moteur,
		array('oui' => _T('item_utiliser_moteur_recherche'),
			'non' => _T('item_non_utiliser_moteur_recherche')), ' &nbsp; ');
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

fin_cadre_trait_couleur();

echo "<p>";


//
// Activer les statistiques
//

debut_cadre_trait_couleur("statistiques-24.gif", false, "", _T('info_forum_statistiques').aide ("confstat"));

$activer_statistiques = lire_meta("activer_statistiques");

	echo "<div class='verdana2'>";
	echo _T('info_question_gerer_statistiques');
	echo "</div>";

	echo "<div class='verdana2'>";
afficher_choix('activer_statistiques', $activer_statistiques,
	array('oui' => _T('item_gerer_statistiques'),
		'non' => _T('item_non_gerer_statistiques')), ' &nbsp; ');
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

fin_cadre_trait_couleur();

echo "<p>";


//
// Notification de modification des articles
//

if ($options == "avancees") {


debut_cadre_trait_couleur("article-24.gif", false, "", _T('info_travail_colaboratif'));
	$articles_modif = lire_meta("articles_modif");


	echo "<div class='verdana2'>";
	echo _T('texte_travail_collaboratif');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_modif', $articles_modif,
		array('oui' => _T('item_activer_messages_avertissement'),
			'non' => _T('item_non_activer_messages_avertissement')));
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

fin_cadre_trait_couleur();

echo "<p>";
}


//
// Gestion des revisions des articles
//

if ($flag_revisions AND $options == "avancees") {

debut_cadre_trait_couleur("historique-24.gif", false, "", _T('info_historique_titre'));
	$articles_versions = lire_meta("articles_versions");


	echo "<div class='verdana2'>";
	echo _T('info_historique_texte');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_versions', $articles_versions,
		array('oui' => _T('info_historique_activer'),
			'non' => _T('info_historique_desactiver')));
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";
}

//
// Correcteur d'orthographe
//

debut_cadre_trait_couleur("ortho-24.gif", false, "", _T('ortho_orthographe'));
	$articles_ortho = lire_meta("articles_ortho");

	echo "<div class='verdana2'>";
	echo _T('ortho_avis_privacy');
	echo "</div>";

	echo "<div class='verdana2'>";
	echo "<blockquote class='spip'>";
	echo _T('ortho_avis_privacy2');
	echo "</blockquote>\n";
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_ortho', $articles_ortho,
		array('oui' => _T('info_ortho_activer'),
			'non' => _T('info_ortho_desactiver')));
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";


//
// Previsualisation sur le site public
//

debut_cadre_trait_couleur("naviguer-site.png", false, "", _T('previsualisation'));
	$preview = lire_meta("preview");
	# non = personne n'est autorise a previsualiser (defaut)
	# oui = les admins
	# 1comite = admins et redacteurs

	echo "<div class='verdana2'>";
	echo _T('info_preview_texte');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('preview', $preview,
		array('oui' => _T('info_preview_admin'),
			'1comite' => _T('info_preview_comite'),
			'non' => _T('info_preview_desactive')
		)
	);
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
fin_cadre_trait_couleur();

	echo "<p>";




//
// Utilisation d'un proxy pour aller lire les sites syndiques
//

if ($options == 'avancees') {



debut_cadre_trait_couleur("base-24.gif", false, "", _T('info_sites_proxy').aide ("confhttpproxy"));
	$http_proxy=entites_html(lire_meta("http_proxy"));


	echo "<div class='verdana2'>";
	echo _T('texte_proxy');
	echo "</div>";

	echo "<div class='verdana2'>";
	echo "<INPUT TYPE='text' NAME='http_proxy' VALUE='$http_proxy' size='40' class='forml'>";

	if ($http_proxy) {
		echo "<p align='$spip_lang_left'><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>"
			. _T('texte_test_proxy');
		echo "</p>";

		echo "<p>";
		echo "<INPUT TYPE='text' NAME='test_proxy' VALUE='http://www.spip.net/' size='40' class='forml'>";
		echo "</p>";
		echo "<div style='text-align: $spip_lang_right;'><INPUT TYPE='submit' NAME='tester_proxy' VALUE='"._T('bouton_test_proxy')."' CLASS='fondo'></div>";

	}


	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
fin_cadre_trait_couleur();
	echo "<p>";
}



//
// Creer fichier .htpasswd ?
//

if ($options == "avancees" AND !@file_exists(_ACCESS_FILE_NAME) AND !$REMOTE_USER ) {
	include_ecrire ("inc_acces.php3");
	ecrire_acces();



debut_cadre_trait_couleur("cadenas-24.gif", false, "", 
			  _T('info_fichiers_authent'));

	$creer_htpasswd = lire_meta("creer_htpasswd");

	echo "<div class='verdana2'>", _T('texte_fichier_authent'), "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('creer_htpasswd', $creer_htpasswd,
		       array('oui' => _T('item_creer_fichiers_authent'),
			     'non' =>  _T('item_non_creer_fichiers_authent')),
		       ' &nbsp; ');
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
fin_cadre_trait_couleur();

	echo "<p>";
}


/*
###### PAS D'INTERFACE DE CONFIG POUR PROTEGER "/IMG"
//
// Creer fichier .htaccess dans les repertoires de documents 
//

if ($options == "avancees" AND !$REMOTE_USER ) {

debut_cadre_trait_couleur("cadenas-24.gif", false, "", 
			  _L("Acc&egrave;s aux document joints par leur URL"));
#	include_ecrire ("inc_acces.php3"); vient d'etre fait
	$creer_htaccess = gerer_htaccess();

	echo "<div class='verdana2'>";
	echo _L("Cette option interdit la lecture des documents joints si le texte auquel ils se rattachent n'est pas publi&eacute");
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('creer_htaccess', $creer_htaccess,
		       array('oui' => _L("interdire la lecture"),
			     'non' => _L("autoriser la lecture")),
		       ' &nbsp; ');
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
fin_cadre_trait_couleur();

	echo "<p>";
}
*/


echo "</form>";

fin_page();

?>
