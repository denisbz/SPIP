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

include_spip('inc/meta');

// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='')
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action($action, $arg, $redirect, $mode, $att);
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$r = generer_url_ecrire($ret, $gra, true, _DIR_RESTREINT_ABS);

	return generer_action_auteur($action, $arg, $r, $mode, $atts);
}

// Retourne un formulaire d'execution de $action sur $id,
// revenant a l'envoyeur $script d'arguments $args.
// Utilise Ajax si dispo, en ecrivant le resultat dans le innerHTML du noeud
// d'attribut  id = $action-$id (cf. AjaxSqueeze dans layer.js)
// Precise le charset de l'envoyeur avec la variable d'url var_ajaxcharset
// qui sert aussi a index.php de savoir que la requete est en Ajax.
// Attention, la redirection doit propager cette variable, 
// i.e. la mettre dans la 2e URL, et avant l'ancre de celle ci.

// http://doc.spip.org/@ajax_action_auteur
function ajax_action_auteur($action, $id, $script, $args='', $corps=false, $args_ajax='', $fct_ajax='')
{
	if (strpos($args,"#")===FALSE)
		$ancre = "$action-" . intval($id);
	else {
		$ancre = explode("#",$args);
		$args = $ancre[0];
		$ancre = $ancre[1];
	}

	// Formulaire (POST)
	// methodes traditionnelle et ajax a unifier...
	if (is_string($corps)) {

		// Methode traditionnelle
		if (_SPIP_AJAX !== 1) {
			return redirige_action_auteur($action,
				$id,
				$script,
				"$args#$ancre",
				$corps,
				"\nmethod='post'");
		}

		// Methode Ajax
		else {
			if ($args AND !$args_ajax) $args_ajax = "&$args";
			return redirige_action_auteur($action,
				$id,
				$action,
				"var_ajaxcharset=utf-8&script=$script$args_ajax",
				$corps,
				(" method='post'\nonsubmit="
				 . ajax_action_declencheur('this', $ancre, $fct_ajax)));
				 
		}
	}

	// Lien (GET)
	else {
		$href = redirige_action_auteur($action,
			$id,
			$script,
			"$args#$ancre",
			false);

		if ($args AND !$args_ajax) $args_ajax = "&$args";

		$ajax = redirige_action_auteur($action,
			$id,
			$action,
			"var_ajaxcharset=utf-8&script=$script$args_ajax");

		$cli = array_shift($corps);
		return "<a href='$href'\nonclick="
		.  ajax_action_declencheur($ajax, $ancre, $fct_ajax)
		. ">"
		. (!$corps ?  $cli : ("\n<span" . $corps[0] . ">$cli</span>"))
		. "</a>";
	}
}

//
// Attention pour que Safari puisse manipuler cet evenement
// il faut onsubmit="return AjaxSqueeze(x,'truc',...)"
// et non pas onsubmit='return AjaxSqueeze(x,"truc",...)'
//
// http://doc.spip.org/@ajax_action_declencheur
function ajax_action_declencheur($request, $noeud, $fct_ajax='') {
	if ($request != 'this')
		$request = "'".$request."'";

	return '"return AjaxSqueeze('
	. $request
	. ",'"
	. $noeud
	. "'"
	. (!$fct_ajax ? '' : ",$fct_ajax")
	. ')"';
}

// http://doc.spip.org/@ajax_action_greffe
function ajax_action_greffe($idom, $corps)
{
	return _request('var_ajaxcharset')
	? $corps
	: "\n<div id='$idom'>$corps\n</div>\n";
}

// http://doc.spip.org/@ajax_retour
function ajax_retour($corps)
{
	$c = $GLOBALS['meta']["charset"];
	header('Content-Type: text/html; charset='. $c);
	$c = '<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n";
	if (isset($GLOBALS['ajax_debug']))
		ajax_debug_retour($corps, $c);
	echo $c, $corps;
	exit;
}

// http://doc.spip.org/@ajax_debug_retour
function ajax_debug_retour($corps, $c)
{
	$sax = charger_fonction('sax', 'inc');
	$corps = $sax($corps);
	if ($GLOBALS['xhtml_error']) {
	  spip_log("ajax_retour " .  $GLOBALS['REQUEST_URI'] . $GLOBALS['xhtml_error']);
	  $debut = "<script type='text/javascript'>console.log('";
	  $fin = "')</script>\n";
	  echo $GLOBALS['xhtml_error']
	  . $debut
	  . join("$fin$debut", split("\n", addslashes($corps)))
	  . $fin;
	  exit;
	}
}

// http://doc.spip.org/@determine_upload
function determine_upload()
{
	global $connect_toutes_rubriques, $connect_login, $connect_statut ;

	if (!$connect_statut) {
		$auth = charger_fonction('auth', 'inc');
		$auth = $auth();
	}
	if ($connect_statut != '0minirezo') return false;
	$repertoire = _DIR_TRANSFERT;
	if(!@file_exists($repertoire)) {
		$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}
	if($connect_toutes_rubriques) return $repertoire;

	$sous_rep = $repertoire . $connect_login ;
	if(!@file_exists($sous_rep)) {
		$sous_rep = sous_repertoire($repertoire, $connect_login);
	}

	return $sous_rep . '/';
}

//
// retourne le statut d'un utilisateur authentifie en php_auth, false sinon
//
// http://doc.spip.org/@verifier_php_auth
function verifier_php_auth() {
	if (@$_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !@$GLOBALS['ignore_auth_http']) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE login=" . _q($_SERVER['PHP_AUTH_USER']));

		$row = @spip_fetch_array($result);
		if ($row AND $row['source'] != 'ldap') {
		  if ($row['pass'] == md5($row['alea_actuel'] . $_SERVER['PHP_AUTH_PW'])) {
			$GLOBALS['auteur_session'] = $row;
			return $row['statut'];
		  } else return false;
		} else {
		  if (!$row AND !$GLOBALS['ldap_present'])
		    return false;
		  else {
			$auth_ldap = charger_fonction('auth_ldap', 'inc', true);
			if ($auth_ldap) {
			  $GLOBALS['auteur_session'] =  $auth_ldap($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			  return $GLOBALS['auteur_session']['statut'];
			}
		  }
		}
	} 
	return false;
}

//
// entete php_auth (est-encore utilise ?)
//
// http://doc.spip.org/@ask_php_auth
function ask_php_auth($pb, $raison, $retour, $url='', $re='', $lien='') {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo "<b>$pb</b><p>$raison</p>[<a href='./'>$retour</a>] ";
	if ($url) {
		echo "[<a href='", generer_url_public('spip_cookie',"essai_auth_http=oui&$url"), "'>$re</a>]";
	}
	
	if ($lien)
		echo " [<a href='" . _DIR_RESTREINT_ABS . "'>"._T('login_espace_prive')."</a>]";
	exit;
}


?>
