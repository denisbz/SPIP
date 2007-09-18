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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/autoriser');
include_spip('base/abstract_sql');

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN
function balise_FORMULAIRE_ADMIN ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ADMIN', array());
}

# on ne peut rien dire au moment de l'execution du squelette

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN_stat
function balise_FORMULAIRE_ADMIN_stat($args, $filtres) {
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN_dyn
function balise_FORMULAIRE_ADMIN_dyn($float='', $debug='') {

	global $var_preview, $use_cache, $forcer_debug, $xhtml;
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, $id_syndic;
	static $dejafait = false;

	if (!@$_COOKIE['spip_admin'])
		return '';

	if (!is_array($debug)) {
		if ($dejafait)
			return '';
	} else {
		if ($dejafait) {
			$res = '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'administration.') !== false)
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
		$_id_type = id_table_objet($type);
		if (isset($$_id_type)) {
			$$_id_type = intval($$_id_type);
			if (sql_countsel(table_objet_sql($type),
					 "$_id_type=".$$_id_type)) {
				$objet_affiche = $type;
				break;
			}
		}
	}

	// Bouton statistiques
	$visites = $popularite = $statistiques = '';
	if ($GLOBALS['meta']["activer_statistiques"] != "non" 
	AND $id_article
	AND !$var_preview
	AND autoriser('voirstats')
	) {
		$result = spip_query("SELECT visites, popularite FROM spip_articles WHERE id_article=$id_article AND statut='publie'");

		if ($row = @sql_fetch($result)) {
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
				AND $_COOKIE['spip_debug']
			)
		) AND (
		       autoriser('debug')
		) AND (
			!$var_preview
		)
	) ? parametre_url(self(),'var_mode', 'debug', '&'): '';
		$analyser = !$xhtml ? "" :
		(($xhtml === 'sax') ?
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

	if (!$GLOBALS['var_preview']) {
		include_spip('inc/autoriser');
		if (autoriser('previsualiser')) {
			$p = ($objet_affiche == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui');

			if ($objet_affiche == 'article'
			OR $objet_affiche == 'breve'
			OR $objet_affiche == 'rubrique'
			OR $objet_affiche == 'syndic')
			  $preview = sql_countsel(table_objet_sql($objet_affiche), id_table_objet($objet_affiche)."=".$$_id_type." AND ((statut IN ('prop', 'prive')) " . (!$p ? '' : "OR (statut='publie' AND date>NOW())") .")");
		}
	}

	//
	// Regler les boutons dans la langue de l'admin (sinon tant pis)
	//

	include_spip('base/abstract_sql');
	$login = preg_replace(',^@,','',@$_COOKIE['spip_admin']);
	$alang = sql_fetsel(array('lang'), array('spip_auteurs'),
		array("login=" . _q($login)));
	if ($alang['lang']) {
		$l = lang_select($alang['lang']);
		$lang = $GLOBALS['spip_lang'];
		if ($l) lang_select();
	} else
		$lang = '';

	// Preparer le #ENV des boutons
	$env = array(
		'ecrire' => $ecrire,
		'action' => self('&'),
		'divclass' => $float,
		'lang' => $lang,
		'calcul' => (_request('var_mode') ? 'recalcul' : 'calcul'),
	);

	if ($preview)
		$env['preview']=parametre_url(self(),'var_mode','preview','&');
	if ($debug)
		$env['debug'] = $debug;
	if ($statistiques) {
		$env['popularite'] = $popularite;
		$env['statistiques'] = $statistiques;
		$env['visites'] = $visites;
	}
	if (!$use_cache)
		$env['use_cache'] = ' *';
	if ($analyser)
		$env['analyser'] = $analyser;
	if (isset($GLOBALS['xhtml_error']) AND $GLOBALS['xhtml_error']) {
		$env['xhtml_error'] = count($GLOBALS['xhtml_error']);
	}
	foreach (array('article','rubrique','auteur','breve','mot','syndic'=>'site')
	as $id => $obj) {
		if (is_int($id)) $id = $obj;
		if (${'id_'.$id}) {
			$env['id_'.$id] = ${'id_'.$id};
			$g = 'generer_url_ecrire_'.$obj;
			$env['voir_'.$obj] = str_replace('&amp;', '&',
				$g(${'id_'.$id}, '','', 'prop'));
		}
	}

	return array('formulaires/administration', 0, $env);
}

?>
