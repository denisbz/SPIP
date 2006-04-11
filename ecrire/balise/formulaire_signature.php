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

function balise_FORMULAIRE_SIGNATURE ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_SIGNATURE', array('id_article', 'petition'));
}

// Verification des arguments (contexte + filtres)
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
		if ($r = spip_abstract_fetsel("texte, site_obli, message", 'spip_petitions', "id_article = ".intval($args[0]))) {
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
function balise_FORMULAIRE_SIGNATURE_dyn($id_article, $petition, $texte, $site_obli, $message) {

	if (_request('var_confirm')) # _GET
		return reponse_confirmation($id_article);

	else if (_request('nom_email') AND _request('adresse_email')) # _POST
		return  reponse_signature($id_article,
			_request('nom_email'), _request('adresse_email'),
			_request('message'), _request('signature_nom_site'),
			_request('signature_url_site'), _request('url_page')
		);

	else {
		return array('formulaire_signature', $GLOBALS['delais'],
		array(
			'id_article' => $id_article,
			'petition' => $petition,
			'texte' => $texte,
			'site_obli' => $site_obli,
			'message' => $message,
			'self' => str_replace('&amp;', '&', self())
		));
	}
}


// Retour a l'ecran du lien de confirmation d'une signature de petition.
// Si var_confirm est non vide, c'est l'appel en debut de inc-public
// pour vider le cache au demarrage afin que la nouvelle signature apparaisse.
// Sinon, c'est l'execution du formulaire et on retourne le message 
// de confirmation ou d'erreur construit lors de l'appel par inc-public.

function reponse_confirmation($id_article, $var_confirm = '') {
	static $confirm = '';

	$id_article = intval($id_article);
	if (!$var_confirm) return $confirm;
	spip_connect();
	if ($GLOBALS['db_ok']) {
		include_spip('inc/texte');
		include_spip('inc/filtres');

		// Eviter les doublons
		$lock = "petition $id_article $var_confirm";
		if (!spip_get_lock($lock, 5)) {
			$confirm= _T('form_pet_probleme_technique');
		}
		else {
			$result_sign = spip_abstract_select('*', 'spip_signatures', "statut='".addslashes($var_confirm)."'");

			if (spip_num_rows($result_sign) > 0) {
				while($row = spip_fetch_array($result_sign)) {
					$id_signature = $row['id_signature'];
					$id_article = $row['id_article'];
					$date_time = $row['date_time'];
					$nom_email = $row['nom_email'];
					$adresse_email = $row['ad_email'];
					$nom_site = $row['nom_site'];
					$url_site = $row['url_site'];
					$message = $row['message'];
					$statut = $row['statut'];
				}
	
				$result_petition = spip_abstract_select('*', 'spip_petitions', "id_article=$id_article");

	
				while ($row = spip_fetch_array($result_petition)) {
					$id_article = $row['id_article'];
					$email_unique = $row['email_unique'];
					$site_obli = $row['site_obli'];
					$site_unique = $row['site_unique'];
					$message_petition = $row['message'];
					$texte_petition = $row['texte'];
				}
	
				if ($email_unique == "oui") {
					$email = addslashes($adresse_email);
					$result = spip_abstract_select('email', 'spip_signatures', "id_article=$id_article AND ad_email='$email' AND statut='publie'");
					if (spip_num_rows($result) > 0) {
						$confirm= (_T('form_pet_deja_signe'));
						$refus = "oui";
					}
				}
	
				if ($site_unique == "oui") {
					$site = addslashes($url_site);
					$result = spip_abstract_select('statut', 'spip_signatures', "id_article=$id_article AND url_site='$site' AND statut='publie'");
					if (spip_num_rows($result) > 0) {
						$confirm= (_T('form_pet_deja_enregistre'));
						$refus = "oui";
					}
				}
	
				if ($refus == "oui") {
					$confirm= (_T('form_deja_inscrit'));
				}
				else {
					$result = spip_query("UPDATE spip_signatures
					SET statut='publie', date_time=NOW()
					WHERE id_signature='$id_signature'");

					// invalider les pages ayant des boucles signatures
					include_spip('inc/invalideur');
					include_spip('inc/meta');
					suivre_invalideur("id='varia/pet$id_article'");
	
					$confirm= (_T('form_pet_signature_validee'));
				}
			}
			else {
				$confirm= (_T('form_pet_aucune_signature'));
			}
			spip_release_lock($lock);
		}
	}
	else {
		$confirm= _T('form_pet_probleme_technique');
	}
}

//
// Retour a l'ecran de la signature d'une petition
//

function reponse_signature($id_article, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page) {

	if ($GLOBALS['db_ok']) {
		include_spip('inc/texte');
		include_spip('inc/filtres');
		include_spip('inc/mail');

		// Eviter les doublons
		$lock = "petition $id_article $adresse_email";
		if (!spip_get_lock($lock, 5)) {
			return _T('form_pet_probleme_technique');
		} else {
			$result_petition = spip_abstract_select('*', 'spip_petitions', "id_article=$id_article");
	
			while ($row = spip_fetch_array($result_petition)) {
				$id_article = $row['id_article'];
				$email_unique = $row['email_unique'];
				$site_obli = $row['site_obli'];
				$site_unique = $row['site_unique'];
				$message_petition = $row['message'];
				$texte_petition = $row['texte'];
			}
	
			if (strlen($nom_email) < 2) {
				return _T('form_indiquer_nom');
			}
	
			if ($adresse_email == _T('info_mail_fournisseur')) {
				return _T('form_indiquer_email');
			}
	
			if ($email_unique == "oui") {
				$email = addslashes($adresse_email);
				$result = spip_abstract_select('statut', 'spip_signatures', "id_article=$id_article AND ad_email='$email' AND statut='publie'");

				if (spip_num_rows($result) > 0) {
					return _T('form_pet_deja_signe');
				}
			}
	
			if (!email_valide($adresse_email)) {
				return _T('form_email_non_valide');
			}
	
			if ($site_obli == "oui") {
				if (!$nom_site) {
					return _T('form_indiquer_nom_site');
				}
				include_spip('inc/sites');
	
				if (!recuperer_page($url_site)) {
					return _T('form_pet_url_invalide');
				}
			}
			if ($site_unique == "oui") {
				$site = addslashes($url_site);
				$result = spip_abstract_select('statut', 'spip_signatures', "id_article=$id_article AND url_site='$site' AND (statut='publie' OR statut='poubelle')");

				if (spip_num_rows($result) > 0) {
					return _T('form_pet_site_deja_enregistre');
				}
			}
	
			$passw = test_pass();
	
			if ($refus == "oui") {
				return _T('form_pet_signature_pasprise');
			}
			else {
			  $result_site = spip_abstract_select('titre', 'spip_articles', "id_article=$id_article");

				while ($row = spip_fetch_array($result_site)) {
					$titre = $row['titre'];
				}
				$url = parametre_url($url_page,
					'var_confirm',$passw,'&')
					."#sp$id_article";
	
				$messagex = _T('form_pet_mail_confirmation', array('titre' => $titre, 'nom_email' => $nom_email, 'nom_site' => $nom_site, 'url_site' => $url_site, 'url' => $url, 'message' => $message));

				if (envoyer_mail($adresse_email, _T('form_pet_confirmation')." ".$titre, $messagex)) {

					$nom_email = addslashes($nom_email);
					$adresse_email = addslashes($adresse_email);
					$nom_site = addslashes($nom_site);
					$url_site = addslashes($url_site);
					$message = addslashes($message);
	
					spip_abstract_insert('spip_signatures', "(id_article, date_time, nom_email, ad_email, nom_site, url_site, message, statut)", "($id_article, NOW(), '$nom_email', '$adresse_email', '$nom_site', '$url_site', '$message', '$passw')");
					return _T('form_pet_envoi_mail_confirmation');
				}
				else {
					return _T('form_pet_probleme_technique');
				}
			}
			spip_release_lock($lock);
		}
	}
	else {
		return _T('form_pet_probleme_technique');
	}

}


function test_pass() {
	include_spip('inc/acces');
	for (;;) {
		$passw = creer_pass_aleatoire();
		if (!spip_num_rows(spip_abstract_select('statut', 'spip_signatures', "statut='$passw'")))
			return $passw;
	}

}
?>
