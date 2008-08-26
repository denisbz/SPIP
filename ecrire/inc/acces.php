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

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@creer_pass_aleatoire
function creer_pass_aleatoire($longueur = 8, $sel = "") {
	$seed = (double) (microtime() + 1) * time();
	mt_srand($seed);
	srand($seed);
	$s = '';
	$pass = '';
	for ($i = 0; $i < $longueur; $i++) {
		if (!$s) {
			$s = mt_rand();
			if (!$s) $s = rand();
			$s = substr(md5(uniqid($s).$sel), 0, 16);
		}
		$r = unpack("Cr", pack("H2", $s.$s));
		$x = $r['r'] & 63;
		if ($x < 10) $x = chr($x + 48);
		else if ($x < 36) $x = chr($x + 55);
		else if ($x < 62) $x = chr($x + 61);
		else if ($x == 63) $x = '/';
		else $x = '.';
		$pass .= $x;
		$s = substr($s, 2);
	}
	$pass = preg_replace("@[./]@", "a", $pass);
	$pass = preg_replace("@[I1l]@", "L", $pass);
	$pass = preg_replace("@[0O]@", "o", $pass);
	return $pass;
}

//
// Creer un identifiant aleatoire
//

// http://doc.spip.org/@creer_uniqid
function creer_uniqid() {
	static $seeded;

	if (!$seeded) {
		$seed = (double) (microtime() + 1) * time();
		mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	$s = mt_rand();
	if (!$s) $s = rand();
	return uniqid($s, 1);
}

//
// Renouvellement de l'alea utilise pour securiser les scripts dans action/
//

// http://doc.spip.org/@renouvelle_alea
function renouvelle_alea() {
	$alea = md5(creer_uniqid());
	ecrire_meta('alea_ephemere_ancien', @$GLOBALS['meta']['alea_ephemere'], 'non');
	ecrire_meta('alea_ephemere', $alea, 'non');
	ecrire_meta('alea_ephemere_date', time(), 'non');
	spip_log("renouvellement de l'alea_ephemere: $alea");
}

//
// low-security : un ensemble de fonctions pour gerer de l'identification
// faible via les URLs (suivi RSS, iCal...)
//
// http://doc.spip.org/@low_sec
function low_sec($id_auteur) {
	// Pas d'id_auteur : low_sec
	if (!$id_auteur = intval($id_auteur)) {
		if (!$low_sec = $GLOBALS['meta']['low_sec']) {
			ecrire_meta('low_sec', $low_sec = creer_pass_aleatoire());
		}
	}
	else {
		$low_sec = sql_getfetsel("low_sec", "spip_auteurs", "id_auteur = $id_auteur");
		if (!$low_sec) {
			$low_sec = creer_pass_aleatoire();
			sql_updateq("spip_auteurs", array("low_sec" => $low_sec), "id_auteur = $id_auteur");
		}
	}
	return $low_sec;
}

// Inclure les arguments significatifs pour le hachage
// cas particulier du statut pour compatibilite ancien suivi_revisions
// http://doc.spip.org/@generer_url_low_sec

// http://doc.spip.org/@generer_url_low_sec
function generer_url_low_sec($action, $args, $id_auteur=0, $lang='')
{
	$a = '';
	$b = "op=$action&id=$id_auteur" . (!$lang ? '' : "&lang=$lang");
	if (is_array($args)) {
		foreach ($args as $val => $var)
			if ($var) {
				if ($val<>'statut') $a .= ':' . $val.'-'.$var;
				$b .= '&' . $val.'='.$var;
			}
		$a = substr($a,1);
	}
	$cle = afficher_low_sec($id_auteur, $action, $a);
	return generer_url_public("rss", "$b&cle=$cle&args=$a");
}

// http://doc.spip.org/@afficher_low_sec
function afficher_low_sec ($id_auteur, $action='', $args='') {
	return substr(md5($action.$args.low_sec($id_auteur)),0,8);
}

// http://doc.spip.org/@verifier_low_sec
function verifier_low_sec ($id_auteur, $cle, $action='', $args='') {
	return ($cle == afficher_low_sec($id_auteur, $action, $args));
}

// http://doc.spip.org/@effacer_low_sec
function effacer_low_sec($id_auteur) {
	if (!$id_auteur = intval($id_auteur)) return; // jamais trop prudent ;)
	sql_updateq("spip_auteurs", array("low_sec" => ''), "id_auteur = $id_auteur");
}

// http://doc.spip.org/@initialiser_sel
function initialiser_sel() {
	global $htsalt;
	if (CRYPT_MD5) $htsalt = '$1$'.creer_pass_aleatoire();
	else return "";
}

// Cette fonction ne sert qu'a la connexion en mode http_auth.non LDAP
// Son role est de creer le fichier htpasswd
// Voir le plugin "acces restreint"
// http://doc.spip.org/@ecrire_acces
function ecrire_acces() {
	$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint ;
	// si .htaccess existe, outrepasser spip_meta
	if (($GLOBALS['meta']['creer_htpasswd'] != 'oui')
	AND !@file_exists($htaccess)) {
		spip_unlink($htpasswd);
		spip_unlink($htpasswd."-admin");
		return;
	}

	# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
	# de devenir redacteur le cas echeant (auth http)... a nettoyer
	// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)

	if (spip_connect_ldap()) return;
	$p1 = ''; // login:htpass pour tous
	$p2 = ''; // login:htpass pour les admins
	$s = sql_select("login, htpass, statut", "spip_auteurs", sql_in("statut",  array('1comite','0minirezo','nouveau')));
	while ($t = sql_fetch($s)) {
		if (strlen($t['login']) AND strlen($t['htpass'])) {
			$p1 .= $t['login'].':'.$t['htpass']."\n";
			if ($t['statut'] == '0minirezo')
				$p2 .= $t['login'].':'.$t['htpass']."\n";
		}
	}
	if ($p1) {
	  ecrire_fichier($htpasswd, $p1);
	  ecrire_fichier($htpasswd.'-admin', $p2);
	  spip_log("Ecriture de $htpasswd et $htpasswd-admin");
	}
}


// http://doc.spip.org/@generer_htpass
function generer_htpass($pass) {
	global $htsalt;
	if (function_exists('crypt'))
		return crypt($pass, $htsalt);
}

//
// Verifier la presence des .htaccess
//
// http://doc.spip.org/@verifier_htaccess
function verifier_htaccess($rep) {
	$htaccess = "$rep/" . _ACCESS_FILE_NAME;
	if ((!@file_exists($htaccess)) AND 
	    !defined('_ECRIRE_INSTALL') AND !defined('_TEST_DIRS')) {
		spip_log("demande de creation de $htaccess");
		if ($_SERVER['SERVER_ADMIN'] != 'www@nexenservices.com'){
			if (!$f = @fopen($htaccess, "w")) {
				spip_log("ECHEC DE LA CREATION DE $htaccess"); # ne pas traduire
			} else {
				fputs($f, "deny from all\n");
				fclose($f);
			}
		} else {
			echo "<span style='color: #FF0000'>IMPORTANT : </span>";
			echo "Votre h&eacute;bergeur est Nexen Services.<br />";
			echo "La protection du r&eacute;pertoire <i>$rep/</i> doit se faire
			par l'interm&eacute;diaire de ";
			echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\"
			target=\"_blank\">l'espace webmestres</a>.";
			echo "Veuillez cr&eacute;er manuellement la protection pour
			ce r&eacute;pertoire (un couple login/mot de passe est
			n&eacute;cessaire).<br />";
		}
	}
}

// http://doc.spip.org/@gerer_htaccess
function gerer_htaccess() {
	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint
	$GLOBALS['meta']['creer_htaccess'];
	$r = sql_select('extension', 'spip_types_documents');
	while ($e = sql_fetch($r)) {
		if (is_dir($dir = _DIR_IMG . $e['extension'])) {
			if ($GLOBALS['meta']['creer_htaccess'] == 'oui')
				verifier_htaccess($dir);
			else spip_unlink("$dir/" . _ACCESS_FILE_NAME);
		}
	}
	return $GLOBALS['meta']['creer_htaccess'];
}

initialiser_sel();

?>
