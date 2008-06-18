<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!isset($GLOBALS['_INC_PUBLIC'])) $GLOBALS['_INC_PUBLIC'] = 0;

// Distinguer une inclusion d'un appel initial
if ($GLOBALS['_INC_PUBLIC']++ > 0) {

	// $fond passe par INCLURE(){fond=...}
	if (isset($contexte_inclus['fond']))
		$fond = $contexte_inclus['fond'];
	$fonds = array($fond);
	if (is_array($fond)) $fonds=$fond;
	foreach($fonds as $fond){
		$subpage = inclure_page($fond, $contexte_inclus, _request('connect'));
		if ($subpage['process_ins'] == 'html'){
			echo $subpage['texte'];
		}
		else {
			xml_hack($subpage, true);
			eval('?' . '>' . $subpage['texte']);
			// xml_hack($subpage); # sera nettoye apres l'eval() final
		}
	
		// est-ce possible ?
		if (isset($subpage['lang_select'])
		AND $subpage['lang_select'] === true)
			lang_select();
	}
}
else {

	//
	// Discriminer les appels
	//

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS'))
		if (defined('_DIR_RESTREINT')
		AND @file_exists(_DIR_RESTREINT.'inc_version.php')) {
			include_once _DIR_RESTREINT.'inc_version.php';
		}
		else
			die('inc_version absent ?');


	// cas normal, $fond defini dans le fichier d'appel
	// note : securise anti-injection par inc/utils.php
	// les actions sont gerees dans
	// public/assembler/traiter_formulaires_dynamiques
	// de maniere unifiee public/prive
	else if (isset($fond)) { }

	// page=xxxx demandee par l'url
	else if (isset($_GET[_SPIP_PAGE])) {
		$fond = $_GET[_SPIP_PAGE];
		// Securite
		if (strstr($fond, '/')) {
			include_spip('inc/minipres');
			echo minipres();
			exit;
		}

	# par defaut
	} else {
		// traiter le cas pathologique d'un upload de document ayant echoue
		// car trop gros
		if (empty($_GET) AND empty($_POST) AND empty($_FILES)
		AND isset($_SERVER["CONTENT_LENGTH"])
		AND strstr($_SERVER["CONTENT_TYPE"], "multipart/form-data;")) {
			include_spip('inc/getdocument');
			erreur_upload_trop_gros();
		}

		// mais plus probablement nous sommes dans le cas
		$fond = 'sommaire';
	}

	// Particularites de certains squelettes
	if ($fond == 'login')
		$forcer_lang = true;


	//
	// Aller chercher la page
	//

	$tableau_des_erreurs = 	$tableau_des_temps = array();
	$assembler = charger_fonction('assembler', 'public');
	$page = $assembler($fond, _request('connect'));

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
		include_spip('inc/minipres'); // pour http_img_pack
		$x = '<div class="spip_large" style="
		display: block;
		color: #eeeeee;
		background-color: #111111;
		padding-right: 5px;
		padding-top: 2px;
		padding-bottom: 5px;
		top: 0px;
		left: 0px;
		position: absolute;
		">' 
		. http_img_pack('naviguer-site.png', _T('previsualisation'), '')
		. '&nbsp;' . majuscules(_T('previsualisation')) . '</div>';
		if (!$pos = strpos($page['texte'], '</body>'))
			$pos = strlen($page['texte']);
		$page['texte'] = substr_replace($page['texte'], $x, $pos, 0);
	}

	// est-on admin ?
	if ($affiche_boutons_admin = (
	isset($_COOKIE['spip_admin']) 
	AND !$flag_preserver
	AND ($html OR ($var_mode == 'debug') OR count($tableau_des_erreurs))
	))
		include_spip('balise/formulaire_admin');

	// Execution de la page calculee

	// decomptage des visites, on peut forcer a oui ou non avec le header X-Spip-Visites
	// par defaut on ne compte que les pages en html (ce qui exclue les js,css et flux rss)
	$spip_compter_visites = $html?'oui':'non';
	if (isset($page['entetes']['X-Spip-Visites'])){
		$spip_compter_visites = in_array($page['entetes']['X-Spip-Visites'],array('oui','non'))?$page['entetes']['X-Spip-Visites']:$spip_compter_visites;
		unset($page['entetes']['X-Spip-Visites']);
	}

	// 1. Cas d'une page contenant uniquement du HTML :
	if ($page['process_ins'] == 'html') {
		envoyer_entetes($page['entetes']);
	}

	// 2. Cas d'une page contenant du PHP :
	// Attention cette partie eval() doit imperativement
	// etre declenchee dans l'espace des globales (donc pas
	// dans une fonction).
	else {
		// Si la retention du flux de sortie est impossible
		// envoi des entetes
		if (!$flag_ob) {
			envoyer_entetes($page['entetes']);
			xml_hack($page, true);
			eval('?' . '>' . $page['texte']);
			$page['texte'] = '';
			// xml_hack($page); # inutile :(
		}

		// sinon, inclure_balise_dynamique nous enverra peut-etre
		// quelques en-tetes de plus (voire qq envoyes directement)
		else {
			ob_start(); 
			xml_hack($page, true);
			$res = eval('?' . '>' . $page['texte']);
			$page['texte'] = ob_get_contents(); 
			xml_hack($page);
			ob_end_clean();

			envoyer_entetes($page['entetes']);
			// en cas d'erreur lors du eval,
			// la memoriser dans le tableau des erreurs
			// On ne revient pas ici si le nb d'erreurs > 4
			if ($res === false AND $affiche_boutons_admin) {
				include_spip('public/debug');
				erreur_squelette(_T('zbug_erreur_execution_page'));
			}
		}

	}

	// Passer la main au debuggueur le cas echeant

	if ($var_mode == 'debug') {
		include_spip('public/debug');
		$var_mode_affiche = _request('var_mode_affiche');
		$var_mode_objet = _request('var_mode_objet');
		debug_dumpfile($var_mode_affiche== 'validation' ? $page['texte'] :"",
			       $var_mode_objet,$var_mode_affiche);
	} 

	if (count($tableau_des_erreurs) AND $affiche_boutons_admin)
		$page['texte'] = affiche_erreurs_page($tableau_des_erreurs)
			. $page['texte'];

	//
	// Post-traitements et affichage final
	//
	page_base_href($page['texte']);

	// (c'est ici qu'on fait var_recherche, tidy, boutons d'admin,
	// cf. public/assembler.php)
	echo pipeline('affichage_final', $page['texte']);

	if (count($tableau_des_temps) AND $affiche_boutons_admin) {
		include_spip('public/debug');
		echo chrono_requete($tableau_des_temps);
	}

	// Gestion des statistiques du site public
	if (($GLOBALS['meta']["activer_statistiques"] != "non")
	AND $spip_compter_visites!='non') {
		$stats = charger_fonction('stats', 'public');
		$stats();
	}

	if (isset($GLOBALS['meta']['date_prochain_postdate'])
	AND $GLOBALS['meta']['date_prochain_postdate'] <= time()) {
		include_spip('inc/rubriques');
		calculer_prochain_postdate(true);
	}

	// Effectuer une tache de fond ?
	// si #SPIP_CRON est present, on ne le tente que pour les navigateurs
	// en mode texte (par exemple), et seulement sur les pages web
	if ($html
	AND !strstr($page['texte'], '<!-- SPIP-CRON -->')
	AND !preg_match(',msie|mozilla|opera|konqueror,i', $_SERVER['HTTP_USER_AGENT']))
		cron();
}

?>