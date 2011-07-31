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

include_spip('inc/boutons');

function definir_barre_contexte($contexte = null){
	if (is_null($contexte))
		$contexte = $_GET;
	elseif(is_string($contexte))
		$contexte = unserialize($contexte);
	if (!isset($contexte['id_rubrique']) AND isset($contexte['exec'])){
		if (!function_exists('trouver_objet_exec'))
			include_spip('inc/pipelines_ecrire');
		if ($e=trouver_objet_exec($contexte['exec'])){
			$_id = $e['id_table_objet'];
			if (isset($contexte[$_id]) AND $id=intval($contexte[$_id])){
				$table = $e['table_objet_sql'];
				$row = sql_fetsel('*',$table,"$_id=".intval($id));
				if (isset($row['id_rubrique']))
				$contexte['id_rubrique'] = $row['id_rubrique'];
			}
		}
	}
	return $contexte;
}

/**
 * definir la liste des boutons du haut et de ses sous-menus
 * On defini les boutons a metrtre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_boutons" pour que des plugins
 * puissent y mettre leur grain de sel
 *
 * @param array $contexte
 * @param bool $icones // rechercher les icones
 * @param bool $autorise // ne renvoyer que les boutons autorises
 * @return array
 */
function definir_barre_boutons($contexte=array(),$icones = true, $autorise = true) {
    include_spip('inc/autoriser');
	$boutons_admin=array();

	// les boutons du core, issus de prive/navigation.xml
	$liste_boutons = array();

	// ajouter les boutons issus des plugin via plugin.xml
	if (function_exists('boutons_plugins')
	  AND is_array($liste_boutons_plugins = boutons_plugins()))
		$liste_boutons = &$liste_boutons_plugins;

	foreach($liste_boutons as $id => $infos){
		// les boutons principaux ne sont pas soumis a autorisation
		if (!($parent = $infos['parent']) OR !$autorise OR autoriser('bouton',$id,0,NULL,array('contexte'=>$contexte))){
			if ($parent AND isset($boutons_admin[$parent])){
				if (!is_array($boutons_admin[$parent]->sousmenu))
					$boutons_admin[$parent]->sousmenu = array();
				$position = (strlen($infos['position'])?intval($infos['position']):count($boutons_admin[$parent]->sousmenu));
				$boutons_admin[$parent]->sousmenu = array_slice($boutons_admin[$parent]->sousmenu,0,$position)
				+ array($id=> new Bouton(
					($icones AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					$infos['action']?$infos['action']:null,
					$infos['parametres']?$infos['parametres']:null
					))
				+ array_slice($boutons_admin[$parent]->sousmenu,$position,100);
			}
			if (!$parent
			// provisoire, eviter les vieux boutons
			AND (!in_array($id,array('forum','statistiques_visites')))

			) {
				$position = $infos['position']?$infos['position']:count($boutons_admin);
				$boutons_admin = array_slice($boutons_admin,0,$position)
				+array($id=> new Bouton(
					($icones AND $infos['icone'])?find_in_theme($infos['icone']):'',  // icone
					$infos['titre'],	// titre
					$infos['action']?$infos['action']:null,
					$infos['parametres']?$infos['parametres']:null
					))
				+ array_slice($boutons_admin,$position,100);
			}
		}
	}

	return pipeline('ajouter_boutons', $boutons_admin);
}

/**
 * Creer l'url a partir de exec et args, sauf si c'est deja une url formatee
 *
 * @param string $url
 * @param string $args
 * @return string
 */
// http://doc.spip.org/@bandeau_creer_url
function bandeau_creer_url($url, $args="", $contexte=null){
	if (!preg_match(',[\/\?],',$url)) {
		$url = generer_url_ecrire($url,$args,true);
		// recuperer les parametres du contexte demande par l'url sous la forme
		// &truc=@machin@
		// @machin@ etant remplace par _request('machin')
		$url = str_replace('&amp;','&',$url);
		while (preg_match(",[&?]([a-z_]+)=@([a-z_]+)@,i",$url,$matches)){
			$val = _request($matches[2],$contexte);
			$url = parametre_url($url,$matches[1],$val?$val:'','&');
		}
		$url = str_replace('&','&amp;',$url);
	}
	return $url;
}


/**
 * Construire tout le bandeau superieur de l'espace prive
 *
 * @param unknown_type $rubrique
 * @param unknown_type $sous_rubrique
 * @param unknown_type $largeur
 * @return unknown
 */
function inc_bandeau_dist() {
	return recuperer_fond('prive/squelettes/inclure/barre-nav',$_GET);
}

?>
