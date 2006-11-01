<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@exec_controle_petition_dist
function exec_controle_petition_dist()
{

	include_spip('inc/presentation');
	include_spip('inc/signatures');
	include_spip('inc/autoriser');

	$id_article = intval(_request('id_article'));

	if (
		autoriser('moderer_petition')
		OR (
			$id_article > 0
			AND autoriser('moderer_petition', 'article', $id_article)
		)
	) {

		$debut = intval(_request('debut'));

		$var_f = charger_fonction('signatures', 'inc');

		$r = $var_f('controle_petition',
			$id_article,
			$debut, 
			"(statut='publie' OR statut='poubelle')",
			"date_time DESC",
			10);
	}
	else
		$r = "<b>"._T('avis_non_acces_page')."</b>";


	if (_request('var_ajaxcharset')) ajax_retour($r);

	debut_page(_T('titre_page_controle_petition'), "forum", "suivi-petition");
	debut_gauche();

	debut_droite();
  
	gros_titre(_T('titre_suivi_petition'));

	$a = "editer_signature-" . $id_article;

	echo  "<div id='", $a, "' class='serif2'>", $r, "</div>", fin_page();
}

?>
