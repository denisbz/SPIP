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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');

// On prend l'email dans le contexte de maniere a ne pas avoir a le
// verifier dans la base ni a le devoiler au visiteur


// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR
function balise_FORMULAIRE_ECRIRE_AUTEUR ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ECRIRE_AUTEUR', array('id_auteur', 'id_article', 'email'));
}

// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR_stat
function balise_FORMULAIRE_ECRIRE_AUTEUR_stat($args, $filtres) {
	include_spip('inc/filtres');

	// Pas d'id_auteur ni d'id_article ? Erreur de squelette
	if (!$args[0] AND !$args[1])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_ECRIRE_AUTEUR',
					'motif' => 'AUTEURS/ARTICLES')), '');

	// Si on est dans un contexte article, sortir tous les mails des auteurs
	// de l'article
	if (!$args[0] AND $args[1]) {
		unset ($args[2]);
		$s = spip_abstract_select('email',
					  array('auteurs' => 'spip_auteurs',
						'L' => 'spip_auteurs_articles'),
					  array('auteurs.id_auteur=L.id_auteur',
						'L.id_article='.intval($args[1])));
		while ($row = spip_abstract_fetch($s)) {
			if ($row['email'] AND email_valide($row['email']))
				$args[2].= ','.$row['email'];
		}
		$args[2] = substr($args[2], 1);
	}

	// On ne peut pas ecrire a un auteur dont le mail n'est pas valide
	if (!$args[2] OR !email_valide($args[2]))
		return '';

	// OK
	return $args;
}

// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR_dyn
function balise_FORMULAIRE_ECRIRE_AUTEUR_dyn($id_auteur, $id_article, $mail) {
	include_spip('inc/texte');
	$puce = definir_puce();

	// id du formulaire (pour en avoir plusieurs sur une meme page)
	$id = ($id_auteur ? '_'.$id_auteur : '_ar'.$id_article);
	#spip_log("id formulaire = $id, "._request("valide".$id));
	$sujet = _request('sujet_message_auteur'.$id);
	$texte = _request('texte_message_auteur'.$id);
	$adres = _request('email_message_auteur'.$id);

	if (_request('valide')) {
		$mailko = !email_valide($adres);
		$sujetko = !(strlen($sujet)>3);
		$texteko = !(strlen($texte)>10);
	}

	$validable = $texte && $sujet && (!$mailko);

	// doit-on envoyer le mail ?
	if ($validable
	AND $id == _request('num_formulaire_ecrire_auteur')
	AND _request('confirmer'.$id)) { 
		$texte .= "\n\n-- "._T('envoi_via_le_site')." ".supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))." (".$GLOBALS['meta']['adresse_site']."/) --\n";
		include_spip('inc/mail');
		envoyer_mail($mail, $sujet, $texte, $adres,
				"X-Originating-IP: ".$GLOBALS['ip']);
		$mailenvoye = _T('form_prop_message_envoye');
	}

	return 
		array('formulaires/ecrire_auteur', 0,
			array(
			'id' => $id,
			'mailko' => $mailko ? _T('form_prop_indiquer_email') : '',
			'mail' => $adres,
			'sujetko' => $sujetko ? _T('forum_attention_trois_caracteres') : '',
			'mailenvoye' => $mailenvoye,
			'sujet' => $sujet,
			'texteko' => $texteko ? _T('forum_attention_dix_caracteres') : '',
			'texte' => $texte,
			'valide' => $validable ? $id : '',
			'bouton' => _T('form_prop_envoyer'),
			'boutonconfirmation' => $validable ? _T('form_prop_confirmer_envoi') : ''
			)
		);
}
?>
