<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// Distinguer une inclusion d'un appel initial
// (cette distinction est obsolete a present, on la garde provisoirement
// par souci de compatiilite).

if (isset($GLOBALS['_INC_PUBLIC'])) {

	echo recuperer_fond($fond, $contexte_inclus, array(), _request('connect'));

} else {

	$GLOBALS['_INC_PUBLIC'] = 0;

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS'))
		if (defined('_DIR_RESTREINT')
		AND @file_exists(_ROOT_RESTREINT . 'inc_version.php')) {
			include_once _ROOT_RESTREINT . 'inc_version.php';
		}
		else
			die('inc_version absent ?');


	// $fond defini dans le fichier d'appel ?

	else if (isset($fond) AND !_request('fond')) { }

	// fond demande dans l'url par page=xxxx ?
	else if (isset($_GET[_SPIP_PAGE])) {
		$fond = $_GET[_SPIP_PAGE];

		// Securite
		if (strstr($fond, '/')
			AND !(
				isset($GLOBALS['visiteur_session']) // pour eviter d'evaluer la suite pour les anonymes
				AND include_spip('inc/autoriser')
				AND autoriser('webmestre'))) {
			include_spip('inc/minipres');
			echo minipres();
			exit;
		}
		// l'argument Page a priorite sur l'argument action
		// le cas se presente a cause des RewriteRule d'Apache
		// qui permettent d'ajouter un argument dans la QueryString
		// mais pas d'en retirer un en conservant les autres.
		if (isset($_GET['action']) AND $_GET['action'] === $fond)
			unset($_GET['action']);
	# sinon, fond par defaut
	} else {
		// traiter le cas pathologique d'un upload de document ayant echoue
		// car trop gros
		if (empty($_GET) AND empty($_POST) AND empty($_FILES)
		AND isset($_SERVER["CONTENT_LENGTH"])
		AND strstr($_SERVER["CONTENT_TYPE"], "multipart/form-data;")) {
			include_spip('inc/getdocument');
			erreur_upload_trop_gros();
		}

		// sinon fond par defaut (cf. assembler.php)
		$fond = '';
	}

	$tableau_des_temps = array();

	// Particularites de certains squelettes
	if ($fond == 'login')
		$forcer_lang = true;

	if (isset($forcer_lang) AND $forcer_lang AND ($forcer_lang!=='non') AND !_request('action')) {
		include_spip('inc/lang');
		verifier_lang_url();
	}

	$lang = !isset($_GET['lang']) ? '' : lang_select($_GET['lang']);

	// Charger l'aiguilleur des traitements derogatoires
	// (action en base SQL, formulaires CVT, AJax)
	if (_request('action') OR _request('var_ajax') OR _request('formulaire_action')){
		include_spip('public/aiguiller');
		if (
			// cas des appels actions ?action=xxx
			traiter_appels_actions()
		OR
			// cas des hits ajax sur les inclusions ajax
			traiter_appels_inclusions_ajax()
		 OR
			// cas des formulaires charger/verifier/traiter
			traiter_formulaires_dynamiques())
			exit; // le hit est fini !
	}

	// Il y a du texte a produire, charger le metteur en page
	include_spip('public/assembler');
	$page = assembler($fond, _request('connect'));

	if (isset($page['status'])) {
		include_spip('inc/headers');
		http_status($page['status']);
	}

	// Content-Type ?
	if (!isset($page['entetes']['Content-Type'])) {
		$page['entetes']['Content-Type'] = 
			"text/html; charset=" . $GLOBALS['meta']['charset'];
		$html = true;
	} else {
		$html = preg_match(',^\s*text/html,',$page['entetes']['Content-Type']);
	}

	if ($var_preview AND $html) {
		include_spip('inc/filtres'); // pour http_img_pack
		$x = _T('previsualisation');
		$x = http_img_pack('naviguer-site.png', $x) . '&nbsp;' . majuscules($x); 
		$x = "<div class='spip-previsu'>$x</div>";
		if (!$pos = strpos($page['texte'], '</body>'))
			$pos = strlen($page['texte']);
		$page['texte'] = substr_replace($page['texte'], $x, $pos, 0);
	}

	// Tester si on est admin et il y a des choses supplementaires a dire
	// type tableau pour y mettre des choses au besoin.
	$debug = ((_request('var_mode') == 'debug') OR $tableau_des_temps) ? array(1) : array();

	$affiche_boutons_admin = ($html AND ((
		isset($_COOKIE['spip_admin'])
		AND !$flag_preserver
				   ) OR $debug));

	if ($affiche_boutons_admin)
		include_spip('balise/formulaire_admin');

	
	// Execution de la page calculee
	define('_PIPELINE_SUFFIX',  test_espace_prive()?'_prive':'');

	// traitements sur les entetes avant envoi
	// peut servir pour le plugin de stats
	$page['entetes'] = pipeline('affichage_entetes_final'._PIPELINE_SUFFIX, $page['entetes']);


	// 1. Cas d'une page contenant uniquement du HTML :
	if ($page['process_ins'] == 'html') {
		envoyer_entetes($page['entetes']);
	}

	// 2. Cas d'une page contenant du PHP :
	// Attention cette partie eval() doit imperativement
	// etre declenchee dans l'espace des globales (donc pas
	// dans une fonction).
	else {
		// sinon, inclure_balise_dynamique nous enverra peut-etre
		// quelques en-tetes de plus (voire qq envoyes directement)

		// restaurer l'etat des notes
		if (isset($page['notes']) AND $page['notes']){
			$notes = charger_fonction("notes","inc");
			$notes($page['notes'],'restaurer_etat');
		}
		ob_start(); 
		xml_hack($page, true);
		$res = eval('?' . '>' . $page['texte']);
		$page['texte'] = ob_get_contents(); 
		xml_hack($page);
		ob_end_clean();

		envoyer_entetes($page['entetes']);
		// en cas d'erreur lors du eval,
		// la memoriser dans le tableau des erreurs

		if ($res === false) {
			$msg = array('zbug_erreur_execution_page');
			erreur_squelette($msg);
		}
	}

	//
	// Post-traitements
	//
	page_base_href($page['texte']);

	// (c'est ici qu'on fait var_recherche, validation, boutons d'admin,
	// cf. public/assembler.php)
	echo pipeline('affichage_final'._PIPELINE_SUFFIX, $page['texte']);

	if ($lang) lang_select();
	// l'affichage de la page a pu lever des erreurs (inclusion manquante)
	// il faut tester a nouveau
	$debug = ((_request('var_mode') == 'debug') OR $tableau_des_temps) ? array(1) : array();

	// Appel au debusqueur en cas d'erreurs ou de demande de trace
	// at last
	if ($debug) {
		// en cas d'erreur, retester l'affichage
		if ($html AND ($affiche_boutons_admin OR $debug)) {
			$var_mode_affiche = _request('var_mode_affiche');
			$GLOBALS['debug_objets'][$var_mode_affiche][$var_mode_objet . 'tout'] = ($var_mode_affiche== 'validation' ? $page['texte'] :"");
			echo erreur_squelette(false);
		}
	} else {

		if (isset($GLOBALS['meta']['date_prochain_postdate'])
		AND $GLOBALS['meta']['date_prochain_postdate'] <= time()) {
			include_spip('inc/rubriques');
			calculer_prochain_postdate(true);
		}

		// Effectuer une tache de fond ?
		// si #SPIP_CRON est present, on ne le tente que pour les navigateurs
		// en mode texte (par exemple), et seulement sur les pages web
		if (defined('_DIRECT_CRON_FORCE')
			OR (
			!defined('_DIRECT_CRON_INHIBE')
			AND $html
			AND !strstr($page['texte'], '<!-- SPIP-CRON -->')
			AND !preg_match(',msie|mozilla|opera|konqueror,i', $_SERVER['HTTP_USER_AGENT']))
			)
			cron();

		// sauver le cache chemin si necessaire
		save_path_cache();
	}
}

?>
