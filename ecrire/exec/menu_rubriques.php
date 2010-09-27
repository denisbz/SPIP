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

include_spip('inc/texte');

// http://doc.spip.org/@exec_menu_rubriques_dist
function exec_menu_rubriques_dist() {

	header("Cache-Control: no-cache, must-revalidate");

	if ($date = intval(_request('date')))
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", $date)." GMT");

	$r = gen_liste_rubriques();
	if (!$r
	AND isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !strstr($_SERVER['SERVER_SOFTWARE'],'IIS/')) {
		include_spip('inc/headers');
		header('Content-Type: text/html; charset='. $GLOBALS['meta']['charset']);
		http_status(304);
	} else {
		include_spip('inc/actions');
		$ret = menu_rubriques();
		ajax_retour($ret);
	}
}

function menu_rubriques($complet = true){
	$ret = "<li class='toutsite'><a href='".generer_url_ecrire('articles_tous')."'>"._T('info_tout_site')."</a></li>";

	if (!$complet) {
		return "<ul class='cols_1'>$ret\n</ul>\n";
	}

	if (!isset($GLOBALS['db_art_cache'])){
		gen_liste_rubriques();
	}
	$arr_low = extraire_article(0, $GLOBALS['db_art_cache']);

	$total_lignes = $i = sizeof($arr_low);

	if ($i > 0) {
		$nb_col = min(8,ceil($total_lignes / 30));
		if ($nb_col <= 1) $nb_col =  ceil($total_lignes / 10);
		foreach( $arr_low as $id_rubrique => $titre_rubrique) {
			if (autoriser('voir','rubrique',$id_rubrique)){
			  $ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i, $largeur, $image);
			  $i++;
			}
		}

		$ret = "<ul class='cols_$nb_col'>"
		  . $ret
		  . "\n</ul>\n";
	}
	else
		$ret = "<ul class='cols_1'>$ret\n</ul>\n";
	
	return $ret;
}

// http://doc.spip.org/@bandeau_rubrique
function bandeau_rubrique($id_rubrique, $titre_rubrique, $zdecal, $largeur, $image='') {
	global $spip_lang_left;

	static $zmax = 6;

	$nav = "<a href='"
	. generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique, false, true)
	. "'>"
	. supprimer_tags(preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique))
	. "</a>\n";

	// Limiter volontairement le nombre de sous-menus
	if (!(--$zmax)) {
		$zmax++;
		return "\n<li>$nav</li>";
	}

	$arr_rub = extraire_article($id_rubrique, $GLOBALS['db_art_cache']);
	$i = sizeof($arr_rub);
	if (!$i) {
		$zmax++;
		return "\n<li>$nav</li>";
	}


	if ($nb_rub = count($arr_rub)) {
		  $nb_col = min(10,max(1,ceil($nb_rub / 10)));
		  $ret_ligne = max(4,ceil($nb_rub / $nb_col));
	}
	$ret = "<li class='haschild'>$nav<ul class='cols_$nb_col'>";
	foreach( $arr_rub as $id_rub => $titre_rub) {
		if (autoriser('voir','rubrique',$id_rub)){
			$titre = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre, $zdecal+$i, $largeur);
			$i++;
		}
	}
	$ret .= "</ul></li>\n";
	$zmax++;
	return $ret;
}


// http://doc.spip.org/@extraire_article
function extraire_article($id_p, $t) {
	return array_key_exists($id_p, $t) ?  $t[$id_p]: array();
}

// http://doc.spip.org/@gen_liste_rubriques
function gen_liste_rubriques() {

	// ici, un petit fichier cache ne fait pas de mal
	$last = $GLOBALS['meta']["date_calcul_rubriques"];
	if (lire_fichier(_CACHE_RUBRIQUES, $cache)) {
		list($date,$GLOBALS['db_art_cache']) = @unserialize($cache);
		if ($date == $last) return false; // c'etait en cache :-)
	}
	// se restreindre aux rubriques utilisees recemment +secteurs

	$where = sql_in_select("id_rubrique", "id_rubrique", "spip_rubriques", "", "", "id_parent=0 DESC, date DESC", _CACHE_RUBRIQUES_MAX);

	// puis refaire la requete pour avoir l'ordre alphabetique

	$res = sql_select("id_rubrique, titre, id_parent", "spip_rubriques", $where, '', 'id_parent, 0+titre, titre');

	// il ne faut pas filtrer le autoriser voir ici
	// car on met le resultat en cache, commun a tout le monde
	$GLOBALS['db_art_cache'] = array();
	while ($r = sql_fetch($res)) {
		$t = sinon($r['titre'], _T('ecrire:info_sans_titre'));
		$GLOBALS['db_art_cache'][$r['id_parent']][$r['id_rubrique']] = supprimer_numero(typo($t));
	}

	$t = array($last ? $last : time(), $GLOBALS['db_art_cache']);
	ecrire_fichier(_CACHE_RUBRIQUES, serialize($t));
	return true;
}
?>
