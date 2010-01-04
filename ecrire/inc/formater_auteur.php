<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/lien');

//
// Construit un tableau des 5 informations principales sur un auteur,
// avec des liens vers les scripts associes:
// 1. l'icone du statut, avec lien vers la page de tous ceux ayant ce statut
// 2. l'icone du mail avec un lien mailto ou a defaut la messagerie de Spip
// 3. le nom, avec lien vers la page complete des informations
// 4. le mot "site" avec le lien vers le site Web personnelle
// 5. le nombre d'objets publies
//

// Un auteur sans autorisation de modification de soi  est un visiteur;
// il n'a pas de messagerie interne, et n'a publie que des messages de forum

// http://doc.spip.org/@inc_formater_auteur_dist
function inc_formater_auteur_dist($id_auteur, $row=NULL) {

  global $connect_id_auteur, $connect_statut;

	$id_auteur = intval($id_auteur);

	if ($row===NULL)
		$row = sql_fetsel("*, " . sql_date_proche('en_ligne', -15, 'DAY') . " AS ici", "spip_auteurs", "id_auteur=$id_auteur");

	$vals = array();
	$statut = $row['statut'];
	$href = generer_url_ecrire("auteurs","statut=$statut");
	$vals[] = "<a href='$href'>" . bonhomme_statut($row) . '</a>';

	if (($id_auteur == $connect_id_auteur) OR !$row['ici'])
		$vals[]= '&nbsp;';
	else	$vals[]= formater_auteur_mail($row, $id_auteur);

	if (!$nom = typo($row['nom']))
		$nom = "<span style='color: red'>" . _T('texte_vide') . '</span>';


	$chercher_logo = ($spip_display != 1
		AND $spip_display != 4
		AND $GLOBALS['meta']['image_process'] != "non")
			? charger_fonction('chercher_logo', 'inc')
			: false;

	if ($chercher_logo
	AND $logo = $chercher_logo($id_auteur, 'id_auteur', 'on')) {
		list($fid) = $logo;
		include_spip('inc/filtres_images_mini');
		$logo = image_reduire("<img src='$fid' alt='' style='float:right;' />", 26, 20);
	}
	else $logo ='';

	$vals[] = "<a href='"
	. generer_url_ecrire('auteur_infos', "id_auteur=$id_auteur")
	. "'"
	. (!$row['bio'] ? '' : (" title=\"" . attribut_html(couper(textebrut($row["bio"]), 200)) ."\""))
	. ">$nom</a>" . $logo;

	$url = traiter_lien_explicite($row["url_site"]);

	$vals[] =  !$url ? "&nbsp;"
	  :  "<a href='$url'>".couper(sinon(typo($row['nom_site']), $row["url_site"]),30)."</a>";

	$contributions = "";
	if (autoriser('modifier', 'auteur', $id_auteur, $row)) {
		$in = sql_in('statut', 
			($connect_statut == "0minirezo"
			? array('prepa', 'prop', 'publie', 'refuse')
			: array('prop', 'publie')));
		if ($cpt = sql_countsel("spip_auteurs_articles AS L LEFT JOIN spip_articles AS A ON A.id_article=L.id_article", "L.id_auteur=$id_auteur AND $in")){
			$contributions = ($cpt>1?$cpt.' '._T('info_article_2'):_T('info_1_article'));
		}
	} 
	else 
		$contributions = pipeline('compter_contributions_visiteur',array('args'=>array('id_auteur'=>$id_auteur,'row'=>$row),'data'=>''));

	$vals[] =  $contributions?$contributions:"&nbsp;";

	return $vals;
}

// http://doc.spip.org/@formater_auteur_mail
function formater_auteur_mail($row, $id_auteur)
{
	if (!in_array($row['statut'], array('0minirezo', '1comite')))
		return '';

	if ($row['imessage'] != 'non'
	AND $GLOBALS['meta']['messagerie_agenda'] != 'non')
		$href = generer_action_auteur("editer_message","normal/$id_auteur");
	else if (strlen($row['email'])
	AND autoriser('voir', 'auteur', $id_auteur))
		$href = 'mailto:' . $row['email'];
	else	return '';

	return "<a href='$href' title=\""
	  .  _T('info_envoyer_message_prive')
	  . "\" class='message'>&nbsp;</a>";
}
?>
