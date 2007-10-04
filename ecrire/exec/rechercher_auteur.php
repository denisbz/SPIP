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

# gerer un charset minimaliste en convertissant tout en unicode &#xxx;

// http://doc.spip.org/@exec_rechercher_auteur_dist
function exec_rechercher_auteur_dist()
{
	$idom = _request('idom');
	if (!preg_match('/\w+/',$idom))
	      {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	      }
	include_spip('inc/actions');
	$where = split("[[:space:]]+", _request('nom'));
	if ($where) {
		foreach ($where as $k => $v) 
			$where[$k] = "'%" . substr(str_replace("%","\%", _q($v)),1,-1) . "%'";
		$where= ("(nom LIKE " . join(" AND nom LIKE ", $where) . ")");
	}

	$q = sql_select("*", "spip_auteurs", "$where", "", "nom");
	include_spip('inc/selectionner_auteur');
	ajax_retour(selectionner_auteur_boucle($q, $idom));
}
?>
