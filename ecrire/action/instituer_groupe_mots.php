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

include_spip('inc/filtres');

// http://doc.spip.org/@action_instituer_groupe_mots_dist
function action_instituer_groupe_mots_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (preg_match(",^([a-zA-Z_]\w+)$,", $arg, $r)) 
	  action_instituer_groupe_mots_get($arg);
	elseif (!preg_match(",^(-?\d+)$,", $arg, $r)) {
		 spip_log("action_instituer_groupe_mots_dist $arg pas compris");
	} else action_instituer_groupe_mots_post($r[1]);
}


// http://doc.spip.org/@action_instituer_groupe_mots_post
function action_instituer_groupe_mots_post($id_groupe)
{
	$acces_comite = _request('acces_comite');
	$acces_forum = _request('acces_forum');
	$acces_minirezo = _request('acces_minirezo');
	$articles = _request('articles');
	$breves = _request('breves');
	$change_type = _request('change_type');
	$descriptif = _request('descriptif');
	$obligatoire = _request('obligatoire');
	$rubriques = _request('rubriques');
	$syndic = _request('syndic');
	$texte = _request('texte');
	$unseul = _request('unseul');

	if ($id_groupe < 0){
		sql_delete("spip_groupes_mots", "id_groupe=" . (0- $id_groupe));
	} else {
		$change_type = (corriger_caracteres($change_type));
		$texte = (corriger_caracteres($texte));
		$descriptif = (corriger_caracteres($descriptif));

		if ($id_groupe) {	// modif groupe
			sql_updateq("spip_mots", array("type" => $change_type), "id_groupe=$id_groupe");

			sql_updateq("spip_groupes_mots", array("titre" =>$change_type,"texte"=>$texte,"descriptif"=>$descriptif,"unseul"=>$unseul,"obligatoire"=>$obligatoire,"articles"=>$articles,"breves"=>$breves,"rubriques"=>$rubriques,"syndic"=>$syndic,"minirezo"=>$acces_minirezo,"comite"=>$acces_comite,"forum"=>$acces_forum), "id_groupe=$id_groupe");

		} else {	// creation groupe
			sql_insert('spip_groupes_mots', "(titre, texte, descriptif, unseul,  obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)", "(" . _q($change_type) . ", " . _q($texte) . " , " . _q($descriptif) . " , " . _q($unseul) . " , " . _q($obligatoire) . " , " . _q($articles) . " ," . _q($breves) . " , " . _q($rubriques) . " , " . _q($syndic) . " , " . _q($acces_minirezo) . " ,  " . _q($acces_comite) . " , " . _q($acces_forum) . " )");
		}
	}
}


// http://doc.spip.org/@action_instituer_groupe_mots_get
function action_instituer_groupe_mots_get($table)
{
	$titre = _T('info_mot_sans_groupe');

	$id_groupe = sql_insert("spip_groupes_mots", "(titre, unseul, obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)", "(" . _q($titre) . ", 'non',  'non', '" . (($table=='articles') ? 'oui' : 'non') ."', '" . (($table=='breves') ? 'oui' : 'non') ."','" . (($table=='rubriques') ? 'oui' : 'non') ."','" . (($table=='syndic') ? 'oui' : 'non') ."', 'oui', 'non', 'non'" . ")");

        redirige_par_entete(parametre_url(urldecode(_request('redirect')),
					  'id_groupe', $id_groupe, '&'));
}

?>
