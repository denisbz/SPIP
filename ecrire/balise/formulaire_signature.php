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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

//
// Formulaire de signature d'une petition
//

include_spip('base/abstract_sql');
spip_connect();

// Contexte necessaire lors de la compilation

// Il *faut* demander petition, meme si on ne s'en sert pas dans l'affichage,
// car on doit obtenir la jointure avec sql_petitions pour verifier si
// une petition est attachee a l'article

// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE
function balise_FORMULAIRE_SIGNATURE ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_SIGNATURE', array('id_article', 'petition'));
}

// Verification des arguments (contexte + filtres)
// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE_stat
function balise_FORMULAIRE_SIGNATURE_stat($args, $filtres) {

	// pas d'id_article => erreur de squelette
	if (!$args[0])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_SIGNATURE',
					'motif' => 'ARTICLES')), '');

	// article sans petition => pas de balise
	else if (!$args[1])
		return '';

	else {
		// aller chercher dans la base la petition associee
		if ($r = sql_fetsel("texte, site_obli, message", 'spip_petitions', "id_article = ".intval($args[0]))) {
			$args[2] = $r['texte'];
			// le signataire doit-il donner un site ?
			$args[3] = ($r['site_obli'] == 'oui') ? '':' ';
			// le signataire peut-il proposer un commentaire
			$args[4] = ($r['message'] == 'oui') ? ' ':'';
		}
		return $args;
	}
}

// Executer la balise
// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE_dyn
function balise_FORMULAIRE_SIGNATURE_dyn($id_article, $petition, $texte, $site_obli, $message) {

	if (_request('var_confirm')) # _GET
		$reponse = reponse_confirmation();  # calculee plus tot: assembler.php

	else if (_request('nom_email') AND _request('adresse_email')){ # _POST 
		if (!spip_connect())
		  $reponse = _T('form_pet_probleme_technique');
		else {
			$controler_signature = charger_fonction('controler_signature', 'inc');
			$reponse = $controler_signature($id_article,
			_request('nom_email'), _request('adresse_email'),
			_request('message'), _request('signature_nom_site'),
			_request('signature_url_site'), _request('url_page'));
		}
	}

	return array('formulaires/signature', $GLOBALS['delais'],
	array(
		'id_article' => $id_article,
		'petition' => $petition,
		'texte' => $texte,
		'site_obli' => $site_obli,
		'message' => $message,
		'self' => $reponse ?'':parametre_url(self(),'debut_signatures','', '&'),
		'reponse' => $reponse
	));
}


// Retour a l'ecran du lien de confirmation d'une signature de petition.
// Si var_confirm est non vide, c'est l'appel dans public/assembler.php
// pour vider le cache au demarrage afin que la nouvelle signature apparaisse.
// Sinon, c'est l'execution du formulaire et on retourne le message 
// de confirmation ou d'erreur construit lors de l'appel par assembler.php
// Le controle d'unicite du mail ou du site (si requis) refait ici correspond
// au cas de mails de demande de confirmation laisses sans reponse

// http://doc.spip.org/@reponse_confirmation
function reponse_confirmation($var_confirm = '') {

	static $confirm = '';
	if (!$var_confirm) return $confirm;

	if ($var_confirm == 'publie' OR $var_confirm == 'poubelle')
		return '';

	if (!spip_connect()) {
		$confirm = _T('form_pet_probleme_technique');
		return '';
	}
	include_spip('inc/texte');
	include_spip('inc/filtres');

	// Suppression d'une signature par un moderateur ?
	// Cf. plugin notifications
	if (isset($_GET['refus'])) {
		// verifier validite de la cle de suppression
		// l'id_signature est dans var_confirm
		include_spip('inc/securiser_action');
		if ($id_signature = intval($var_confirm)
		    AND (
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere')
				OR
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere_ancien')
			)) {
			sql_updateq("spip_signatures", array("statut" => 'poubelle'), "id_signature=$id_signature");
			$confirm = _T('info_signature_supprimee');
		} else $confirm = _T('info_signature_supprimee_erreur');
		return '';
	}

	$row = sql_fetsel('*', 'spip_signatures', "statut=" . sql_quote($var_confirm), '', "1");

	if (!$row) {
		$confirm = _T('form_pet_aucune_signature');
		return '';
	}

	$id_signature = $row['id_signature'];
	$id_article = $row['id_article'];
	$adresse_email = $row['ad_email'];
	$url_site = $row['url_site'];

	$row = sql_fetsel('email_unique, site_unique', 'spip_petitions', "id_article=$id_article");

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";

	sql_updateq('spip_signatures',
		    array('statut' => 'publie', 'date_time' => 'NOW()'),
		    "id_signature=$id_signature");

	if ($email_unique) {

		$r = "id_article=$id_article AND ad_email=" . sql_quote($adresse_email);
		if (signature_entrop($r))
			  $confirm =  _T('form_pet_deja_signe');
	} 

	if ($site_unique) {
		$r = "id_article=$id_article AND url_site=" . sql_quote($url_site);
		if (signature_entrop($r))
			$confirm = _T('form_pet_site_deja_enregistre');
	}

	if (!$confirm) {
		$confirm = _T('form_pet_signature_validee');
		// invalider les pages ayant des boucles signatures
		include_spip('inc/invalideur');
		suivre_invalideur("id='varia/pet$id_article'");
	}
}

//
// Recevabilite de la signature d'une petition
//

// http://doc.spip.org/@inc_controler_signature_dist
function inc_controler_signature_dist($id_article, $nom, $mail, $message, $site, $url_site, $url_page) {

	include_spip('inc/texte');
	include_spip('inc/filtres');

	if (strlen($nom) < 2)
		return _T('form_indiquer_nom');
	elseif ($mail == _T('info_mail_fournisseur'))
		return _T('form_indiquer');
	elseif (!email_valide($mail)) 
		return _T('form_email_non_valide');
	elseif (strlen(_request('nobot'))
		OR (@preg_match_all(',\bhref=[\'"]?http,i', // bug PHP
				    $message 
				    # ,  PREG_PATTERN_ORDER
				   )
		    >2)) {
		#$envoyer_mail = charger_fonction('envoyer_mail','inc');
		#envoyer_mail('email_moderateur@example.tld', 'spam intercepte', var_export($_POST,1));
		return _T('form_pet_probleme_liens');
	}

	// tout le monde est la.

	$result_petition = sql_select('*', 'spip_petitions', "id_article=$id_article");

	if (!$row = sql_fetch($result_petition)) 
		return _T('form_pet_probleme_technique');

	if ($row['site_obli'] == "oui") {
		if (!strlen($site)
		OR !vider_url($url_site)) {
			return  _T('form_indiquer_nom_site');
		}
		include_spip('inc/sites');
		if (!recuperer_page($url_site, false, true, 0))
			return _T('form_pet_url_invalide');
	}

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";

	// Refuser si deja signe par le mail ou le site quand demande
	// Il y a un acces concurrent potentiel,
	// mais ca n'est qu'un cas particulier de qq n'ayant jamais confirme'.
	// On traite donc le probleme a la confirmation.

	if ($email_unique) {
		$r = sql_countsel('spip_signatures', "id_article=$id_article AND ad_email=" . sql_quote($mail) . " AND statut='publie'");

		if ($r)	return _T('form_pet_deja_signe');
	}

	if ($site_unique) {
		$r = sql_countsel('spip_signatures', "id_article=$id_article AND url_site=" . sql_quote($url_site) . " AND (statut='publie' OR statut='poubelle')");

		if ($r)	return _T('form_pet_site_deja_enregistre');
	}
	
	$passw = test_pass();
	if (!signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url_site, $message, $lang, $passw))
		return _T('form_pet_probleme_technique');

	$id_signature = sql_insertq('spip_signatures', array(
		'id_article' => $id_article,
		'date_time' => 'NOW()',
		'statut' => $passw,
		'ad_email' => $mail,
		'url_site' => $url_site));

	if (!$id_signature) return _T('form_pet_probleme_technique');
	include_spip('inc/modifier');
	revision_signature($id_signature, array(
				'nom_email' => $nom,
				'ad_email' => $mail,
				'message' => $message,
				'nom_site' => $site,
				'url_site' => $url_site
				));
	return _T('form_pet_envoi_mail_confirmation');
}

function signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url, $msg, $lang, $passw)
{
	$row = sql_fetsel('titre,lang', 'spip_articles', "id_article=$id_article");
	$lang = lang_select($row['lang']);
	$titre = textebrut(typo($row['titre']));
	if ($lang) lang_select();

	if ($lang != $GLOBALS['meta']['langue_site'])
		  $url_page = parametre_url($url_page, "lang", $lang,'&');

	$url_page = parametre_url($url_page, 'var_confirm', $passw, '&')
	. "#sp$id_article";

	$r = _T('form_pet_mail_confirmation',
		 array('titre' => $titre,
		       'nom_email' => $nom,
		       'nom_site' => $site,
		       'url_site' => $url, 
		       'url' => $url_page,
		       'message' => $msg));

	$titre = _T('form_pet_confirmation')." ". $titre;
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	return $envoyer_mail($mail,$titre, $r); 

}

// Pour eviter le recours a un verrou (qui bloque l'acces a la base),
// on commence par inserer systematiquement la signature 
// puis on demande toutes celles ayant la propriete devant etre unique
// (mail ou site). S'il y en a plus qu'une on les retire sauf la premiere
// En cas d'acces concurrents il y aura des requetes de retraits d'elements
// deja detruits. Bizarre ?  C'est mieux que de bloquer!

// http://doc.spip.org/@signature_entrop
function signature_entrop($where)
{
	$query = sql_select('id_signature', 'spip_signatures', $where . " AND statut='publie'",'',"date_time desc");
	$entrop = '';
	$n = sql_count($query);
	if ($n>1) {
		$entrop = array();
		for ($i=$n-1;$i;$i--) {
			$r = sql_fetch($query);
			$entrop[]=$r['id_signature'];
		}
		$entrop = " OR (id_signature IN (" . join(',',$entrop) .'))';
	}
	
	sql_delete('spip_signatures', "($where AND statut<>'publie')$entrop");

	return $entrop;
}

// http://doc.spip.org/@test_pass
function test_pass() {
	include_spip('inc/acces');
	for (;;) {
		$passw = creer_pass_aleatoire();
		if (!sql_countsel('spip_signatures', "statut='$passw'"))
			return $passw;
	}

}
?>
