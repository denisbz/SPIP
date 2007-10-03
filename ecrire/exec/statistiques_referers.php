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

include_spip('inc/presentation');
include_spip('inc/statistiques');

// http://doc.spip.org/@exec_statistiques_referers_dist
function exec_statistiques_referers_dist()
{
	global $connect_statut;

	$id_article = intval(_request('id_article'));
	$jour = _request('jour');
	$limit  = _request('limit');

	if (!autoriser('voirstats', $id_article ? 'article':'', $id_article)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	if ($id_article){
		$result = sql_select("titre, visites, popularite", "spip_articles", "statut='publie' AND id_article ='$id_article'");

		if ($row = sql_fetch($result)) {
			$total_absolu = $row['visites'];
		}
	}  else {
		$result = spip_query("SELECT SUM(visites) AS total_absolu FROM spip_visites");

		if ($row = sql_fetch($result)) {
			$total_absolu = $row['total_absolu'];
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques_referers'), "statistiques_visites", "referers");
	echo "<br /><br /><br />";

	echo gros_titre(_T('titre_liens_entrants'),'', false);

//barre_onglets("statistiques", "referers");

echo debut_gauche('', true);
echo debut_boite_info(true);
echo "<p style='font-size:small; text-align:left;' class='verdana1'>"._T('info_gauche_statistiques_referers')."</p>";
echo fin_boite_info(true);

echo debut_droite('', true);


//
// Affichage des referers
//

// nombre de referers a afficher
$limit = intval($limit);	//secu
if ($limit == 0) $limit = 100;

if ($jour<>'veille')
	$jour='jour';

echo barre_onglets("stat_referers", $jour);


// afficher quels referers ?

 $result = spip_query("SELECT referer_md5, referer, SUM(visites_$jour) AS vis FROM spip_referers WHERE visites_$jour>0 GROUP BY referer ORDER BY vis DESC LIMIT $limit");

 echo "<br /><div style='font-size:small;' class='verdana1'>";
 echo aff_referers ($result, $limit, generer_url_ecrire('statistiques_referers', ("jour=$jour&limit=" . strval($limit+200))));

 echo "</div><br />";

 echo fin_gauche(), fin_page();
}

?>
