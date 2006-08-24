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

// http://doc.spip.org/@action_editer_article_dist
function action_editer_article_dist() {


	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$lier_trad = _request('lier_trad');
	$id_parent =_request('id_parent');

	if (!$id_article = intval($arg)) {
		if ($arg != 'oui') redirige_par_entete('./');
	        $id_article = insert_article($id_parent);
	} 
	  
	// Enregistre l'envoi dans la BD
	$err = articles_set($id_article, $id_parent, $lier_trad, $arg=='oui');

	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_article', $id_article, '&') . ($err ? '&trad_err=1' : '');

	redirige_par_entete($redirect);
}

// http://doc.spip.org/@insert_article
function insert_article($id_parent)
{
	include_spip('base/abstract_sql');
	$id_auteur =  _request('id_auteur');
	$id_parent =  _request('id_parent');

	// Avec l'Ajax parfois id_rubrique vaut 0... ne pas l'accepter
	if (!$id_rubrique = intval($id_parent)) {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
		$id_rubrique = $row['id_rubrique'];
	}

	$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

	$id_article = spip_abstract_insert("spip_articles",
			"(id_rubrique, statut, date, accepter_forum, lang, langue_choisie)", 
			"($id_rubrique, 'prepa', NOW(), '" .
				substr($GLOBALS['meta']['forums_publics'],0,3)
				. "', '"
				. ($row["lang"] ? $row["lang"] : $GLOBALS['meta']['langue_site'])
				. "', 'non')");
	spip_abstract_insert('spip_auteurs_articles', "(id_auteur,id_article)", "('$id_auteur','$id_article')");
	return $id_article;
}

// http://doc.spip.org/@articles_set
function articles_set($id_article, $id_rubrique, $lier_trad, $new)
{
	include_spip('inc/filtres');
	include_spip('inc/rubriques');

	// si editer_article='oui', on modifie le contenu
	if (_request('editer_article') == 'oui') {
		revisions_articles($id_article, $id_rubrique, $new);
	}

	if ($lier_trad)
		$err = article_referent($id_article, $lier_trad);

	return $err;
}

// http://doc.spip.org/@revisions_articles
function revisions_articles ($id_article, $id_rubrique, $new) {
{
	global $flag_revisions, $champs_extra;

	$id_auteur = _request('id_auteur');

	// unifier $texte en cas de texte trop long
	trop_longs_articles();

	// ne pas accepter de titre vide
	if (_request('titre') === '')
		$_POST['titre'] = _T('ecrire:info_sans_titre');

	foreach (array(
	'surtitre', 'titre', 'soustitre', 'descriptif',
	'nom_site', 'url_site', 'chapo', 'texte', 'ps') as $champ) {
		if (($val = _request($champ)) !== NULL) {
			$champs[$champ] = corriger_caracteres($val);
		}
	}

	// Stockage des versions : creer une premier version si non-existante
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		include_spip('inc/revisions');
		if  (!$new) {
			$query = spip_query("SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1");
			if (!spip_num_rows($query)) {
				$select = join(", ", array_keys($champs));
				$query = spip_query("SELECT $select FROM spip_articles WHERE id_article=$id_article");
				$champs_originaux = spip_fetch_array($query);
				$id_version = ajouter_version($id_article, $champs_originaux, _T('version_initiale'), 0);

				// Remettre une date un peu ancienne pour la version initiale 
				if ($id_version == 1) // test inutile ?
				spip_query("UPDATE spip_versions SET date=DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE id_article=$id_article AND id_version=1");
			}
		}
	}

	if ($champs_extra) {
		include_spip('inc/extra');
		$champs_extra = extra_recup_saisie("articles", _request('id_secteur'));
	}

	$update = '';
	foreach ($champs as $champ => $val)
		$update .= $champ . '=' . spip_abstract_quote($val).', ';

	spip_query("UPDATE spip_articles SET id_rubrique=$id_rubrique, $update date_modif=NOW() " . ($champs_extra ? (", extra = " . spip_abstract_quote($champs_extra)) : '') . " WHERE id_article=$id_article");

	// Stockage des versions
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		ajouter_version($id_article, $champs, '', $id_auteur);
	}

	// marquer le fait que l'article est travaille par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=articles
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		if ($id_article)
			signale_edition ($id_article, $id_auteur, 'article');
	}


	// Changer la langue heritee
	if ($id_rubrique != _request('id_rubrique_old')) {
		propager_les_secteurs();
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
			if ($langue_new != $langue_old)
				spip_query("UPDATE spip_articles SET lang = '$langue_new' WHERE id_article = $id_article");
		}
	}

	calculer_rubriques();
 }
}


//
// Reunit les textes decoupes parce que trop longs
//

// http://doc.spip.org/@trop_longs_articles
function trop_longs_articles() {
#	print_r($_POST);exit;
	if (isset($_POST['texte_plus']) && is_array($_POST['texte_plus'])) {
		foreach ($_POST['texte_plus'] as $t) {
			$_POST['texte'] = preg_replace(",<!--SPIP-->[\n\r]*,","", $t)
				. $_POST['texte'];
		}
	}
}

// Poser un lien de traduction vers un article de reference
// http://doc.spip.org/@article_referent
function article_referent ($id_article, $lier_trad) {

	$lier_trad = intval($lier_trad);

	// selectionner l'article cible, qui doit etre different de nous-meme,
	// et quitter s'il n'existe pas
	if (!$row = spip_fetch_array(
	spip_query("SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad AND NOT(id_article=$id_article)")))
	{
		spip_log("echec lien de trad vers article inexistant ($lier_trad)");
		return 'erreur';
	}

	// $id_lier est le numero du groupe de traduction
	$id_lier = $row['id_trad'];
	spip_log("traduction $id_article, $id_trad, $lier_trad, $id_lier");

	// Si l'article vise n'est pas deja traduit, son identifiant devient
	// le nouvel id_trad de ce nouveau groupe et on l'affecte aux deux
	// articles
	if ($id_lier == 0) {
		spip_query("UPDATE spip_articles SET id_trad = $lier_trad WHERE id_article IN ($lier_trad, $id_article)");
	}
	// sinon on ajouter notre article dans le groupe
	else {
		spip_query("UPDATE spip_articles SET id_trad = $id_lier WHERE id_article = $id_article");
	}

	return false; # pas d'erreur
}

?>
