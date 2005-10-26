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
if (defined("_ECRIRE_INC_MAIL")) return;
define("_ECRIRE_INC_MAIL", "1");



//
// Infos de mails sur l'hebergeur (tout ca est assez sale)
//
global $hebergeur;
global $HTTP_X_HOST, $REQUEST_URI, $SERVER_NAME, $HTTP_HOST;
$hebergeur = '';

// Lycos (ex-Multimachin)
if ($HTTP_X_HOST == 'membres.lycos.fr') {
	$hebergeur = 'lycos';
}
// Altern
else if (ereg('altern\.com$', $SERVER_NAME)) {
	$hebergeur = 'altern';
}
// NexenServices
else if ($_SERVER['SERVER_ADMIN'] == 'www@nexenservices.com') {
	if (!function_exists('email'))
		include ('mail.inc');
	$hebergeur = 'nexenservices';
}
// Online
else if (function_exists('email')) {
	$hebergeur = 'online';
}


//
// Chez lyconiania, envoyer un mail coupe la connection MySQL (sic)
//

$GLOBALS['queue_mails'] = '';

function envoyer_queue_mails() {
	global $queue_mails;
	if (!$queue_mails) return;
	reset($queue_mails);
	while (list(, $val) = each($queue_mails)) {
		$email = $val['email'];
		$sujet = $val['sujet'];
		$texte = $val['texte'];
		$headers = $val['headers'];
		@mail($email, $sujet, $texte, $headers);
	}
}

if ($GLOBALS['hebergeur'] == 'lycos') {
	register_shutdown_function(envoyer_queue_mails);
}

// Apparemment free etait le dernier hebergeur connu a ne pas offrir de mail
// cette fonction va donc pouvoir disparaitre
function tester_mail() {
	global $hebergeur;
	$test_mail = true;
	return $test_mail;
}

function nettoyer_caracteres_mail($t) {

	$t = filtrer_entites($t);

	if (lire_meta('charset') <> 'utf-8') {
		$t = str_replace(
			array("&#8217;","&#8220;","&#8221;"),
			array("'",      '"',      '"'),
		$t);
	}

	$t = str_replace(
		array("&mdash;", "&endash;"),
		array("--","-" ),
	$t);

	return $t;
}

function envoyer_mail($email, $sujet, $texte, $from = "", $headers = "") {
	global $hebergeur, $queue_mails, $flag_wordwrap;
	include_ecrire('inc_filtres.php3');
	include_ecrire('inc_charsets.php3');

	if (!email_valide($email)) return false;
	if ($email == _T('info_mail_fournisseur')) return false; // tres fort

	if (!$from) {
		$email_envoi = lire_meta("email_envoi");
		$from = email_valide($email_envoi) ? $email_envoi : $email;
	}
	spip_log("mail ($email): $sujet". ($from ?", from <$from>":''));

	$charset = lire_meta('charset');

	// Ajouter au besoin le \n final dans les $headers passes en argument
	if ($headers = trim($headers)) $headers .= "\n";

	// Ajouter le Content-Type s'il n'y est pas deja
	if (strpos($headers, "Content-Type: ") === false)
		$headers .=
		"MIME-Version: 1.0\n".
		"Content-Type: text/plain; charset=$charset\n".
		"Content-Transfer-Encoding: 8bit\n";

	// Et maintenant le champ From:
	$headers .= "From: $from\n";

	// nettoyer les &eacute; &#8217, &emdash; etc...
	$texte = nettoyer_caracteres_mail($texte);
	$sujet = nettoyer_caracteres_mail($sujet);

	// encoder le sujet si possible selon la RFC
	if (init_mb_string()) {
		# un bug de mb_string casse mb_encode_mimeheader si l'encoding interne
		# est UTF-8 et le charset iso-8859-1 (constate php5-mac ; php4.3-debian)
		mb_internal_encoding($charset);
		$sujet = mb_encode_mimeheader($sujet, $charset, 'Q');
		mb_internal_encoding('utf-8');
	}

	if ($flag_wordwrap) $texte = wordwrap($texte);

	if (os_serveur == 'windows') {
		$texte = ereg_replace ("\r*\n","\r\n", $texte);
		$headers = ereg_replace ("\r*\n","\r\n", $headers);
	}

	switch($hebergeur) {
	case 'lycos':
		$queue_mails[] = array(
			'email' => $email,
			'sujet' => $sujet,
			'texte' => $texte,
			'headers' => $headers);
		return true;
	case 'free':
		return false;
	case 'online':
		return @email('webmaster', $email, $sujet, $texte);
	default:
		return @mail($email, $sujet, $texte, $headers);
	}
}

function extrait_article($row) {
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");

	$adresse_site = lire_meta("adresse_site");

	$id_article = $row[0];
	$titre = $row[2];
	$chapo = $row[6];
	$texte = $row[7];
	$date = $row[9];
	$statut = $row[10];

	$les_auteurs = "";
 	$query = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur";
	$result_auteurs = spip_query($query);

	while ($row = spip_fetch_array($result_auteurs)) {
		$nom_auteur = trim(supprimer_tags(typo($row['nom'])));

		if ($les_auteurs) $les_auteurs .= ', ';
		$les_auteurs .= $nom_auteur;
	}

	$extrait = "** $titre **\n";
	if ($les_auteurs) $extrait .= _T('info_les_auteurs_1', array('les_auteurs' => $les_auteurs));
	if ($statut == 'publie') $extrait .= " "._T('date_fmt_nomjour_date', array('nomjour'=>nom_jour($date), 'date'=>affdate($date)));
	$extrait .= "\n\n".textebrut(propre(couper_intro("$chapo<p>$texte", 700)))."\n\n";
	if ($statut == 'publie') $extrait .= "-> ".$adresse_site."/spip_redirect.php3?id_article=$id_article\n\n";
	return $extrait;
}


function nettoyer_titre_email($titre) {
	$titre = ereg_replace("\n", ' ', supprimer_tags($titre));
	return ($titre);
}

function envoyer_mail_publication($id_article) {
	global $connect_nom;
	$adresse_suivi = lire_meta("adresse_suivi");
	$nom_site_spip = lire_meta("nom_site");
	$suivi_edito = lire_meta("suivi_edito");

	if ($suivi_edito == "oui") {
		$query = "SELECT * FROM spip_articles WHERE id_article = $id_article";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {

			// selectionne langue
			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_publie_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$courr = _T('info_publie_2')."\n\n";
			$nom = trim(supprimer_tags(typo($connect_nom)));
			$courr .= _T('info_publie_01', array('titre' => $titre, 'connect_nom' => $nom))."\n\n\n";
			$courr = $courr . extrait_article($row);
			envoyer_mail($adresse_suivi, $sujet, $courr);

			// reinstalle la langue utilisateur (au cas ou)
			changer_langue($lang_utilisateur);
		}
	}
}

function envoyer_mail_proposition($id_article) {
	$adresse_suivi = lire_meta("adresse_suivi");
	$adresse_site = lire_meta("adresse_site");
	$nom_site_spip = lire_meta("nom_site");
	$suivi_edito = lire_meta("suivi_edito");

	if ($suivi_edito == "oui") {
		if ($row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article"))) {

			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_propose_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			envoyer_mail($adresse_suivi,
				     $sujet,
				     _T('info_propose_2')
				     ."\n\n" 
				     . _T('info_propose_3', array('titre' => $titre))
				     ."\n" 
				     . _T('info_propose_4')
				     ."\n" 
				     . _T('info_propose_5')
				     ."\n" 
				     . $adresse_site 
				     . '/' 
				     . _DIR_RESTREINT_ABS 
				     . "articles.php3?id_article=$id_article\n\n\n" 
				     . extrait_article($row));
			changer_langue($lang_utilisateur);
		}
	}
}

?>
