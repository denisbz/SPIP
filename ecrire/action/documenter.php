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

include_spip('action/supprimer');

// http://doc.spip.org/@action_documenter_dist
function action_documenter_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(-?)(\d+)\W(\w+)\W?(\d*)$,", $arg, $r))
		spip_log("action_documenter $arg pas compris");
	else action_documenter_post($r);
}

// http://doc.spip.org/@action_documenter_post
function action_documenter_post($r)
{
	list($x, $sign, $id, $type, $vignette) = $r;

	if ($vignette) {
		// on ne supprime pas, on dissocie
		// supprimer_document_et_vignette($vignette);
		// on dissocie, mais si le doc est utilise dans le texte, il sera reassocie ..., donc condition sur vu !
		sql_delete("spip_documents_".$type."s",
			"id_$type="._q($id)." AND id_document="._q($vignette)." AND (vu='non' OR vu IS NULL)");

		// Ensuite on supprime les docs orphelins, ca supprimera
		// physiquement notre document s'il n'est pas attache ailleurs
		// Je mets l'option a *false* pour ne rien casser chez les
		// experimentateurs [FORMULAIRE_UPLOAD, FORMS&TABLES], mais
		// par defaut ca devrait etre *true*
		// Quoi qu'il en soit les boucles n'affichent plus les documents
		// orphelins, sauf critere {tout}
		define('_SUPPRIMER_DOCUMENTS_ORPHELINS', false);
		if (_SUPPRIMER_DOCUMENTS_ORPHELINS) {
			include_spip('inc/documents');
			supprimer_les_documents_orphelins();
		}
		// Version plus soft : on ne supprime que le doc en cours de suppression
		include_spip('inc/documents');
		if (in_array($vignette, lister_les_documents_orphelins()))
			supprimer_documents(array($vignette));
	}
	else {
		if ($sign)
			$x = sql_select("docs.id_document", "spip_documents AS docs, spip_documents_".$type."s AS l", "l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document' AND docs.extension IN ('gif', 'jpg', 'png')");
		else
			$x = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l WHERE l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document'  AND docs.extension NOT IN ('gif', 'jpg', 'png')");

		while ($r = sql_fetch($x)) {
			// supprimer_document_et_vignette($r['id_document']);
			// on dissocie, mais si le doc est utilise dans le texte,
			// il sera reassocie ..., donc condition sur vu !
			sql_delete("spip_documents_".$type."s", "id_$type=$id AND id_document=".$r['id_document']." AND (vu='non' OR vu IS NULL)");
		}
	}
	if ($type == 'rubrique') {
		include_spip('inc/rubriques');
		depublier_branche_rubrique_if($id);
	}
}
?>
