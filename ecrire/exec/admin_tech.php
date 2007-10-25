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

// http://doc.spip.org/@exec_admin_tech_dist
function exec_admin_tech_dist()
{
	global $flag_gz, $spip_lang_right;
	if (!autoriser('sauvegarder')){
		include_spip('inc/minipres');
		echo minipres();
	} else {
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_admin_tech'), "configuration", "base");


	if ($GLOBALS['connect_toutes_rubriques']) {

		echo debut_gauche('',true);
		echo "<br /><br /><br /><br />";
		echo debut_boite_info(true);
		echo  _T('info_gauche_admin_tech');
		echo fin_boite_info(true);
		$repertoire = _DIR_DUMP;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		$dir_dump = $repertoire;
		$onglet = barre_onglets("administration", "sauver") . "<br />";
	} else {
		echo debut_gauche(true);
		$dir_dump = determine_upload();
		$onglet = '';
	}

	$dir_dump = joli_repertoire($dir_dump);

	echo debut_droite('',true);

	echo "<div style='text-align: center'>",
	  gros_titre(_T('titre_admin_tech'),'',false),
	  '</div>',
	  $onglet;

	//
	// Sauvegarde de la base
	//

	echo debut_cadre_trait_couleur('',true,'',_T('texte_sauvegarde'),'sauvegarder');

	// a passer en fonction
	if (substr(_DIR_IMG, 0, strlen(_DIR_RACINE)) === _DIR_RACINE)
	 $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
	else
	 $dir_img = _DIR_IMG;

	$res = 
	 "\n<p>" .
	 http_img_pack('warning.gif', _T('info_avertissement'), 
		 "style='width: 48px; height: 48px; float: right;margin: 10px;'") .
	 _T('texte_admin_tech_01',
	   array('dossier' => '<i>'.$dir_dump.'</i>', 'img'=>'<i>'.$dir_img.'</i>')) .
	 '&nbsp;' .
	 _T('texte_admin_tech_02') .
	"</p>";
	
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form = $chercher_rubrique(0, 'rubrique', !$GLOBALS['connect_toutes_rubriques'], 0, 'admin_tech');

	$res .= "\n<p><label for='id_parent'>" .
		  _L("Dans un but de fusion avec une autre base, vous pouvez limiter la sauvegarde &agrave; la rubrique: ") .
		  "</label>" .
		  $form
		  . "</p>";

	$file = nom_fichier_dump();
	$nom = "\n<input name='nom_sauvegarde' id='nom_sauvegarde' size='40' value='$file' />";
	$znom = "\n<input name='znom_sauvegarde' id='znom_sauvegarde' size='40' value='$file' />";
	
	if ($flag_gz) {
	
	$res .= "\n<p>" .
	  _T('texte_admin_tech_03') .
	  "</p>\n<ul>" .
	  "\n<li style='list-style:none;'><input type='radio' name='gz' value='1' id='gz_on' checked='checked' /><label for='gz_on'> " .
	  _T('bouton_radio_sauvegarde_compressee', array('fichier'=>'')) .
	  " </label><br />\n" .
	  '<b>' . $dir_dump . "</b>" .
	  $znom .
	  "<b>.xml.gz</b></li>" . 
	  "\n<li style='list-style:none;'><input type='radio' name='gz' value='0' id='gz_off' /><label for='gz_off'>" .
	  _T('bouton_radio_sauvegarde_non_compressee',  array('fichier'=>'')) .
	  '</label><br /><b>' .
	  $dir_dump .
	  "</b>$nom<b>.xml</b></li></ul>\n";
	}
	else {
	  $res .= "\n<p><label for='gz'>" .
	    _T('texte_sauvegarde_compressee' .
	       array('fichier'=>'<br /><b>' . $dir_dump . "</b>$nom<b>.xml</b>")) .
	       "</label>" .
	    "\n<input type='hidden' name='gz' id='gz' value='0' /></p>";
	}


	$res .= "\n<input type='hidden' name='reinstall' value='non' />";
 
	echo 
 		generer_form_ecrire('export_all', $res, '', _T('texte_sauvegarde_base')),
 		fin_cadre_trait_couleur(true);

	//
	// Restauration de la base
	//

	// restaurer est equivalent a detruire, ou pas (cas des restaurations partielles, a affiner ?)
	if (autoriser('detruire')) {
	
		$liste_dump = preg_files(_DIR_DUMP,'\.xml(\.gz)?$',50,false);
		$selected = end($liste_dump);
		$liste = ""; 
		$i = 0;
		foreach($liste_dump as $k=>$f){
		  $i++;
		  $class = 'row_'.alterner($i, 'even', 'odd');
		  $liste .= liste_sauvegardes($k, $f, $class, $selected);
		}
		$class = 'row_'.alterner($i+1, 'even', 'odd');
		$liste = "<br /><br /><table class='spip'>"
		.  $liste
		. "\n<tr class='$class'><td><input type='radio' name='archive' id='archive' value='' /></td><td  colspan='3'>"
		. "\n<span class='spip_x-small'><input type='text' name='archive_perso' id='archive_perso' value='$fichier_defaut' size='55' /></span></td></tr>"
		. '</table>';

		if ($flag_gz) {
			$fichier_defaut = str_replace(array("@stamp@","@nom_site@"),array("",""),_SPIP_DUMP) . '.gz';
			$texte_compresse = _T('texte_compresse_ou_non')."&nbsp;";
		} else {
			$fichier_defaut = str_replace(array("@stamp@","@nom_site@"),array("",""),_SPIP_DUMP);
			$texte_compresse = _T('texte_non_compresse')."&nbsp;";
		}

		echo debut_cadre_trait_couleur('',true,'',
			_T('texte_restaurer_base'),'restaurer');

		$res = "\n<p style='text-align: justify;'> " .
		_T('texte_restaurer_sauvegarde', array('dossier' => '<i>'.$dir_dump.'</i>')) .
		  '</p>' .
		_T('entree_nom_fichier', array('texte_compresse' => $texte_compresse)) .
		$liste  ;

		// restauration partielle / fusion
		$res .=
		  debut_cadre_enfonce('',true) .
		"\n<div>" .
		 "<input name='insertion' id='insertion' type='checkbox' />&nbsp; <label for='insertion'>". 
		  _T('sauvegarde_fusionner') .
		  "</label><br />\n" .
		 "<input name='statut' id='statut' type='checkbox' />&nbsp; <label for='statut'>\n". 
		  _L('D&eacute;publier les objets fusionn&eacute;s') .
		  "</label><br />\n" .
		  "<label for='url_site'>" .
		  _T('sauvegarde_url_origine') .
		  "</label>" .
		  " &nbsp;\n<input name='url_site' id='url_site' type='text' size='25' />" .
		  '</div>' .
		  fin_cadre_enfonce(true);

		echo generer_form_ecrire('import_all', $res, '', _T('bouton_restaurer_base'));

		echo fin_cadre_trait_couleur(true);

	}

	//
	// Lien vers la reparation
	//

	if (autoriser('detruire')) {
		if (version_compare(sql_version(),'3.23.14','>=')) {
			$res = "\n<p style='text-align: justify;'>".
				_T('texte_crash_base') .
				"\n</p>";
	
			echo 
				debut_cadre_trait_couleur('',true,'',_T('texte_recuperer_base'),'reparer'),
				generer_form_ecrire('admin_repair', $res, '', _T('bouton_tenter_recuperation')),
				fin_cadre_trait_couleur(true);
		}
	}

	echo fin_cadre_relief(true);

	echo "<br />";

	echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@liste_sauvegardes
function liste_sauvegardes($key, $fichier, $class, $selected)
{
	$affiche_fichier = substr($fichier,strlen(_DIR_DUMP));
	return "\n<tr class='$class'><td><input type='radio' name='archive' value='"
		. $affiche_fichier
		. "' id='dump_$key' "
		.  (($fichier==$selected)?"checked='checked' ":"")
		. "/></td><td>\n<label for='dump_$key'>"
		.   $file = str_replace('/', ' / ', $affiche_fichier)
		. '</label></td><td>'
		. taille_en_octets(filesize($fichier))
		. '</td><td>'
		. affdate_heure(date('Y-m-d H:i:s',filemtime($fichier)))
		. '</td></tr>';
}

// http://doc.spip.org/@nom_fichier_dump
function nom_fichier_dump()
{
	global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();
	$site = isset($GLOBALS['meta']['nom_site'])
	  ? preg_replace(array(",\W,is",",_(?=_),",",_$,"),array("_","",""), couper(translitteration(trim($GLOBALS['meta']['nom_site'])),30,""))
	  : 'spip';

	$site .= '_' . date('Ymd');

	$nom = $site;
	$cpt=0;
	while (file_exists($dir. $nom . ".xml") OR
	       file_exists($dir. $nom . ".xml.gz")) {
		$nom = $site . sprintf('_%03d', ++$cpt);
	}
	return $nom;
}
?>
