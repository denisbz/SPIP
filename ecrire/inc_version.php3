<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");

function define_once ($constant, $valeur) {
	if (!defined($constant)) define($constant, $valeur);
}

// 6 constantes incontournables et prioritaires

define('_EXTENSION_PHP', '.php3'); # a etendre
define('_DIR_RESTREINT_ABS', 'ecrire/');
define('_DIR_RESTREINT', (!@is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
define('_FILE_OPTIONS', _DIR_RESTREINT . 'mes_options.php3');
define('_FILE_CONNECT_INS', (_DIR_RESTREINT . "inc_connect"));
define('_FILE_CONNECT',
	(@file_exists(_FILE_CONNECT_INS . _EXTENSION_PHP) ?
		(_FILE_CONNECT_INS . _EXTENSION_PHP)
	 : false));

//
// Gestion des inclusions et infos repertoires
//

$included_files = array();

function include_local($file) {
	if (@$GLOBALS['included_files'][$file]++) return;
	include($file);
}

function include_ecrire($file) {
# Hack pour etre compatible avec les mes_options qui appellent cette fonction
	define_once('_DIR_INCLUDE', _DIR_RESTREINT);
	$file = _DIR_INCLUDE . $file;
	if (@$GLOBALS['included_files'][$file]++) return;
	include($file);
}

function include_lang($file) {
	$file = _DIR_LANG . $file;
	if (@$GLOBALS['included_files'][$file]++) return;
	include($file);
}

function include_plug($file) {
	$file = _DIR_RESTREINT . $file;
	if (@$GLOBALS['included_files'][$file]++) return;
	if (file_exists($file)) include($file);
}


// *********** traiter les variables ************

// Recuperer les superglobales $_GET si non definies
// (en theorie c'est impossible depuis PHP 4.0.3, cf. track_vars)
foreach (array('_GET', '_POST', '_COOKIE', '_SERVER') as $_table) {
	if (!is_array($GLOBALS[$_table])) {
		$GLOBALS[$_table] = array();
		if (is_array($GLOBALS[$http_table_vars]))
			$GLOBALS[$_table] = & $GLOBALS[$http_table_vars];
	} /* else
	$GLOBALS[$http_table_vars] = & $GLOBALS[$_table]; */
}


// Magic quotes : on n'en veut pas sur la base,
// et on nettoie les GET/POST/COOKIE le cas echeant
function magic_unquote($_table) {

	// Certains hebergeurs n'activent pas $GLOBALS['GLOBALS']
	if ($_table == 'GLOBALS'
	AND !isset($GLOBALS['GLOBALS']))
		$GLOBALS['GLOBALS'] = &$GLOBALS;

	if (is_array($GLOBALS[$_table])) {
		foreach ($GLOBALS[$_table] as $key => $val) {
			if (is_string($val))
				$GLOBALS[$_table][$key] = stripslashes($val);
		}
	}
}

@set_magic_quotes_runtime(0);
if (@get_magic_quotes_gpc()) {
	magic_unquote('_GET');
	magic_unquote('_POST');
	magic_unquote('_COOKIE');

	if (@ini_get('register_globals'))
		magic_unquote('GLOBALS');
}



// Dirty hack contre le register_globals a 'Off' (PHP 4.1.x)
// A remplacer (un jour!) par une gestion propre des variables admissibles ;-)
// Attention pour compatibilite max $_GET n'est pas superglobale
// NB: c'est une fonction de maniere a ne pas pourrir $GLOBALS
function spip_register_globals() {

	// Liste des variables dont on refuse qu'elles puissent provenir du client
	$refuse_gpc = array (
		# inc-public.php3
		'fond', 'delais',

		# ecrire/inc_auth.php3
		'REMOTE_USER',
		'PHP_AUTH_USER', 'PHP_AUTH_PW',

		# ecrire/inc_texte.php3
		'debut_intertitre', 'fin_intertitre', 'ligne_horizontale',
		'ouvre_ref', 'ferme_ref', 'ouvre_note', 'ferme_note',
		'les_notes', 'compt_note', 'nombre_surligne',
		'url_glossaire_externe', 'puce', 'puce_rtl'
	);

	// Liste des variables (contexte) dont on refuse qu'elles soient cookie
	// (histoire que personne ne vienne fausser le cache)
	$refuse_c = array (
		# inc-calcul.php3
		'id_parent', 'id_rubrique', 'id_article',
		'id_auteur', 'id_breve', 'id_forum', 'id_secteur',
		'id_syndic', 'id_syndic_article', 'id_mot', 'id_groupe',
		'id_document', 'date', 'lang'
	);


	// Si les variables sont passees en global par le serveur, il faut
	// faire quelques verifications de base
	if (@ini_get('register_globals')) {
		foreach ($refuse_gpc as $var) {
			if (isset($GLOBALS[$var])) {
				foreach (array('_GET', '_POST', '_COOKIE') as $_table) {
					if (
					// demande par le client
					isset ($GLOBALS[$_table][$var])
					// et pas modifie par les fichiers d'appel
					AND $GLOBALS[$_table][$var] == $GLOBALS[$var]
					) // On ne sait pas si c'est un hack
					{
						# REMOTE_USER ou fond, c'est grave ;
						# pour le reste (cookie 'lang', par exemple), simplement
						# interdire la mise en cache de la page produite
						switch ($var) {
							case 'REMOTE_USER':
							case 'fond':
								die ("$var interdite");
								break;
							default:
								define ('spip_interdire_cache', true);
						}
					}
				}
			}
		}
		foreach ($refuse_c as $var) {
			if (isset($GLOBALS[$var])) {
				foreach (array('_COOKIE') as $_table) {
					if (
					// demande par le client
					isset ($GLOBALS[$_table][$var])
					// et pas modifie par les fichiers d'appel
					AND $GLOBALS[$_table][$var] == $GLOBALS[$var]
					)
						define ('spip_interdire_cache', true);
				}
			}
		}
	}

	// sinon il faut les passer nous-memes, a l'exception des interdites.
	// (A changer en une liste des variables admissibles...)
	else {
		foreach (array('_SERVER', '_COOKIE', '_POST', '_GET') as $_table) {
			foreach ($GLOBALS[$_table] as $var => $val) {
				if (!isset($GLOBALS[$var])
				AND isset($GLOBALS[$_table][$var])
				AND ($_table == '_SERVER' OR !in_array($var, $refuse_gpc))
				AND ($_table <> '_COOKIE' OR !in_array($var, $refuse_c)))
					$GLOBALS[$var] = $val;
			}
		}
	}
}
spip_register_globals();





//
// *** Parametrage par defaut de SPIP ***
//
// Ces parametres d'ordre technique peuvent etre modifies
// dans ecrire/mes_options.php3 (_FILE_OPTIONS) Les valeurs
// specifiees dans ce dernier fichier remplaceront automatiquement
// les valeurs ci-dessous.
//
// Pour creer ecrire/mes_options.php3 : recopier simplement
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
// 'standard': article.php3?id_article=123
// 'html': article123.html
// 'propres': Titre-de-l-article.html <http://lab.spip.net/spikini/UrlsPropres>
$type_urls = 'standard';

// creation des vignettes avec image magick en ligne de commande : mettre
// le chemin complet '/bin/convert' (Linux) ou '/sw/bin/convert' (fink/Mac OS X)
// Note : preferer GD2 ou le module php imagick s'ils sont disponibles
$convert_command = 'convert';

// creation des vignettes avec pnmscale
// Note: plus facile a installer par FTP,
// voir http://gallery.menalto.com/modules.php?op=modload&name=GalleryFAQ&file=index&myfaq=yes&id_cat=2
$pnmscale_command = 'pnmscale';

// faut-il passer les connexions MySQL en mode debug ?
$mysql_debug = false;

// faut-il chronometrer les requetes MySQL ?
$mysql_profile = false;

// faut-il faire des connexions completes rappelant le nom du serveur et de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL ;
// a desactiver en cas de soucis de connexion chez certains hebergeurs [??])
$mysql_rappel_connexion = true;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// faut-il souligner en gris, dans articles.php3, les espaces insecables ?
$activer_revision_nbsp = false;

// gestion des extras (voir inc_extra.php3 pour plus d'informations)
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
$quota_cache = 5;

// code a fournir pour obtenir le debuggueur (urls &var_mode=debug)
// par defaut seuls les admins : $code_activation_debug='';
// pour mettre un mot de passe : $code_activation_debug='x5g8jk9';
$code_activation_debug = '';

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

// Masquer les warning
error_reporting(E_ALL ^ E_NOTICE);

/* ATTENTION CETTE VARIABLE NE FONCTIONNE PAS ENCORE */
// Extension du fichier du squelette 
$extension_squelette = 'html';
/* / MERCI DE VOTRE ATTENTION */

// Droits d'acces maximum par defaut
@umask(0);

//
// Definition des repertoires standards, _FILE_OPTIONS ayant priorite
//

if (@file_exists(_FILE_OPTIONS)) {
  include(_FILE_OPTIONS);
}

// la taille maxi des logos (0 : pas de limite)
define_once('_LOGO_MAX_SIZE', 0); # poids en ko
define_once('_LOGO_MAX_WIDTH', 0); # largeur en pixels
define_once('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels


define_once('_DIR_INCLUDE', _DIR_RESTREINT);
define_once('_DIR_PREFIX1', (_DIR_RESTREINT ? "" : "../"));
define_once('_DIR_PREFIX2', _DIR_RESTREINT);

// les repertoires des logos, des pieces rapportees, du CACHE et des sessions

define_once('_DIR_IMG', _DIR_PREFIX1 ."IMG/");
define_once('_DIR_DOC', _DIR_PREFIX1 ."IMG/");
define_once('_DIR_CACHE', _DIR_PREFIX1 ."CACHE/");

define_once('_DIR_SESSIONS', _DIR_PREFIX2 . "data/");
define_once('_DIR_TRANSFERT', _DIR_PREFIX2 . "upload/");


## c'est tres bete de charger ce fichier a chaque hit sur le serveur !
if (@file_exists(_DIR_SESSIONS . 'inc_plugins.php3')) {
	include(_DIR_SESSIONS . 'inc_plugins.php3');
}

// exemples de redefinition possible, 
// SOUS RESERVE QUE php.ini N'AIT PAS pas openbasedir=. !!!!!!
// il est recommande de mettre les deux premiers en dehors de l'arbo http
// pour _DIR_DOC, on ne peut le faire qu'en configuration securisee
// pour _DIR_IMG, NE PAS LE METTRE en dehors de l'arborescence http

//define('_DIR_CACHE', "/tmp/c/");
//define('_DIR_SESSIONS', "/tmp/s/");

//define('_DIR_DOC', "/tmp/d/");
//define('_DIR_INCLUDE', _DIR_RESTREINT  ? 'Include/' : '../Include/');
// globale des repertoires devant etre accessibles en ecriture
// (inutile de mettre leurs sous-repertoires)

$test_dirs = array(_DIR_CACHE, _DIR_IMG, _DIR_SESSIONS);

// les fichiers qu'on y met, entre autres,

define_once('_FILE_CRON_LOCK', _DIR_SESSIONS . 'cron.lock');
define_once('_FILE_MYSQL_OUT', _DIR_SESSIONS . 'mysql_out');
define_once('_FILE_GARBAGE', _DIR_SESSIONS . '.poubelle');


// sous-repertoires d'images accessible en ecriture

define_once('_DIR_IMG_ICONES', _DIR_IMG . "icones/");
define_once('_DIR_IMG_ICONES_BARRE', _DIR_IMG . "icones_barre/");
define_once('_DIR_TeX', _DIR_IMG . "TeX/");

// pour ceux qui n'aiment pas nos icones et notre vocabulaire, tout est prevu

define_once('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
define_once('_DIR_LANG', (_DIR_RESTREINT . 'lang/'));

// qq chaines standard

define_once('_ACCESS_FILE_NAME', '.htaccess');
define_once('_AUTH_USER_FILE', '.htpasswd');


// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.812;

// version de spip
$spip_version_affichee = "1.8 PR1 CVS";

// version de spip / tag cvs
if (ereg('Name: v(.*) ','$Name$', $regs)) $spip_version_affichee = $regs[1];


// ** Securite **
$auteur_session = '';
$connect_statut = '';
$hash_recherche = '';
$hash_recherche_strict = '';



//
// Capacites php (en fonction de la version)
//

$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_ini_get = (function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
$flag_gz = function_exists("gzencode"); #php 4.0.4
$flag_ob = ($flag_ini_get
	&& !ereg("ob_", ini_get('disable_functions'))
	&& function_exists("ob_start")); 
$flag_pcre = function_exists("preg_replace");
$flag_crypt = function_exists("crypt");
$flag_wordwrap = function_exists("wordwrap");
$flag_apc = function_exists("apc_rm");
$flag_sapi_name = function_exists("php_sapi_name");
$flag_utf8_decode = function_exists("utf8_decode");
$flag_ldap = function_exists("ldap_connect");
$flag_flock = function_exists("flock");
$flag_ImageCreateTrueColor = function_exists("ImageCreateTrueColor");
$flag_ImageCopyResampled = function_exists("ImageCopyResampled");
$flag_ImageGif = function_exists("ImageGif");
$flag_ImageJpeg = function_exists("ImageJpeg");
$flag_ImagePng = function_exists("ImagePng");
$flag_imagick = function_exists("imagick_readimage");	// http://pear.sourceforge.net/en/packages.imagick.php
$flag_multibyte = function_exists("mb_encode_mimeheader");
$flag_iconv = function_exists("iconv");
$flag_strtotime = function_exists("strtotime");

$flag_gd = $flag_ImageGif || $flag_ImageJpeg || $flag_ImagePng;
$flag_revisions = ($flag_pcre AND function_exists("gzcompress"));

// deux constantes pour le surlignage (cf inc_surligne et inc-balise)

define("MARQUEUR_SURLIGNE",  'span class="spip_surligneconditionnel">');
define("MARQUEUR_FSURLIGNE", '/span                                 >'); 

//
// Appliquer le prefixe cookie
//
function spip_setcookie ($name='', $value='', $expire=0, $path='AUTO', $domain='', $secure='') {
	$name = ereg_replace ('^spip_', $GLOBALS['cookie_prefix'].'_', $name);
	if ($path == 'AUTO') $path=$GLOBALS['cookie_path'];

	if ($secure)
		@setcookie ($name, $value, $expire, $path, $domain, $secure);
	else if ($domain)
		@setcookie ($name, $value, $expire, $path, $domain);
	else if ($path)
		@setcookie ($name, $value, $expire, $path);
	else if ($expire)
		@setcookie ($name, $value, $expire);
	else
		@setcookie ($name, $value);
}

if ($cookie_prefix != 'spip') {
	foreach ($_COOKIE as $name => $value) {
		if (ereg('^spip_', $name)) {
			unset($_COOKIE[$name]);
			unset($$name);
		}
	}
	foreach ($_COOKIE as $name => $value) {
		if (ereg('^'.$cookie_prefix.'_', $name)) {
			$spipname = ereg_replace ('^'.$cookie_prefix.'_', 'spip_', $name);
			$_COOKIE[$spipname] = $INSECURE[$spipname] = $value;
			$$spipname = $value;
		}
	}
}


//
// Sommes-nous dans l'empire du Mal ?
//
if (strpos($_SERVER['SERVER_SOFTWARE'], '(Win') !== false)
	define ('os_serveur', 'windows');


//
// Enregistrement des evenements
//
function spip_log($message, $logname='spip') {

	$pid = '(pid '.@getmypid().')';
	if (!$ip = $GLOBALS['REMOTE_ADDR']) $ip = '-';

	$message = date("M d H:i:s")." $ip $pid "
		.ereg_replace("\n*$", "\n", $message);

	$logfile = _DIR_SESSIONS . $logname . '.log';
	if (@file_exists($logfile) && (@filesize($logfile) > 10*1024)) {
		$rotate = true;
		$message .= "[-- rotate --]\n";
	}
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, $message);
		fclose($f);
	}
	if ($rotate) {
		@unlink($logfile.'.3');
		@rename($logfile.'.2',$logfile.'.3');
		@rename($logfile.'.1',$logfile.'.2');
		@rename($logfile,$logfile.'.1');
	}

	// recopier les spip_log mysql (ce sont uniquement des erreurs)
	// dans le spip_log general
	if ($logname == 'mysql')
		spip_log($message);
}


//
// Infos sur le fichier courant
//

// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
if (!$REQUEST_URI) {
	$REQUEST_URI = $PHP_SELF;
	if ($QUERY_STRING AND !strpos($REQUEST_URI, '?'))
		$REQUEST_URI .= '?'.$QUERY_STRING;
}

if (!$PATH_TRANSLATED) {
	if ($SCRIPT_FILENAME) $PATH_TRANSLATED = $SCRIPT_FILENAME;
	else if ($DOCUMENT_ROOT && $SCRIPT_URL) $PATH_TRANSLATED = $DOCUMENT_ROOT.$SCRIPT_URL;
}

# obsoletes: utiliser les constantes ci-dessus.
# Conserver pour compatibilite vieilles contrib uniquement
$flag_ecrire = !@file_exists(_DIR_RESTREINT_ABS . 'inc_version.php3');
$dir_ecrire = (ereg("/ecrire/", $GLOBALS['REQUEST_URI'])) ? '' : 'ecrire/';

// API d'appel a la base de donnees
function spip_query($query) {

	// Remarque : si on est appele par l'install,
	// la connexion initiale a ete faite avant
	if (!$GLOBALS['db_ok']) {
		// Essaie de se connecter
		if (_FILE_CONNECT)
			include_local(_FILE_CONNECT);
	}

	// Erreur de connexion
	if (!$GLOBALS['db_ok'])
		return;

	// Vieux format de fichier connexion
	if ($GLOBALS['spip_connect_version'] < 0.1) {
		if (!_DIR_RESTREINT) {$GLOBALS['db_ok'] = false; return;}
		@Header("Location: upgrade.php3?reinstall=oui");
		exit;
	}

	// Faire la requete
	return spip_query_db($query);
}

function appliquer_fonction($lafonction, $entree) {

	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["avant"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["avant"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}
	
	if (@function_exists($lafonction)) $sortie = $lafonction($sortie);

	if (isset($GLOBALS["fonctions"]["$lafonction"]["apres"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["apres"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}	
	return $sortie;
}

function appliquer_fonction_avant($lafonction, $entree) {
	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["avant"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["avant"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}
			
	return $sortie;
}

function appliquer_fonction_apres($lafonction, $entree) {

	$sortie = $entree;
	
	if (isset($GLOBALS["fonctions"]["$lafonction"]["apres"])) {
		foreach ($GLOBALS["fonctions"]["$lafonction"]["apres"] as $key => $value) {
			if (@function_exists($value)) $sortie = $value($sortie);
		}
	}	
		
	return $sortie;
}


// Destine a "completer" une fonction
function completer_fonction($fonction_base, $fonction_avant="", $fonction_apres="") {
	if (strlen($fonction_avant) > 0) 
		$GLOBALS["fonctions"]["$fonction_base"]["avant"][] = $fonction_avant;
		
	if (strlen($fonction_apres) > 0) 
		$GLOBALS["fonctions"]["$fonction_base"]["apres"][] = $fonction_apres;	
}


//
// Infos de config PHP
//

// cf. liste des sapi_name - http://fr.php.net/php_sapi_name
$php_module = (($flag_sapi_name AND eregi("apache", @php_sapi_name())) OR
	ereg("^Apache.* PHP", $SERVER_SOFTWARE));
$php_cgi = ($flag_sapi_name AND eregi("cgi", @php_sapi_name()));

function http_status($status) {
	global $php_cgi, $REDIRECT_STATUS;

	if ($REDIRECT_STATUS && $REDIRECT_STATUS == $status) return;
	$status_string = array(
		200 => '200 OK',
		304 => '304 Not Modified',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found'
	);

	if ($php_cgi) Header("Status: $status");
	else Header("HTTP/1.0 ".$status_string[$status]);
}

function http_gmoddate($lastmodified) {
	return gmdate("D, d M Y H:i:s", $lastmodified);
}
function http_last_modified($lastmodified, $expire = 0) {
	$gmoddate = http_gmoddate($lastmodified);
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE']) {
		$if_modified_since = ereg_replace(';.*$', '', $GLOBALS['HTTP_IF_MODIFIED_SINCE']);
		$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
		if ($if_modified_since == $gmoddate) {
			http_status(304);
			$headers_only = true;
		}
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	if ($expire) 
		@Header ("Expires: ".http_gmoddate($expire)." GMT");
	return $headers_only;
}

$flag_upload = (!$flag_get_cfg_var || (get_cfg_var('upload_max_filesize') > 0));

function tester_upload() {
	return $GLOBALS['flag_upload'];
}


//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante
//
function test_obgz () {
	return
	$GLOBALS['auto_compress']
	&& $GLOBALS['flag_ob']
	&& (phpversion()<>'4.0.4')
	&& function_exists("ob_gzhandler")
	&& $GLOBALS['flag_obgz']
	// special bug de proxy
	&& !eregi("NetCache|Hasd_proxy", $GLOBALS['HTTP_VIA'])
	// special bug Netscape Win 4.0x
	&& !eregi("Mozilla/4\.0[^ ].*Win", $GLOBALS['HTTP_USER_AGENT'])
	// special bug Apache2x
	&& !eregi("Apache(-[^ ]+)?/2", $GLOBALS['SERVER_SOFTWARE'])
	&& !($GLOBALS['flag_sapi_name'] AND ereg("^apache2", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler");
}
// si un buffer est deja ouvert, stop
if ($flag_ob AND !headers_sent()) {
	@header("Vary: Cookie, Accept-Encoding");
	if (test_obgz())
		ob_start('ob_gzhandler');
}


class Link {
	var $file;
	var $vars;
	var $arrays;

	//
	// Contructeur : a appeler soit avec l'URL du lien a creer,
	// soit sans parametres, auquel cas l'URL est l'URL courante
	//
	function Link($url = '', $reentrant = false) {
		global $_POST;
		static $link = '';

		$this->vars = array();
		$this->arrays = array();

		// Normal case
		if ($link) {
			if ($url) {
				$v = split('[\?\&]', $url);
				list(, $this->file) = each($v);
				while (list(, $var) = each($v)) {
					list($name, $value) = split('=', $var, 2);
					$name = urldecode($name);
					$value = urldecode($value);
					if (ereg('^(.*)\[\]$', $name, $regs)) {
						$this->arrays[$regs[1]][] = $value;
					}
					else {
						$this->vars[$name] = $value;
					}
				}
			}
			else {
				$this->file = $link->file;
				$this->vars = $link->vars;
				$this->arrays = $link->arrays;
			}
			return;
		}

		// Si aucun URL n'est specifie, creer le lien "propre"
		// ou l'on supprime de l'URL courant les bidules inutiles
		if (!$url) {
			// GET variables are read from the original URL
			// (_GET may contain additional variables
			// introduced by rewrite-rules)
			$url = $GLOBALS['REQUEST_URI'];
			// Warning !!!! 
			// since non encoded arguments may be present
			// (especially those coming from Rewrite Rule)
			// find the begining of the query string
			// to compute the script-name
			if ($v = strpos($url,'?'))
			  $v = strrpos(substr($url, 0, $v), '/');
			else $v = strrpos($url, '/');
			$url = substr($url, $v + 1);
			if (!$url) $url = "./";
			if (count($_POST)) {
				$vars = array();
				foreach ($_POST as $var => $val)
					if (preg_match('/^id_/', $var))
						$vars[$var] = $val;
			}
		}
		$v = split('[\?\&]', $url);
		list(, $this->file) = each($v);
		if (!$vars) {
			while (list(,$var) = each($v)) {
				list($name, $value) = split('=', $var, 2);
				$name = urldecode($name);
				$value = urldecode($value);
				if (ereg('^(.*)\[\]$', $name, $regs))
					$vars[$regs[1]][] = $value;
				else
					$vars[$name] = $value;
			}
		}

		if (is_array($vars)) {
			foreach ($vars as $name => $value) {
				// items supprimes
				if (!preg_match('/^('.
				(!_DIR_RESTREINT ?
					'|lang|set_options|set_couleur|set_disp|set_ecran':
					'var_mode|show_docs')
				. ')$/i', $name)) {
					if (is_array($value))
						$this->arrays[$name] = $value;
					else
						$this->vars[$name] = $value;
				}
			}
		}
	}

	//
	// Effacer une variable
	//
	function delVar($name) {
		if($this->vars[$name]) unset($this->vars[$name]);
		if($this->arrays[$name]) unset($this->arrays[$name]);
	}

	//
	// Ajouter une variable
	// (si aucune valeur n'est specifiee, prend la valeur globale actuelle)
	//
	function addVar($name, $value = '__global__') {
		if ($value == '__global__') $value = $GLOBALS[$name];
		if (is_array($value))
			$this->arrays[$name] = $value;
		else
			$this->vars[$name] = $value;
	}

	//
	// Recuperer l'URL correspondant au lien
	//
	function getUrl($anchor = '') {
		$url = $this->file;
		if (!$url) $url = './';
		$query = '';
		foreach($this->vars as $name => $value)
			$query .= '&'.$name.'='.urlencode($value);

		foreach ($this->arrays as $name => $table)
		foreach ($table as $value)
			$query .= '&'.$name.'[]='.urlencode($value);

		if ($query) $query = '?'. substr($query, 1);
		if ($anchor) $anchor = '#'.$anchor;
		return "$url$query$anchor";
	}

	//
	// Recuperer le debut de formulaire correspondant au lien
	// (tag ouvrant + entrees cachees representant les variables)
	//

	function getForm($method = 'get', $anchor = '', $enctype = '') {
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= " style='border: 0px; margin: 0px;'>\n";
		foreach ($this->vars as $name => $value) {
			$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
		}
		foreach ($this->arrays as $name => $table)
		foreach ($table as $value) {
			$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
			$form .= "<input type=\"hidden\" name=\"".$name."[]\" value=\"".$value."\" />\n";
		}

		return $form;
	}
}


// Lien vers la page demandee et lien nettoye ne contenant que des id_objet
$clean_link = new Link();


// URLs avec passage & -> &amp;
function quote_amp ($url) {
	$url = str_replace("&amp;", "&", $url);
	$url = str_replace("&", "&amp;", $url);
	return $url;
}


//
// Module de lecture/ecriture/suppression de fichiers utilisant flock()
//
include_ecrire('inc_flock.php3');


//
// Gerer les valeurs meta
//
function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}
function lire_meta_maj($nom) {
	global $meta_maj;
	return $meta_maj[$nom];
}

// Lire les meta cachees
if (!defined('_DATA_META_CACHE') AND !defined('_ECRIRE_INC_META')) {
	unset($meta); # parano

	if (file_exists(_DIR_SESSIONS . 'meta_cache.php3'))
		include(_DIR_SESSIONS . 'meta_cache.php3');
	// en cas d'echec refaire le fichier
	if (!is_array($meta) AND _FILE_CONNECT) {

		include_ecrire('inc_meta.php3');
		ecrire_metas();
	}
}

// Verifier la conformite d'une ou plusieurs adresses email
function email_valide($adresse) {
	$adresses = explode(',', $adresse);
	if (is_array($adresses)) {
		while (list(, $adresse) = each($adresses)) {
			// nettoyer certains formats
			// "Marie Toto <Marie@toto.com>"
			$adresse = eregi_replace("^[^<>\"]*<([^<>\"]+)>$", "\\1", $adresse);
			// RFC 822
			if (!eregi('^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)?$', trim($adresse)))
				return false;
		}
		return true;
	}
	return false;
}

//
// Traduction des textes de SPIP
//
function _T($texte, $args = '') {
	include_ecrire('inc_lang.php3');
	$text = traduire_chaine($texte, $args);
	if (!empty($GLOBALS['xhtml'])) {
		include_ecrire("inc_charsets.php3");
		$text = html2unicode($text);
	}

	return $text;
}

// chaines en cours de traduction
function _L($text) {
	if ($GLOBALS['test_i18n'])
		return "<span style='color:red;'>$text</span>";
	else
		return $text;
}

// Langue principale du site
$langue_site = lire_meta('langue_site');
if (!$langue_site) include_ecrire('inc_lang.php3');
$spip_lang = $langue_site;


// Nommage bizarre des tables d'objets
function table_objet($type) {
	if ($type == 'syndic' OR $type == 'forum')
		return $type;
	else
		return $type.'s';
}


//
// spip_timer : on l'appelle deux fois et on a la difference, affichable
//
function spip_timer($t='rien') {
	static $time;
	$a=time(); $b=microtime();

	if (isset($time[$t])) {
		$p = $a + $b - $time[$t];
		unset($time[$t]);
		return sprintf("%.2fs", $p);
	} else
		$time[$t] = $a + $b;
}


// spip_touch : verifie si un fichier existe et n'est pas vieux (duree en s)
// et le cas echeant le touch() ; renvoie true si la condition est verifiee
// et fait touch() sauf si ca n'est pas souhaite
// (regle aussi le probleme des droits sur les fichiers touch())
function spip_touch($fichier, $duree=0, $touch=true) {
	if (!($exists = @file_exists($fichier))
	|| ($duree == 0)
	|| (@filemtime($fichier) < time() - $duree)) {
		if ($touch) {
			if (!@touch($fichier)) { @unlink($fichier); @touch($fichier); };
			if (!$exists) @chmod($fichier, 0666);
		}
		return true;
	}
	return false;
}

//
// cron() : execution des taches de fond
// quand il est appele par spip_background.php3, il est gourmand ;
// quand il est appele par inc-public il n'est pas gourmand
//
function cron($gourmand = false) {
	$touch = _DIR_SESSIONS.'.background';
	$touch_gourmand = $touch.'-gourmand';

	// Si on est gourmand, ou si le fichier gourmand n'existe pas
	// (ou est trop vieux), on va voir si un cron est necessaire.
	// Au passage si on est gourmand on le dit aux autres
	if (spip_touch($touch_gourmand, 60, $gourmand)
	OR $gourmand) {

		// Faut-il travailler ? Pas tous en meme temps svp
		// Au passage si on travaille on bloque les autres
		if (spip_touch($touch, 1)) {
			include_ecrire('inc_cron.php3');
			spip_cron();
		}
	}
}


//
// qq  fonctions service pour les 2 niveaux
//
function calculer_hierarchie($id_rubrique, $exclure_feuille = false) {
	if (!$id_rubrique)
		return '0';
	if (!$exclure_feuille)
		$hierarchie = ",$id_rubrique";

	do {
		$q = spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
		list($id_rubrique) = spip_fetch_array($q);
		$hierarchie = ",$id_rubrique".$hierarchie;
	} while ($id_rubrique);

	return substr($hierarchie, 1); // Attention ca demarre toujours par '0'
}


//
// Retourne $subdir/ si le sous-repertoire peut etre cree, '' sinon
//

function creer_repertoire($base, $subdir) {
	if (@file_exists("$base/.plat")) return '';
	$path = $base.'/'.$subdir;
	if (@file_exists($path)) return "$subdir/";

	@mkdir($path, 0777);
	@chmod($path, 0777);
	$ok = false;
	if ($f = @fopen("$path/.test", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		include("$path/.test");
	}
	if (!$ok) {
		$f = @fopen("$base/.plat", "w");
		if ($f)
			fclose($f);
		else {
			redirige_par_entete("spip_test_dirs.php3");
		}
	}
	return ($ok? "$subdir/" : '');
}


//
// Entetes
//
function redirige_par_entete($url) {
	header("Location: $url");
#	include_ecrire('inc_cron.php3');
#	spip_cron();
	spip_log("redirige $url");
	exit;
}

function debut_entete($title, $entete='') {
	global $flag_preserver;

	if (!$charset = lire_meta('charset'))
		$charset = 'utf-8';
	if (!$entete)
		$entete = "Content-Type: text/html; charset=$charset";

	if (!$flag_preserver) @header($entete);
	return "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n" .
	  "<html lang='".$GLOBALS['spip_lang']."' dir='".($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr')."'>\n" .
	  "<head>\n" .
#	  "<base href='$base' />\n" .
	  "<title>$title</title>\n" .
	  "<meta http-equiv='Content-Type' content='text/html; charset=$charset' />\n";
}


// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
function texte_script($texte) {
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}

//
// find_in_path() : chercher un fichier nomme x selon le chemin rep1:rep2:rep3
//
define_once('_SPIP_PATH', './:squelettes/:dist/:formulaires/');
function find_in_path ($filename, $path='AUTO') {
	// Chemin standard depuis l'espace public

	if ($path == 'AUTO') {
		$path = _SPIP_PATH;
		if ($GLOBALS['dossier_squelettes'])
			$path = $GLOBALS['dossier_squelettes'].'/:'.$path;
	}

	// Depuis l'espace prive, remonter d'un cran 
	$racine = (_DIR_RESTREINT ? '' : '../');

	// Parcourir le chemin
	foreach (split(':', $path) as $dir) {
		if (substr($dir, 0,1)<>'/')
			$f = "$racine$dir$filename";
		else
			$f = "$dir$filename";

		if (@is_readable($f)) {
			return $f;
		}
	}
#	spip_log("find_in_path: pas de fichier '$filename' sur le chemin '$path'");
}


//
// Que faire si Spip n'est pas installe... sauf si justement on l'installe!
//
if (!(_FILE_CONNECT
OR defined('_ECRIRE_INSTALL')
OR defined('_TEST_DIRS')
OR defined('_ECRIRE_AIDE'))) {
	// Soit on est dans ecrire/ et on envoie sur l'installation
	if (@file_exists("inc_version.php3")) {
		header("Location: " . _DIR_RESTREINT . "install.php3");
		exit;
	}
	// Soit on est dans le site public
	else if (defined("_INC_PUBLIC")) {
		# on ne peut pas deviner ces repertoires avant l'installation !
		define('_DIR_INCLUDE', _DIR_RESTREINT);
		define('_DIR_IMG_PACK', (_DIR_RESTREINT . 'img_pack/'));
		define('_DIR_LANG', (_DIR_RESTREINT . 'lang/'));
		$db_ok = false;
		include_ecrire ("inc_presentation.php3");
		install_debut_html(_T('info_travaux_titre'));
		echo "<p>"._T('info_travaux_texte')."</p>";
		install_fin_html();
		exit;
	}
	// Soit on est appele de l'exterieur (spikini, etc)
}

?>
