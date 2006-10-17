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

# compatibilite anciennes versions
# si vous n'avez aucun fichier .php3, redefinissez a ""
# ca fera foncer find_in_path
@define('_EXTENSION_PHP', '.php3');
#@define('_EXTENSION_PHP', '');

# le nom du repertoire ecrire/
@define('_DIR_RESTREINT_ABS', 'ecrire/');
# sommes-nous dans ecrire/ ?
@define('_DIR_RESTREINT',
 (!@is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
# ou inversement ?
@define('_DIR_RACINE', _DIR_RESTREINT ? '' : '../');

# le nom du repertoire config/
define('_DIR_CONFIG', _DIR_RACINE . "config/");

// nombre de repertoires depuis la racine

$profondeur_url = _DIR_RESTREINT ? 0 : 1;

//
// *** Parametrage par defaut de SPIP ***
//
// Ces parametres d'ordre technique peuvent etre modifies
// dans config/mes_options (_FILE_OPTIONS) Les valeurs
// specifiees dans ce dernier fichier remplaceront automatiquement
// les valeurs ci-dessous.
//
// Pour creer config/mes_options : recopier simplement
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

// Pour le javascript, trois modes : parano (-1), prive (0), ok (1)
// parano le refuse partout, ok l'accepte partout
// le mode par defaut le signale en rouge dans l'espace prive
$filtrer_javascript = 0;
// PS: dans les forums, petitions, flux syndiques... c'est *toujours* securise

// Type d'URLs
// 'page': spip.php?article123 [c'est la valeur par defaut pour SPIP 1.9]
// 'html': article123.html
// 'propres': Titre-de-l-article <http://lab.spip.net/spikini/UrlsPropres>
// 'propres2' : Titre-de-l-article.html (base sur 'propres')
// 'standard': article.php3?id_article=123 [urls SPIP < 1.9]
$type_urls = 'page';


//
// On note le numero IP du client dans la variable $ip
//
($ip = @$_SERVER['HTTP_X_FORWARDED_FOR']) OR $ip = @$_SERVER['REMOTE_ADDR'];

// Pour renforcer la privacy, decommentez la ligne ci-dessous (ou recopiez-la
// dans le fichier config/mes_options : SPIP ne pourra alors conserver aucun
// numero IP, ni temporairement lors des visites (pour gerer les statistiques
// ou dans spip.log), ni dans les forums (responsabilite)
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

// Autre approche : tout invalider si modif
// Si votre site a des problemes de performance face a une charge tres elevee,
// il est recommande de mettre cette globale a false (dans mes_options).
$derniere_modif_invalide = true;

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
$home_server = 'http://www.spip.net';
$help_server = $home_server . '/aide';
# TeX
$tex_server = 'http://math.spip.org/tex.php';
# MathML (pas pour l'instant: manque un bon convertisseur)
// $mathml_server = 'http://arno.rezo.net/tex2mathml/latex.php';
# Orthographe (serveurs multiples) [pas utilise pour l'instant]
$ortho_servers = array ('http://ortho.spip.net/ortho_serveur.php');

// Produire du TeX ou du MathML ?
$traiter_math = 'tex';

// Vignettes de previsulation des referers
// dans les statistiques
// 3 de trouves, possibilite de switcher
// - Thumbshots.org: le moins instrusif, quand il n'a pas, il renvoit un pixel vide
// - Girafa semble le plus complet, bicoz renvoit toujours la page d'accueil; mais avertissement si pas de preview
// - Alexa, equivalent Thumbshots, avec vignettes beaucoup plus grandes mais avertissement si pas de preview
//   Pour Alexa, penser a indiquer l'url du site dans l'id.
//   Dans Alexa, si on supprimer size=small, alors vignettes tres grandes
$source_vignettes = "http://open.thumbshots.org/image.pxf?url=http://";
// $source_vignettes = "http://msnsearch.srv.girafa.com/srv/i?s=MSNSEARCH&r=http://";
// $source_vignettes = "http://pthumbnails.alexa.com/image_server.cgi?id=www.monsite.net&size=small&url=http://";

$formats_logos =  array ('gif', 'jpg', 'png');

// Controler les dates des item dans les flux RSS ?
$controler_dates_rss = true;

//
// Pipelines & plugins
//
# les pipeline standards (traitements derivables aka points d'entree)
# ils seront compiles par la suite
# note: un pipeline non reference se compile aussi, mais uniquement
# lorsqu'il est rencontre
// http://doc.spip.org/@Tuto-Se-servir-des-points-d-entree
$spip_pipeline = array(
	'affichage_final' => '|f_surligne|f_tidy|f_admin', # cf. public/assembler
	'affiche_droite' => '',
	'affiche_gauche' => '',
	'affiche_milieu' => '',
	'ajouter_boutons' => '',
	'ajouter_onglets' => '',
	'body_prive' => '',
	'exec_init' => '',
	'header_prive' => '|f_jQuery',
	'insert_head' => '|f_jQuery',
	'nettoyer_raccourcis_typo' => '',
	'pre_indexation' => '',
	'pre_enregistre_contenu' => '',
	'pre_propre' => '|extraire_multi',
	'pre_syndication' => '',
	'pre_typo' => '|extraire_multi',
	'post_propre' => '',
	'post_syndication' => '',
	'post_typo' => '|quote_amp',
	'agenda_rendu_evenement' => '',
	'taches_generales_cron' => ''
);


# la matrice standard (fichiers definissant les fonctions a inclure)
$spip_matrice = array ();
# les plugins a activer
$plugins = array();  // voir le contenu du repertoire /plugins/
# les surcharges de include_spip()
$surcharges = array(); // format 'inc_truc' => '/plugins/chose/inc_truc2.php'

// Masquer les warning
error_reporting(E_ALL ^ E_NOTICE);

// Variables du compilateur de squelettes

$exceptions_des_tables = array();
$tables_principales = array();
$table_des_tables = array();
$tables_auxiliaires = array();
$table_primary = array();
$table_date = array();
$tables_jointures = array();
$tables_des_serveurs_sql['localhost'] =  &$tables_principales;

// Experimental : pour supprimer systematiquement l'affichage des numeros
// de classement des titres, recopier la ligne suivante dans mes_options :
# $table_des_traitements['TITRE'][]= 'typo(supprimer_numero(%s))';

// Droits d'acces maximum par defaut
@umask(0);

// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.918;

// version de spip en chaine
// et en numerique a incrementer sur les evolutions qui cassent la compatibilite descendante
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
// type nouvelles fonctionnalites, deplacement de fonctions ...
$spip_version_affichee = '1.9.2 alpha 2';
$spip_version_code = 1.9203;

// ** Securite **
$auteur_session = $connect_statut = $connect_toutes_rubriques = $connect_id_rubrique = $hash_recherche = $hash_recherche_strict = '';

// Fonction definissant les repertoires et fichiers non partageables
// Elle indique dans $test_dirs ceux devant etre accessibles en ecriture
// mais ne touche pas a cette variable si elle est deja definie
// afin que mes_options.php puisse en specifier d'autres.

function spip_initialisation_parametree($dir1) {

	static $too_late = false;
	if ($too_late) return;
	$too_late = true;

	define('_DIR_CHMOD', 0777);

	define('_DIR_IMG', $dir1 ."IMG/");
	define('_DIR_DOC', $dir1 ."IMG/");
	define('_DIR_LOGOS', $dir1 ."IMG/");
	define('_DIR_CACHE', $dir1 ."tmp/CACHE/");
	define('_DIR_SKELS', $dir1 ."tmp/CACHE/skel/");
	define('_DIR_PLUGINS', $dir1 . "plugins/");

	define('_DIR_TMP', $dir1 . "tmp/");
	define('_DIR_DUMP', $dir1 . "tmp/data/");
	define('_DIR_SESSIONS', $dir1 . "tmp/sessions/");
	define('_DIR_TRANSFERT', $dir1 . "tmp/upload/");

	// les fichiers qu'on y met, entre autres
	define('_FILE_CRON_LOCK', _DIR_TMP . 'cron.lock');
	define('_FILE_MYSQL_OUT', _DIR_TMP . 'mysql_out');
	define('_FILE_GARBAGE', _DIR_TMP . '.poubelle');
	define('_FILE_META', _DIR_TMP . 'meta_cache.txt');

	// sous-repertoires d'images et d'icones
	define('_DIR_TeX', _DIR_IMG . "cache-TeX/");
	define('_DIR_IMG_ICONES', _DIR_IMG . "icones/");

	// Le fichier de connexion a la base de donnees
	define('_FILE_CONNECT_INS_191', (_DIR_RESTREINT . "inc_connect"));
	define('_FILE_CONNECT_INS', (_DIR_CONFIG . "connect"));
	define('_FILE_CONNECT',
		(@is_readable($f = _FILE_CONNECT_INS . '.php') ? $f
	:	(@is_readable($f = _FILE_CONNECT_INS_191 . '.php') ? $f
	:	(@is_readable($f = _FILE_CONNECT_INS_191 . '.php3') ? $f
	:	false))));

	if (!isset($GLOBALS['test_dirs']))
		$GLOBALS['test_dirs'] =  array(_DIR_IMG, _DIR_TMP);
}

//
// Inclure le fichier config/mes_options (ou equivalent)
//

if (defined('_FILE_OPTIONS')) {
	if (@file_exists(_FILE_OPTIONS)) {
		include_once(_FILE_OPTIONS);
	}
} else {
	if (@file_exists(_DIR_CONFIG . 'mes_options.php')) {
		define('_FILE_OPTIONS',_DIR_CONFIG . 'mes_options.php');
		include_once(_FILE_OPTIONS);
	}
	else if (@file_exists(_DIR_RESTREINT . 'mes_options.php')) {
		define('_FILE_OPTIONS',_DIR_RESTREINT . 'mes_options.php');
		include_once(_FILE_OPTIONS);
	}
	# COMPATIBILITE .php3
	else if (_EXTENSION_PHP && @file_exists(_DIR_RESTREINT . 'mes_options.php3')) {
		define('_FILE_OPTIONS', _DIR_RESTREINT . 'mes_options.php3');
		include_once(_FILE_OPTIONS);
	}
}

//
// INITIALISER LES REPERTOIRES NON PARTAGEABLES
//
// 
// mais cette fonction a peut-etre deja ete appelee par mes_options

spip_initialisation_parametree(_DIR_RACINE) ;

//
// Definitions standards (charge aussi inc/flock)
//

require_once(_DIR_RESTREINT . 'inc/utils.php');

//
// INITIALISER LES CONSTANTES ET LES VARIABLES SYSTEMES DE SPIP
//

spip_initialisation();

// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins
if (@is_readable(_DIR_TMP."charger_plugins_options.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_options.php");
} else {
	include_spip('inc/plugin');
	// generer les fichiers php precompiles
	// de chargement des plugins et des pipelines
	verif_plugin();
	if (@is_readable(_DIR_TMP."charger_plugins_options.php")){
		include_once(_DIR_TMP."charger_plugins_options.php");
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
	if (!_DIR_RESTREINT) {
		redirige_par_entete(generer_url_ecrire("install"));
	} else {
	// Si on est dans le site public, dire que qq s'en occupe
		include_spip('inc/minipres');
		minipres(_T('info_travaux_titre'), "<p>"._T('info_travaux_texte')."</p>");
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}

//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante ; sauf dans l'espace
// prive car sinon ca rame a l'affichage (a revoir...)
//

// si un buffer est deja ouvert, stop
if (_DIR_RESTREINT AND $flag_ob AND strlen(ob_get_contents())==0 AND !headers_sent()) {
	@header("Vary: Cookie, Accept-Encoding");

	if (
	$GLOBALS['auto_compress']
	&& (phpversion()<>'4.0.4')
	&& function_exists("ob_gzhandler")
	// special bug de proxy
	&& !(isset($GLOBALS['HTTP_VIA']) AND preg_match(",NetCache|Hasd_proxy,i", $GLOBALS['HTTP_VIA']))
	// special bug Netscape Win 4.0x
	&& !preg_match(",Mozilla/4\.0[^ ].*Win,i", $GLOBALS['HTTP_USER_AGENT'])
	// special bug Apache2x
	&& !preg_match(",Apache(-[^ ]+)?/2,i", $GLOBALS['SERVER_SOFTWARE'])
	// test suspendu: http://article.gmane.org/gmane.comp.web.spip.devel/32038/
	#&& !($GLOBALS['flag_sapi_name'] AND preg_match(",^apache2,", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler")
	&& !isset($GLOBALS['var_mode']) # bug avec le debugueur qui appelle ob_end_clean()
	)
		ob_start('ob_gzhandler');
}
else
	@header("Vary: Cookie");

// Vanter notre art de la composition typographique

if (!headers_sent())
	@header("Composed-By: SPIP $spip_version_affichee @ www.spip.net");

# spip_log($_SERVER['REQUEST_METHOD'].' '.self() . ' - '._FILE_CONNECT);

?>