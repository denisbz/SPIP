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

// http://doc.spip.org/@action_editer_breve_dist
function action_editer_breve_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// Envoi depuis les boutons "publier/supprimer cette breve"
	if (preg_match(',^(\d+)\Wstatut\W(\w+)$,', $arg, $r)) {
		$id_breve = $r[1];
		set_request('statut', $r[2]);
		revisions_breves($id_breve);
	} 
	// Envoi depuis le formulaire d'edition pour chgt de langue
	else if (preg_match(',^(\d+)\W(\w+)$,', $arg, $r)) {
		revisions_breves_langue($id_breve=$r[1], $r[2], _request('changer_lang'));
	}
	// Envoi depuis le formulaire d'edition d'une breve existante
	else if ($id_breve = intval($arg)) {
		revisions_breves($id_breve);
	}
	// Envoi depuis le formulaire de creation d'une breve
	else if ($arg == 'oui') {
		$id_breve = insert_breve(_request('id_parent'));
		if ($id_breve) revisions_breves($id_breve);
	} 
	// Erreur
	else{
		redirige_par_entete(generer_url_ecrire());
	}

	// Rediriger le navigateur
	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_breve', $id_breve, '&');

	redirige_par_entete($redirect);
}

// http://doc.spip.org/@insert_breve
function insert_breve($id_rubrique) {

	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer la breve
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = sql_fetsel("id_rubrique", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
		$id_rubrique = $row['id_rubrique'];
	}

	// La langue a la creation : c'est la langue de la rubrique
	$row = sql_fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
	$choisie = 'non';
	$lang = $row['lang'];
	$id_rubrique = $row['id_secteur']; // garantir la racine

	$id_breve = sql_insert("spip_breves",
		"(id_rubrique, statut, date_heure, lang, langue_choisie)",
		"($id_rubrique, 'prop', NOW(), '$lang', '$choisie')");
	return $id_breve;
}


// Enregistre une revision de breve
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_breves
function revisions_breves ($id_breve, $c=false) {
	include_spip('inc/filtres');
	include_spip('inc/rubriques');
	include_spip('inc/autoriser');

	// Ces champs seront pris nom pour nom (_POST[x] => spip_breves.x)
	$champs_normaux = array('titre', 'texte', 'lien_titre', 'lien_url');

	// ne pas accepter de titre vide
	if (_request('titre', $c) === '')
		$c = set_request('titre', _T('ecrire:info_sans_titre'), $c);

	$champs = array();
	foreach ($champs_normaux as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	// Changer le statut de la breve ?
	$row = sql_fetsel("statut, id_rubrique,lang, langue_choisie", "spip_breves", "id_breve=$id_breve");

	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$langue_old = $row['lang'];
	$langue_choisie_old = $row['langue_choisie'];

	if (_request('statut', $c)
	AND _request('statut', $c) != $statut
	AND autoriser('publierdans', 'rubrique', $id_rubrique)) {
		$statut = $champs['statut'] = _request('statut', $c);
	}

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est a la racine et differente
	// de la rubrique actuelle
	if ($id_parent = intval(_request('id_parent', $c))
	AND $id_parent != $id_rubrique
	AND (NULL !== ($lang=sql_getfetsel('lang', 'spip_rubriques', "id_parent=0 AND id_rubrique=$id_parent")))) {
		$champs['id_rubrique'] = $id_parent;
		// - changer sa langue (si heritee)
		if ($langue_choisie_old != "oui") {
			if ($lang != $langue_old)
				$champs['lang'] = $lang;
		}
		// si la breve est publiee
		// et que le demandeur n'est pas admin de la rubrique
		// repasser la breve en statut 'prop'.
		if ($statut == 'publie') {
			if (!autoriser('publierdans','rubrique',$id_parent))
				$champs['statut'] = $statut = 'prop';
		}
	}

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update('breves', $id_breve, $c))
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	include_spip('inc/modifier'); # temporaire pour eviter un bug
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_breves',
				'id_objet' => $id_breve
			),
			'data' => $champs
		)
	);

	if (!$champs) return;

	sql_updateq('spip_breves', $champs, "id_breve=$id_breve");

	// marquer le fait que la breve est travaillee par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=breves_voir
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id_breve, $GLOBALS['auteur_session'], 'breve');
	}

	//
	// Post-modifications
	//

	// Invalider les caches
	if ($statut == 'publie') {
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_breve/$id_breve'");
	}


	// Au besoin, changer le statut des rubriques concernees 
	calculer_rubriques_if($id_rubrique, $champs, $statut_ancien);

	// Notifications, revisions, reindexation...
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_breves',
				'id_objet' => $id_breve
			),
			'data' => $champs
		)
	);
}

// http://doc.spip.org/@revisions_breves_langue
function revisions_breves_langue($id_breve, $id_rubrique, $changer_lang)
{
	if ($changer_lang == "herit") {
		$row = sql_fetsel("lang", "spip_rubriques", "id_rubrique=$id_rubrique");
		$langue_parent = $row['lang'];
		sql_updateq('spip_breves', array('lang'=>$langue_parent, 'langue_choisie'=>'non'), "id_breve=$id_breve");
	} else 	{
		sql_updateq('spip_breves', array('lang'=>$changer_lang, 'langue_choisie'=>'oui'), "id_breve=$id_breve");
		include_spip('inc/rubriques');
		$langues = calculer_langues_utilisees();
		ecrire_meta('langues_utilisees', $langues);
	}

}

?>
