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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/date');
include_spip('base/abstract_sql');

//
// Vignette pour les documents lies
//

// http://doc.spip.org/@vignette_par_defaut
function vignette_par_defaut($ext, $size=true, $loop = true) {

	if (!$ext)
		$ext = 'txt';

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	if (!@file_exists($v = _DIR_IMG_ICONES . $ext.'.png')
	AND !@file_exists($v = _DIR_IMG_ICONES . $ext.'.gif')
	# icones standard
	AND !@file_exists($v = _DIR_IMG_ICONES_DIST . $ext.'-dist.png')
	# cas d'une install dans un repertoire "applicatif"...
	AND !@file_exists(_ROOT_IMG_ICONES_DIST . $v)
	)
		if ($loop)
			$v = vignette_par_defaut('defaut', false, $loop=false);
		else
			$v = false; # pas trouve l'icone de base

	if (!$size) return $v;

	if ($size = @getimagesize($v)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($v, $largeur, $hauteur);
}

//
// Affiche le document avec sa vignette par defaut
//
// Attention : en mode 'doc', si c'est un fichier graphique on prefere
// afficher une vue reduite, quand c'est possible (presque toujours, donc)
// En mode 'vignette', l'image conserve sa taille
//
// A noter : dans le portfolio prive on pousse le vice jusqu'a reduire la taille
// de la vignette -> c'est a ca que sert la variable $portfolio
// http://doc.spip.org/@image_pattern
function image_pattern($vignette) {
	return "<img src='"
			. _DIR_RACINE
			. $vignette['fichier']."'
			width='".$vignette['largeur']."'
			height='".$vignette['hauteur']."' />";
}

// http://doc.spip.org/@document_et_vignette
function document_et_vignette($document, $url, $portfolio=false) {
	// a supprimer avec spip_types_documents
	$extension = spip_fetch_array(spip_query("SELECT extension, mime_type FROM	spip_types_documents WHERE id_type=".$document['id_type']));
	$mime = $extension['mime_type'];
	$extension = $extension['extension'];
	$vignette = $document['id_vignette'];

	if ($vignette) 
		$vignette = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = ".$vignette));
	if ($vignette) {
			if (!$portfolio OR !($GLOBALS['meta']['creer_preview'] == 'oui')) {
				$image = image_pattern($vignette);
			} else {
				include_spip('inc/logos');
				$image = reduire_image_logo((_DIR_RACINE . $vignette['fichier']), 120, 110);
			}
	} else if (strstr($GLOBALS['meta']['formats_graphiques'], $extension)
	AND $GLOBALS['meta']['creer_preview'] == 'oui') {
		include_spip('inc/distant');
		include_spip('inc/logos');
		$local = copie_locale($document['fichier']);
		if ($portfolio)
			$image = reduire_image_logo($local, 110, 120);
		else
			$image = reduire_image_logo($local);
	} else $image = '';

	if (!$image) {
		list($fichier, $largeur, $hauteur) = vignette_par_defaut($extension);
		$image = "<img src='$fichier'\n\theight='$hauteur' width='$largeur' />";
	}

	if (!$url)
		return $image;
	else
		return "<a href='$url'\n\ttype='$mime'>$image</a>";
}

//
// Retourner le code HTML d'utilisation de fichiers envoyes
//

// http://doc.spip.org/@texte_upload_manuel
function texte_upload_manuel($dir, $inclus = '', $mode = 'document') {
	$fichiers = preg_files($dir);
	$exts = array();
	$dirs = array(); 
	$texte_upload = array();
	foreach ($fichiers as $f) {
		$f = preg_replace(",^$dir,",'',$f);
		if (ereg("\.([^.]+)$", $f, $match)) {
			$ext = strtolower($match[1]);
			if (!isset($exts[$ext])) {
				if ($ext == 'jpeg') $ext = 'jpg'; # cf. corriger_extension dans inc/getdocument
				if (spip_abstract_fetsel('extension', 'spip_types_documents', "extension='$ext'" . (!$inclus ? '':  " AND inclus='$inclus'")))
					$exts[$ext] = 'oui';
				else $exts[$ext] = 'non';
			}
			
			$k = 2*substr_count($f,'/');
			$n = strrpos($f, "/");
			if ($n === false)
			  $lefichier = $f;
			else {
			  $lefichier = substr($f, $n+1, strlen($f));
			  $ledossier = substr($f, 0, $n);
			  if (!in_array($ledossier, $dirs)) {
				$texte_upload[] = "\n<option value=\"$ledossier\">"
				. str_repeat("&nbsp;",$k) 
				._T('tout_dossier_upload', array('upload' => $ledossier))
				."</option>";
				$dirs[]= $ledossier;
			  }
			}

			if ($exts[$ext] == 'oui')
			  $texte_upload[] = "\n<option value=\"$f\">" .
			    str_repeat("&nbsp;",$k+2) .
			    $lefichier .
			    "</option>";
		}
	} 

	$texte = join('', $texte_upload);

	if ($mode == "document" AND count($texte_upload)>1) {
		$texte = "\n<option value=\"/\" style='font-weight: bold;'>"
				._T('info_installer_tous_documents')
				."</option>" . $texte;
	}

	return $texte;
}


//
// Construire un formulaire pour telecharger un fichier
//

function formulaire_upload($script, $args, $id=0, $intitule='', $mode='', $type='', $ancre='', $id_document=0) {
	global $spip_lang_right;
	$vignette_de_doc = ($mode == 'vignette' AND $id_document>0);
	$distant = ($mode == 'document' AND $type);
	if ($intitule) $intitule = "<span>$intitule</span><br />";

	if (!_DIR_RESTREINT AND !$vignette_de_doc) {
		$dir_ftp = determine_upload();
		// quels sont les docs accessibles en ftp ?
		$l = texte_upload_manuel($dir_ftp, '', $mode);
		// s'il n'y en a pas, on affiche un message d'aide
		// en mode document, mais pas en mode vignette
		if ($l OR ($mode == 'document'))
			$dir_ftp = afficher_transferer_upload($l);
		else
			$dir_ftp = '';
	}

	// Un menu depliant si on a une possibilite supplementaire

	if ($dir_ftp OR $distant OR $vignette_de_doc) {
		$bloc = "ftp_$mode" .'_'. intval($id_document);
		$debut = "\n\t<div style='float:".$GLOBALS['spip_lang_left'].";'>"
			. bouton_block_invisible($bloc) ."</div>\n";
		$milieu = debut_block_invisible($bloc);
		$fin = "\n\t" . fin_block();

	} else $debut = $milieu = $fin = '';

	// Lien document distant, jamais en mode image
	if ($distant) {
		$distant = "<p />\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			"\n\t<img src='"._DIR_IMG_PACK.'attachment.gif' .
			"' style='float: $spip_lang_right;' alt=\"\" />\n" .
			_T('info_referencer_doc_distant') .
			"<br />\n\t<input name='url' class='fondo' value='http://' />" .
			"\n\t<div align='$spip_lang_right'><input name='sousaction2' type='Submit' value='".
			_T('bouton_choisir').
			"' class='fondo'></div>" .
			"\n</div>";
	}

	$res = "<input name='fichier' type='file' style='font-size: 10px;' class='forml' size='15' />"
	. "\n\t\t<input type='hidden' name='ancre' value='$ancre' />"
	. "\n\t\t<div align='$spip_lang_right'><input name='sousaction1' type='submit' value='"
	. _T('bouton_telecharger')
	. "' class='fondo' /></div>";

	if ($vignette_de_doc)
		$res = $milieu . $res;
	else
		$res = $res . $milieu;

	$f = generer_action_auteur('joindre',
		(intval($id) .'/' .intval($id_document) . "/$mode/$type"),
		generer_url_ecrire($script, $args),
		"$debut$intitule$res$dir_ftp$distant$fin",
		" method='post' enctype='multipart/form-data' style='border: 0px; margin: 0px;'");

	return $f;
}

// http://doc.spip.org/@construire_upload
function construire_upload($corps, $args, $enctype='')
{
	$res = "";
	foreach($args as $k => $v)
	  if ($v)
	    $res .= "\n<input type='hidden' name='$k' value='$v' />";

# ici enlever $action pour uploader directemet dans l'espace prive (UPLOAD_DIRECT)
	return "\n<form method='post' action='" . generer_url_action('joindre') .
	  "'" .
	  (!$enctype ? '' : " enctype='$enctype'") .
	  " 
	  >\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='joindre' />" .
	  $res . $corps . "</div></form>";
}

// http://doc.spip.org/@afficher_transferer_upload
function afficher_transferer_upload($texte_upload)
{
	$doc = array('upload' => '<b>' . joli_repertoire(determine_upload()) . '</b>');
	if (!$texte_upload) {
		return "\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			_T('info_installer_ftp', $doc) .
			aide("ins_upload") .
			"</div>";
		}
	else {  return
		"\n<div style='color: #505050;'>"
		._T('info_selectionner_fichier', $doc)
		."&nbsp;:<br />\n" .
		"\n<select name='chemin' size='1' class='fondl'>" .
		$texte_upload .
		"\n</select>" .
		"\n<div align='".
		$GLOBALS['spip_lang_right'] .
		"'><input name='sousaction3' type='Submit' value='" .
		_T('bouton_choisir').
		"' class='fondo'></div>" .
		"</div>\n";
	}
}

function formulaire_joindre($id, $type = "article", $script, $flag_editable) {
	global $spip_lang_left;

	if ($GLOBALS['meta']["documents_$type"]!='non' AND $flag_editable) {

	  $res = debut_cadre_relief("image-24.gif", true, "", _T('titre_joindre_document'))
	  . formulaire_upload($script, "id_$type=$id", $id, _T('info_telecharger_ordinateur'), 'document', $type)
	  . fin_cadre_relief(true);

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

	  if (!($align = $GLOBALS['browser_name']=="MSIE")) {
		$res = "\n<table width='50%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td style='text-align: $spip_lang_left;'>\n$res</td></tr></table>";
		$align = " align='right'";
	  }
	  $res = "<div$align>$res</div>";
	} else $res ='';

	$f = charger_fonction('documenter', 'inc');

	return $f($id, $type, 'portfolio', $flag_editable)
	. $f($id, $type, 'documents', $flag_editable)
	. $res;
}

//
// Afficher un document dans la colonne de gauche
//

// http://doc.spip.org/@afficher_documents_colonne
function afficher_documents_colonne($id, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut, $options, $id_doc_actif;

	// seuls cas connus : exec=articles_edit ou breves_edit
	$script = $type.'s_edit';

	/// Ajouter nouvelle image
	echo "<a name='images'></a>\n";
	$titre_cadre = _T('bouton_ajouter_image').aide("ins_img");
	debut_cadre_relief("image-24.gif", false, "creer.gif", $titre_cadre);
	echo formulaire_upload($script, "id_$type=$id", $id, _T('info_telecharger'),'vignette',$type);

	fin_cadre_relief();

	//// Documents associes
	$res = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l WHERE l.id_".$type."=$id AND l.id_document=docs.id_document AND docs.mode='document' ORDER BY docs.id_document");

	$documents_lies = array();
	while ($row = spip_fetch_array($res))
		$documents_lies[]= $row['id_document'];

	if (count($documents_lies)) {
		$res = spip_query("SELECT DISTINCT id_vignette FROM spip_documents WHERE id_document in (".join(',', $documents_lies).")");
		while ($v = spip_fetch_array($res))
			$vignettes[]= $v['id_vignette'];
		$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));

		if ($docs_exclus) $docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
	} else $docs_exclus = '';

	//// Images sans documents
	$images_liees = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l "."WHERE l.id_".$type."=$id AND l.id_document=docs.id_document ".$docs_exclus."AND docs.mode='vignette' ORDER BY docs.id_document");

	echo "\n<p />";
	while ($doc = spip_fetch_array($images_liees)) {
		$id_document = $doc['id_document'];
		afficher_case_document($id_document, $id, $script, $type, $id_doc_actif == $id_document);
	}

	/// Ajouter nouveau document
	echo "<p>&nbsp;</p>\n<a name='documents'></a>\n<a name='portfolio'></a>\n";
	if ($type == "article") {
		if ($GLOBALS['meta']["documents_article"] != 'non') {
			$titre_cadre = _T('bouton_ajouter_document').aide("ins_doc");
			debut_cadre_enfonce("doc-24.gif", false, "creer.gif", $titre_cadre);
			echo formulaire_upload($script, "id_$type=$id", $id, _T('info_telecharger_ordinateur'), 'document',$type);
			fin_cadre_enfonce();
		}

		// Afficher les documents lies
		echo "<p />\n";

		foreach($documents_lies as $doc) {
			afficher_case_document($doc, $id, $script, $type, $id_doc_actif == $doc);
		}
	}
}

//
// Affiche le raccourci <doc123|left>
// et l'insere quand on le clique
//
// http://doc.spip.org/@affiche_raccourci_doc
function affiche_raccourci_doc($doc, $id, $align) {
	if ($align) {
		$pipe = "|$align";

		if ($GLOBALS['browser_barre'])
			$onclick = "\nondblclick='barre_inserer(\"&lt;$doc$id$pipe&gt;\", document.formulaire.texte);'\ntitle=\"". entites_html(_T('double_clic_inserer_doc'))."\"";
	} else {
		$align='center';
	}
	return "\n<div align='$align'$onclick>&lt;$doc$id$pipe&gt;</div>\n";
}


// Est-ce que le document est inclus dans le texte ?
// http://doc.spip.org/@est_inclus
function est_inclus($id_document) {
	return is_array($GLOBALS['doublons_documents_inclus']) ?
		in_array($id_document,$GLOBALS['doublons_documents_inclus']) : false;
}

//
// Afficher un document sous forme de ligne depliable (pages xxx_edit)
//

// http://doc.spip.org/@afficher_case_document
function afficher_case_document($id_document, $id, $script, $type, $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $options, $couleur_foncee, $spip_lang_left, $spip_lang_right;

	charger_generer_url();
	$flag_deplie = teste_doc_deplie($id_document);

	$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));

	$id_vignette = $document['id_vignette'];
	$id_type = $document['id_type'];
	$titre = $document['titre'];
	$descriptif = $document['descriptif'];
	$url = generer_url_document($id_document);
	$fichier = $document['fichier'];
	$largeur = $document['largeur'];
	$hauteur = $document['hauteur'];
	$taille = $document['taille'];
	$mode = $document['mode'];

	// le doc est-il appele dans le texte ?
	$doublon = est_inclus($id_document);

	$cadre = strlen($titre) ? $titre : basename($document['fichier']);

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($letype = @spip_fetch_array($result)) {
		$type_extension = $letype['extension'];
		$type_inclus = $letype['inclus'];
		$type_titre = $letype['titre'];
	}

	//
	// Afficher un document
	//

	if ($mode == 'document') {
		if ($options == "avancees") {
			# 'extension', a ajouter dans la base quand on supprimera spip_types_documents
			switch ($id_type) {
				case 1:
					$document['extension'] = "jpg";
					break;
				case 2:
					$document['extension'] = "png";
					break;
				case 3:
					$document['extension'] = "gif";
					break;
			}

		echo "<a id='document$id_document' name='document$id_document'></a>\n";
		debut_cadre_enfonce("doc-24.gif", false, "", lignes_longues(typo($cadre),30));

		//
		// Affichage de la vignette
		//
		echo "\n<div align='center'>";
		echo document_et_vignette($document, $url, true); 
		echo '</div>';
		echo "\n<div class='verdana1' style='text-align: center; color: black;'>\n";
		echo ($type_titre ? $type_titre : 
		      ( _T('info_document').' '.majuscules($type_extension)));
		echo "</div>";

		// Affichage du raccourci <doc...> correspondant
		if (!$doublon) {
			echo "\n<div style='padding:2px; font-size: 10px; font-family: arial,helvetica,sans-serif'>";
			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<b>"._T('info_inclusion_vignette')."</b><br />";
			}
			echo "<div style='color: 333333'>"
			. affiche_raccourci_doc('doc', $id_document, 'left')
			. affiche_raccourci_doc('doc', $id_document, 'center')
			. affiche_raccourci_doc('doc', $id_document, 'right')
			. "</div>\n";
			echo "</div>";

			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<div style='padding:2px; font-size: 10px; font-family: arial,helvetica,sans-serif'>";
				echo "<b>"._T('info_inclusion_directe')."</b></br>";
				echo "<div style='color: 333333'>"
				. affiche_raccourci_doc('emb', $id_document, 'left')
				. affiche_raccourci_doc('emb', $id_document, 'center')
				. affiche_raccourci_doc('emb', $id_document, 'right')
				. "</div>\n";
				echo "</div></div>";
			}
		} else {
			echo "<div style='padding:2px;'><font size='1' face='arial,helvetica,sans-serif'>",
			  affiche_raccourci_doc('doc', $id_document, ''),
			  "</font></div>";
		}

		$f = charger_fonction('legender', 'inc');
		echo $f($id_document, $document, $script, $type, $id, "document$id_document");

		fin_cadre_enfonce();
		}
	}

	//
	// Afficher une image inserable dans l'article
	//
	else if ($mode == 'vignette') {
	
		debut_cadre_relief("image-24.gif", false, "", lignes_longues(typo($cadre),30));

		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
		$raccourci_doc = "<div style='padding:2px;'>
		<font size='1' face='arial,helvetica,sans-serif'>";
		if (strlen($descriptif) > 0 OR strlen($titre) > 0)
			$doc = 'doc';
		else
			$doc = 'img';
		if (!$doublon) {
			$raccourci_doc .=
				affiche_raccourci_doc($doc, $id_document, 'left')
				. affiche_raccourci_doc($doc, $id_document, 'center')
				. affiche_raccourci_doc($doc, $id_document, 'right');
		} else {
			$raccourci_doc .= affiche_raccourci_doc($doc, $id_document, '');;
		}
		$raccourci_doc .= "</font></div>\n";

		//
		// Afficher un apercu (pour les images)
		//
		if ($type_inclus == 'image') {
			echo "<div style='text-align: center; padding: 2px;'>\n";
			echo document_et_vignette($document, $url, true);
			echo "</div>\n";
			if (!$doublon)
				echo $raccourci_doc;
		}

		if ($doublon)
			echo $raccourci_doc;

		$f = charger_fonction('legender', 'inc');
		echo $f($id_document, $document, $script, $type, $id, "document$id_document");
		
		fin_cadre_relief();
	}
}

// http://doc.spip.org/@teste_doc_deplie
function teste_doc_deplie($id_document) {
	global $show_docs;
	static $deplies;

	if (!$deplies)
		$deplies = split('-',$show_docs);

	return in_array($id_document, $deplies);
}
?>
