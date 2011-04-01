<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Point d'entree d'edition d'un objet
 * on ne peut entrer que par un appel en fournissant $id et $objet
 * mais pas pas une url
 *
 * @param int $id
 * @param string $objet
 * @return array
 */
function action_editer_objet_dist($id=null, $objet=null, $set=null) {

	// appel direct depuis une url interdit
	if (is_null($id) OR is_null($objet)){
		include_spip('inc/minipres');
		echo minipres(_T('acces_interdit'));
		die();
	}

	// si id n'est pas un nombre, c'est une creation
	// mais on verifie qu'on a toutes les donnees qu'il faut.
	if (!$id = intval($id)) {
		// on ne sait pas si un parent existe mais on essaye
		$id_parent = _request('id_parent');
	  $id = insert_objet($objet, $id_parent);
	}

	if (!($id = intval($id))>0)
		return array($id,_L('echec enregistrement en base'));

	// Enregistre l'envoi dans la BD
	$err = objets_set($objet, $id, $set);

	return array($id,$err);
}

/**
 * Appelle toutes les fonctions de modification d'un objet
 * $err est un message d'erreur eventuelle
 *
 * @param string $objet
 * @param int $id
 * @param array|null $set
 * @return mixed|string
 */
function objets_set($objet, $id, $set=null) {
	$err = '';

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table_sql);
	if (!$desc OR !isset($desc['field'])) {
		spip_log("Objet $objet inconnu dans objets_set",_LOG_ERREUR);
		return _L("Erreur objet $objet inconnu");
	}
	include_spip('inc/modifier');

	$champ_date = '';
	if (isset($desc['date']) AND $desc['date'])
		$champ_date = $desc['date'];
	elseif (isset($desc['field']['date']))
		$champ_date = 'date';

	$white = array_keys($desc['field']);
	if (isset($desc['champs_editables']) AND is_array($desc['champs_editables']))
		$white = $desc['champs_editables'];
	$c = collecter_requests(
		// white list
		$white,
		// black list
		array($champ_date,'statut','id_parent','id_secteur'),
		// donnees eventuellement fournies
		$set
	);

	// Si l'objet est publie, invalider les caches et demander sa reindexation
	if (objet_test_si_publie($objet,$id)){
		$invalideur = "id='$objet/$id'";
		$indexation = true;
	}

	modifier_contenu($objet, $id,
		array(
			'nonvide' => '',
			'invalideur' => $invalideur,
			'indexation' => $indexation,
			 // champ a mettre a date('Y-m-d H:i:s') s'il y a modif
			'date_modif' => (isset($desc['field']['date_modif'])?'date_modif':'')
		),
		$c);

	// Modification de statut, changement de rubrique ?
	$c = collecter_requests(array($champ_date, 'statut', 'id_parent'),array(),$set);
	$err = instituer_objet($objet, $id, $c);

	return $err;
}

/**
 * Inserer en base un objet generique
 * @param  $objet
 * @param null $id_parent
 * @return bool|int
 */
function insert_objet($objet, $id_parent=null) {

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table_sql);
	if (!$desc OR !isset($desc['field']))
		return 0;

	$champs = array();
	if (isset($desc['field']['id_rubrique'])){
		// Si id_rubrique vaut 0 ou n'est pas definie, creer l'objet
		// dans la premiere rubrique racine
		if (!$id_rubrique = intval($id_parent)) {
			$row = sql_fetsel("id_rubrique, id_secteur, lang", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
			$id_rubrique = $row['id_rubrique'];
		}
		else
			$row = sql_fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=".intval($id_rubrique));

		$champs['id_rubrique'] = $id_rubrique;
		if (isset($desc['field']['id_secteur']))
			$champs['id_secteur'] = $row['id_secteur'];
		$lang_rub = $row['lang'];
	}

	// La langue a la creation : si les liens de traduction sont autorises
	// dans les rubriques, on essaie avec la langue de l'auteur,
	// ou a defaut celle de la rubrique
	// Sinon c'est la langue de la rubrique qui est choisie + heritee
	if ($GLOBALS['meta']['multi_objets'] AND in_array($table_sql,$GLOBALS['meta']['multi_objets'])) {
		lang_select($GLOBALS['visiteur_session']['lang']);
		if (in_array($GLOBALS['spip_lang'],
		explode(',', $GLOBALS['meta']['langues_multilingue']))) {
			$champs['lang'] = $GLOBALS['spip_lang'];
			if (isset($desc['field']['langue_choisie']))
				$champs['langue_choisie'] = 'oui';
		}
	}
	elseif (isset($desc['field']['lang']) AND isset($desc['field']['langue_choisie'])) {
		$champs['lang'] = ($lang_rub ? $lang_rub : $GLOBALS['meta']['langue_site']);
		$champs['langue_choisie'] = 'non';
	}

	if (isset($desc['field']['statut']))
		$champs['statut'] = 'prepa';

	if ((isset($desc['date']) AND $d=$desc['date']) OR $desc['field'][$d='date'])
		$champs[$d] = date('Y-m-d H:i:s');

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => $table_sql,
			),
			'data' => $champs
		)
	);

	$id = sql_insertq($table_sql, $champs);

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => $table_sql,
				'id_objet' => $id,
			),
			'data' => $champs
		)
	);

	// controler si le serveur n'a pas renvoye une erreur
	// et associer l'auteur sinon
	if ($id > 0 AND $GLOBALS['visiteur_session']['id_auteur']) {
		include_spip('action/editer_auteur');
		auteur_associer($GLOBALS['visiteur_session']['id_auteur'], array($objet=>$id));
	}

	return $id;
}


/**
 * $c est un array ('statut', 'id_parent' = changement de rubrique)
 * statut et rubrique sont lies, car un admin restreint peut deplacer
 * un objet publie vers une rubrique qu'il n'administre pas
 *
 * @param string $objet
 * @param int $id
 * @param array $c
 * @param bool $calcul_rub
 * @return mixed|string
 */
function instituer_objet($objet, $id, $c, $calcul_rub=true) {

	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table_sql);
	if (!$desc OR !isset($desc['field']))
		return _L("Impossible d'instituer $objet : non connu en base");

	include_spip('inc/autoriser');
	include_spip('inc/rubriques');
	include_spip('inc/modifier');

	$sel = array();
	$sel[] = (isset($desc['field']['statut'])?"statut":"'' as statut");

	$champ_date = '';
	if (isset($desc['date']) AND $desc['date'])
		$champ_date = $desc['date'];
	elseif (isset($desc['field']['date']))
		$champ_date = 'date';
	if ($champ_date)
	$sel[] = ($champ_date?"$champ_date as date":"'' as date");
	$sel[] = (isset($desc['field']['id_rubrique'])?'id_rubrique':"0 as id_rubrique");

	$row = sql_fetsel($sel, $table_sql, id_table_objet($objet).'='.intval($id));

	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$date_ancienne = $date = $row['date'];
	$champs = array();

	$d = ($date AND isset($c[$champ_date]))?$c[$champ_date]:null;
	$s = ($statut AND isset($c['statut']))?$c['statut']:$statut;

	// cf autorisations dans inc/instituer_objet
	if ($s != $statut OR ($d AND $d != $date)) {
		if ($id_rubrique ?
				autoriser('publierdans', 'rubrique', $id_rubrique)
			:
				autoriser('publier', $objet, $id)
			)
			$statut = $champs['statut'] = $s;
		else if (autoriser('modifier', $objet, $id) AND $s != 'publie')
			$statut = $champs['statut'] = $s;
		else
			spip_log("editer_objet $id refus " . join(' ', $c));

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		// ou si l'objet est deja date dans le futur
		// En cas de proposition d'un objet (mais pas depublication), idem
		if ($champ_date) {
			if ($champs['statut'] == 'publie'
			 OR ($champs['statut'] == 'prop' AND !in_array($statut_ancien, array('publie', 'prop')))
			) {
				if ($d OR strtotime($d=$date)>time())
					$champs[$champ_date] = $date = $d;
				else
					$champs[$champ_date] = $date = date('Y-m-d H:i:s');
			}
		}
	}

	// Verifier que la rubrique demandee existe et est differente
	// de la rubrique actuelle
	if ($id_rubrique
	  AND $id_parent = $c['id_parent']
	  AND $id_parent != $id_rubrique
	  AND (sql_fetsel('1', "spip_rubriques", "id_rubrique=".intval($id_parent)))) {
		$champs['id_rubrique'] = $id_parent;

		// si l'objet etait publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser l'objet en statut 'propose'.
		if ($statut == 'publie'
		AND !autoriser('publierdans', 'rubrique', $id_rubrique))
			$champs['statut'] = 'prop';
	}


	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => $table_sql,
				'id_objet' => $id,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	if (!count($champs)) return;

	// Envoyer les modifs.
	editer_objet_heritage($objet, $id, $id_rubrique, $statut_ancien, $champs, $calcul_rub);

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id'");

	/*
	if ($date) {
		$t = strtotime($date);
		$p = @$GLOBALS['meta']['date_prochain_postdate'];
		if ($t > time() AND (!$p OR ($t < $p))) {
			ecrire_meta('date_prochain_postdate', $t);
		}
	}*/

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => $table_sql,
				'id_objet' => $id,
				'action'=>'instituer',
				'statut_ancien' => $statut_ancien,
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications("instituer$objet", $id,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien, 'date'=>$date)
		);
	}

	return ''; // pas d'erreur
}

/**
 * fabrique la requete d'institution de l'objet, avec champs herites
 *
 * @param string $objet
 * @param int $id
 * @param int $id_rubrique
 * @param string $statut
 * @param array $champs
 * @param bool $cond
 * @return 
 */
function editer_objet_heritage($objet, $id, $id_rubrique, $statut, $champs, $cond=true) {
	$table_sql = table_objet_sql($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table_sql);

	// Si on deplace l'objet
	// changer aussi son secteur et sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {

		$row_rub = sql_fetsel("id_secteur, lang", "spip_rubriques", "id_rubrique=".sql_quote($champs['id_rubrique']));
		$langue = $row_rub['lang'];

		if (isset($desc['field']['id_secteur']))
			$champs['id_secteur'] = $row_rub['id_secteur'];

		if (isset($desc['field']['lang']) AND isset($desc['field']['langue_choisie']))
			if (sql_fetsel('1', $table_sql, id_table_objet($objet)."=".intval($id)." AND langue_choisie<>'oui' AND lang<>" . sql_quote($langue))) {
				$champs['lang'] = $langue;
			}
	}

	if (!$champs) return;
	sql_updateq($table_sql, $champs, id_table_objet($objet).'='.intval($id));

	// Changer le statut des rubriques concernees
	if ($cond) {
		include_spip('inc/rubriques');
		//$postdate = ($GLOBALS['meta']["post_dates"] == "non" AND isset($champs['date']) AND (strtotime($champs['date']) < time()))?$champs['date']:false;
		$postdate = false;
		calculer_rubriques_if($id_rubrique, $champs, $statut, $postdate);
	}
}

?>
