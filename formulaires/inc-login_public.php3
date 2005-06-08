<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include(_FILE_CONNECT);
include_ecrire("inc_meta.php3");
include_ecrire("inc_session.php3");
include_ecrire("inc_filtres.php3");

global $balise_LOGIN_PUBLIC_collecte;
$balise_LOGIN_PUBLIC_collecte = array('url');

# retourner:
# 1. l'url collectee ci-dessus (args0) ou donnee en filtre (filtre0)
# 2. l'eventuel parametre de la balise (args1) fournie par
#    calculer_balise_dynamique, en l'occurence le #LOGIN courant si l'on
#    programme une <boucle(AUTEURS)>[(#LOGIN_PUBLIC{#LOGIN})]

function balise_LOGIN_PUBLIC_stat ($args, $filtres) {
	return array($filtres[0] ? $filtres[0] : $args[0], $args[1], $args[2]);
}

function balise_LOGIN_PUBLIC_dyn($url, $login) {

	if (!$url		# pas d'url passee en filtr eou dans le contexte
	AND !$url = _request('url') # ni d'url passee par l'utilisateur
	) {
		$link = new Link();
		$link->delVar('var_erreur');
		$link->delVar('var_login');
		$url = $link->getUrl();
	}
	return login_explicite($login, $url);
}

function login_explicite($login, $cible) {
	global $auteur_session;

	$link = new Link();
	$link->delVar('var_erreur');
	$link->delVar('var_login');
	$action = $link->getUrl();

	if ($cible) {
	  $cible = ereg_replace("[?&]var_erreur=[^&]*", '', $cible);
	  $cible = ereg_replace("[?&]var_login=[^&]*", '', $cible);
	} else {
	  if (ereg("[?&]url=([^&]*)", $action, $m))
	    $cible = urldecode($m[1]);
	  else
	    $cible = _DIR_RESTREINT ;
	}
	      
	include_ecrire("inc_session.php3");
	verifier_visiteur();

	if ($auteur_session AND 
	($auteur_session['statut']=='0minirezo' OR $auteur_session['statut']=='1comite')) {
		if (($cible != $action) && !headers_sent()
		AND !$_GET['var_mode'])
			redirige_par_entete($cible);
		return http_href($cible, _T('login_par_ici'));
	}
	return login_pour_tous($login ? $login : _request('var_login'), $cible, $action);
}

function login_pour_tous($login, $cible, $action) {
	global $ignore_auth_http, $php_module, $_SERVER, $_COOKIE;

	// en cas d'echec de cookie, inc_auth a renvoye vers spip_cookie qui
	// a tente de poser un cookie ; s'il n'est pas la, c'est echec cookie
	// s'il est la, c'est probablement un bookmark sur bonjour=oui,
	// et pas un echec cookie.
	if (_request('var_echec_cookie'))
		$echec_cookie = ($_COOKIE['spip_session'] != 'test_echec_cookie');
	$auth_http = ($echec_cookie AND $php_module AND !$ignore_auth_http) ?
		'spip_cookie.php3' : '';
	// Attention dans le cas 'intranet' la proposition de se loger
	// par auth_http peut conduire a l'echec.
	if ($_SERVER['PHP_AUTH_USER'] AND $_SERVER['PHP_AUTH_PW'])
		$auth_http = '';

	// Le login est memorise dans le cookie d'admin eventuel
	if (!$login) {
		if (ereg("^@(.*)$", $_COOKIE['spip_admin'], $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	$erreur = '';
	if ($login) {
		$s = spip_query("SELECT * FROM spip_auteurs
			WHERE login='" .addslashes($login) ."'");
		$row =  spip_fetch_array($s);

		// Retrouver ceux qui signent de leur nom ou email
		if (!$row AND !$GLOBALS['ldap_present']) {
			if ($t = spip_fetch_array(
			spip_query(
				"SELECT * FROM spip_auteurs
				WHERE (nom LIKE '" .addslashes($login) ."'
				OR email LIKE '" .addslashes($login) ."')
				AND login<>'' AND statut<>'5poubelle'"
			))) {
				$row = $t;
				$login_alt = $login; # afficher ce qu'on a tape
				$login = $t['login'];
			}
		}

		if ((!$row AND !$GLOBALS['ldap_present']) OR
			($row['statut'] == '5poubelle') OR 
			(($row['source'] == 'spip') AND $row['pass'] == '')) {
			$erreur =  _T('login_identifiant_inconnu',
				array('login' => htmlspecialchars($login)));
			$row = array();
			$login = '';
			@spip_setcookie("spip_admin", "", time() - 3600);
		} else {
			// on laisse le menu decider de la langue
			unset($row['lang']);
		}
	}
	if (!$row)
		$row = array();

	// afficher "erreur de mot de passe" si &var_erreur=pass
	if (_request('var_erreur') == 'pass')
		$erreur = _T('login_erreur_pass');

	return array('formulaire_login', 0, 
		array_merge(
				array_map('texte_script', $row),
				array(
					'action2' => ($login ? 'spip_cookie.php3' : $action),
					'erreur' => $erreur,
					'action' => $action,
					'url' => $cible,
					'auth_http' => $auth_http,
					'echec_cookie' => ($echec_cookie ? ' ' : ''),
					'login' => $login,
					'login_alt' => ($login_alt ? $login_alt : $login)
					)
				)
			);

}

// Bouton duree de connexion

function filtre_rester_connecte($prefs) {
	$prefs = unserialize(stripslashes($prefs));
	return $prefs['cnx'] == 'perma' ? ' ' : '';
}

// made in cherche_image_nommee. A partager.

function silogoauteur($id_auteur, $formats = array ('gif', 'jpg', 'png')) {
	reset($formats);
	while (list(, $format) = each($formats)) {
		$d = _DIR_IMG . "auton$id_auteur.$format";
		if (@file_exists($d)) return $d;
	}
	return  '';
}

?>
