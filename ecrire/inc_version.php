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


if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");

@define('_EXTENSION_PHP', '.php3');

# le nom du repertoire ecrire/
@define('_DIR_RESTREINT_ABS', 'ecrire/');
# sommes-nous dans ecrire/ ?
@define('_DIR_RESTREINT',
 (!@is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
# ou inversement ?
@define('_DIR_RACINE', _DIR_RESTREINT ? '' : '../');

# le fichier ecrire/mes_options
@define('_FILE_OPTIONS', _DIR_RESTREINT . 'mes_options' . _EXTENSION_PHP);


//
// *** Parametrage par defaut de SPIP ***
//
// Ces parametres d'ordre technique peuvent etre modifies
// dans ecrire/mes_options (_FILE_OPTIONS) Les valeurs
// specifiees dans ce dernier fichier remplaceront automatiquement
// les valeurs ci-dessous.
//
// Pour creer ecrire/mes_options : recopier simplement
// les lignes ci-dessous, et ajouter le marquage de debut et
// de fin de fichier PHP ("< ?php" et "? >", sans les espaces)
//

// Prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
$table_prefix = "spip";

// Prefixe et chemin des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";
$cookie_path = "";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// faut-il autoriser SPIP a compresser les pages a la volee quand le
// navigateur l'accepte (valable pour apache >= 1.3 seulement) ?
$auto_compress = true;

// Type d'URLs
// 'page': ?article=123
// 'html': article123.html
// 'propres': Titre-de-l-article <http://lab.spip.net/spikini/UrlsPropres>
// 'propres2' : Titre-de-l-article.html (base sur 'propres')
// 'standard': article.php3?id_article=123
$type_urls = 'page';


//
// On note le numero IP du client dans la variable $ip
//
($ip = @$_SERVER['HTTP_X_FORWARDED_FOR']) OR $ip = @$_SERVER['REMOTE_ADDR'];

// Pour renforcer la privacy, decommentez la ligne ci-dessous : SPIP ne pourra
// alors conserver aucun numero IP, ni temporairement lors des visites (pour
// gerer les statistiques ou dans spip.log), ni dans les forums (responsabilite)
# $ip = substr(md5($ip),0,16);


// creation des vignettes avec image magick en ligne de commande : mettre
// le chemin complet '/bin/convert' (Linux) ou '/sw/bin/convert' (fink/Mac OS X)
// Note : preferer GD2 ou le module php imagick s'ils sont disponibles
$convert_command = 'convert';

// creation des vignettes avec pnmscale
// Note: plus facile a installer par FTP,
// voir http://gallery.menalto.com/modules.php?op=modload&name=GalleryFAQ&file=index&myfaq=yes&id_cat=2#43
$pnmscale_command = 'pnmscale';

// faut-il passer les connexions MySQL en mode debug ?
$mysql_debug = false;

// faut-il chronometrer les requetes MySQL ?
$mysql_profile = false;

// faut-il faire des connexions completes rappelant le nom du serveur et/ou de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL)
// (A desactiver en cas de soucis de connexion chez certains hebergeurs)
// Note: un test a l'installation peut aussi avoir desactive
// $mysql_rappel_nom_base directement dans le fichier inc_connect
$mysql_rappel_connexion = true;
$mysql_rappel_nom_base = true;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// gestion des extras (voir inc_extra pour plus d'informations)
$champs_extra = false;
$champs_extra_proposes = false;

// faut-il ignorer l'authentification par auth http/remote_user ?
$ignore_auth_http = false;
$ignore_remote_user = true; # methode obsolete et risquee

// Faut-il "invalider" les caches quand on depublie ou modifie un article ?
# NB: cette option ne concerne pas les forums et petitions qui sont toujours
# invalidants. (fonctionnalite experimentale : decommenter ci-dessous)
#$invalider_caches = 'id_article,id_breve,id_rubrique,id_syndic';
$invalider_caches = '';

// Quota : la variable $quota_cache, si elle est > 0, indique la taille
// totale maximale desiree des fichiers contenus dans le CACHE/ ;
// ce quota n'est pas "dur", il ne s'applique qu'une fois par heure et
// fait redescendre le cache a la taille voulue ; valeur en Mo
// Si la variable vaut 0 aucun quota ne s'applique
$quota_cache = 10;

//
// Serveurs externes
//
# aide en ligne
$help_server = 'http://www.spip.net/aide';
# TeX
$tex_server = 'http://math.spip.org/tex.php';
# MathML (pas pour l'instant: manque un bon convertisseur)
// $mathml_server = 'http://arno.rezo.net/tex2mathml/latex.php';
# Orthographe (serveurs multiples) [pas utilise pour l'instant]
$ortho_servers = array ('http://ortho.spip.net/ortho_serveur.php');

// Produire du TeX ou du MathML ?
$traiter_math = 'tex';

// Controler les dates des item dans les flux RSS ?
$controler_dates_rss = true;

//
// Pipelines & plugins
//
# les pipeline standards (traitements derivables aka points d'entree)
# ils seront compiles par la suite
# note: un pipeline non reference se compile aussi, mais uniquement
# lorsqu'il est rencontre
$spip_pipeline = array(
	'pre_typo' => '|extraire_multi',
	'post_typo' => '|quote_amp',
	'pre_propre' => '|extraire_multi',
	'post_propre' => '',
	'pre_indexation' => '',
	'pre_syndication' => '',
	'post_syndication' => '',
	'ajouter_boutons' => '',
	'ajouter_onglets' => '',
	'header_prive' => ''
);
# la matrice standard (fichiers definissant les fonctions a inclure)
$spip_matrice = array ();
# les plugins a activer
$plugins = array();  // voir le contenu du repertoire /plugins/


// Masquer les warning
error_reporting(E_ALL ^ E_NOTICE);

// Variables du compilateur de squelettes

$exceptions_des_tables = array();
$tables_principales = array();
$table_des_tables = array();
$tables_relations = array();
$tables_auxiliaires = array();
$tables_relations_keys = array();
$table_primary = array();
$table_date = array();
$tables_des_serveurs_sql['localhost'] =  &$tables_principales;

/* ATTENTION CETTE VARIABLE NE FONCTIONNE PAS ENCORE */
// Extension du fichier du squelette 
$extension_squelette = 'html';
/* / MERCI DE VOTRE ATTENTION */

// Droits d'acces maximum par defaut
@umask(0);

// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.906;

// version de spip
$spip_version_affichee = "1.9 alpha 3";

// ** Securite **
$auteur_session = '';
$connect_statut = '';
$hash_recherche = '';
$hash_recherche_strict = '';



//
// Inclure le fichier ecrire/mes_options (ou equivalent)
//
if (@file_exists(_FILE_OPTIONS)) {
	include_once(_FILE_OPTIONS);
}


//
// Definitions standards (charge aussi inc_flock)
//
# on peu mettre ces deux lignes dans mes_options si on veut beneficier
# des definitions (notamment de $auteur_session)
define('_DIR_INCLUDE', _DIR_RESTREINT);
require_once(_DIR_INCLUDE . 'inc_utils.php');


// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins
if (@is_readable(_DIR_SESSIONS."charger_plugins_options.php")){
	// chargement optimise precompile
	include_once(_DIR_SESSIONS."charger_plugins_options.php");
} else {
	include_ecrire('inc_plugin');
	// generer les fichiers php precompiles
	// de chargement des plugins et des pipelines
	verif_plugin();
	if (@is_readable(_DIR_SESSIONS."charger_plugins_options.php")){
		include_once(_DIR_SESSIONS."charger_plugins_options.php");
	}
	else
		spip_log("generation de charger_plugins_options.php impossible; pipeline desactives");
}


//
// Installer Spip si pas installe... sauf si justement on est en train
//
if (!(_FILE_CONNECT
OR autoriser_sans_cookie(_request('exec'))
OR _request('action') == 'cookie'
OR _request('action') == 'test_dirs')) {

	// Si on peut installer, on lance illico
	if (@file_exists('inc_version.php'))
		redirige_par_entete(generer_url_ecrire("install"));
	else if (defined("_INC_PUBLIC")) {
	// Si on est dans le site public, dire que qq s'en occupe
		include_ecrire ("inc_minipres");
		minipres(_T('info_travaux_titre'), "<p>"._T('info_travaux_texte')."</p>");
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}

//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante
//

// si un buffer est deja ouvert, stop
if ($flag_ob AND strlen(ob_get_contents())==0 AND !headers_sent()) {
	@header("Vary: Cookie, Accept-Encoding");

	if (
	$GLOBALS['auto_compress']
	&& (phpversion()<>'4.0.4')
	&& function_exists("ob_gzhandler")
	// special bug de proxy
	&& !preg_match(",NetCache|Hasd_proxy,i", $GLOBALS['HTTP_VIA'])
	// special bug Netscape Win 4.0x
	&& !preg_match(",Mozilla/4\.0[^ ].*Win,i", $GLOBALS['HTTP_USER_AGENT'])
	// special bug Apache2x
	&& !preg_match(",Apache(-[^ ]+)?/2,i", $GLOBALS['SERVER_SOFTWARE'])
	// test suspendu: http://article.gmane.org/gmane.comp.web.spip.devel/32038/
	#&& !($GLOBALS['flag_sapi_name'] AND preg_match(",^apache2,", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler")
	&& !$GLOBALS['var_mode'] # bug avec le debugueur qui appelle ob_end_clean()
	)
		ob_start('ob_gzhandler');
}

// Vanter notre art de la composition typographique

if (!headers_sent())
	@header("Composed-By: SPIP $spip_version_affichee @ www.spip.net");

// Lien vers la page demandee et lien nettoye ne contenant que des id_objet
$clean_link = new Link();

# spip_log($_SERVER['REQUEST_METHOD'].' '.$clean_link->getUrl() . _FILE_CONNECT);

?>
