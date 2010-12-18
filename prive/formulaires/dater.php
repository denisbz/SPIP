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

function formulaires_dater_charger_dist($objet, $id_objet, $retour='', $options=array()){

	$objet = objet_type($objet);
	if (!$objet OR !intval($id_objet))
		return false;

	if (!is_array($options))
		$options = unserialize($options);

	$_id_objet = id_table_objet($objet);
	$table = table_objet($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table);

	if (!$desc)
		return false;
	include_spip('public/interfaces');
	$champ_date = @$GLOBALS['table_date'][$table];
	if (!$champ_date) $champ_date = 'date';

	$valeurs = array(
		'objet'=>$objet,
		'id_objet'=>$id_objet,
		'id'=>$id_objet,
	);


	$select = "$champ_date as date";
	if (isset($desc['field']['date_redac']))
		$select .= ",date_redac";
	if (isset($desc['field']['statut']))
		$select .= ",statut";


	$row = sql_fetsel($select, $desc['table'], "$_id_objet=".intval($id_objet));
	$statut = isset($row['statut'])?$row['statut']:'publie'; // pas de statut => publie

	$valeurs['editable'] = autoriser('dater',$objet,$id_objet,null,array('statut'=>$statut));

	$possedeDateRedac = false;

	if (isset($row['date_redac']) AND
		$regs = recup_date($row['date_redac'], false)) {
		$annee_redac = $regs[0];
		$mois_redac = $regs[1];
		$jour_redac = $regs[2];
		$heure_redac = $regs[3];
		$minute_redac = $regs[4];
		$possedeDateRedac= ($annee_redac + $mois_redac + $jour_redac);
	}
	else
		$annee_redac = $mois_redac = $jour_redac = $heure_redac = $minute_redac = 0;

	if ($regs = recup_date($row['date'], false)) {
		$annee = $regs[0];
		$mois = $regs[1];
		$jour = $regs[2];
		$heure = $regs[3];
		$minute = $regs[4];
	}

	// attention, si la variable s'appelle date ou date_redac, le compilo va
	// la normaliser, ce qu'on ne veut pas ici.
	$valeurs['afficher_date_redac'] = ($possedeDateRedac?$row['date_redac']:'');
	$valeurs['date_redac_jour'] = dater_formater_saisie_jour($jour_redac,$mois_redac,$annee_redac);
	$valeurs['date_redac_heure'] = "$heure_redac:$minute_redac";

	$valeurs['afficher_date'] = $row['date'];
	$valeurs['date_jour'] = dater_formater_saisie_jour($jour,$mois,$annee);
	$valeurs['date_heure'] = "$heure:$minute";

	$valeurs['sans_redac'] = !$possedeDateRedac;

	$valeurs['_editer_date_anterieure'] = ($objet=='article' AND ($GLOBALS['meta']["articles_redac"] != 'non' OR $possedeDateRedac));
	$valeurs['_label_date'] = (($statut == 'publie' OR $objet != 'article')? _T('texte_date_publication_article'): _T('texte_date_creation_article'));
	$valeurs['_saisie_en_cours'] = (_request('date_jour')!==null);

	return $valeurs;
}

function dater_formater_saisie_jour($jour,$mois,$annee,$sep="/"){
	if (intval($jour))
		return "$jour$sep$mois$sep$annee";
	if (intval($mois))
		return "$mois$sep$annee";
	return $annee;
}
/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_dater_identifier_dist($objet, $id_objet, $retour='', $options=array()){
	return serialize(array($objet, $id_objet));
}

/**
 * Verification avant traitement
 *
 * On verifie que l'upload s'est bien passe et
 * que le document recu est une image (d'apres son extension)
 *
 * @param string $objet
 * @param integer $id_objet
 * @param string $retour
 * @return Array Tableau des erreurs
 */
function formulaires_dater_verifier_dist($objet, $id_objet, $retour=''){
	$erreurs = array();

	foreach(array('date','date_redac') as $k)
		if ($v=_request($k."_jour") AND !dater_recuperer_date_saisie($v))
			$erreurs[$k] = _T('format_date_incorrecte');
		elseif ($v=_request($k."_heure") AND !dater_recuperer_heure_saisie($v))
			$erreurs[$k] = _T('format_heure_incorrecte');

	if (!_request('date_jour'))
		$erreurs['date'] = _T('info_obligatoire');

	return $erreurs;
}

/**
 * Traitement 
 *
 * @param string $objet
 * @param integer $id_objet
 * @param string $retour
 * @return Array
 */
function formulaires_dater_traiter_dist($objet, $id_objet, $retour=''){
	$res = array('editable'=>' ');

	$_id_objet = id_table_objet($objet);
	$table = table_objet($objet);
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table($table);

	if (!$desc)
		return array('message_erreur'=>_L('erreur')); #impossible en principe

	include_spip('public/interfaces');
	$champ_date = @$GLOBALS['table_date'][$table];
	if (!$champ_date) $champ_date = 'date';

	$set = array();

	include_spip('inc/date');
	if (!$d = dater_recuperer_date_saisie(_request('date_jour')))
		$d = array(date('Y'),date('m'),date('d'));
	if (!$h = dater_recuperer_heure_saisie(_request('date_heure')))
		$h = array(0,0);

	$set[$champ_date] = format_mysql_date($d[0], $d[1], $d[2], $h[0], $h[1]);

	if (isset($desc['field']['date_redac'])){
		if (!_request('date_redac_jour') OR _request('sans_redac'))
			$set['date_redac'] = format_mysql_date(0,0,0,0,0,0);
		else {
			if (!$d = dater_recuperer_date_saisie(_request('date_redac_jour')))
				$d = array(date('Y'),date('m'),date('d'));
			if (!$h = dater_recuperer_heure_saisie(_request('date_redac_heure')))
				$h = array(0,0);
			$set['date_redac'] = format_mysql_date($d[0], $d[1], $d[2], $h[0], $h[1]);
		}
	}

	include_spip('action/editer_'.$objet);
	include_spip('inc/modifier');
	if (function_exists($f=$objet."s_set")
		OR function_exists($f="instituer_".$objet)
		OR function_exists($f="revision_".$objet)
	){
		$f($id_objet,$set);
	}
	else {
		modifier_contenu($objet, $id_objet, array(), $set);
	}

	if ($retour)
		$res['redirect'] = $retour;

	set_request('date_jour');
	set_request('date_redac_jour');
	set_request('date_heure');
	set_request('date_redac_heure');

	return $res;
}

/**
 * Recuperer annee,mois,jour sur la date saisie
 * @param string $post
 * @return array
 */
function dater_recuperer_date_saisie($post) {
	if (!preg_match('#^(?:(?:([0-9]{1,2})[/-])?([0-9]{1,2})[/-])?([0-9]{4}|[0-9]{1,2})#', $post, $regs))
		return '';
	if ($regs[3]<>'' AND $regs[3] < 1001)
		$regs[3] += 9000;

	return array($regs[3],$regs[2],$regs[1]);
}

/**
 * Recuperer heures,minutes sur l'heure saisie
 * @param string $post
 * @return array
 */
function dater_recuperer_heure_saisie($post) {
	if (!preg_match('#([0-9]{1,2})(?:[h:](?:([0-9]{1,2}))?)?#', $post, $regs))
		return '';
	return array($regs[1],$regs[2]);
}

?>
