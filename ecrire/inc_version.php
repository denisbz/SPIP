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
// (experimental)
# NB: cette option ne concerne que articles,breves,rubriques et site
# car les forums et petitions sont toujours invalidants.
$invalider_caches = false;

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
// Plugins
//
// (plus tard on fera une interface graphique qui les liste et permet de
// les activer un par un, dans tel ordre, etc)
# les pipeline standards (traitements derivables aka points d'entree)
$spip_pipeline = array(
	'pre_typo' => '|extraire_multi',
	'post_typo' => '|quote_amp',
	'pre_propre' => '|extraire_multi',
	'post_propre' => '',
	'pre_indexation' => '',
	'pre_syndication' => '',
	'post_syndication' => '',
	'ajouter_boutons' => '',
	'ajouter_onglets' => ''
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
	include(_FILE_OPTIONS);
}

//
// Definitions standards
//

// la taille maxi des logos (0 : pas de limite)
define('_LOGO_MAX_SIZE', 0); # poids en ko
define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels

// Le fichier de connexion a la base de donnees
define('_FILE_CONNECT_INS', (_DIR_RESTREINT . "inc_connect"));
define('_FILE_CONNECT',
	(@is_readable(_FILE_CONNECT_INS . _EXTENSION_PHP) ?
		(_FILE_CONNECT_INS . _EXTENSION_PHP)
	 : false));

// les repertoires annexes
define('_DIR_INCLUDE', _DIR_RESTREINT);
define('_DIR_IMG', _DIR_RACINE ."IMG/");
define('_DIR_DOC', _DIR_RACINE ."IMG/");
define('_DIR_CACHE', _DIR_RACINE ."CACHE/");
define('_DIR_SESSIONS', _DIR_RESTREINT . "data/");
define('_DIR_TRANSFERT', _DIR_RESTREINT . "upload/");
define('_DIR_PLUGINS', _DIR_RACINE . "plugins/");
define('_DIR_LOGOS', _DIR_RACINE ."IMG/");

// les fichiers qu'on y met, entre autres
define('_FILE_CRON_LOCK', _DIR_SESSIONS . 'cron.lock');
define('_FILE_MYSQL_OUT', _DIR_SESSIONS . 'mysql_out');
define('_FILE_GARBAGE', _DIR_SESSIONS . '.poubelle');
define('_FILE_META', _DIR_SESSIONS . 'meta_cache.txt');

// sous-repertoires d'images 
define('_DIR_TeX', _DIR_IMG . "cache-TeX/");

// Icones
# le chemin http (relatif) vers les images standard
define('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
# vers les logos de type de document, standard & perso
define('_DIR_IMG_ICONES_DIST', _DIR_IMG_PACK . "icones/");
define('_DIR_IMG_ICONES', _DIR_IMG . "icones/");
# les icones de la barre d'edition des formulaires
define('_DIR_IMG_ICONES_BARRE', _DIR_IMG_PACK . "icones_barre/");

# le chemin php (absolu) vers les images standard (pour hebergement centralise)
define('_ROOT_IMG_PACK', (dirname(__FILE__) . '/img_pack/'));
define('_ROOT_IMG_ICONES_DIST', (dirname(__FILE__) . '/img_pack/icones/'));

// Fichiers de langue
define('_DIR_LANG', (_DIR_RESTREINT . 'lang/'));

// Le charset par defaut lors de l'installation
define('_DEFAULT_CHARSET', 'utf-8');

// les repertoires devant etre TOUJOURS accessibles en ecriture

$test_dirs = array(_DIR_CACHE, _DIR_IMG, _DIR_SESSIONS);

// qq chaines standard

define('_ACCESS_FILE_NAME', '.htaccess');
define('_AUTH_USER_FILE', '.htpasswd');

define('_DOCTYPE_ECRIRE', "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n");

define('_SPIP_PATH', './:squelettes/:dist/:formulaires/:ecrire/');


// charge les fonctions indispensables
include(_DIR_INCLUDE . 'inc_utils.php');


// *********** traiter les variables ************

// Recuperer les superglobales $_GET si non definies
// (en theorie c'est impossible depuis PHP 4.0.3, cf. track_vars)
// et les identifier aux $HTTP_XX_VARS
foreach (array('_GET', '_POST', '_COOKIE', '_SERVER') as $_table) {
	$http_table_vars = 'HTTP'.$_table.'_VARS';
	if (!is_array($GLOBALS[$_table])) {
		$GLOBALS[$_table] = array();
		if (is_array($GLOBALS[$http_table_vars]))
			$GLOBALS[$_table] = & $GLOBALS[$http_table_vars];
	}
		$GLOBALS[$http_table_vars] = & $GLOBALS[$_table];
}

//
// Securite
//

// Ne pas se faire manger par un bug php qui laisse passer ?GLOBALS[truc]=toto
if (isset($_REQUEST['GLOBALS'])) die();
// nettoyer les magic quotes \' et les caracteres nuls %00
spip_desinfecte($_GET);
spip_desinfecte($_POST);
spip_desinfecte($_COOKIE);
#	if (@ini_get('register_globals')) // pas fiable
spip_desinfecte($GLOBALS);
// a la fin supprimer la variable anti-recursion devenue inutile
// (et meme nuisible, notamment si on teste $_POST)
unset($_GET['spip_recursions']);
unset($_POST['spip_recursions']);
unset($_COOKIE['spip_recursions']);
unset($GLOBALS['spip_recursions']);
// Par ailleurs on ne veut pas de magic_quotes au cours de l'execution
@set_magic_quotes_runtime(0);

// Remplir $GLOBALS avec $_GET et $_POST (methode a revoir pour fonctionner
// completement en respectant register_globals = off)
spip_register_globals();

// appliquer le cookie_prefix
if ($cookie_prefix != 'spip') {
	include_ecrire('inc_cookie');
	recuperer_cookies_spip($cookie_prefix);
}


//
// Capacites php (en fonction de la version)
//

$flag_gz = function_exists("gzencode"); #php 4.0.4
$flag_ob = (function_exists("ob_start")
	&& function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0)
	&& !strstr(ini_get('disable_functions'), 'ob_'));
$flag_sapi_name = function_exists("php_sapi_name");
$flag_revisions = function_exists("gzcompress");
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_upload = (!$flag_get_cfg_var || (get_cfg_var('upload_max_filesize') > 0));

//
// Sommes-nous dans l'empire du Mal ?
// (ou sous le signe du Pingouin, ascendant GNU ?)
//

if (strpos($_SERVER['SERVER_SOFTWARE'], '(Win') !== false)
	define ('os_serveur', 'windows');

//
// Infos sur le fichier courant
//

// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
if (!$REQUEST_URI) {
	$REQUEST_URI = $PHP_SELF;
	if ($QUERY_STRING AND !strpos($REQUEST_URI, '?'))
		$REQUEST_URI .= '?'.$QUERY_STRING;
}


// tidy en ligne de commande (si on ne l'a pas en module php,
// ou si le module php ne marche pas)
// '/bin/tidy' ou '/usr/local/bin/tidy' ou tout simplement 'tidy'
#define('_TIDY_COMMAND', 'tidy');

//
// Module de lecture/ecriture/suppression de fichiers utilisant flock()
//
include_ecrire('inc_flock');


// Lire les meta cachees

if (lire_fichier(_DIR_SESSIONS . 'meta_cache.txt', $meta))
		$meta = @unserialize($meta);
	// en cas d'echec refaire le fichier
if (!is_array($meta) AND _FILE_CONNECT) {
		include_ecrire('inc_meta');
		ecrire_metas();
	}


// Langue principale du site
$langue_site = $GLOBALS['meta']['langue_site'];
if (!$langue_site) include_ecrire('inc_lang');
$spip_lang = $langue_site;


// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins
if (@is_readable(_DIR_SESSIONS."charger_plugins_options.php")){
	// chargement optimise precompile
	include_once(_DIR_SESSIONS."charger_plugins_options.php");
}
else
{
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
