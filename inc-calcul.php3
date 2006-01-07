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

//
// Ce fichier calcule une page en executant un squelette.
//

include_ecrire("inc_meta");
include_ecrire("inc_index");
include_ecrire("inc_texte");
include_ecrire("inc_lang");
include_ecrire("inc_documents");
include_ecrire("inc_abstract_sql");
include_ecrire("inc_forum");
include_ecrire("inc_debug_sql");
include_ecrire("inc_distant");
include_local("inc-calcul-outils");

// NB: Ce fichier peut initialiser $dossier_squelettes (old-style)
if ($f = find_in_path("mes_fonctions" . _EXTENSION_PHP))
	include_local ($f);

charger_generer_url();

// Le squelette compile est-il trop vieux ?
function squelette_obsolete($skel, $squelette) {
	return (
		($GLOBALS['var_mode'] AND $GLOBALS['var_mode']<>'calcul')
		OR !@file_exists($skel)
		OR (@filemtime($squelette) > ($date = @filemtime($skel)))
		OR (@filemtime('mes_fonctions' . _EXTENSION_PHP) > $date)
		OR (@filemtime(_FILE_OPTIONS) > $date)
	);
}


# Charge un squelette (au besoin le compile) 
# et retoune le nom de sa fonction principale, ou '' s'il est indefini
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php' pouvant contenir:
# - des filtres
# - des fonctions de traduction de balise, de critere et de boucle
# - des declaration de tables SQL supplementaires

function charger_squelette ($squelette) {
	$ext = $GLOBALS['extension_squelette'];
	$nom = $ext . '_' . md5($squelette);
	$sourcefile = $squelette . ".$ext";

	// le squelette est-il deja en memoire (INCLURE  a repetition)
	if (function_exists($nom))
		return $nom;

	$phpfile = _DIR_CACHE . 'skel_' . $nom . '.php';

	// le squelette est-il deja compile et perenne ?
	if (!squelette_obsolete($phpfile, $sourcefile)
	AND lire_fichier ($phpfile, $contenu,
	array('critique' => 'oui', 'phpcheck' => 'oui'))) 
		eval('?'.'>'.$contenu);

	// sinon, charger le compilateur et verifier que le source est lisible
	if (!function_exists($nom)) {
		include_local("inc-compilo");
		lire_fichier ($sourcefile, $skel);
	}

	// Le fichier suivant peut contenir entre autres:
	// 1. les filtres utilises par le squelette
	// 2. des ajouts au compilateur
	// Le point 1 exige qu'il soit lu dans tous les cas.
	// Le point 2 exige qu'il soit lu apres inc-compilo
	// (car celui-ci initialise $tables_principales) mais avant la compil

	include_local($squelette . '_fonctions', true);

	if (function_exists($nom)) return $nom;

	$skel_code = calculer_squelette($skel, $nom, $ext, $sourcefile);
	// Tester si le compilateur renvoie une erreur

	if (is_array($skel_code))
		erreur_squelette($skel_code[0], $skel_code[1]);
	else {

		if ($GLOBALS['var_mode'] == 'debug') {
			include_ecrire("inc_debug_sql");
			debug_dumpfile ($skel_code, $nom, 'code');
		}
#		spip_log($skel_code);
		eval('?'.'>'.$skel_code);
		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_code);
			return $nom;
		} else {
			erreur_squelette($sourcefile, _L('Erreur de compilation'));
		}
	}
}

# Provoque la recherche du squelette $fond d'une $lang donnee,
# et l'applique sur un $contexte pour un certain $cache.
# Retourne un tableau de 3 elements:
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs (cf inc-calcul-squel)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

# La recherche est assuree par la fonction chercher_squelette,
# definie dans inc-chercher, fichier non charge si elle est deja definie
# (typiquement dans mes_fonctions.php)

function cherche_page ($cache, $contexte, $fond)  {
	if (!function_exists('chercher_squelette'))
		include_local("inc-chercher-squelette");

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = sql_rubrique_fond($contexte))
		list($id_rubrique_fond, $lang) = $r;
	if (!$lang)
		$lang = $GLOBALS['meta']['langue_site'];
	// Si inc-urls ou un appel dynamique veut fixer la langue, la recuperer
	$lang = $contexte['lang'];

	if (!$GLOBALS['forcer_lang'])
		lang_select($lang);

	$skel = chercher_squelette($fond,
			$id_rubrique_fond,
			$GLOBALS['spip_lang']);

	// Charger le squelette et recuperer sa fonction principale
	// (compilation automatique au besoin) et calculer

	$page = array();

	if ($skel) {
		if ($fonc = charger_squelette($skel)) {
			spip_timer('calcul page');
			$page = $fonc(array('cache' => $cache), array($contexte));
			spip_log("calcul ("
				.spip_timer('calcul page')
				.") ".trim("[$skel] $cache")
				." - ".strlen($page['texte']).' octets'
			);
		}

		// Passer la main au debuggueur)
		if ($GLOBALS['var_mode'] == 'debug') {
			include_ecrire("inc_debug_sql");
			debug_dumpfile ($page['texte'], $fonc, 'resultat');
		}
	}
#	spip_log("page " . strlen($page['texte']) . " $skel .  $fonc");
	// Retourner la structure de la page

	return $page;
}


//
// Contexte : lors du calcul d'une page spip etablit le contexte a partir
// des variables $_GET et $_POST, et leur ajoute la date
// Note : pour hacker le contexte depuis le fichier d'appel (page.php),
// il est recommande de modifier $_GET['toto'] (meme si la page est
// appelee avec la methode POST).
//
function calculer_contexte() {
	global $_GET, $_POST;

	$contexte = array();
	foreach($_GET as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}
	foreach($_POST as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}

	if ($GLOBALS['date'])
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

function calculer_page_globale($cache, $fond) {

	global $lastmodified, $_SERVER, $contexte;

	$contexte = calculer_contexte();

	// Gestion des URLs personnalises (propre etc)
	// ATTENTION: $contexte est global car cette fonction le modifie.
	// $fond est passe par reference aussi pour modification
	// (tout ca parce que ces URL masque ces donnees qu'on restaure ici)
	// Bref,  les URL dites propres ont une implementation sale.
	// Interdit de nettoyer, faut assumer l'histoire.
	if (function_exists("recuperer_parametres_url")) {
		recuperer_parametres_url($fond, nettoyer_uri());
		// remettre les globales pour le bouton "Modifier cet article"
		foreach ($contexte as $var=>$val)
			if (substr($var,0,3) == 'id_') $GLOBALS[$var] = $val;
	}

	// si le champ chapo commence par '=' c'est une redirection.
	if ($id_article = intval($contexte['id_article'])) {
		if ($art = sql_chapo($id_article)) {
			$chapo = $art['chapo'];
			if (substr($chapo, 0, 1) == '=') {
				include_ecrire('inc_texte');
				list(,$url) = extraire_lien(array('','','',
				substr($chapo, 1)));
				if ($url) { // sinon les navigateurs pataugent
					$url = texte_script(str_replace('&amp;', '&', $url));
					return array('texte' => "<".
					"?php redirige_par_entete('$url'); ?" . ">",
					'process_ins' => 'php');
				}
			}
		}
	}

	// Go to work !
	$page = cherche_page($cache, $contexte, $fond);
	$signal = array();
	foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
	'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article',
	'id_mot', 'id_groupe', 'id_document') as $val) {
		if ($contexte[$val])
			$signal['contexte'][$val] = intval($contexte[$val]);
	}

	$page['signal'] = $signal;
	$page['signal']['process_ins'] = $page['process_ins'];
	$lastmodified = time();
	return $page;
}
?>
