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


// http://doc.spip.org/@inc_legender_auteur
function inc_legender_auteur_dist($id_auteur, $auteur, $mode, $echec='', $redirect='')
{
	$corps = (($mode < 0) OR !statut_modifiable_auteur($id_auteur, $auteur))
	? legender_auteur_voir($auteur, $redirect)
	: legender_auteur_saisir($id_auteur, $auteur, $mode, $echec, $redirect);
	
	return  $redirect ? $corps :
	  ajax_action_greffe("legender_auteur-$id_auteur", $corps);

}

function legender_auteur_saisir($id_auteur, $auteur, $mode, $echec='', $redirect='')
{
	global $options, $connect_statut, $connect_id_auteur, $connect_toutes_rubriques;
	$corps = '';

	if ($echec){

		foreach (split('@@@',$echec) as $e)
			$corps .= '<p>' . _T($e) . "</p>\n";
		
		$corps = debut_cadre_relief('', true)
		.  http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='left'")
		.  "<div style='color: red; left-margin: 5px'>"
		. $corps
		. "<p>"
		.  _T('info_recommencer')
		.  "</p></div>\n"
		. fin_cadre_relief(true)
		.  "\n<p>";
	}

	$setmail = ($connect_statut == "0minirezo"
		AND ($connect_toutes_rubriques OR $auteur['statut']<>'0minirezo'));

	$setconnecte = ($connect_id_auteur == $id_auteur);

	$corps .= _T('titre_cadre_signature_obligatoire')
	. "("
	. _T('entree_nom_pseudo')
	. ")<br />\n"
	. "<input type='text' name='nom' class='formo' size='40' value=\""
	. entites_html($auteur['nom'])
	. "\" "
	. (!$mode ? '' : ' onfocus="if(!antifocus){this.value=\'\';antifocus=true;}"')
	. " />\n<p>"
	. "<b>"._T('entree_adresse_email')."</b>";

	if ($setmail) {
		$corps .= "<br /><input type='text' name='email' class='formo' size='40' value=\""
		. entites_html($auteur['email'])
		. "\"  />\n<p>\n";
	} else {
		$corps .= "&nbsp;: <tt>".$auteur['email']."</tt>"
		. "<br />("._T('info_reserve_admin').")\n"
		. "\n<p>";
	}

	$corps .= "<b>"._T('entree_infos_perso')."</b><br />\n"
	. "("._T('entree_biographie')
	. ")<br />\n"
	. "<textarea name='bio' class='forml' rows='4' cols='40'>"
	. entites_html($auteur['bio'])
	. "</textarea><br />\n"
	. debut_cadre_enfonce("site-24.gif", true, "", _T('info_site_web'))
	. "<b>"._T('entree_nom_site')."</b><br />\n"
	. "<input type='text' name='nom_site_auteur' class='forml' value=\""
	. entites_html($auteur['nom_site'])
	. "\" size='40' /><p>\n"
	. "<b>"
	. _T('entree_url')
	. "</b><br />\n"
	. "<input type='text' name='url_site' class='forml' value=\""
	. entites_html($auteur['url_site'])
	. "\" size='40' />\n"
	. fin_cadre_enfonce(true)
	. "\n<p>";

	if ($options == "avancees") {
		$corps .= debut_cadre_enfonce("cadenas-24.gif", true, "", _T('entree_cle_pgp'))
		. "<textarea name='pgp' class='forml' rows='4' cols='40' wrap=soft>"
		. entites_html($auteur['pgp'])
		. "</textarea>\n"
		. fin_cadre_enfonce(true)
		. "\n<p>";
	} else {
		$corps .= "<input type='hidden' name='pgp' value=\""
		. entites_html($auteur['pgp'])
		. "\" />";
	}

	$corps .= "\n<p>";

	if ($champs_extra) {
		include_spip('inc/extra');
		$corps .= extra_saisie($auteur['extra'], 'auteurs', $auteur['statut'],'', false);
	}

//
// Login et mot de passe :
// accessibles seulement aux admins non restreints et l'auteur lui-meme
//

if ($auteur['source'] != 'spip') {
	$edit_login = false;
	$edit_pass = false;
}
else if (($connect_statut == "0minirezo") AND $connect_toutes_rubriques) {
	$edit_login = true;
	$edit_pass = true;
}
else if ($connect_id_auteur == $id_auteur) {
	$edit_login = false;
	$edit_pass = true;
}
else {
	$edit_login = false;
	$edit_pass = false;
}

	$corps .= debut_cadre_relief("base-24.gif", true);

// Avertissement en cas de modifs de ses propres donnees
	if (($edit_login OR $edit_pass) AND $connect_id_auteur == $id_auteur) {
		$corps .= debut_cadre_enfonce('', true)
		.  http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='right'")
		. "<b>"._T('texte_login_precaution')."</b>\n"
		. fin_cadre_enfonce(true)
		. "\n<p>";
	}

// Un redacteur n'a pas le droit de modifier son login !
	if ($edit_login) {
		$corps .= "<b>"._T('item_login')."</b> "
		. "<font color='red'>("._T('texte_plus_trois_car').")</font> :<br />\n"
		. "<input type='text' name='new_login' class='formo' value=\"".entites_html($auteur['login'])."\" size='40' /><p>\n";
	} else {
		$corps .= "<fieldset style='padding:5'><legend><B>"._T('item_login')."</B><br />\n</legend><br><b>".$auteur['login']."</b> "
		. "<i> ("._T('info_non_modifiable').")</i>\n<p>";
	}

// On ne peut modifier le mot de passe en cas de source externe (par exemple LDAP)
	if ($edit_pass) {
		$res = "<b>"._T('entree_nouveau_passe')."</b> "
		. "<font color='red'>("._T('info_plus_cinq_car').")</font> :<br />\n"
		. "<input type='password' name='new_pass' class='formo' value=\"\" size='40' /><br />\n"
		. _T('info_confirmer_passe')."<br />\n"
		. "<input type='password' name='new_pass2' class='formo' value=\"\" size='40'><p>\n";
		$corps .= $res;
	}

	$corps .= fin_cadre_relief(true)
	. "<p />"
	. (!$setconnecte ? '' : apparait_auteur_infos($id_auteur, $auteur))
	. "\n<div align='right'>"
	. "\n<input type='submit' class='fondo' value='"
	. _T('bouton_enregistrer')
	. "'></div>"
	. pipeline('affiche_milieu',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
		      'data'=>''));

	$arg = intval($id_auteur) . '/';

	return '<div>&nbsp;</div>'
	. "\n<div class='serif'>"
	. debut_cadre_relief("fiche-perso-24.gif", true, "", _T("icone_informations_personnelles"))
	. ($redirect
	     ? generer_action_auteur('legender_auteur', $arg, $redirect, $corps)
	   : ajax_action_auteur('legender_auteur', $arg, 'auteur_infos', "id_auteur=$id_auteur&initial=-1&retour=$redirect", $corps))
	. fin_cadre_relief(true)
	. '</div>';
}

//
// Apparaitre dans la liste des redacteurs connectes
//

// http://doc.spip.org/@apparait_auteur_infos
function apparait_auteur_infos($id_auteur, $auteur)
{

	if ($auteur['imessage']=="non"){
		$res = "<input type='radio' name='perso_activer_imessage' value='oui' id='perso_activer_imessage_on'>"
		. " <label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label> "
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' checked id='perso_activer_imessage_off'>"
		. " <b><label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label></b> ";
	} else {
		$res = "<input type='radio' name='perso_activer_imessage' value='oui' id='perso_activer_imessage_on' checked>"
		. " <b><label for='perso_activer_imessage_on'>"
		. _T('bouton_radio_apparaitre_liste_redacteurs_connectes')
		. "</label></b> "
		. "<br />\n<input type='radio' name='perso_activer_imessage' value='non' id='perso_activer_imessage_off'>"
		. " <label for='perso_activer_imessage_off'>"
		. _T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')
		. "</label> ";
	}

	return 	debut_cadre_formulaire('', true)
	. debut_cadre_relief("messagerie-24.gif", true, "", _T('info_liste_redacteurs_connectes'))
	. "\n<div>"
	. _T('texte_auteur_messagerie')
	. "</div>"
	. $res
	. fin_cadre_relief(true)
	. "<p />"
	. fin_cadre_formulaire(true);
}


// http://doc.spip.org/@table_auteurs_edit
function legender_auteur_voir($auteur, $redirect)
{
	global $connect_toutes_rubriques, $connect_statut, $connect_id_auteur, $champs_extra, $options,$spip_lang_right ;

	$id_auteur=$auteur['id_auteur'];
	$nom=$auteur['nom'];
	$bio=$auteur['bio'];
	$email=$auteur['email'];
	$nom_site_auteur=$auteur['nom_site'];
	$url_site=$auteur['url_site'];
	$statut=$auteur['statut'];
	$pgp=$auteur["pgp"];
	$extra = $auteur["extra"];

	$res .= "<table width='100%' cellpadding='0' border='0' cellspacing='0'>"
	. "<tr>"
	. "<td valign='top' width='100%'>"
	. gros_titre($nom,'',false)
	. "<div>&nbsp;</div>";

	if (strlen($email) > 2)
		$res .= "<div>"._T('email_2')." <b><a href='mailto:$email'>$email</a></b></div>";

	if ($url_site) {
		if (!$nom_site_auteur) $nom_site_auteur = _T('info_site');
		$res .= propre(_T('info_site_2')." [{{".$nom_site_auteur."}}->".$url_site."]");
	}
		
	$res .= "</td>"
	.  "<td>";
	
	if (statut_modifiable_auteur($id_auteur, $auteur)) {
		$ancre = "legender_auteur-$id_auteur";
		$clic = _T("admin_modifier_auteur");
		$h = generer_url_ecrire("auteur_infos","id_auteur=$id_auteur&initial=0");
		if (($_COOKIE['spip_accepte_ajax'] == 1 ) AND !$redirect) {
		  $evt .= "\nonclick=" . ajax_action_declencheur("\"$h\"",$ancre);
		  $h = "<a\nhref='$h$a'$evt>$clic</a>";
		}
	  $res .= icone($clic, $h, "redacteurs-24.gif", "edit.gif", '', '',true);
	}
	$res .= "</td></tr></table>";

	if (strlen($bio) > 0) { $res .= "<div>".propre("<quote>".$bio."</quote>")."</div>"; }
	if (strlen($pgp) > 0) { $res .= "<div>".propre("PGP:<cadre>".$pgp."</cadre>")."</div>"; }

	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		$res .= extra_affichage($extra, "auteurs", true);
	}

	return $res;

}

// http://doc.spip.org/@statut_modifiable_auteur
function statut_modifiable_auteur($id_auteur, $auteur)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

// on peut se changer soi-meme
	  return  (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et pas admin restreint pour changer un autre admin ou creer qq
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($id_auteur AND ($auteur['statut'] != "0minirezo")))));
}

?>
