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


// methodes sql
function inc_recherche_to_array_dist($recherche, $options = array()) {

	// options par defaut
	$options = array_merge(
		array(
		'score' => true,
		'champs' => false,
		'toutvoir' => false,
		'matches' => false,
		'jointures' => false
		),
		$options
	);

	include_spip('inc/rechercher');
	include_spip('inc/autoriser');

	$requete = array(
		"SELECT"=>array(),
		"FROM"=>array(),
		"WHERE"=>array(),
		"GROUPBY"=>array(),
		"ORDERBY"=>array(),
		"LIMIT"=>"",
		"HAVING"=>array()
	);

	$table = sinon($options['table'], 'article');
	if ($options['champs'])
		$champs = $options['champs'];
	else {
		$l = liste_des_champs();
		$champs = $l['article'];
	}
	$serveur = $options['serveur'];

	list($methode, $q, $preg) = expression_recherche($recherche, $options);

	$jointures = $options['jointures']
		? liste_des_jointures()
		: array();

	$_id_table = id_table_objet($table);
	$requete['SELECT'][] = "t.".$_id_table;
	$a = array();
	// Recherche fulltext
	foreach ($champs as $champ => $poids) {
		if (is_array($champ)){
			spip_log("requetes imbriquees interdites");
		} else {
			if (strpos($champ,".")===FALSE)
				$champ = "t.$champ";
			$requete['SELECT'][] = $champ;
			$a[] = $champ.' '.$methode.' '.$q;
		}
	}
	if ($a) $requete['WHERE'][] = join(" OR ", $a);
	$requete['FROM'][] = table_objet_sql($table).' AS t';

	$results = array();

	$s = sql_select(
		$requete['SELECT'], $requete['FROM'], $requete['WHERE'],
		implode(" ",$requete['GROUPBY']),
		$requete['ORDERBY'], $requete['LIMIT'],
		$requete['HAVING'], $serveur
	);

	while ($t = sql_fetch($s,$serveur)
	AND (!isset($t['score']) OR $t['score']>0)) {
		$id = intval($t[$_id_table]);

		if ($options['toutvoir']
		OR autoriser('voir', $table, $id)) {
			// indiquer les champs concernes
			$champs_vus = array();
			$score = 0;
			$matches = array();

			$vu = false;
			foreach ($champs as $champ => $poids) {
				$champ = explode('.',$champ);
				$champ = end($champ);
				if ($n =
					($options['score'] || $options['matches'])
					? preg_match_all($preg, translitteration_rapide($t[$champ]), $regs, PREG_SET_ORDER)
					: preg_match($preg, translitteration_rapide($t[$champ]))
				) {
					$vu = true;

					if ($options['champs'])
						$champs_vus[$champ] = $t[$champ];
					if ($options['score'])
						$score += $n * $poids;
					if ($options['matches'])
						$matches[$champ] = $regs;

					if (!$options['champs']
					AND !$options['score']
					AND !$options['matches'])
						break;
				}
			}

			if ($vu) {
				if (!isset($results))
					$results = array();
				$results[$id] = array();
				if ($champs_vus)
					$results[$id]['champs'] = $champs_vus;
				if ($score)
					$results[$id]['score'] = $score;
				if ($matches)
					$results[$id]['matches'] = $matches;
			}
		}
	}


	// Gerer les donnees associees
	// ici on est un peu naze : pas capables de reconstruire une jointure complexe
	// on ne sait passer que par table de laison en 1 coup
	if (isset($jointures[$table])
	AND $joints = recherche_en_base(
			$recherche,
			$jointures[$table],
			array_merge($options, array('jointures' => false))
		)
	) {
		include_spip('action/editer_liens');
		$trouver_table = charger_fonction('trouver_table','base');
		$cle_depart = id_table_objet($table);
		$table_depart = table_objet($table,$serveur);
		$desc_depart = $trouver_table($table_depart,$serveur);
		$depart_associable = objet_associable($table);
		foreach ($joints as $table_liee => $ids_trouves) {
			// on peut definir une fonction de recherche jointe pour regler les cas particuliers
			if (!$rechercher_joints = charger_fonction("rechercher_joints_${table}_${table_liee}","inc",true)){
				$cle_arrivee =  id_table_objet($table_liee);
				$table_arrivee = table_objet($table_liee,$serveur);
				$desc_arrivee = $trouver_table($table_arrivee,$serveur);
				// cas simple : $cle_depart dans la table_liee
				if (isset($desc_arrivee['field'][$cle_depart])){
					$s = sql_select("$cle_depart, $cle_arrivee", $desc_arrivee['table_sql'], sql_in($cle_arrivee, array_keys($ids_trouves)), '','','','',$serveur);
				}
				// cas simple : $cle_arrivee dans la table
				elseif (isset($desc_depart['field'][$cle_arrivee])){
					$s = sql_select("$cle_depart, $cle_arrivee", $desc_depart['table_sql'], sql_in($cle_arrivee, array_keys($ids_trouves)), '','','','',$serveur);
				}
				// sinon cherchons une table de liaison
				// cas recherche principale article, objet lie document : passer par spip_documents_liens
				elseif ($l = objet_associable($table_liee)){
					list($primary, $table_liens) = $l;
					$s = sql_select("id_objet as $cle_depart, $primary as $cle_arrivee", $table_liens, array("objet='$table'",sql_in($primary, array_keys($ids_trouves))), '','','','',$serveur);
				}
				// cas recherche principale auteur, objet lie article: passer par spip_auteurs_liens
				elseif ($l = $depart_associable){
					list($primary, $table_liens) = $l;
					$s = sql_select("$primary as $cle_depart, id_objet as $cle_arrivee", $table_liens, array("objet='$table_liee'",sql_in('id_objet', array_keys($ids_trouves))), '','','','',$serveur);
				}
				// cas table de liaison generique spip_xxx_yyy
				elseif($t=$trouver_table($table_arrivee."_".$table_depart,$serveur)
				  OR $t=$trouver_table($table_depart."_".$table_arrivee,$serveur)){
					$s = sql_select("$cle_depart,$cle_arrivee", $t["table_sql"], sql_in($cle_arrivee, array_keys($ids_trouves)), '','','','',$serveur);
				}
			}
			else
				list($cle_depart,$cle_arrivee,$s) = $rechercher_joints($table,$table_liee,array_keys($ids_trouves), $serveur);

			while ($t = is_array($s)?array_shift($s):sql_fetch($s)) {
				$id = $t[$cle_depart];
				$joint = $ids_trouves[$t[$cle_arrivee]];
				if (!isset($results))
					$results = array();
				if (!isset($results[$id]))
					$results[$id] = array();
				if ($joint['score'])
					$results[$id]['score'] += $joint['score'];
				if ($joint['champs'])
				foreach($joint['champs'] as $c => $val)
					$results[$id]['champs'][$table_liee.'.'.$c] = $val;
				if ($joint['matches'])
				foreach($joint['matches'] as $c => $val)
					$results[$id]['matches'][$table_liee.'.'.$c] = $val;
			}
		}
	}

	return $results;
}


?>
