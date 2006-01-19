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

include_ecrire('inc_minipres');

// Creer IMG/pdf/
function creer_repertoire_documents($ext) {
	$rep = _DIR_DOC . creer_repertoire(_DIR_DOC, $ext);

	if (!$ext OR !$rep) {
		spip_log("creer_repertoire_documents interdit");
		exit;
	}

	if ($GLOBALS['meta']["creer_htaccess"] == 'oui') {
		include_ecrire('inc_acces');
		verifier_htaccess($rep);
	}

	return $rep;
}

// Efface le repertoire de maniere recursive !
function effacer_repertoire_temporaire($nom) {
	$d = opendir($nom);
	while (($f = readdir($d)) !== false) {
		if (is_file("$nom/$f"))
			@unlink("$nom/$f");
		else if ($f <> '.' AND $f <> '..'
		AND is_dir("$nom/$f"))
			effacer_repertoire_temporaire("$nom/$f");
	}
	@rmdir($nom);
}

function copier_document($ext, $orig, $source) {

	$dir = creer_repertoire_documents($ext);
	$dest = ereg_replace("[^.a-zA-Z0-9_=-]+", "_", 
			translitteration(ereg_replace("\.([^.]+)$", "", 
						      ereg_replace("<[^>]*>", '', basename($orig)))));

	// Si le document "source" est deja au bon endroit, ne rien faire
	if ($source == ($dir . $dest . '.' . $ext))
		return $source;

	// sinon tourner jusqu'a trouver un numero correct
	$n = 0;
	while (@file_exists($dir.($newFile = $dest.($n++ ? '-'.$n : '').'.'.$ext)));

	$newFile = preg_replace('/[.]+/', '.', $newFile);
	if ($r = deplacer_fichier_upload($source, $dir.$newFile))
		return $dir.$newFile;
}

//
// Deplacer un fichier
//

function deplacer_fichier_upload($source, $dest) {
	// Securite
	## !! interdit pour le moment d'uploader depuis l'espace prive (UPLOAD_DIRECT)
	if (strstr($dest, "..")) {
		spip_log("stop deplacer_fichier_upload: '$dest'");
		exit;
	}

	$ok = @copy($source, $dest);
	if (!$ok) $ok = @move_uploaded_file($source, $dest);
	if ($ok)
		@chmod($dest, 0666);
	else {
		$f = @fopen($dest,'w');
		if ($f) {
			fclose ($f);
		} else {
			redirige_par_entete(generer_url_public('spip_action.php'), "?action=test_dirs&test_dir=". dirname($dest));
		}
		@unlink($dest);
	}
	return $ok;
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs affiche le message d'erreur et meurt
function check_upload_error($error, $msg='') {
	global $spip_lang_right;
	switch ($error) {
		case 0:
			return false;
		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T('upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
	}

	spip_log ("erreur upload $error");

	install_debut_html($msg); echo '<form action="' ,
	  _DIR_RESTREINT_ABS,
	  urldecode($GLOBALS['redirect']),
	  '"><div align="',
	  $spip_lang_right,
	  '"><input type="submit" class="fondl"  value="',
	  _T('ecrire:bouton_suivant'),
	  ' >>"></div>',
	  '</form></body></html>';
	install_fin_html();
	exit;
}


//
// Gestion des fichiers ZIP
//
function accepte_fichier_upload ($f) {
	if (!ereg(".*__MACOSX/", $f)
	AND !ereg("^\.", basename($f))) {
		$ext = substr(strrchr($f, "."), 1);
		$result = spip_query("SELECT * FROM spip_types_documents
		WHERE extension='"
		. corriger_extension(addslashes(strtolower($ext)))
		. "' AND upload='oui'");
		if ($row = @spip_fetch_array($result))
			return true;
	}
}

# callback pour le deballage d'un zip telecharge
# http://www.phpconcept.net/pclzip/man/en/?options-pclzip_cb_pre_extractfunction
function callback_deballe_fichier($p_event, &$p_header) {
	if (accepte_fichier_upload($p_header['filename'])) {
		$p_header['filename'] = _tmp_dir . basename($p_header['filename']);
		return 1;
	} else {
		return 0;
	}
}

function verifier_compactes($zip) {
	if (!$list = $zip->listContent()) return array();
	// si pas possible de decompacter: installer comme fichier zip joint
	// Verifier si le contenu peut etre uploade (verif extension)
	$aff_fichiers = array();
	foreach ($list as $file) {
		if (accepte_fichier_upload($f = $file['stored_filename']))
			$aff_fichiers[]= $f;
		else spip_log("chargement de $f interdit");
		}
	sort($aff_fichiers);
	return $aff_fichiers;
}

//
// Ajouter un document (au format $_FILES)
//
# $source,	# le fichier sur le serveur (/var/tmp/xyz34)
# $nom_envoye,	# son nom chez le client (portequoi.pdf)
# $type_lien,	# lie a un article, une breve ou une rubrique ?
# $id_lien,	# identifiant de l'article (ou rubrique) lie
# $mode,	# 'vignette' => image en mode image
#		# ou vignette personnalisee liee a un document
		# 'document' => doc ou image en mode document
		# 'distant' => lien internet
# $id_document,	# pour une vignette, l'id_document de maman
# $actifs	# les documents dont il faudra ouvrir la boite de dialogue

function ajouter_un_document ($source, $nom_envoye, $type_lien, $id_lien, $mode, $id_document, &$documents_actifs) {

// Documents distants : pas trop de verifications bloquantes, mais un test
// via une requete HEAD pour savoir si la ressource existe (non 404), si le
// content-type est connu, et si possible recuperer la taille, voire plus.
	spip_log ("ajout du document $nom_envoye  (M '$mode' T '$type_lien' L '$id_lien' D '$id_document')");
	if ($mode == 'distant') {
		include_ecrire('inc_distant');
		if ($a = recuperer_infos_distantes($source)) {
			# fichier local pour creer la vignette (!!),
			# on retablira la valeur de l'url a la fin
			$fichier = $a['fichier'];

			$id_type = $a['id_type'];
			$taille = $a['taille'];
			$titre = $a['titre'];
			$largeur = $a['largeur'];
			$hauteur = $a['hauteur'];
			$ext = $a['extension'];
			$type_image = $a['type_image'];

			$distant = 'oui';
			$mode = 'document';
		}
		else {
			spip_log("Echec du lien vers le document $source, abandon");
			return;
		}
	}

	else {
	
		$distant = 'non';

		// tester le type de document :
		// - interdit a l'upload ?
		// - quel numero dans spip_types_documents ?  =-(
		// - est-ce "inclus" comme une image ?
		ereg("\.([^.]+)$", $nom_envoye, $match);
		$ext = addslashes(corriger_extension(strtolower($match[1])));

		// Si le fichier est de type inconnu, on va le stocker en .zip
		if (!$row = spip_fetch_array(spip_query(
		"SELECT * FROM spip_types_documents
		WHERE extension='$ext' AND upload='oui'"))) {

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .BIN */
/*			$ext = 'bin';
			$nom_envoye .= '.bin';
			spip_log("Extension $ext");
			if (!$row = spip_fetch_array(spip_query(
			"SELECT * FROM spip_types_documents
			WHERE extension='bin' AND upload='oui'"))) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
*/

/* STOCKER LES DOCUMENTS INCONNUS AU FORMAT .ZIP */
			$ext = 'zip';

			if (!$row = spip_fetch_array(spip_query(
			"SELECT * FROM spip_types_documents
			WHERE extension='zip' AND upload='oui'"))) {
				spip_log("Extension $ext interdite a l'upload");
				return;
			}
			if (!$tmp_dir = tempnam(_DIR_SESSIONS, 'tmp_upload')) return;
			@unlink($tmp_dir); @mkdir($tmp_dir);
			if (!is_dir(_DIR_IMG.'tmp')) @mkdir(_DIR_IMG.'tmp');
			$tmp = $tmp_dir.'/'.translitteration($nom_envoye);
			$nom_envoye .= '.zip'; # conserver l'extension dans le nom de fichier, par exemple toto.js => toto.js.zip
			$fichier = deplacer_fichier_upload($source, $tmp);
			include_ecrire('pclzip.lib');
			$source = _DIR_IMG.'tmp/archive.zip';
			$archive = new PclZip($source);
			$v_list = $archive->create($tmp,
				PCLZIP_OPT_REMOVE_PATH, $tmp_dir,
				PCLZIP_OPT_ADD_PATH, '');
			effacer_repertoire_temporaire($tmp_dir);
			if (!$v_list) {
				spip_log("Echec creation du zip ");
				return;
			}
		}
		$id_type = $row['id_type'];	# numero du type dans spip_types_documents:(
		$type_inclus_image = ($row['inclus'] == 'image');

		// Recopier le fichier a son emplacement definitif
		$fichier = copier_document($ext, $nom_envoye, $source);
		if (!$fichier) {
			spip_log("Impossible de copier_document($ext, $nom_envoye, $source)");
			return;
		}

		// Quelques infos sur le fichier
		if (!@file_exists($fichier)
		OR !$taille = @filesize($fichier)) {
			spip_log ("Echec copie du fichier $fichier");
			return;
		}

		// Si c'est une image, recuperer sa taille et son type (detecte aussi swf)
		$size_image = @getimagesize($fichier);
		$largeur = intval($size_image[0]);
		$hauteur = intval($size_image[1]);
		$type_image = decoder_type_image($size_image[2]);

		// Si on veut uploader une vignette, il faut qu'elle ait ete bien lue
		if ($mode == 'vignette' AND !($largeur * $hauteur)) {
			@unlink($fichier);
			return;
		}
	}

	// regler l'ancre du retour
	if (!$GLOBALS['ancre']) {
		if ($mode=='vignette')
			$GLOBALS['ancre'] = 'images';
		else if ($type_image)
			$GLOBALS['ancre'] = 'portfolio';
		else
			$GLOBALS['ancre'] = 'documents';
	}

	// Preparation vignette du document $id_document
	$id_document=intval($id_document);
	if ($mode == 'vignette' AND $id_document_lie = $id_document) {
		# on force le statut "document" de ce fichier (inutile ?)
		spip_query("UPDATE spip_documents
			SET mode='document'
			WHERE id_document=$id_document");
		$id_document = 0;
	}

	// Installer le document dans la base
	// attention piege semantique : les images s'installent en mode 'vignette'
	// note : la fonction peut "mettre a jour un document" si on lui
	// passe "mode=document" et "id_document=.." (pas utilise)
	if (!$id_document) {
		// Inserer le nouveau doc et recuperer son id_
		$id_document = spip_abstract_insert("spip_documents",
		"(id_type, titre, date, distant)",
		"($id_type, '".addslashes($titre)."', NOW(), '$distant')");

		if ($id_lien
		AND preg_match('/^[a-z0-9_]+$/i', $type_lien) # securite
		)
			spip_query("INSERT INTO spip_documents_".$type_lien."s
				(id_document, id_".$type_lien.")
				VALUES ($id_document, $id_lien)");

		// par defaut (upload ZIP ou ftp) integrer
		// les images en mode 'vignette' et le reste en mode document
		if (!$mode)
			if ($type_image AND $type_inclus_image)
				$mode = 'vignette';
			else
				$mode = 'document';
		$update = "mode='$mode', ";
	}

	// Mise a jour des donnees
	spip_query("UPDATE spip_documents
		SET $update
		taille='$taille', largeur='$largeur', hauteur='$hauteur',
		fichier='$fichier'
		WHERE id_document=$id_document");

	if ($id_document_lie) {
		spip_query ("UPDATE spip_documents
		SET id_vignette=$id_document
		WHERE id_document=$id_document_lie");
		// hack pour que le retour vers ecrire/ active le bon doc.
		$documents_actifs[] = $id_document_lie; 
	}
	else
		$documents_actifs[] = $id_document; 

	// Pour les fichiers distants remettre l'URL de base
	if ($distant == 'oui')
		spip_query("UPDATE spip_documents SET fichier='".addslashes($source)."'
		WHERE id_document = $id_document");

	// Demander l'indexation du document
	include_ecrire('inc_index');
	marquer_indexer('document', $id_document);

	return true;
}

function afficher_compactes($action) {
	install_debut_html(_T('upload_fichier_zip')); echo "<p>",
		_T('upload_fichier_zip_texte'),
		"</p>",
		"<p>",
		_T('upload_fichier_zip_texte2'),
							"</p>",$action;
	install_fin_html();
}

//
// Traiter la liste des fichiers (spip_action_joindre3)
//

function examiner_les_fichiers($files, $mode, $type, $id, $id_document, $hash, $id_auteur, $redirect, &$actifs)
{
	if (function_exists('gzopen') 
	AND !($mode == 'distant')
	AND (count($files) == 1)) {

		$desc = $files[0];
		if (preg_match('/\.zip$/i', $desc['name'])
		    OR ($desc['type'] == 'application/zip')) {
	
	  // on pose le fichier dans le repertoire zip 
	  // (nota : copier_document n'ecrase pas un fichier avec lui-meme
	  // ca autorise a boucler)
			$zip = copier_document("zip",
					$desc['name'],
					$desc['tmp_name']
				);
			if (!$zip) die ('Erreur upload zip'); # pathologique
			// Est-ce qu'on sait le lire ?
			include_ecrire('pclzip.lib');
			$archive = new PclZip($zip);
			if ($archive) {
// presenter une interface pour choisir si fichier joint ou decompacte
// passer ca en squelette un de ces jours.

			  include_ecrire('inc_documents');
			  $texte =
			"<div><input type='radio' checked='checked' name='sousaction5' value='5'>" .
			_T('upload_zip_telquel').
			"</div>".
			"<div><input type='radio' name='sousaction5' value='6'>".
			_T('upload_zip_decompacter').
			"</div>".
			"<ul><li>" .
			join("</li>\n<li>",verifier_compactes($archive)) .
			"</li></ul>".
			"<div>&nbsp;</div>".
			"<div style='text-align: right;'><input class='fondo' style='font-size: 9px;' type='submit' value='".
			_T('bouton_valider').
			  "'></div>";
			  afficher_compactes(construire_upload($texte, array(
					 'redirect' => $redirect,
					 'hash' => $hash,
					 'id_auteur' => $id_auteur,
					 'id' => $id,
					 'chemin' => $zip,
					 'arg' => $mode,
					 'type' => $type)));
			  // a tout de suite en joindre5 ou joindre6
			  exit;
			}
		}
	}
	foreach ($files as $arg) {
		check_upload_error($arg['error']);
		ajouter_un_document($arg['tmp_name'], $arg['name'], 
				    $type, $id, $mode, $id_document, $actifs);
	}
}


function identifie_repertoire_et_rubrique($DIR, $id_rubrique, $id_auteur, $art=0)
{
  static $exts = array();

  $handle = @opendir($DIR);
  if (!$handle) {spip_log("$DIR pas lisible") ; return 0;}

  $textes = array();
  $docs = array();
  $rubriques = array();

  // collecte des 3 sortes d'entrees
  while (($entree = readdir($handle)) !== false) {
    $chemin = "$DIR/$entree";
    if ($entree[0] !='.') {
      if (is_dir($chemin)) {
	$rubriques[$chemin] = $entree;
    } else {
	if (is_readable($chemin) &&
	    (preg_match("/^([a-zA-Z0-9_].*)\.([a-zA-Z0-9]+)$/", $entree, $match))) {
	  $ext = strtolower($match[2]);

	  if (($ext == 'html') || ($ext == 'htm') || ($ext == 'txt'))
	    $textes[$match[1]] = $match[2];
	  else {
	    if ($ext == 'jpeg') $ext = 'jpg';
	    if (!isset($exts[$ext])) {
	      $req = "SELECT extension FROM spip_types_documents WHERE extension='$ext'";
	      $exts[$ext] = spip_fetch_array(spip_query($req)) ? 'oui' : 'non';
	    }
	    if ($exts[$ext] == 'non') continue;
	    $docs[] = $entree;
	  }
	}
      }
    }
  }
  closedir($handle);
  $collecte = count($textes);
  if (isset($textes['index'])) {

    $texte = spip_file_get_contents($DIR . "/index." .$textes['index']);
    if (preg_match(',<body[^>]*>(.*)</body>,is', $texte, $match))
      $texte = $match[1];
    $texte = '<html>'. $texte  . '</html>';
    $texte = addslashes($texte);
    spip_query("UPDATE spip_rubriques SET texte='" .$texte . "'
WHERE id_rubrique=$id_rubrique");
	unset($textes['index']);
  }

  // integration a la base SQL en commencant par les textes,
  // afin de savoir combien ont ete crees, a chaque niveau.

  if ($textes) {
    ksort($textes);
    foreach($textes as $k=>$v) {
	$titre= addslashes(trim(preg_replace('/[-_]/', ' ', $k)));
	$chemin = "$DIR/$k." . $v;
	$texte = spip_file_get_contents($chemin);
	if ($v[0] == 'h') {
	  // cas du html
	  if (preg_match(',<body[^>]*>(.*)</body>,is', $texte, $match))
	    $texte = $match[1];
	  $texte = '<html>'. $texte  . '</html>';
	} else {$texte = '<pre>'. $texte  . '</pre>';}
	$texte = addslashes($texte);
	$date = date("Y-m-d H:i:s", filemtime($chemin));
	$art=spip_abstract_insert($GLOBALS['table_prefix']. "_articles",
				  "(id_rubrique,titre,texte,date,statut)",
				  "($id_rubrique, '$titre', '$texte', '$date', 'prop')");
	spip_abstract_insert($GLOBALS['table_prefix']. "_auteurs_articles", 
			     "(id_auteur,id_article)",
			     "($id_auteur,$art)");
    }
  }


  // si la rubrique a 0 ou 1  article, les documents sont 
  // joints l'article present ou de niveau superieur, sinon a la rubrique.


  if (($collecte <= 1) AND $art)
    {$type = 'article'; $id = $art;}
  else
    {$type = 'rubrique'; $id = $id_rubrique; $collecte += count($docs);}

  if ($docs) {
    foreach($docs as $v) 
	ajouter_un_document ("$DIR/$v",$v,$type,$id,'document',0,$actifs);
  }

  if ($rubriques) {
    foreach ($rubriques as $k => $v)
      {

	$rub=spip_abstract_insert($GLOBALS['table_prefix'] . "_rubriques",
				  "(titre,id_parent,statut)",
				  "('" . addslashes($v) . "', $id_rubrique, 'prepa')");
	$m = identifie_repertoire_et_rubrique($k, $rub, $id_auteur, $art);
	if ($m)
	  $collecte+=$m;
	else {
	  // faudrait essayer de prevenir plutot que de guerir
	  spip_query("DELETE FROM spip_rubriques WHERE id_rubrique=$rub");
	}
      }
  }
  return $collecte;
 
}


//
// Convertit le type numerique retourne par getimagesize() en extension fichier
//

function decoder_type_image($type, $strict = false) {
	switch ($type) {
	case 1:
		return "gif";
	case 2:
		return "jpg";
	case 3:
		return "png";
	case 4:
		return $strict ? "" : "swf";
	case 5:
		return "psd";
	case 6:
		return "bmp";
	case 7:
	case 8:
		return "tif";
	default:
		return "";
	}
}


//
// Corrige l'extension du fichier dans quelques cas particuliers
//

function corriger_extension($ext) {
	switch ($ext) {
	case 'htm':
		return 'html';
	case 'jpeg':
		return 'jpg';
	case 'tiff':
		return 'tif';
	default:
		return $ext;
	}
}



?>
