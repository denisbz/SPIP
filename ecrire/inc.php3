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


if (!defined('_ECRIRE_INC_VERSION')) {
	include ("inc_version.php3");
}

include_ecrire('inc_auth.php3');
include_ecrire("inc_minipres.php"); // choisit la langue
include_ecrire('inc_admin.php3');
include_ecrire("inc_presentation.php3");
include_ecrire('inc_cookie.php');
include_ecrire("inc_calendrier.php");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_layer.php3");
include_ecrire("inc_rubriques.php3");

//
// Preferences de presentation
//

# teste la capacite ajax : on envoie un cookie -1
# et un script ajax ; si le script reussit le cookie passera a +1
if (!$GLOBALS['_COOKIE']['spip_accepte_ajax']) {
	spip_setcookie('spip_accepte_ajax', -1);
}

if ($spip_lang_ecrire = $GLOBALS['_COOKIE']['spip_lang_ecrire']
AND $spip_lang_ecrire <> $auteur_session['lang']
AND changer_langue($spip_lang_ecrire)) {
	spip_query ("UPDATE spip_auteurs SET lang = '".addslashes($spip_lang_ecrire)
	."' WHERE id_auteur = $connect_id_auteur");
	$auteur_session['lang'] = $spip_lang_ecrire;
	ajouter_session($auteur_session, $spip_session);
}

if ($set_couleur) {
	$prefs['couleur'] = floor($set_couleur);
	$prefs_mod = true;
}
if ($set_disp) {
	$prefs['display'] = floor($set_disp);
	$prefs_mod = true;
}
if ($set_options == 'avancees' OR $set_options == 'basiques') {
	$prefs['options'] = $set_options;
	$prefs_mod = true;
}
if ($prefs_mod) {
	spip_query ("UPDATE spip_auteurs SET prefs = '".addslashes(serialize($prefs))."' WHERE id_auteur = $connect_id_auteur");
}

if ($set_ecran) {
	// Poser un cookie, car ce reglage depend plus du navigateur que de l'utilisateur
	spip_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
	$spip_ecran = $set_ecran;
}
if (!$spip_ecran) $spip_ecran = "etroit";


// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];


// Vert
if (!$couleurs_spip[1]) $couleurs_spip[1] = array (
		"couleur_foncee" => "#9DBA00",
		"couleur_claire" => "#C5E41C",
		"couleur_lien" => "#657701",
		"couleur_lien_off" => "#A6C113"
);
// Violet clair
if (!$couleurs_spip[2]) $couleurs_spip[2] = array (
		"couleur_foncee" => "#eb68b3",
		"couleur_claire" => "#ffa9e6",
		"couleur_lien" => "#8F004D",
		"couleur_lien_off" => "#BE6B97"
);
// Orange
if (!$couleurs_spip[3]) $couleurs_spip[3] = array (
		"couleur_foncee" => "#fa9a00",
		"couleur_claire" => "#ffc000",
		"couleur_lien" => "#FF5B00",
		"couleur_lien_off" => "#B49280"
);
// Saumon
if (!$couleurs_spip[4]) $couleurs_spip[4] = array (
		"couleur_foncee" => "#CDA261",
		"couleur_claire" => "#FFDDAA",
		"couleur_lien" => "#AA6A09",
		"couleur_lien_off" => "#B79562"
);
//  Bleu pastelle
if (!$couleurs_spip[5]) $couleurs_spip[5] = array (
		"couleur_foncee" => "#5da7c5",
		"couleur_claire" => "#97d2e1",
		"couleur_lien" => "#116587",
		"couleur_lien_off" => "#81B7CD"
);
//  Gris
if (!$couleurs_spip[6]) $couleurs_spip[6] = array (
		"couleur_foncee" => "#85909A",
		"couleur_claire" => "#C0CAD4",
		"couleur_lien" => "#3B5063",
		"couleur_lien_off" => "#6D8499"
);


$choix_couleur = $prefs['couleur'];
if (strlen($couleurs_spip[$choix_couleur]['couleur_foncee']) < 7) $choix_couleur = 1;

$couleur_foncee = $couleurs_spip[$choix_couleur]['couleur_foncee'];
$couleur_claire = $couleurs_spip[$choix_couleur]['couleur_claire'];
$couleur_lien = $couleurs_spip[$choix_couleur]['couleur_lien'];
$couleur_lien_off = $couleurs_spip[$choix_couleur]['couleur_lien_off'];

$attributes_body = "text='#000000' bgcolor='#f8f7f3' link='$couleur_lien' vlink='$couleur_lien_off' alink='$couleur_lien_off' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'" .
  ($spip_lang_rtl ? " dir='rtl'" : "");

//
// Gestion de version
//

$version_installee = (double) str_replace(',','.',lire_meta('version_installee'));
if ($version_installee <> $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = _T('info_anterieur');
	echo "<blockquote><blockquote><h4><font color='red'>"._T('info_message_technique')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')." <a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";
	fin_page();
	exit;
}


//
// Gestion de la configuration globale du site
//

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
}
if (!$activer_breves){
	$activer_breves = lire_meta("activer_breves");
	$articles_mots = lire_meta("articles_mots");
}

if (!$activer_statistiques){
	$activer_statistiques = lire_meta("activer_statistiques");
}

if (!$nom_site_spip) {
	$nom_site_spip = _T('info_mon_site_spip');
	ecrire_meta("nom_site", $nom_site_spip);
	ecrire_metas();
}

if (!$adresse_site) {
	$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/ecrire"));
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_metas();
}



//
// Recuperation du cookie
//

$cookie_admin = $_COOKIE['spip_admin'];

?>
