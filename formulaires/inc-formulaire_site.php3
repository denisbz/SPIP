<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// Le contexte indique dans quelle rubrique le visiteur peut proposer le site
global $balise_FORMULAIRE_SITE_collecte;
$balise_FORMULAIRE_SITE_collecte = array('id_rubrique');

function balise_FORMULAIRE_SITE_stat($args, $filtres) {

	// Pas d'id_rubrique ? Erreur de squelette
	if (!$args[0])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_SITE',
					'motif' => 'RUBRIQUES')), '');

	// Verifier que les visisteurs sont autorises a proposer un site
	return ((lire_meta("proposer_sites") != 2) ? '' : $args);
}

function balise_FORMULAIRE_SITE_dyn($id_rubrique, $nom_site, $url_site, $description_site) {

	if (!_request('nom_site')) return array('formulaire_site', 0);

	// Tester le nom du site
	if (strlen (_request('nom_site')) < 2){
		return _T('form_prop_indiquer_nom_site');
	}

	// Tester l'URL du site
	include_ecrire("inc_sites.php3");
	if (!recuperer_page(_request('url_site')))
		return _T('form_pet_url_invalide');

	// Integrer a la base de donnees
	$nom_site = addslashes(_request('nom_site'));
	$url_site = addslashes(_request('url_site'));
	$description_site = addslashes(_request('description_site'));
			
	spip_query("INSERT INTO spip_syndic
	(nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication)
	VALUES ('$nom_site', '$url_site', $id_rubrique, '$description_site', NOW(), NOW(), 'prop', 'non')");

	return  _T('form_prop_enregistre');
}

?>
