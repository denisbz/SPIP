<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/abstract_sql');
//
// Fonctions de gestion de l'acces restreint aux rubriques
//

// http://doc.spip.org/@acces_restreint_rubrique
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;

	return (isset($connect_id_rubrique[$id_rubrique]));
}

// http://doc.spip.org/@auteurs_article
function auteurs_article($id_article, $cond='')
{
	return sql_select("id_auteur", "spip_auteurs_articles", "id_article=$id_article". ($cond ? " AND $cond" : ''));
}

// http://doc.spip.org/@auteurs_autorises
function auteurs_autorises($cond='', $order='')
{
	return sql_select('*', 'spip_auteurs', "statut IN ('0minirezo','1comite')" . ($cond ? " AND $cond" : ''),'', $order);
}

// Un nouvel inscrit prend son statut definitif a la 1ere connexion.
// Le statut a ete memorise dans bio (cf formulaire_inscription).
// On le verifie, car la config a peut-etre change depuis,
// et pour compatibilite avec les anciennes versions n'utilisait pas "bio".

// http://doc.spip.org/@acces_statut
function acces_statut($id_auteur, $statut, $bio)
{
	if ($statut != 'nouveau') return $statut;
	include_spip('inc/filtres');
	if (!($s = tester_config('', $bio))) return $statut;
	spip_query("UPDATE spip_auteurs SET bio='', statut=" . _q($s) . " WHERE id_auteur=$id_auteur");
	return $s;
}

// Fonction d'authentification
// retourne -1 si authentification impossible a cause du serveur SQL 
// retourne une chaine vide si authentification reussie
// retourne une chaine non vide expliquant l'echec sinon:
//	"rien" ==> nouvel arrivant, envoyer le formulaire
//	autre  ==> statut incompatible

// http://doc.spip.org/@inc_auth_dist
function inc_auth_dist() {
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;
	global $connect_id_auteur, $connect_login ;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;
	//
	// Initialiser variables (eviter hacks par URL)
	//

	$connect_login = '';
	$connect_id_auteur = NULL;
	$auth_can_disconnect = false;

	//
	// Recuperer les donnees d'identification
	//

	// Session valide en cours ?
	if (isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($connect_id_auteur = $session()) {
			$auth_can_disconnect = true;
		} else unset($_COOKIE['spip_session']);
	}

	// Essayer auth http si significatif
	// (ignorer les login d'intranet independants de spip)
	if (!$ignore_auth_http) {
		if (isset($_SERVER['PHP_AUTH_USER'])
		AND isset($_SERVER['PHP_AUTH_PW'])) {
			include_spip('inc/actions');
			if ($r = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				if (!$connect_id_auteur) {
					$_SERVER['PHP_AUTH_PW'] = '';
					$auth_can_disconnect = true;
					$GLOBALS['auteur_session'] = $r;
					$connect_login = $GLOBALS['auteur_session']['login'];
				} else {
				  // cas de la session en plus de PHP_AUTH
				  /*				  if ($connect_id_auteur != $r['id_auteur']){
				    spip_log("vol de session $connect_id_auteur" . join(', ', $r));
					unset($_COOKIE['spip_session']);
					$connect_id_auteur = '';
					} */
				}
			}
		} else { if (isset($_SERVER['REMOTE_USER']))

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW

				$connect_login = $_SERVER['REMOTE_USER'];
		}
	}

	$where = (is_numeric($connect_id_auteur)) ?
	  "id_auteur=$connect_id_auteur" :
	  (!$connect_login ? '' : "login=" . _q($connect_login));

	// pas authentifie par cookie ni http_auth:


	if (!$where) return "inconnu";

	// Trouver les autres infos dans la table auteurs.
	// le champ 'quand' est utilise par l'agenda
	$result = sql_select("*, en_ligne AS quand", "spip_auteurs", "$where AND statut!='5poubelle'");
	if (!$row = sql_fetch($result)) {
		// il n'est PLUS connu. c'est SQL qui est desyncrho
		auth_areconnecter($connect_login);
		return -1;
	}

	// Le visiteur est connu

	// Des globales pour tout l'espace prive
	$connect_id_auteur = $row['id_auteur'];
	$connect_login = $row['login'];
	$connect_statut = acces_statut($connect_id_auteur, $row['statut'], $row['bio']);

	// Le tableau global auteur_session contient toutes les infos pertinentes
	// et a jour (tandis que $auteur_session peut avoir des valeurs un peu datees
	// s'il est pris dans le fichier de session)
	// Les plus utiles sont aussi dans les variables simples ci-dessus
	$GLOBALS['auteur_session'] = array_merge($GLOBALS['auteur_session'], $row);
	$r = @unserialize($row['prefs']);
	$GLOBALS['auteur_session']['prefs'] =
	  (@isset($r['couleur'])) ? $r : array('couleur' =>1, 'display'=>0);
	// au cas ou : ne pas memoriser les champs sensibles
	unset($GLOBALS['auteur_session']['pass']);
	unset($GLOBALS['auteur_session']['htpass']);
	unset($GLOBALS['auteur_session']['alea_actuel']);
	unset($GLOBALS['auteur_session']['alea_futur']);

	// rajouter les sessions meme en mode auth_http
	// pour permettre les connexions multiples et identifier les visiteurs
	if (!isset($_COOKIE['spip_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($spip_session = $session($row)) {
			include_spip('inc/cookie');
			spip_setcookie(
				'spip_session',
				$_COOKIE['spip_session'] = $spip_session,
				time() + 3600 * 24 * 14
			);
		}
	}

	// Etablir les droits selon le codage attendu
	// dans ecrire/index.php ecrire/prive.php

	// Pas autorise a acceder a ecrire ? on renvoie le statut
	// A noter : le premier appel a autoriser() a le bon gout
	// d'initialiser $GLOBALS['auteur_session']['restreint'],
	// qui ne figure pas dans le fichier de session
	include_spip('inc/autoriser');

	if (!autoriser('ecrire'))
		return $connect_statut;

	// autoriser('ecrire') ne laisse passer que les Admin et les Redac

	// Administrateurs
	if ($connect_statut == '0minirezo') {
		if (is_array($GLOBALS['auteur_session']['restreint']))
			$connect_id_rubrique = $GLOBALS['auteur_session']['restreint'];
		$connect_toutes_rubriques = !$connect_id_rubrique;
	} 
	// Pour les redacteurs, inc_version a fait l'initialisation minimale

	// Indiquer la connexion. A la minute pres ca suffit.
	if (!is_numeric($connect_quand = $row['quand']))
		$connect_quand = strtotime($connect_quand);

	if ((time() - $connect_quand)  >= 60) {
		spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur=$connect_id_auteur");
	}

	return ''; // i.e. pas de pb.
}

// Cas ou l'auteur a ete identifie mais on n'a pas d'info sur lui
// C'est soit parce que le serveur MySQL ne repond pas,
// soit parce que la table des auteurs a changee (restauration etc)
// Pas la peine d'insister.  Envoyer un message clair au client.

// http://doc.spip.org/@auth_areconnecter
function auth_areconnecter($auth_login)
{
	include_spip('inc/minipres');
	if (!spip_connect()) {
		spip_log("Erreur base de donnees");

		echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".sql_errno()." ".sql_error()."</tt></p>");
	} else {
		echo minipres(_T('avis_erreur_connexion'), "<br /><br /><p>" . _T('texte_inc_auth_1', array('auth_login' => $auth_login)). " <a href='".  generer_url_action('logout', "logout=prive"). "'>". _T('texte_inc_auth_2'). "</a>"._T('texte_inc_auth_3'));
	}
	exit();
}
?>
