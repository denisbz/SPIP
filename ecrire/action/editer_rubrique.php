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

include_spip('inc/rubriques');

// http://doc.spip.org/@action_editer_rubrique_dist
function action_editer_rubrique_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!$id_rubrique = intval($arg)) {
		if ($arg != 'oui') 
			redirige_par_entete(generer_url_ecrire());
		$id_rubrique = insert_rubrique(_request('id_parent'));
	}

	revisions_rubriques($id_rubrique);

	$redirect = parametre_url(
		urldecode(_request('redirect')),
		'id_rubrique', $id_rubrique, '&');
	redirige_par_entete($redirect);
}


// http://doc.spip.org/@insert_rubrique
function insert_rubrique($id_parent) {
	return sql_insert("spip_rubriques",
		"(titre, id_parent, statut)",
		"('"._T('item_nouvelle_rubrique')."', ".intval($id_parent).",'new')"
	);
}

// Enregistrer certaines modifications d'une rubrique
// $c est un tableau qu'on peut proposer en lieu et place de _request()
// http://doc.spip.org/@revisions_rubriques
function revisions_rubriques($id_rubrique, $c=false) {
	include_spip('inc/autoriser');
	include_spip('inc/filtres');

	// Ces champs seront pris nom pour nom (_POST[x] => spip_articles.x)
	$champs_normaux = array('titre', 'texte', 'descriptif');

	// ne pas accepter de titre vide
	if (_request('titre', $c) === '')
		$c = set_request('titre', _T('ecrire:info_sans_titre'), $c);

	$champs = array();
	foreach ($champs_normaux as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// traitement de la rubrique parente
	// interdiction de deplacer vers ou a partir d'une rubrique
	// qu'on n'administre pas.
	$statut_ancien = $parent = '';
	if (NULL !== ($id_parent = _request('id_parent', $c))) {
		include_spip('inc_rubrique');
		$id_parent = intval($id_parent);
		$filles = calcul_branche($id_rubrique);
		if (strpos(",$id_parent',", "$,filles,") != false)
			spip_log("La rubrique $id_rubrique ne peut etre fille de sa descendante $id_parent");
		else {
			$s = sql_fetsel("id_parent, statut", "spip_rubriques", "id_rubrique=$id_rubrique");
			$old_parent = $s['id_parent'];

			if (!($id_parent != $old_parent
			AND autoriser('publierdans', 'rubrique', $id_parent)
			AND autoriser('creerrubriquedans', 'rubrique', $id_parent)
			AND autoriser('publierdans', 'rubrique', $old_parent)
			      )) {
				if ($s['statut'] != 'new') {
					spip_log("deplacement de $id_rubrique vers $id_parent refuse a " . $GLOBALS['auteur_session']['id_auteur'] . ' '.  $GLOBALS['auteur_session']['statut']);
				}
			} elseif (editer_rubrique_breves($id_rubrique, $id_parent, $c)) {
				$champs['id_parent'] = $id_parent;
				$statut_ancien = $s['statut'];
			}
		}
	}


	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update('rubriques', $id_rubrique, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	include_spip('inc/modifier'); # temporaire pour eviter un bug
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_rubriques',
				'id_objet' => $id_rubrique
			),
			'data' => $champs
		)
	);

	sql_updateq('spip_rubriques', $champs, "id_rubrique=$id_rubrique");

	propager_les_secteurs(); 

	// Deplacement d'une rubrique publiee ==> chgt general de leur statut
	if ($statut_ancien == 'publie')
		calculer_rubriques_if($old_parent, array('id_rubrique' => $id_parent), $statut_ancien);

	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");

	// Notification ?
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_rubriques',
				'id_objet' => $id_rubrique
			),
			'data' => $champs
		)
	);

}

// si c'est une rubrique-secteur contenant des breves, ne deplacer
// que si $confirme_deplace == 'oui', et changer l'id_rubrique des
// breves en question

// http://doc.spip.org/@editer_rubrique_breves
function editer_rubrique_breves($id_rubrique, $id_parent, $c=false)
{
	$t = sql_countsel('spip_breves', "id_rubrique=$id_rubrique");
	if (!$t) return true;
	$t = (_request('confirme_deplace', $c) <> 'oui');
	if ($t) return false;
	$id_secteur = sql_fetsel("id_secteur", "spip_rubriques", "id_rubrique=$id_parent");
	if ($id_secteur= $id_secteur['id_secteur'])
		spip_query("UPDATE spip_breves SET id_rubrique=$id_secteur WHERE id_rubrique=$id_rubrique");
	return true;
}

