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

include_spip('base/abstract_sql');
include_spip('inc/lang');

// NB: Ce fichier peut initialiser $dossier_squelettes (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')
OR $f = find_in_path('mes_fonctions.php3')) {
	global $dossier_squelettes;
	@include ($f); 
}
if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

charger_generer_url(); # pour recuperer_parametres_url

//
// Contexte : lors du calcul d'une page spip etablit le contexte a partir
// des variables $_GET et $_POST, et leur ajoute la date
// Note : pour hacker le contexte depuis le fichier d'appel (page.php),
// il est recommande de modifier $_GET['toto'] (meme si la page est
// appelee avec la methode POST).
//
// http://doc.spip.org/@calculer_contexte
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

	if (isset($GLOBALS['date']))
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

// http://doc.spip.org/@signaler_squelette
function signaler_squelette($contexte)
{
	$signal = array();
	foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
	'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article',
	'id_mot', 'id_groupe', 'id_document') as $val) {
		if (isset($contexte[$val]))
			$signal['contexte'][$val] = intval($contexte[$val]);
	}

	return $signal;
}

// http://doc.spip.org/@analyse_resultat_skel
function analyse_resultat_skel($nom, $cache, $corps) {
	$headers = array();

	// Recupere les < ?php header('Xx: y'); ? > pour $page['headers']
	// note: on essaie d'attrapper aussi certains de ces entetes codes
	// "a la main" dans les squelettes, mais evidemment sans exhaustivite
	if (preg_match_all(
	'/(<[?]php\s+)@?header\s*\(\s*.([^:]*):\s*([^)]*)[^)]\s*\)\s*[;]?\s*[?]>/ims',
	$corps, $regs, PREG_SET_ORDER))
	foreach ($regs as $r) {
		$corps = str_replace($r[0], '', $corps);
		# $j = Content-Type, et pas content-TYPE.
		$j = join('-', array_map('ucwords', explode('-', strtolower($r[2]))));
		$headers[$j] = $r[3];
	}

	return array('texte' => $corps,
		'squelette' => $nom,
		'process_ins' => ((strpos($corps,'<'.'?')=== false)?'html':'php'),
		'invalideurs' => $cache,
		'entetes' => $headers,
		'duree' => isset($headers['X-Spip-Cache']) ? intval($headers['X-Spip-Cache']) : 0 
	);
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@sql_rubrique_fond
function sql_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])) {
		$id = intval($contexte['id_rubrique']);
		$row = spip_abstract_fetsel(array('lang'),
					    array('spip_rubriques'),
					    array("id_rubrique=$id"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])) {
		$id = intval($contexte['id_breve']);
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('spip_breves'), 
			array("id_breve=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])) {
		$id = intval($contexte['id_syndic']);
		$row = spip_abstract_fetsel(array('id_rubrique'),
			array('spip_syndic'),
			array("id_syndic=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$row = spip_abstract_fetsel(array('lang'),
			array('spip_rubriques'),
			array("id_rubrique='$id_rubrique_fond'"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])) {
		$id = intval($contexte['id_article']);
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('spip_articles'),
			array("id_article=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}
}

# retourne le chapeau d'un article, et seulement s'il est publie

// http://doc.spip.org/@sql_chapo
function sql_chapo($id_article) {
	$chapo= spip_abstract_fetsel(array('chapo'),
		array('spip_articles'),
		array("id_article=".intval($id_article),
		"statut='publie'"));
	return $chapo['chapo'];
}

# retourne le parent d'une rubrique

// http://doc.spip.org/@sql_parent
function sql_parent($id_rubrique) {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;

	$id_parent = spip_abstract_fetsel(array('id_parent'),
		array('spip_rubriques'), 
		array("id_rubrique=" . $id_rubrique));

	if ($id_parent['id_parent']!=$id_rubrique)
		return intval($id_parent['id_parent']);
	else
		spip_log("erreur: la rubrique $id_rubrique est son propre parent");
}

# retourne la profondeur d'une rubrique

// http://doc.spip.org/@sql_profondeur
function sql_profondeur($id) {
	$n = 0;
	while ($id) {
		$n++;
		$id = sql_parent($id);
	}
	return $n;
}

# retourne la rubrique d'un article

// http://doc.spip.org/@sql_rubrique
function sql_rubrique($id_article) {
	$id_rubrique = spip_abstract_fetsel(array('id_rubrique'),
			array('spip_articles'),
			array("id_article=" . intval($id_article)));
	return $id_rubrique['id_rubrique'];
}

// http://doc.spip.org/@sql_petitions
function sql_petitions($id_article, $table, $id_boucle, $serveur, &$cache) {
	$retour = spip_abstract_fetsel(
		array('texte'),
		array('spip_petitions'),
		array("id_article=".intval($id_article)),
		'',array(),'','','', 
		$table, $id_boucle, $serveur);

	if (!$retour) return '';
	# cette page est invalidee par toute petition
	$cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return ($retour['texte'] ? $retour['texte'] : ' ');
}

# retourne le champ 'accepter_forum' d'un article
// http://doc.spip.org/@sql_accepter_forum
function sql_accepter_forum($id_article) {
	static $cache = array();

	if (!$id_article) return;

	if (!isset($cache[$id_article])) {
		$row = spip_abstract_fetsel(array('accepter_forum'),
			array('spip_articles'),
			array("id_article=".intval($id_article)));
		$cache[$id_article] = $row['accepter_forum'];
	}

	return $cache[$id_article];
}

# Determine les parametres d'URL (hors r��criture) et consorts
# En deduit un contexte disant si la page est une redirection ou 
# exige un squelette deductible de $fond et du contexte linguistique.
# Applique alors le squelette sur le contexte et le nom du cache.
# Retourne un tableau ainsi construit
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs de ce cache
# 'entetes' => headers http
# 'duree' => duree de vie du cache
# 'signal' => contexte (les id_* globales)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

// http://doc.spip.org/@public_parametrer_dist
function public_parametrer_dist($fond, $local='', $cache='')  {

	// distinguer le premier appel des appels par inclusion
	if (!is_array($local)) {
		global $contexte;
		// ATTENTION, gestion des URLs personnalises (propre etc):
		// 1. $contexte est global car cette fonction le modifie.
		// 2. $fond est passe par reference, pour la meme raison
		// Bref,  les URL dites propres ont une implementation sale.
		// Interdit de nettoyer, faut assumer l'histoire.
		include_spip('inc/filtres'); // pour normaliser_date
		$contexte = calculer_contexte();
		if (function_exists("recuperer_parametres_url")) {
			recuperer_parametres_url($fond, nettoyer_uri());
			// remettre les globales (bouton "Modifier cet article" etc)
			foreach ($contexte as $var=>$val) {
				if (substr($var,0,3) == 'id_') $GLOBALS[$var] = $val;
			}
		}
		$local = $contexte;

		// si le champ chapo commence par '=' c'est une redirection.
		if ($fond == 'article'
		AND $id_article = intval($local['id_article'])) {
			if ($chapo = sql_chapo($id_article)) {
				if (preg_match(',^=(\[->)?(.*?)[]]?$,', $chapo, $url)){
					include_spip('inc/texte');
					$url = calculer_url($url[2]);
					if ($url) { // sinon les navigateurs pataugent
						$url = texte_script(str_replace('&amp;', '&', $url));
						return array('texte' => "<".
						"?php redirige_par_entete('$url'); ?" . ">",
						'process_ins' => 'php');
					}
				}
			}
		}
	}

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = sql_rubrique_fond($local))
		list($id_rubrique_fond, $lang) = $r;

	// Si inc-urls ou un appel dynamique veut fixer la langue, la recuperer
	if (isset($local['lang']))
		$lang = $local['lang'];

	if (!isset($lang))
		$lang = $GLOBALS['meta']['langue_site'];

	if (!$GLOBALS['forcer_lang'])
		lang_select($lang);

	$f = charger_fonction('styliser', 'public');
	list($skel,$mime_type, $gram, $sourcefile) = $f($fond, $id_rubrique_fond,$GLOBALS['spip_lang']);

	// Charger le squelette en specifiant les langages cibles et source
	// au cas il faudrait le compiler (source posterieure au resultat)
	// et appliquer sa fonction principale sur le contexte.
	// Passer le nom du cache pour produire sa destruction automatique

	$f = charger_fonction('composer', 'public');

	if ($fonc = $f($skel, $mime_type, $gram, $sourcefile)){
		spip_timer('calcul page');
		$page = $fonc(array('cache' => $cache), array($local));
		spip_log("calcul ("
			.spip_timer('calcul page')
			.") [$skel] ".
			 join(", ", $local)
			.' ('.strlen($page['texte']).' octets)'
		);
	} else
		$page = array();

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		debug_dumpfile ($page['texte'], $fonc, 'resultat');
	}
	$page['signal'] = signaler_squelette($local);
	return $page;
}
?>
