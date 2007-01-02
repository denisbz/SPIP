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

// http://doc.spip.org/@action_instituer_rubrique_articles_dist
function action_instituer_rubrique_articles_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id, $statut) = preg_split('/\W/', $arg);
	$id = intval($id);
	$statut =array('statut' => $statut);

	include_spip('action/editer_article');

	$table = 'articles';
	$key = 'id_article';

	$voss = spip_query("SELECT $key AS id FROM spip_$table WHERE id_rubrique=$id AND (statut = 'publie' OR statut = 'prop' OR statut = 'prepa')");

	while($row = spip_fetch_array($voss)) {
		instituer_article($row['id'], $statut, false);
	}
	include_spip('inc/rubriques');
	calculer_rubriques();
	redirige_par_entete(generer_url_ecrire('meme_rubrique', "id=$id&type=article&date=date", true));
}
?>
