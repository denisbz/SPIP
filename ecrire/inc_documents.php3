<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_DOCUMENTS")) return;
define("_ECRIRE_INC_DOCUMENTS", "1");

include_ecrire ("inc_objet.php3");
include_ecrire ("inc_admin.php3");


//
// Vignette pour les documents lies
//

function vignette_par_defaut($type_extension) {
	if ($GLOBALS['flag_ecrire'])
		$img = "../IMG/icones";
	else
		$img = "IMG/icones";

	$filename = "$img/$type_extension";

	// Glurps !
	if (@file_exists($filename.'.png')) {
		$vig = "$filename.png";
	}
	else if (@file_exists($filename.'.gif')) {
		$vig = "$filename.gif";
	}
	else if (@file_exists($filename.'-dist.png')) {
		$vig = "$filename-dist.png";
	}
	else if (@file_exists($filename.'-dist.gif')) {
		$vig = "$filename-dist.gif";
	}
	else if (@file_exists("$img/defaut.png")) {
		$vig = "$img/defaut.png";
	}
	else if (@file_exists("$img/defaut.gif")) {
		$vig = "$img/defaut.gif";
	}
	else if (@file_exists("$img/defaut-dist.png")) {
		$vig = "$img/defaut-dist.png";
	}
	else if (@file_exists("$img/defaut-dist.gif")) {
		$vig = "$img/defaut-dist.gif";
	}

	if ($size = @getimagesize($vig)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($vig, $largeur, $hauteur);
}


//
// Integration (embed) multimedia
//

function embed_document($id_document, $les_parametres="", $afficher_titre=true) {
	global $id_doublons;

	$id_doublons['documents'] .= ",$id_document";

	if ($les_parametres) {
		$parametres = explode("|",$les_parametres);

		for ($i = 0; $i < count($parametres); $i++) {
			$parametre = $parametres[$i];
			
			if (eregi("^left|right|center$", $parametre)) {
				$align = $parametre;
			}
			else {
				$params[] = $parametre;
			}
		}
	}

	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = propre($row ['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = generer_url_document($id_document);
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];


		$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
		$result_type = spip_query($query_type);
		if ($row_type = @spip_fetch_array($result_type)) {
			$type = $row_type['titre'];
			$inclus = $row_type['inclus'];
			$extension = $row_type['extension'];
		}
		else $type = 'fichier';

		// Pour RealVideo
		if ((!ereg("^controls", $les_parametres)) AND (ereg("^(rm|ra|ram)$", $extension))) {
			$real = true;
		}

		if ($inclus == "embed" AND !$real) {
		
				for ($i = 0; $i < count($params); $i++) {
					if (ereg("([^\=]*)\=([^\=]*)", $params[$i], $vals)){
						$nom = $vals[1];
						$valeur = $vals[2];
						$inserer_vignette .= "<param name='$nom' value='$valeur'>";
						$param_emb .= " $nom='$valeur'";
						if ($nom == "controls" AND $valeur == "PlayButton") { 
							$largeur = 40;
							$hauteur = 25;
						}
						else if ($nom == "controls" AND $valeur == "PositionSlider") { 
							$largeur = $largeur - 40;
							$hauteur = 25;
						}
					}
				}
				
				$vignette = "<object width='$largeur' height='$hauteur'>";
				$vignette .= "<param name='movie' value='$fichier'>";
				$vignette .= "<param name='src' value='$fichier'>";
				$vignette .= $inserer_vignette;
		
				$vignette .= "<embed src='$fichier' $param_emb width='$largeur' height='$hauteur'></embed></object>";
		
		}
		else if ($inclus == "embed" AND $real) {
			$vignette .= embed_document ($id_document, "controls=ImageWindow|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= "<br />";
			$vignette .= embed_document ($id_document, "controls=PlayButton|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= embed_document ($id_document, "controls=PositionSlider|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
		}
		else if ($inclus == "image") {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
			if ($fichier_vignette) {
				$vignette = "<img src='$fichier_vignette' border=0";
				if ($largeur_vignette && $hauteur_vignette)
					$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
				if ($titre) {
					$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
					$titre_ko = supprimer_tags(propre($titre_ko));
					$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
				}
				$vignette .= ">";
			}
		}
		
		if ($afficher_titre) {
			if ($largeur_vignette < 120) $largeur_vignette = 120;
			$forcer_largeur = " width = '$largeur_vignette'";

			$retour = "<table cellpadding=0 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr>";
			if ($align == "right") $retour .= "<td width='10'> &nbsp; </td>";
			$retour .= "<td align='center'$forcer_largeur>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<div style='text-align: center;'><b>$titre</b></div>";
			
			if ($descriptif) $retour .= "<div style='text-align: left;'>$descriptif</div>";
			

			$retour .= "</div>\n</td>";
			if ($align == "left") $retour .= "<td width='10'> &nbsp; </td>";
			$retour .= "</tr>\n</table>\n";
		}
		else {
			$retour = $vignette;
		}

		return $retour;		

	}
}


//
// Integration des images et documents
//

function integre_image($id_document, $align, $type_aff = 'IMG') {
	global $id_doublons;
	global $flag_ecrire;

	$id_doublons['documents'] .= ",$id_document";

	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = typo($row['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$url_fichier = generer_url_document($id_document);
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];
		$id_vignette = $row['id_vignette'];

		// type d'affichage : IMG, DOC
		$affichage_detaille = (strtoupper($type_aff) == 'DOC');

		// on construira le lien en fonction du type de doc
		$result_type = spip_query("SELECT * FROM spip_types_documents WHERE id_type = $id_type");
		if ($type = @spip_fetch_object($result_type)) {
			$extension = $type->extension;
		}

		// recuperer la vignette pour affichage inline
		if ($id_vignette) {
			$query_vignette = "SELECT * FROM spip_documents WHERE id_document = $id_vignette";
			$result_vignette = spip_query($query_vignette);
			if ($row_vignette = @spip_fetch_array($result_vignette)) {
				$fichier_vignette = $row_vignette['fichier'];
				$url_fichier_vignette = generer_url_document($id_vignette);
				$largeur_vignette = $row_vignette['largeur'];
				$hauteur_vignette = $row_vignette['hauteur'];

				// verifier l'existence du fichier correspondant
				$path = ($flag_ecrire?'../':'') . $fichier_vignette;
				if (!@file_exists($path) AND (!$flag_ecrire OR !@file_exists('../IMG/test.jpg')))
					$url_fichier_vignette = '';
			}
		}
		else if ($mode == 'vignette') {
			$url_fichier_vignette = $url_fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
		}

		// si pas de vignette, utiliser la vignette par defaut
		// ou essayer de creer une preview (images)
		if (!$url_fichier_vignette) {
			if (!ereg(",$extension,", ','.lire_meta('formats_graphiques').',')) {
				list($url_fichier_vignette, $largeur_vignette, $hauteur_vignette) = vignette_par_defaut($extension);
			} else {
				$url_fichier_vignette = ($flag_ecrire?'../':'').'spip_image.php3?vignette='.rawurlencode(str_replace('../', '', $fichier));
			}
		}

		if ($type_aff=='fichier_vignette')
			return $url_fichier_vignette;

		if ($url_fichier_vignette) {
			$vignette = "<img src='$url_fichier_vignette' border=0";
			if ($largeur_vignette && $hauteur_vignette)
				$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
			if ($titre) {
				$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
				$titre_ko = supprimer_tags(propre($titre_ko));
				$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
			}
			if ($affichage_detaille)
				$vignette .= ">";
			else {
				if ($align)
					$vignette .= " align='$align' hspace='5' vspace='3'>";
				else
					$vignette .= " align='middle'>";
				if ($align == 'center') $vignette = "<p align='center'>$vignette</p>";
			}
		}

		if ($mode == 'document')
			$vignette = "<a href='$url_fichier'>$vignette</a>";

		// si affichage detaille ('DOC'), ajouter une legende
		if (strtoupper($type_aff) == 'DOC') {
			$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
			$result_type = spip_query($query_type);
			if ($row_type = @spip_fetch_array($result_type)) {
				$type = $row_type['titre'];
			}
			else $type = 'fichier';
			
			if ($largeur_vignette < 120) $largeur_vignette = 120;
			$forcer_largeur = " width = '$largeur_vignette'";

			$retour = "<table cellpadding='0' cellspacing='0' border='0' align='$align'>\n";
			$retour .= "<tr>";
			if ($align == "right") $retour .= "<td width='10'> &nbsp; </td>";
			
			$retour .= "<td align='center'$forcer_largeur>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<div style='text-align: center;'><b>$titre</b></div>";
			if ($descriptif) $retour .= "<div style='text-align:left;'>$descriptif</div>";

			if ($mode == 'document')
				$retour .= "<div>(<a href='$url_fichier'>$type, ".taille_en_octets($taille)."</a>)</div>";

			$retour .= "</div>\n</td>";
			if ($align == "left") $retour .= "<td width='10'> &nbsp; </td>";

			$retour .= "</tr>\n</table>\n";
		}
		else $retour = $vignette;
	}
	return $retour;
}

//
// Parcourt recursivement le repertoire upload/ (ou tout autre repertoire, d'ailleurs)
//
function fichiers_upload($dir) {
	$fichiers = array();
	$d = opendir($dir);

	while ($f = readdir($d)) {
		if (is_file("$dir/$f") AND $f != 'remove.txt') {
			$fichiers[] = "$dir/$f";
		}
		else
		if (is_dir("$dir/$f") AND $f != '.' AND $f != '..') {
			$fichiers_dir = fichiers_upload("$dir/$f");
			while (list(,$f2) = each ($fichiers_dir))
				$fichiers[] = $f2;
		}

	}
	closedir($d);

	sort($fichiers);

	return $fichiers;
}

//
// Retourner le code HTML d'utilisation de fichiers uploades a la main
//

function texte_upload_manuel($dir, $inclus = '') {
	$fichiers = fichiers_upload($dir);
	$exts = array();

	while (list(, $f) = each($fichiers)) {
		$f = ereg_replace("^$dir/","",$f);
		if (ereg("\.([^.]+)$", $f, $match)) {
			$ext = strtolower($match[1]);
			if (!$exts[$ext]) {
				if ($ext == 'jpeg') $ext = 'jpg';
				$req = "SELECT extension FROM spip_types_documents WHERE extension='$ext'";
				if ($inclus) $req .= " AND inclus='$inclus'";
				if (@spip_fetch_array(spip_query($req))) $exts[$ext] = 'oui';
				else $exts[$ext] = 'non';
			}
			
			$ledossier = substr($f, 0, strrpos($f,"/"));
			if (strlen($ledossier) > 0) $ledossier = "$ledossier";
			$lefichier = substr($f, strrpos($f, "/"), strlen($f));
			
			if ($ledossier != $ledossier_prec) {
				$texte_upload .= "\n<option value=\"$ledossier\" style='font-weight: bold;'>Tout le dossier $ledossier</option>";
			}
			
			$ledossier_prec = $ledossier;
			
			if ($exts[$ext] == 'oui') $texte_upload .= "\n<option value=\"$f\">&nbsp; &nbsp; &nbsp; &nbsp; $lefichier</option>";
		}
	}

	return $texte_upload;
}


function texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, $fichier_document) {
	if ($largeur_vignette > 120) {
		$rapport = 120.0 / $largeur_vignette;
		$largeur_vignette = 120;
		$hauteur_vignette = ceil($hauteur_vignette * $rapport);
	}
	if ($hauteur_vignette > 110) {
		$rapport = 110.0 / $hauteur_vignette;
		$hauteur_vignette = 110;
		$largeur_vignette = ceil($largeur_vignette * $rapport);
	}

	if ($fichier_document)
		return "<a href='$fichier_document'><img src='$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette' align='top'></a>\n";
	else
		return "<img src='$fichier_vignette' border='0' height='$hauteur_vignette' width='$largeur_vignette' align='top'>\n";
}


//
// Afficher un formulaire d'upload
//

function afficher_upload($link, $intitule, $inclus = '', $afficher_texte_ftp = true, $forcer_document = false, $dossier_complet = false) {
	global $clean_link, $connect_statut, $connect_toutes_rubriques, $options;
	static $num_form = 0;

	if (!$link->getVar('redirect'))
		$link->addVar('redirect', $clean_link->getUrl());
	if ($forcer_document)
		$link->addVar('forcer_document', 'oui');


	echo $link->getForm('POST', 'docs', 'multipart/form-data');

	if (tester_upload()) {
		echo "<div>$intitule</div>";
		echo "<div><input name='image' type='File' style='font-size: 10px;' class='forml' size='15'></div>\n";
		echo "<div align='".$GLOBALS['spip_lang_right']."'><input name='ok' type='Submit' VALUE='"._T('bouton_telecharger')."' CLASS='fondo'></div>\n";
	}

	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques AND $options == "avancees") {
		$texte_upload = texte_upload_manuel("upload", $inclus);
		if ($texte_upload AND $afficher_texte_ftp) {
			echo "<p><div style='color: #505050;'>";
			if ($forcer_document) echo '<input type="hidden" name="forcer_document" value="oui">';
			echo "\n"._T('info_selectionner_fichier')."&nbsp;:<br />";
			echo "\n<select name='image2' size='1' class='fondl'>";
			echo $texte_upload;
			echo "\n</select>";
			echo "\n  <div align='".$GLOBALS['spip_lang_right']."'><input name='ok' type='Submit' value='"._T('bouton_choisir')."' class='fondo'></div>";

			/*
			if ($afficher_texte_ftp){
				if ($dossier_complet){
					echo "\n<p><b>"._T('info_portfolio_automatique')."</b>";
					echo "\n<br />"._T('info_installer_documents');
					echo "\n<div align='".$GLOBALS['spip_lang_right']."'><input name='dossier_complet' type='Submit' value='"._T('info_installer_tous_documents')."' class='fondo'></div>";
				}
			}
			*/
			echo "</div>\n";
		}
		else if ($afficher_texte_ftp) {
			echo "<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>";
			echo _T('info_installer_ftp').aide("ins_upload");
			echo "</div>";
		}
	}
	echo "</form>\n";
}




//
// Afficher les documents non inclus
// (page des articles)

function afficher_documents_non_inclus($id_article, $type = "article", $flag_modif) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;
	global $id_doublons, $options;
	global $spip_lang_left, $spip_lang_right;

	$image_link = new Link('../spip_image.php3');
	if ($id_article) $image_link->addVar('id_article', $id_article);
	if ($type == "rubrique") $image_link->addVar('modifier_rubrique','oui');

	if ($GLOBALS['id_document'] > 0) {
		$id_document_deplie = $GLOBALS['id_document'];
	}
	if (!$redirect_url) $redirect_url = $clean_link->getUrl();

	// Afficher portfolio
	/////////

	$query = "SELECT docs.* FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes ".
		"WHERE l.id_$type=$id_article AND l.id_document=docs.id_document ".
		"AND docs.mode='document'".
		" AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png')";

	if ($id_doublons['documents']) $query .= " AND docs.id_document NOT IN (".$id_doublons['documents'].") ";
	$query .= " ORDER BY docs.id_document";

	$images_liees = fetch_document($query);

	if ($images_liees) {

		$case = "0";
		echo "<a name='portfolio'></a>";
		echo "<div>&nbsp;</div>";
		echo "<div style='background-color: $couleur_claire; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'><b>PORTFOLIO</b></div>";
		echo "<table width='100%' cellspacing='0' cellpadding='3'>";
		reset($images_liees);
		while (list(, $id_document) = each($images_liees)) {
			
			$flag_deplier = ($id_document_deplie == $id_document);
			
			if ($case == 0) {
				echo "<tr style='border-top: 1px solid black;'>";
			}
			
			$style = "border-left: 1px solid $couleur_claire; border-bottom: 1px solid $couleur_claire;";
			if ($case == 2) $style .= " border-right: 1px solid $couleur_claire;";
			echo "<td width='33%' style='text-align: $spip_lang_left; $style' valign='top'>";
			//afficher_horizontal_document($id_document, $image_link, $redirect_url, $flag_modif);
			
			
			$document = fetch_document($id_document);
		
			$id_vignette = $document->get('id_vignette');
			$id_type = $document->get('id_type');
			$titre = $document->get('titre');
			$descriptif = $document->get('descriptif');
			$fichier = generer_url_document($id_document);
			$fichier = substr($fichier, 3, strlen($fichier));
			$largeur = $document->get('largeur');
			$hauteur = $document->get('hauteur');
			$taille = $document->get('taille');
			$date = $document->get('date');
			$mode = $document->get('mode');
			
					
			echo "<div style='text-align:center;'>";
			if ($flag_modif) {
			
				if ($id_vignette == 0) {
					echo "<div class='bouton_rotation' style='float: left;'>";		
					echo bouton_block_invisible("gerer_vignette$id_document");	
					echo "</div>";
					$vignette_perso = false;

				} else {
					$link = $image_link;
					$link->addVar('redirect', $redirect_url);
					$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
					$link->addVar('hash_id_auteur', $connect_id_auteur);
					$link->addVar('doc_supp', $id_vignette);
					echo "<div style='float: left;'>";		
					echo "<a ".$link->getHref($ancre)." title=\""._T('info_supprimer_vignette')."\" class='bouton_rotation'><img src='img_pack/croix-rouge.gif' border='0' /></a>";	
					echo "</div>";
					$vignette_perso = true;
				
				}
				
			
			
			
				$process = lire_meta('image_process');
				 // imagick (php4-imagemagick)
				 if ($process == 'imagick' OR $process == 'gd2' OR $process == 'convert') {
					echo "<div class='verdana1' style='float: right; text-align:left;'>";		
					$link_rot = $image_link;
					$link_rot->addVar('redirect', $redirect_url);
					$link_rot->addVar('hash', calculer_action_auteur("rotate ".$id_document));
					$link_rot->addVar('hash_id_auteur', $connect_id_auteur);
					$link_rot->addVar('doc_rotate', $id_document);
					$link_rot->addVar('vignette_aff', $id_document);
					$link_rot->addVar('var_rot', -90);
					echo "<a href='".$link_rot->getUrl("portfolio")."' class='bouton_rotation'><img src='img_pack/tourner-gauche.gif' border='0' /></a> ";
					echo "<br />";
					$link_rot = $image_link;
					$link_rot->addVar('redirect', $redirect_url);
					$link_rot->addVar('hash', calculer_action_auteur("rotate ".$id_document));
					$link_rot->addVar('hash_id_auteur', $connect_id_auteur);
					$link_rot->addVar('doc_rotate', $id_document);
					$link_rot->addVar('vignette_aff', $id_document);
					$link_rot->addVar('var_rot', 90);
					echo "<a href='".$link_rot->getUrl("portfolio")."' class='bouton_rotation'><img src='img_pack/tourner-droite.gif' border='0' /></a> ";
					echo "<br />";
	
					$link_rot = $image_link;
					$link_rot->addVar('redirect', $redirect_url);
					$link_rot->addVar('hash', calculer_action_auteur("rotate ".$id_document));
					$link_rot->addVar('hash_id_auteur', $connect_id_auteur);
					$link_rot->addVar('doc_rotate', $id_document);
					$link_rot->addVar('vignette_aff', $id_document);
					$link_rot->addVar('var_rot', 180);
					echo "<a href='".$link_rot->getUrl("portfolio")."' class='bouton_rotation'><img src='img_pack/tourner-180.gif' border='0' /></a>";
					echo "</div>";
				}
			}
			//
			// Recuperer la vignette
			//
			if ($id_vignette >0) {
				$vignette = fetch_document($id_vignette);
			
				if ($vignette) {
					$fichier_vignette = generer_url_document($id_vignette);
					$largeur_vignette = $vignette->get('largeur');
					$hauteur_vignette = $vignette->get('hauteur');
					$taille_vignette = $vignette->get('taille');
			
			
					echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "../$fichier");
				}
			}
			else {
				$fichier_vignette = "../spip_image.php3?vignette=$fichier";
				echo "<a href='../$fichier'><img src='$fichier_vignette' border='0'></a>";
			}
				echo "</div>";
					
					
			if ($flag_modif) {
					
				if ($vignette_perso) {
				}
				else {
						echo debut_block_invisible("gerer_vignette$id_document");
						echo "<div class='verdana1' style='color: $couleur_foncee; border: 1px solid $couleur_foncee; padding: 5px; margin-top: 3px; text-align: left; background-color: white;'>";
						$link = $image_link;
						$link->addVar('redirect', $redirect_url);
						$link->addVar('hash', calculer_action_auteur("ajout_doc"));
						$link->addVar('hash_id_auteur', $connect_id_auteur);
						$link->addVar('ajout_doc', 'oui');
						$link->addVar('id_document', $id_document);
						$link->addVar('mode', 'vignette');	
						afficher_upload($link, _T('info_remplacer_vignette'), 'image', false);
						echo "</div>";		
						echo fin_block();						
				}
				
				echo "</div>";		
			}
			
					
						
			
			if ($flag_modif) {
				if ($flag_deplier) $triangle = bouton_block_visible("port$id_document");
				else $triangle =  bouton_block_invisible("port$id_document");
			}
			if (strlen($titre) > 0) {
				echo "<div class='verdana2'><b>$triangle".propre($titre)."</b></div>";
			} else {
				$nom_fichier = substr($fichier, strrpos($fichier, "/")+1, strlen($fichier));
				echo "<div class='verdana1'>$triangle$nom_fichier</div>";
			}
			
			if (strlen($descriptif) > 0) {
				echo "<div class='verdana1'>".propre($descriptif)."</div>";
			}
			echo "<div class='verdana1' style='text-align: center;'>$largeur x $hauteur pixels</div>";
			
			
			
			if ($flag_modif) {
				if ($flag_deplier) echo debut_block_visible("port$id_document");
				else echo debut_block_invisible("port$id_document");
				echo "<div class='verdana1' style='color: $couleur_foncee; border: 1px solid $couleur_foncee; padding: 5px; margin-top: 3px;'>";
				$link = new Link($redirect_url);
				$link->addVar('modif_document', 'oui');
				$link->addVar('id_document', $id_document);
				$ancre = "portfolio";
				if ($flag_modif) {
					echo $link->getForm('POST', $ancre);
			
					echo "<b>"._T('titre_titre_document')."</b><br />\n";
					echo "<input type='text' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='titre_document' class='formo' style='font-size:11px;' value=\"".entites_html($titre)."\" size='40'><br />";
			
					if ($GLOBALS['coll'] > 0 AND $options == "avancees") {
						if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
							$mois = $regs[2];
							$jour = $regs[3];
							$annee = $regs[1];
						}
						echo "<b>"._T('info_mise_en_ligne')."</b><br />\n";
						echo "<SELECT NAME='jour_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_jour($jour);
						echo "</SELECT>";
						echo "<SELECT NAME='mois_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_mois($mois);
						echo "</SELECT>";
						echo "<SELECT NAME='annee_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_annee($annee);
						echo "</SELECT><br />";
					}
			
					if ($options == "avancees") {
						echo "<b>"._T('info_description')."</b><br />\n";
						echo "<textarea name='descriptif_document' rows='4' class='forml' style='font-size:10px;' cols='*' wrap='soft' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						echo entites_html($descriptif);
						echo "</textarea>\n";
					} else {
						echo "<input type='hidden' name='descriptif_document' value='".entites_html($descriptif)."' />\n";
					}
								
					echo "<div class='display_au_chargement' id='valider_doc$id_document' align='".$GLOBALS['spip_lang_right']."'>";
					echo "<input TYPE='submit' class='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
					echo "</div>";
					echo "</form>";
				}
				echo "</div>";
				
	

				
				$link_supp = $image_link;
				$link_supp->addVar('redirect', $redirect_url);
				$link_supp->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
				$link_supp->addVar('hash_id_auteur', $connect_id_auteur);
				$link_supp->addVar('doc_supp', $id_document);
		
				icone_horizontale(_T('icone_supprimer_document'), $link_supp->getUrl(), "doc-24.gif", "supprimer.gif");
				
				
				echo fin_block();
			}
			
			echo "</td>\n";
			$case ++;
			
			if ($case == 3) {
				$case = 0;
				echo "</tr>\n";
			}
			
			$id_doublons['documents'] .= ",$id_document";
		}
		if ($case > 0) {
			echo "<td style='border-left: 1px solid $couleur_claire;'>&nbsp;</td>";
			echo "</tr>";
		}

		echo "</table>";
	}



	//// Documents associes
	$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
		"WHERE l.id_$type=$id_article AND l.id_document=docs.id_document ".
		"AND docs.mode='document'";

	if ($id_doublons['documents']) $query .= " AND docs.id_document NOT IN (".$id_doublons['documents'].") ";
	$query .= " ORDER BY docs.id_document";

	$documents_lies = fetch_document($query);


	if ($documents_lies) {

		$case = "0";
		echo "<a name='docs'></a>";
		echo "<div>&nbsp;</div>";
		echo "<div style='background-color: #aaaaaa; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'><b>DOCUMENTS</b></div>";
		echo "<table width='100%' cellspacing='0' cellpadding='5'>";
		reset($documents_lies);
		while (list(, $id_document) = each($documents_lies)) {
			
			$flag_deplier = ($id_document_deplie == $id_document);
			
			if ($case == 0) {
				echo "<tr style='border-top: 1px solid black;'>";
			}
			
			$style = "border-left: 1px solid #aaaaaa; border-bottom: 1px solid #aaaaaa;";
			if ($case == 1) $style .= " border-right: 1px solid #aaaaaa;";
			echo "<td width='50%' style='text-align: $spip_lang_left; $style' valign='top'>";
			//afficher_horizontal_document($id_document, $image_link, $redirect_url, $flag_modif);
			
			
			$document = fetch_document($id_document);
			$id_vignette = $document->get('id_vignette');
			$id_type = $document->get('id_type');
			$titre = $document->get('titre');
			$descriptif = $document->get('descriptif');
			$fichier = generer_url_document($id_document);
			$fichier = substr($fichier, 3, strlen($fichier));
			$largeur = $document->get('largeur');
			$hauteur = $document->get('hauteur');
			$taille = $document->get('taille');
			$date = $document->get('date');
			$mode = $document->get('mode');
		
			$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
			if ($type_doc = @spip_fetch_array($result))	{
				$type_extension = $type_doc['extension'];
				$type_inclus = $type_doc['inclus'];
				$type_titre = $type_doc['titre'];
			}



			//
			// Recuperer la vignette
			//
			$vignette = "";
			$fichier_vignette = "";
			
		
			if ($id_vignette > 0) {
				$vignette = fetch_document($id_vignette);
			
				if ($vignette) {
					$fichier_vignette = generer_url_document($id_vignette);
					$largeur_vignette = $vignette->get('largeur');
					$hauteur_vignette = $vignette->get('hauteur');
					$taille_vignette = $vignette->get('taille');
				}
				echo "<div style='text-align:center;'>";
				echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "../$fichier");
				echo "</div>";
			}
			else {
				$fichier_vignette = "../spip_image.php3?vignette=$fichier";
				echo "<div style='text-align: center;'><a href='../$fichier'><img src='$fichier_vignette' border='0'></a></div>";
			}
			
			
			if ($flag_modif) {
				if ($flag_deplier) $triangle = bouton_block_visible("port$id_document");
				else $triangle =  bouton_block_invisible("port$id_document");
			}
			if (strlen($titre) > 0) {
				echo "<div class='verdana2'><b>$triangle".propre($titre)."</b></div>";
			} else {
				$nom_fichier = substr($fichier, strrpos($fichier, "/")+1, strlen($fichier));
				echo "<div class='verdana1'>$triangle$nom_fichier</div>";
			}
			
			if (strlen($descriptif) > 0) {
				echo "<div class='verdana1'>".propre($descriptif)."</div>";
			}
			echo "<div class='verdana1' style='text-align: center;'>".taille_en_octets($taille)."</div>";
			if ($largeur > 0 AND $hauteur > 0) echo "<div class='verdana1' style='text-align: center;'>$largeur x $hauteur pixels</div>";
			
			
			if ($flag_modif) {
				if ($flag_deplier) echo debut_block_visible("port$id_document");
				else echo debut_block_invisible("port$id_document");
				echo "<div class='verdana1' style='color: #999999; border: 1px solid #999999; padding: 5px; margin-top: 3px;'>";
				$link = new Link($redirect_url);
				$link->addVar('modif_document', 'oui');
				$link->addVar('id_document', $id_document);
				$ancre = "docs";
				if ($flag_modif) {
					echo $link->getForm('POST', $ancre);
			
					echo "<b>"._T('titre_titre_document')."</b><br />\n";
					echo "<input type='text' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='titre_document' class='formo' style='font-size:11px;' value=\"".entites_html($titre)."\" size='40'><br />";
			
					if ($GLOBALS['coll'] > 0 AND $options == "avancees") {
						if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
							$mois = $regs[2];
							$jour = $regs[3];
							$annee = $regs[1];
						}
						echo "<b>"._T('info_mise_en_ligne')."</b><br />\n";
						echo "<SELECT NAME='jour_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_jour($jour);
						echo "</SELECT>";
						echo "<SELECT NAME='mois_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_mois($mois);
						echo "</SELECT>";
						echo "<SELECT NAME='annee_doc' SIZE=1 CLASS='fondl' style='font-size:9px;' onChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
						afficher_annee($annee);
						echo "</SELECT><br />";
					}
			
					if ($options == "avancees") {
						echo "<b>"._T('info_description')."</b><br />\n";
						echo "<textarea onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='descriptif_document' rows='4' class='forml' style='font-size:10px;' cols='*' wrap='soft'>";
						echo entites_html($descriptif);
						echo "</textarea><br />\n";
					} else {
						echo "<input type='hidden' name='descriptif_document' value='".entites_html($descriptif)."' /><br />\n";
					}

					if ($type_inclus == "embed" OR $type_inclus == "image") {
						echo "<b>"._T('info_dimension')."</b><br />\n";
						echo "<input type='text' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='largeur_document' class='fondl' style='font-size:9px;' value=\"$largeur\" size='5'>";
						echo " x <input type='text' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='hauteur_document' class='fondl' style='font-size:9px;' value=\"$hauteur\" size='5'> "._T('info_pixels');
					} else {
						echo "<input type='hidden' name='largeur_document' value=\"$largeur\" />\n";
						echo "<input type='hidden' name='hauteur_document' value=\"$hauteur\" /><br >\n";
					}
			
					echo "<div class='display_au_chargement' id='valider_doc$id_document' align='".$GLOBALS['spip_lang_right']."'>";
					echo "<input TYPE='submit' class='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
					echo "</div>";
					echo "</form>";
				}
				echo "</div>";
				
				
				$link_supp = $image_link;
				$link_supp->addVar('redirect', $redirect_url);
				$link_supp->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
				$link_supp->addVar('hash_id_auteur', $connect_id_auteur);
				$link_supp->addVar('doc_supp', $id_document);
		
				icone_horizontale(_T('icone_supprimer_document'), $link_supp->getUrl(), "doc-24.gif", "supprimer.gif");
				
				
				echo fin_block();
			}
			
			echo "</td>\n";
			$case ++;
			
			if ($case == 2) {
				$case = 0;
				echo "</tr>\n";
			}
			
			$id_doublons['documents'] .= ",$id_document";
		}
		if ($case > 0) {
			echo "<td style='border-left: 1px solid #aaaaaa;'>&nbsp;</td>";
			echo "</tr>";
		}

		echo "</table>";
	}


	if (lire_meta("documents_$type") != 'non' AND $flag_modif) {
		/// Ajouter nouveau document/image

		echo "<div align='right'>";
		echo "<table width='50%' cellpadding=0 cellspacing=0 border=0><tr><td style='text-align:left;'>";
		
		echo debut_cadre_relief("image-24.gif", false, "", _T('titre_joindre_document'));
		
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		$link->addVar('type', $type);

		afficher_upload($link, "", '', true, true, true);
		
		echo fin_cadre_relief();
		
		
		echo "</td></tr></table>";
		echo "</div>";
	}

	/*
	if (lire_meta("documents_$type") != 'non' AND $flag_modif) {
		/// Ajouter nouveau document/image

		echo debut_cadre_enfonce("doc-24.gif",false,"creer.gif");
		echo "<div style='padding: 2px; background-color: $couleur_claire; text-align: ".$GLOBALS['spip_lang_left']."; color: black;'>";
		echo bouton_block_invisible("ajouter_document");
		if ($type == "rubrique") echo "<b><font size=1>"._T('titre_publier_document')."</font></b>".aide("ins_doc");
		else echo "<b><font size=1>"._T('titre_joindre_document')."</font></b>".aide("ins_doc");
		echo "</div>\n";
		echo debut_block_invisible("ajouter_document");

		echo "<p><table width='100%' cellpadding=0 cellspacing=0 border=0>";
		echo "<tr>";
		echo "<td width='200' valign='top' class='verdana2'>";

		if ($type == "article")
			echo _T('info_joindre_document_article')."&nbsp;: ";
		else if ($type == "rubrique")
			echo _T('info_joindre_document_rubrique')."&nbsp;: ";
		$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
		$result_types_docs = spip_query($query_types_docs);

		$extension = '';
		while ($row = spip_fetch_array($result_types_docs)) {
			if ($extension) echo ", ";
			$extension = $row['extension'];
			echo $extension;
		}
		echo ".";

		if (lire_meta("creer_preview") == 'oui') {
			$taille_preview = lire_meta("taille_preview");
			$formats_graphiques = lire_meta("formats_graphiques");
			if ($taille_preview < 15) $taille_preview = 120;
			echo "<p>"._T('texte_creation_automatique_vignette', array('gd_formats' => $formats_graphiques, 'taille_preview' => $taille_preview));
		}
		echo "</td><td width=20>&nbsp;</td>";
		echo "<td valign='top'><font face='Verdana,Arial,Sans,sans-serif' size=2>";
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		$link->addVar('type', $type);

		afficher_upload($link, _T('info_telecharger'), '', true, true, true);

		echo "</font>\n";
		echo "</td></tr></table>";
		echo fin_block();
		fin_cadre_enfonce();
	}
	*/
}



//
// Afficher un document sous forme de ligne horizontale
//

function afficher_horizontal_document($id_document, $image_link, $redirect_url = "", $flag_modif) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;
	global $options, $id_document_deplie;

	if ($GLOBALS['id_document']) $id_document_deplie = $GLOBALS['id_document'];
	if ($id_document == $id_document_deplie) $flag_deplie = true;

	if (!$redirect_url) $redirect_url = $clean_link->getUrl();
	$ancre = 'doc'.$id_document;

	$document = fetch_document($id_document);

	$id_vignette = $document->get('id_vignette');
	$id_type = $document->get('id_type');
	$titre = $document->get('titre');
	$descriptif = $document->get('descriptif');
	$fichier = generer_url_document($id_document);
	$largeur = $document->get('largeur');
	$hauteur = $document->get('hauteur');
	$taille = $document->get('taille');
	$date = $document->get('date');
	$mode = $document->get('mode');
	
	if ($mode != 'document') return;

	if (!$titre) {
		$titre_aff = ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier);
	} else {
		$titre_aff = $titre;
	}

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($type = @spip_fetch_array($result))	{
		$type_extension = $type['extension'];
		$type_inclus = $type['inclus'];
		$type_titre = $type['titre'];
	}

	echo "<a name='$ancre'></a>";
	debut_cadre_enfonce("doc-24.gif");
	echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: left; color: black;'>";
	if ($flag_deplie) echo bouton_block_visible("doc_vignette $id_document,document $id_document");
	else echo bouton_block_invisible("doc_vignette $id_document,document $id_document");

	echo "<font size=1 face='arial,helvetica,sans-serif'>"._T('info_document')." - </font> <b><font size=2>".typo($titre_aff)."</font></b>";
	echo "</div>\n";


	//
	// Recuperer la vignette
	//
	if ($id_vignette) $vignette = fetch_document($id_vignette);
	if ($vignette) {
		$fichier_vignette = generer_url_document($id_vignette);
		$largeur_vignette = $vignette->get('largeur');
		$hauteur_vignette = $vignette->get('hauteur');
		$taille_vignette = $vignette->get('taille');
	}

	echo "<p></p><div style='border: 1px dashed #666666; padding: 5px; background-color: #f0f0f0;'>";
	if ($fichier_vignette) {
		// Afficher la vignette
		echo "<div align='left'>\n";
		echo "<div align='center'>";
		$block = "doc_vignette $id_document";
		echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
		echo "</div>";
		echo "<font size='2'>\n";
		$hash = calculer_action_auteur("supp_doc ".$id_vignette);

		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('doc_supp', $id_vignette);

		if ($flag_deplie) echo debut_block_visible($block);
		else echo debut_block_invisible($block);

		echo "<b>"._T('info_vignette_personnalisee')."</b>";
		echo "<center>$largeur_vignette x $hauteur_vignette "._T('info_pixels')."</center>";
		if ($flag_modif)
			echo "<center><font face='Verdana,Arial,Sans,sans-serif'><b>[<a ".$link->getHref($ancre).">"._T('info_supprimer_vignette')."</a>]</b></font></center>\n";
		echo fin_block();
		echo "</div>\n";
	}
	else {
		// Pas de vignette : afficher un formulaire d'ajout
		echo "<div align='center'>\n";
		$block = "doc_vignette $id_document";
		list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
		if ($icone) {
			echo "<a href='$fichier'><img src='$icone' border=0 width='$largeur_icone' align='top' height='$hauteur_icone'></a>\n";
		}
		echo "</div>\n";
		echo "<font size='2'>\n";

		echo "<div align='left'>\n";
		$hash = calculer_action_auteur("ajout_doc");

		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("ajout_doc"));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('ajout_doc', 'oui');
		$link->addVar('id_document', $id_document);
		$link->addVar('mode', 'vignette');

		if ($options == 'avancees'){
			if ($flag_deplie) echo debut_block_visible($block);
			else  echo debut_block_invisible($block);

			if ($flag_modif) {
				echo bouton_block_invisible("doc_vignette_gerer $id_document");
				echo "<b>"._T('info_vignette_defaut')."</b>";

				echo debut_block_invisible("doc_vignette_gerer $id_document");
				echo "<font size=1>";
				afficher_upload($link, _T('info_remplacer_vignette'), 'image', false);
				echo "</font>";
				echo fin_block();
			}
			echo fin_block();
		}
		echo "</div>\n";
	}
	echo "</div>";

	$block = "document $id_document";

	//
	// Boite d'edition du document
	//
	if ($flag_deplie) echo debut_block_visible($block);
	else  echo debut_block_invisible($block);

	echo "<p></p><div style='border: 1px solid #666666; padding: 0px; background-color: #f0f0f0;'>";

	echo "<div style='padding: 5px;'>";
	if (strlen($descriptif)>0) echo propre($descriptif)."<br />";

	if ($type_titre)
		echo "$type_titre";
	else
		echo "Document ".majuscules($type_extension);
	echo " : <a href='$fichier'>".taille_en_octets($taille)."</a>";

	$link = new Link($redirect_url);
	$link->addVar('modif_document', 'oui');
	$link->addVar('id_document', $id_document);
	if ($flag_modif) {
		echo $link->getForm('POST', $ancre);

		echo "<b>"._T('titre_titre_document')."</b><br />\n";
		echo "<input type='text' name='titre_document' class='formo' style='font-size:11px;' value=\"".entites_html($titre)."\" size='40'><br />";

		if ($GLOBALS['coll'] > 0 AND $options == "avancees") {
			if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)) {
				$mois = $regs[2];
				$jour = $regs[3];
				$annee = $regs[1];
			}
			echo "<b>"._T('info_mise_en_ligne')."</b><br />\n";
			echo "<SELECT NAME='jour_doc' SIZE=1 CLASS='fondl' style='font-size:px;'>";
			afficher_jour($jour);
			echo "</SELECT> ";
			echo "<SELECT NAME='mois_doc' SIZE=1 CLASS='fondl' style='font-size:9px;'>";
			afficher_mois($mois);
			echo "</SELECT> ";
			echo "<SELECT NAME='annee_doc' SIZE=1 CLASS='fondl' style='font-size:9px;'>";
			afficher_annee($annee);
			echo "</SELECT><br />";
		}

		if ($options == "avancees") {
			echo "<b>"._T('info_description')."</b><br />\n";
			echo "<textarea name='descriptif_document' rows='4' class='forml' style='font-size:10px;' cols='*' wrap='soft'>";
			echo entites_html($descriptif);
			echo "</textarea>\n";
		} else {
			echo "<input type='hidden' name='descriptif_document' value='".entites_html($descriptif)."' />\n";
		}

		if ($type_inclus == "embed" OR $type_inclus == "image") {
			echo "<br /><b>"._T('info_dimension')."</b><br />\n";
			echo "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"$largeur\" size='5'>";
			echo " x <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"$hauteur\" size='5'> "._T('info_pixels');
		} else {
			echo "<input type='hidden' name='largeur_document' value=\"$largeur\" />\n";
			echo "<input type='hidden' name='hauteur_document' value=\"$hauteur\" />\n";
		}

		echo "<div align='".$GLOBALS['spip_lang_right']."'>";
		echo "<input TYPE='submit' class='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
		echo "</div>";
		echo "</form>";
	}

	$link_supp = $image_link;
	$link_supp->addVar('redirect', $redirect_url);
	$link_supp->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
	$link_supp->addVar('hash_id_auteur', $connect_id_auteur);
	$link_supp->addVar('doc_supp', $id_document);

	echo "</font></center>\n";
	echo "</div>";
	echo "</div>";

	// Icone de suppression du document

	if ($flag_modif) {
		echo "<p></p><div align='center'>";
		icone_horizontale(_T('icone_supprimer_document'), $link_supp->getUrl('docs'), "doc-24.gif", "supprimer.gif");
		echo "</div>";
	}
	echo fin_block();

	fin_cadre_enfonce();
}



//
// Afficher un document dans la colonne de gauche
// (edition des articles)

function afficher_documents_colonne($id_article, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire, $options;
	global $clean_link;

	$image_link = new Link('../spip_image.php3');
	if ($id_article) $image_link->addVar('id_article', $id_article);

	$id_doc_actif = $id_document;

	// Ne pas afficher vignettes en tant qu'images sans docs
	//// Documents associes
	$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
		"WHERE l.id_".$type."=$id_article AND l.id_document=docs.id_document ".
		"AND docs.mode='document' ORDER BY docs.id_document";

	$documents_lies = fetch_document($query);

	if ($documents_lies){
		global $descriptif, $texte, $chapo;
		$pour_documents_doublons = propre("$descriptif$texte$chapo");

		$res = spip_query("SELECT DISTINCT id_vignette FROM spip_documents ".
			"WHERE id_document in (".join(',', $documents_lies).")");
		while ($v = spip_fetch_object($res))
			$vignettes[] = $v->id_vignette;

		$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));

		if ($docs_exclus)
			$docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
	}

	//// Images sans documents
	$query = "SELECT * FROM #table AS docs, spip_documents_".$type."s AS l ".
			"WHERE l.id_".$type."=$id_article AND l.id_document=docs.id_document ".$docs_exclus.
			"AND docs.mode='vignette' ORDER BY docs.id_document";

	$images_liees = fetch_document($query);

	/// Ajouter nouvelle image
	echo "\n<p>";
	//debut_cadre_relief("image-24.gif");
	if ($images_liees) {
		reset($images_liees);
		while (list(, $id_document) = each($images_liees)) {
			afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
			//echo "<p>\n";
		}
	}


	debut_cadre_relief("image-24.gif", false, "creer.gif");

	echo "<div style='padding: 2px; background-color: $couleur_claire; text-align: center; color: black;'>";
	echo bouton_block_invisible("ajouter_image");
	echo "<b><font size='2'>"._T('bouton_ajouter_image').aide("ins_img")."</font></b>";
	echo "</div>\n";

	echo debut_block_invisible("ajouter_image");
	echo "<font size='2'>";
	echo _T('info_installer_images');
	echo "</font>";

	$link = $image_link;
	$link->addVar('redirect', $redirect_url);
	$link->addVar('hash', calculer_action_auteur("ajout_doc"));
	$link->addVar('hash_id_auteur', $connect_id_auteur);
	$link->addVar('ajout_doc', 'oui');
	$link->addVar('mode', 'vignette');
	$link->addVar('type', $type);

	afficher_upload($link, _T('info_telecharger'));
	echo fin_block();

	echo "</font>\n";
	fin_cadre_relief();

	//fin_cadre_relief();

	if ($type == "article") {
		echo "<p>&nbsp;<p>";
		if ($documents_lies) {
			reset($documents_lies);
			while (list(, $id_document) = each($documents_lies)) {
				afficher_case_document($id_document, $image_link, $redirect_url, $id_doc_actif == $id_document);
				echo "<p>\n";
			}
		}

		if (lire_meta("documents_$type") != 'non') {
			/// Ajouter nouveau document

			debut_cadre_enfonce("doc-24.gif", false, "creer.gif");
			echo "<div style='padding: 2px;background-color: $couleur_claire; text-align: center; color: black;'>";
			echo bouton_block_invisible("ajouter_document");
			echo "<b><font size='2'>"._T('bouton_ajouter_document')."</font></b>".aide("ins_doc");
			echo "</div>\n";

			echo debut_block_invisible("ajouter_document");
			echo "<font size='2'>";
			echo _T('info_joindre_documents_article')."&nbsp;";
			$query_types_docs = "SELECT extension FROM spip_types_documents ORDER BY extension";
			$result_types_docs = spip_query($query_types_docs);

			$extension = "";
			while ($row = spip_fetch_array($result_types_docs)) {
				if ($extension) echo ", ";
				$extension=$row['extension'];
				echo $extension;
			}
			echo "</font>";

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('mode', 'document');
			$link->addVar('type', $type);

			afficher_upload($link, _T('info_telecharger_ordinateur'));
			echo fin_block();

			echo "</font>\n";
			fin_cadre_enfonce();
		}
	}
}


//
// Afficher un document sous forme de ligne depliable
//

function afficher_case_document($id_document, $image_link, $redirect_url = "", $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;
	global $options;
	global $id_doublons;


	if ($GLOBALS['id_document'] > 0) {
		$id_document_deplie = $GLOBALS['id_document'];
	}

	if ($id_document == $id_document_deplie) $flag_deplie = true;

 	$doublons = $id_doublons['documents'].",";

	if (!$redirect_url) $redirect_url = $clean_link->getUrl();

	$document = fetch_document($id_document);

	$id_vignette = $document->get('id_vignette');
	$id_type = $document->get('id_type');
	$titre = $document->get('titre');
	$descriptif = $document->get('descriptif');
	$fichier = generer_url_document($id_document);
	$largeur = $document->get('largeur');
	$hauteur = $document->get('hauteur');
	$taille = $document->get('taille');
	$mode = $document->get('mode');
	if (!$titre) {
		$titre_fichier = _T('info_sans_titre_2');
		$titre_fichier .= " <small>(".ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier).")</small>";
	}

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($type = @spip_fetch_array($result))	{
		$type_extension = $type['extension'];
		$type_inclus = $type['inclus'];
		$type_titre = $type['titre'];
	}

	//
	// Afficher un document
	//

	if ($mode == 'document') {
		debut_cadre_enfonce("doc-24.gif");

		echo "<div style='padding: 2px; background-color: #aaaaaa; text-align: center; color: black;'>";
		$block = "document $id_document";
		if ($flag_deplie) echo bouton_block_visible("$block,doc_vignette $id_document");
		else echo bouton_block_invisible("$block,doc_vignette $id_document");
		echo "<font size='3'>".typo($titre).typo($titre_fichier)."</font>";
		echo "</div>\n";

		//
		// Edition de la vignette
		//

		if ($id_vignette) $vignette = fetch_document($id_vignette);
		if ($vignette) {
			$fichier_vignette = generer_url_document($id_vignette);
			$largeur_vignette = $vignette->get('largeur');
			$hauteur_vignette = $vignette->get('hauteur');
			$taille_vignette = $vignette->get('taille');
		}

		echo "<p></p><div style='border: 1px dashed #666666; padding: 5px; background-color: #f0f0f0;'>";
		if ($fichier_vignette) {
			echo "<div align='left'>\n";
			echo "<div align='center''>";
			echo texte_vignette_document($largeur_vignette, $hauteur_vignette, $fichier_vignette, "$fichier");
			echo "</div>";
			echo "<font size='2'>\n";
			$hash = calculer_action_auteur("supp_doc ".$id_vignette);

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_vignette));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('doc_supp', $id_vignette);
			if ($flag_deplie) echo debut_block_visible("doc_vignette $id_document");
			else  echo debut_block_invisible("doc_vignette $id_document");
			echo "<b>"._T('info_vignette_personnalisee')."</b>";
			echo "<center>"._T('info_largeur_vignette', array('largeur_vignette' => $largeur_vignette, 'hauteur_vignette' => $hauteur_vignette))."</center>";
			echo "<center><font face='Verdana,Arial,Sans,sans-serif'><b>[<a ".$link->getHref().">"._T('info_supprimer_vignette')."</a>]</b></font></center>\n";
			echo fin_block();
			echo "</div>\n";
		}
		else {
			// pas de vignette
			echo "<div align='center'>\n";
			$block = "doc_vignette $id_document";
			list($icone, $largeur_icone, $hauteur_icone) = vignette_par_defaut($type_extension);
			if ($icone) {
				echo "<a href='$fichier'><img src='$icone' border=0 width='$largeur_icone' align='top' height='$hauteur_icone'></a>\n";
			}
			echo "</div>\n";
			echo "<font size='2'>\n";

			echo "<div align='left'>\n";
			$hash = calculer_action_auteur("ajout_doc");

			$link = $image_link;
			$link->addVar('redirect', $redirect_url);
			$link->addVar('hash', calculer_action_auteur("ajout_doc"));
			$link->addVar('hash_id_auteur', $connect_id_auteur);
			$link->addVar('ajout_doc', 'oui');
			$link->addVar('id_document', $id_document);
			$link->addVar('mode', 'vignette');

			if ($options == 'avancees'){
				if ($flag_deplie) echo debut_block_visible("doc_vignette $id_document");
				else  echo debut_block_invisible("doc_vignette $id_document");

				echo bouton_block_invisible("doc_vignette_gerer $id_document");
				echo "<b>"._T('info_vignette_defaut')."</b>";
					
				echo debut_block_invisible("doc_vignette_gerer $id_document");
				echo "<font size=1>";
				afficher_upload($link, _T('info_remplacer_vignette'), 'image', false);
				echo "</font>";
				echo fin_block();
				
				echo fin_block();

			}
			echo "</div></font>\n";
		}
		echo "</div>";

		if (!ereg(",$id_document,", "$doublons")) {
			echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<b>"._T('info_inclusion_vignette')."</b></br>";
			}
			echo "<font color='333333'><div align=left>&lt;doc$id_document|left&gt;</div><div align=center>&lt;doc$id_document|center&gt;</div><div align='right'>&lt;doc$id_document|right&gt;</div></font>\n";
			echo "</font></div>";

			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
				echo "<b>"._T('info_inclusion_directe')."</b></br>";
				echo "<font color='333333'><div align=left>&lt;emb$id_document|left&gt;</div><div align=center>&lt;emb$id_document|center&gt;</div><div align='right'>&lt;emb$id_document|right&gt;</div></font>\n";
				echo "</font></div>";
			}
		}

		$block = "document $id_document";

		if ($flag_deplie) echo debut_block_visible($block);
		else  echo debut_block_invisible($block);
		if (ereg(",$id_document,", "$doublons")) {
			echo "<div style='padding:2px;'><font size=1 face='arial,helvetica,sans-serif'>";
			echo "<div align=center>&lt;doc$id_document&gt;</div>\n";
			echo "</font></div>";
		}

		//
		// Edition des champs
		//

		echo "<div style='border: 1px solid #666666; padding: 5px; background-color: #f0f0f0;'>";
		if (strlen($descriptif) > 0) echo propre($descriptif)."<br />";

		echo "<font size='2'>";
		if ($options == "avancees") {
			if ($type_titre){
				echo "$type_titre";
			} else {
				echo _T('info_document').' '.majuscules($type_extension);
			}
			echo " : <a href='$fichier'>".taille_en_octets($taille)."</a>";
		}

		$link = new Link($redirect_url);
		$link->addVar('modif_document', 'oui');
		$link->addVar('id_document', $id_document);
		echo $link->getForm('POST');

		echo "<b>"._T('entree_titre_document')."</b><br />\n";
		echo "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre)."\" size='40'><br />";

		if ($descriptif OR $options == "avancees") {
			echo "<b>"._T('info_description_2')."</b><br />\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' cols='*' wrap='soft'>";
			echo entites_html($descriptif);
			echo "</textarea>\n";
		}

		if (($type_inclus == "embed" OR $type_inclus == "image") AND $options == "avancees") {
			echo "<br /><b>"._T('entree_dimensions')."</b><br />\n";
			echo "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"$largeur\" size='5'>";
			echo " x <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"$hauteur\" size='5'> "._T('info_pixels');
		}

		echo "<div align='".$GLOBALS['spip_lang_right']."'>";
		echo "<input TYPE='submit' class='fondo' style='font-size:9px;' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
		echo "</div>";
		echo "</form>";

		$link_supp = $image_link;
		$link_supp->addVar('redirect', $redirect_url);
		$link_supp->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
		$link_supp->addVar('hash_id_auteur', $connect_id_auteur);
		$link_supp->addVar('doc_supp', $id_document);

		echo "</font></div>";

		echo "<p></p><div align='center'>";
		icone_horizontale(_T('icone_supprimer_document'), $link_supp->getUrl(), "doc-24.gif", "supprimer.gif");
		echo "</div>";
	
		//echo "</div>\n";
		fin_cadre_enfonce();
	}

	//
	// Afficher une image inserable dans l'article
	//
	else if ($mode == 'vignette') {
		//echo "<div style='border: 1px dashed #aaaaaa; padding: 4px; background-color: #f0f0f0;'>\n";
		debut_cadre_relief("image-24.gif");

		$block = "image $id_document";
		echo "<div style='padding: 2px; background-color: #e4e4e4; text-align: center; color: black;'>";

		if ($flag_deplie) echo bouton_block_visible("$block");
		else echo bouton_block_invisible("$block");

		echo "<font size='3'>".typo($titre).typo($titre_fichier)."</font>";
		echo "</div>\n";

		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
		if (!ereg(",$id_document,", "$doublons")) {
			$raccourci_doc = "<div><font size='2' color='#666666' face='arial,helvetica,sans-serif'>";
			if (strlen($descriptif) > 0 OR strlen($titre) > 0) {
				$raccourci_doc .= "<div align='left'>&lt;doc$id_document|left&gt;</div>\n".
					"<div align='center'>&lt;doc$id_document|center&gt;</div>\n".
					"<div align='right'>&lt;doc$id_document|right&gt;</div>\n";
			} else {
				$raccourci_doc .= "<div align='left'>&lt;img$id_document|left&gt;</div>\n".
					"<div align='center'>&lt;img$id_document|center&gt;</div>\n".
					"<div align='right'>&lt;img$id_document|right&gt;</div>\n";
			}
			$raccourci_doc .= "</font></div>\n";
		} else {
			$raccourci_doc = "<div><font size='2' color='#666666' face='arial,helvetica,sans-serif'>";
			$raccourci_doc .= "<div align='center'>&lt;img$id_document&gt;</div>\n";
			$raccourci_doc .= "</font></div>\n";
		}

		//
		// Afficher un apercu (pour les images)
		//

		if ($type_inclus == 'image') {
			echo "<div style='text-align: center; padding: 2px;'>\n";
			echo texte_vignette_document($largeur, $hauteur, $fichier, $fichier);
			echo "</div>\n";
			echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
			if (strlen($descriptif)>0)
				echo propre($descriptif);

			if (!ereg(",$id_document,", "$doublons")) echo $raccourci_doc;
		}

		if ($flag_deplie) echo debut_block_visible($block);
		else  echo debut_block_invisible($block);

		if (ereg(",$id_document,", "$doublons")) echo $raccourci_doc;
		echo "\n<div align='center'><font face='Verdana,Arial,Sans,sans-serif' size='1'>$largeur x $hauteur "._T('info_pixels')."<br /></font></div>\n";

		$link = new Link($redirect_url);
		$link->addVar('modif_document', 'oui');
		$link->addVar('id_document', $id_document);
		echo $link->getForm('POST');

		echo "<p></p><div class='iconeoff'>";
		echo "<b>"._T('entree_titre_image')."</b><br />\n";
		echo "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre)."\" size='40'><br />";

		if ($descriptif OR $options == "avancees") {
			echo "<b>"._T('info_description_2')."</b><br />\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' cols='*' style='font-size:9px;' wrap='soft'>";
			echo entites_html($descriptif);
			echo "</textarea>\n";
		}

		echo "<div align='".$GLOBALS['spip_lang_right']."'>";
		echo "<input class='fondo' style='font-size: 9px;' TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."'>";
		echo "</div>";
		echo "</div>";
		echo "</form>";

		echo "<center>";
		$link = $image_link;
		$link->addVar('redirect', $redirect_url);
		$link->addVar('hash', calculer_action_auteur("supp_doc ".$id_document));
		$link->addVar('hash_id_auteur', $connect_id_auteur);
		$link->addVar('doc_supp', $id_document);
		icone_horizontale (_T('icone_supprimer_image'), $link->getUrl(), "image-24.gif", "supprimer.gif");
		echo "</center>\n";

		echo fin_block();

		//echo "</div>";
		fin_cadre_relief();
	}
}


?>
