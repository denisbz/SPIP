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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite


function balise_FORMULAIRE_ADMIN ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ADMIN', array());
}

# on ne peut rien dire au moment de l'execution du squelette

function balise_FORMULAIRE_ADMIN_stat($args, $filtres) {
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

function balise_FORMULAIRE_ADMIN_dyn($float='', $debug='') {

	global $var_preview, $use_cache, $forcer_debug, $xhtml;
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, $id_syndic;
	static $dejafait = false;

	if (!$GLOBALS['spip_admin'])
		return '';

	if (!is_array($debug)) {
		if ($dejafait)
			return '';
	} else {
		if ($dejafait) {
			$res = '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'formulaire_admin.') !== false)
					return $debug['resultat'][$k . 'tout'];
			}
			return '';
		}
	}
	$dejafait = true;
	include_spip('inc/urls');
	$objet_affiche = '';

	// Ne pas afficher le bouton 'Modifier ce...' si l'objet n'existe pas
	foreach (array('article', 'breve', 'rubrique', 'mot', 'auteur', 'syndic') as $type) {
		$id_type = id_table_objet($type);
		if ($n = intval($$id_type)) {
			$s = spip_query("SELECT $id_type FROM spip_".table_objet($type)."	WHERE $id_type=".$$id_type);
			if ($s AND spip_num_rows($s)) {
				$$id_type = $n;
				$objet_affiche = $type;
				break;
			}
		}
	}

	$statut = isset($GLOBALS['auteur_session']['statut']) ?
		$GLOBALS['auteur_session']['statut'] : '';

	// Bouton statistiques
	$visites = $popularite = $statistiques = '';
	if ($GLOBALS['meta']["activer_statistiques"] != "non" 
	AND $id_article
	AND !$var_preview
	AND $statut == '0minirezo'
	) {
		$result = spip_query("SELECT visites, popularite FROM spip_articles WHERE id_article=$id_article AND statut='publie'");

		if ($row = @spip_fetch_array($result)) {
			$visites = intval($row['visites']);
			$popularite = ceil($row['popularite']);
			$statistiques = str_replace('&amp;', '&', generer_url_ecrire_statistiques($id_article));
		}
	}

	// Bouton de debug
	$debug =
	(
		(	$forcer_debug
			OR $GLOBALS['bouton_admin_debug']
			OR (
				$GLOBALS['var_mode'] == 'debug'
				AND $GLOBALS['_COOKIE']['spip_debug']
			)
		) AND (
			$statut == '0minirezo'
		) AND (
			!$var_preview
		)
	) ? parametre_url(self(),'var_mode', 'debug', '&'): '';
	$analyser = !$xhtml ? "" :
		(($xhtml === 'spip_sax') ?
		(parametre_url(self(), 'var_mode', 'debug', '&')
			.'&var_mode_affiche=validation') :
		('http://validator.w3.org/check?uri='
		. rawurlencode("http://" . $_SERVER['HTTP_HOST'] . nettoyer_uri())));

	// hack - ne pas avoir la rubrique si un autre bouton est deja present
	if ($id_article OR $id_breve) unset ($id_rubrique);

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!($id_article || $id_rubrique || $id_auteur || $id_breve || $id_mot || $id_syndic))
		$ecrire = _DIR_RESTREINT_ABS;
	else $ecrire = '';
	// Bouton "preview" si l'objet demande existe et est previsualisable
	$preview = false;

	if (!$GLOBALS['var_preview'] AND (
	(($GLOBALS['meta']['preview']=='1comite'
		AND $statut =='1comite')
	OR ($GLOBALS['meta']['preview']<>''
		AND $statut =='0minirezo'))
	)) {
		$p = ($objet_affiche == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui');

		if ($objet_affiche == 'article'
		OR $objet_affiche == 'breve'
		OR $objet_affiche == 'rubrique'
		OR $objet_affiche == 'syndic')
		  $preview = spip_num_rows(spip_query("SELECT id_$objet_affiche FROM spip_".table_objet($objet_affiche)." WHERE ".id_table_objet($objet_affiche)."=".$$id_type." AND ((statut IN ('prop', 'prive')) " . (!$p ? '' : "OR (statut='publie' AND date>NOW())") .")"));
	}

	//
	// Regler les boutons dans la langue de l'admin (sinon tant pis)
	//
	include_spip('inc/lang');
	include_spip('base/abstract_sql');
	$login = preg_replace(',^@,','',$GLOBALS['spip_admin']);
	$alang = spip_abstract_fetsel(array('lang'), array('spip_auteurs'),
		array("login=" . spip_abstract_quote($login)));
	if ($alang['lang']) {
		lang_select($alang['lang']);
		$lang = $GLOBALS['spip_lang'];
		lang_dselect();
	} else
		$lang = '';


	return array('formulaires/formulaire_admin', 0,
		array(
			'id_article' => $id_article,
			'id_rubrique' => $id_rubrique,
			'id_auteur' => $id_auteur,
			'id_breve' => $id_breve,
			'id_mot' => $id_mot,
			'id_syndic' => $id_syndic,
			'voir_article' => str_replace('&amp;', '&', generer_url_ecrire_article($id_article, 'prop')),
			'voir_breve' => str_replace('&amp;', '&', generer_url_ecrire_breve($id_breve, 'prop')),
			'voir_rubrique' => str_replace('&amp;', '&', generer_url_ecrire_rubrique($id_rubrique, 'prop')),
			'voir_mot' => str_replace('&amp;', '&', generer_url_ecrire_mot($id_mot, 'prop')),
			'voir_site' => str_replace('&amp;', '&', generer_url_ecrire_site($id_syndic, 'prop')),
			'voir_auteur' => str_replace('&amp;', '&', generer_url_ecrire_auteur($id_auteur, 'prop')),
			'ecrire' => $ecrire,
			'action' => self(),
			'preview' => $preview?parametre_url(self(),'var_mode','preview','&'):'',
			'debug' => $debug,
			'popularite' => $popularite,
			'statistiques' => $statistiques,
			'visites' => $visites,
			'use_cache' => ($use_cache ? '' : ' *'),
			'divclass' => $float,
			'analyser' => $analyser,
			'lang' => $lang,
			'xhtml_error' => isset($GLOBALS['xhtml_error']) ? $GLOBALS['xhtml_error'] : ''
			)
		     );
}

?>
