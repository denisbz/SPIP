<?php

// SPIP est-il installe ?
if (!@file_exists("inc_connect.php3")) {
	header("Location: install.php3");
	exit;
}

include ("inc_version.php3");

include_ecrire("inc_auth.php3");

include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_layer.php3");
include_ecrire("inc_rubriques.php3");

if (!@file_exists("data/inc_meta_cache.php3")) ecrire_metas();


//
// Preferences de presentation
//

if ($lang = $GLOBALS['HTTP_COOKIE_VARS']['spip_lang_ecrire'] AND $lang <> $auteur_session['lang'] AND changer_langue($lang)) {
	spip_query ("UPDATE spip_auteurs SET lang = '".addslashes($lang)."' WHERE id_auteur = $connect_id_auteur");
	$auteur_session['lang'] = $lang;
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


// Debloquer articles
if ($debloquer_article) {
	if ($debloquer_article <> 'tous')
		$where_id = "AND id_article=".intval($debloquer_article);
	$query = "UPDATE spip_articles SET auteur_modif='0' WHERE auteur_modif=$connect_id_auteur $where_id";
	spip_query ($query);
}

// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];

switch ($prefs['couleur']) {
	case 6:
		/// Jaune
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#657701";
		$couleur_lien_off="#A6C113";
		break;
	case 1:
		/// Violet clair
		$couleur_foncee="#AA015D";
		$couleur_claire="#DF87C1";
		$couleur_lien="#E95503";
		$couleur_lien_off="#8F004D";
		break;
	case 2:
		/// Orange
		$couleur_foncee="#87585a";
		$couleur_claire="#f78820";
		$couleur_lien="#81A0C1";
		$couleur_lien_off="#FF5B00";
		break;
	case 3:
		/// Saumon
		$couleur_foncee="#CDA261";
		$couleur_claire="#FFDDAA";
		$couleur_lien="#5E0283";
		$couleur_lien_off="#472854";
		break;
	case 4:
		/// Bleu pastelle
		$couleur_foncee="#316a7e";
		$couleur_claire="#629599";
		$couleur_lien="#869100";
		$couleur_lien_off="#5B55A0";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#727D87";
		$couleur_claire="#C0CAD4";
		$couleur_lien="#854270";
		$couleur_lien_off="#666666";
		break;
	default:
		/// Jaune
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#116E95";
		$couleur_lien_off="#50A4C7";
}


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
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


function tester_rubrique_vide($id_rubrique) {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_rubrique' LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_article FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_document FROM spip_documents_rubriques WHERE id_rubrique='$id_rubrique' LIMIT 0,1";
	list($n) = spip_fetch_row(spip_query($query));
	if ($n > 0) return false;

	return true;
}


//
// Recuperation du cookie
//

$cookie_admin = $HTTP_COOKIE_VARS['spip_admin'];



//
// Supprimer / valider forum
//

function changer_statut_forum($id_forum, $statut) {
	global $connect_statut, $connect_toutes_rubriques;

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) return;

	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_forum";
	$result = spip_query($query);
 	if ($row = spip_fetch_array($result)) {
		$id_parent = $row['id_parent'];
		$id_rubrique = $row['id_rubrique'];
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_syndic = $row['id_syndic'];
	}
	else return;

	unset($where);
	if ($id_article) $where[] = "id_article=$id_article";
	if ($id_rubrique) $where[] = "id_rubrique=$id_rubrique";
	if ($id_breve) $where[] = "id_breve=$id_breve";
	if ($id_syndic) $where[] = "id_syndic=$id_syndic";
	if ($id_parent) $where[] = "id_forum=$id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = spip_query($query);
		unset($fichiers);
		if ($result) while ($row = spip_fetch_array($result)) {
			$fichier = $row['fichier'];
			// crade, ne marche pas avec open_basedir=.
			@unlink("../CACHE/$fichier");
			@unlink("../CACHE/$fichier.NEW");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			spip_query($query);
		}
	}

	// signaler au moteur de recherche qu'il faut reindexer le thread
	// (en fait on se contente de demander une reindexation du parent)
	include_ecrire('inc_index.php3');
	marquer_indexer ('forum', $id_parent);

	// changer le statut de toute l'arborescence dependant de ce message
	$id_messages = array($id_forum);
	while ($id_messages) {
		$id_messages = join(',', $id_messages);
		$query_forum = "UPDATE spip_forum SET statut='$statut' WHERE id_forum IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		$query_forum = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($id_messages)";
		$result_forum = spip_query($query_forum);
		unset($id_messages);
		while ($row = spip_fetch_array($result_forum)) {
			$id_messages[] = $row['id_forum'];
		}
	}
}

if ($supp_forum) changer_statut_forum($supp_forum, 'off');
if ($supp_forum_priv) changer_statut_forum($supp_forum_priv, 'privoff');
if ($valid_forum) changer_statut_forum($valid_forum, 'publie');



// Supprimer rubrique
if ($supp_rubrique = intval($supp_rubrique) AND $connect_statut == '0minirezo' AND acces_rubrique($supp_rubrique)) {
	$query = "DELETE FROM spip_rubriques WHERE id_rubrique=$supp_rubrique";
	$result = spip_query($query);

	calculer_rubriques();
}


?>
