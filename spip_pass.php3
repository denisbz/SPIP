<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_meta.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_session.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_mail.php3");
include_ecrire("inc_acces.php3");

utiliser_langue_site();
utiliser_langue_visiteur();

unset($erreur);

$mode = $GLOBALS['mode'];
if ($oubli_pass == 'oui') $mode = 'oubli_pass';	# backward compatible

// recuperer le cookie de relance
if ($p = addslashes($p)) {
	$mode = 'oubli_pass';
	$res = spip_query ("SELECT * FROM spip_auteurs WHERE cookie_oubli='$p' AND statut<>'5poubelle' AND pass<>''");
	if ($row = spip_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			spip_query ("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',
				cookie_oubli='' WHERE cookie_oubli='$p'");

			$login = $row['login'];
			$erreur = "<b>"._T('pass_nouveau_enregistre')."</b>".
			"<p>"._T('pass_rappel_login', array('login' => $login));
		} else {
			install_debut_html(_T('pass_nouveau_pass'));
			echo "<p><br>",
			"<form action='spip_pass.php3' method='post'>",
			"<input type='hidden' name='p' value='".htmlspecialchars($p)."'>",
			_T('pass_choix_pass')."<br>\n",
			"<input type='password' name='pass' value=''>",
			'<input type=submit class="fondl" value="'._T('pass_ok').'"></div></form>';
			install_fin_html();
			exit;
		}
	}
	else
		$erreur = _T('pass_erreur_code_inconnu');
}

// envoyer le cookie de relance mot de passe
if ($email_oubli) {
	if (email_valide($email_oubli)) {
		$email = addslashes($email_oubli);
		$res = spip_query("SELECT * FROM spip_auteurs WHERE email ='$email'");
		if ($row = spip_fetch_array($res)) {
			if ($row['statut'] == '5poubelle' OR $row['pass'] == '')
				$erreur = _T('pass_erreur_acces_refuse');
			else {
				$cookie = creer_uniqid();
				spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

				$nom_site_spip = lire_meta("nom_site");
				$adresse_site = lire_meta("adresse_site");

				$message = _T('pass_mail_passcookie', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site, 'cookie' => $cookie));
				if (envoyer_mail($email, "[$nom_site_spip] "._T('pass_oubli_mot'), $message))
					$erreur = _T('pass_recevoir_mail');
				else
					$erreur = _T('pass_erreur_probleme_technique');
			}
		}
		else
			$erreur = _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($email_oubli)));
	}
	else
		$erreur = _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($email_oubli)));
}

if ($mode == 'oubli_pass') {
	// debut presentation
	install_debut_html(_T('pass_mot_oublie'));

	echo "<p>";
	if ($erreur)
		echo $erreur;
	else {
		echo _T('pass_indiquez_cidessous'),
			"<p>",
			'<form action="spip_pass.php3" method="post">',
			'<div align="right">',
			'<input type="text" class="fondo" name="email_oubli" value="">',
			'<input type="hidden" name="mode" value="oubli_pass">',
			'<input type=submit class="fondl" value="'._T('pass_ok').'"></div></form>';
	}
}
else {
	$inscriptions = (lire_meta("accepter_inscriptions") == "oui");

	if ($inscriptions OR (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo')) {
	// debut presentation

		if (!$mode)
		  $mode = $inscriptions ? 'redac' : 'forum';

		include_local("inc-inscription.php3");
		include_local("inc-public-global.php3"); 
		install_debut_html(_T('pass_vousinscrire'));
		echo "<p>",
		  (($mode != 'forum') ? _T('pass_espace_prive_bla') :
		   _T('pass_forum_bla')),
		  "\n</p>",
		  inclure_formulaire(inscription_dyn($mode,
							$GLOBALS['mail_inscription'],
							$GLOBALS['nom_inscription']));
	}
	else {
		install_debut_html(_T('pass_erreur'));
		echo "<p>",_T('pass_rien_a_faire_ici'), '</p>';
	}
}

echo "<p align='right'>",
http_script("if (window.opener) document.write(\"<a href='javascript:close();'>\");
else document.write(\"<a href='./'>\");
document.write(\""._T('pass_quitter_fenetre')."</a>\");",
	    '',
	    "[<a href='./'>"._T('pass_retour_public')."</a>]"),
  "</p>";

install_fin_html();

?>
