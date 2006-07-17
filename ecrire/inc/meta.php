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

function lire_metas() {
	$result = spip_query("SELECT nom,valeur FROM spip_meta");
	if($GLOBALS['db_ok']) {
		$GLOBALS['meta'] = array();
		while ($row = spip_fetch_array($result))
			$GLOBALS['meta'][$row['nom']] = $row['valeur'];
	}
	if (!$GLOBALS['meta']['charset'])
		ecrire_meta('charset', _DEFAULT_CHARSET);

	if (isset($GLOBALS['noyau']))
		$GLOBALS['meta']['noyau'] = $GLOBALS['noyau'];
}

function ecrire_meta($nom, $valeur) {
	if (strlen($nom)){
		$GLOBALS['meta'][$nom] = $valeur; 
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('$nom', " . spip_abstract_quote($valeur) . " )");
	}
}

function effacer_meta($nom) {
	spip_query("DELETE FROM spip_meta WHERE nom='$nom'");
}

//
// Mettre a jour le fichier cache des metas
//
// Ne pas oublier d'appeler cette fonction apres ecrire_meta() et effacer_meta() !
//
function ecrire_metas() {

	lire_metas();

	if (is_array($GLOBALS['meta'])) {
		$ok = ecrire_fichier (_FILE_META, serialize($GLOBALS['meta']));
		if (!$ok && $GLOBALS['connect_statut'] == '0minirezo') {
			include_spip('inc/minipres');
			minipres(_T('texte_inc_meta_2'), "<h4 font color=red>"
			. _T('texte_inc_meta_1', array('fichier' => _FILE_META))
			. " <a href='". generer_url_action('test_dirs'). "'>"
			. _T('texte_inc_meta_2')
			. "</a> "
			. _T('texte_inc_meta_3',
				array('repertoire' => joli_repertoire(_DIR_TMP)))
			. "</h4>\n");
		}
	}
}

// On force lire_metas() si le cache n'a pas ete utilise
if (!isset($GLOBALS['meta']))
	lire_metas();

?>
