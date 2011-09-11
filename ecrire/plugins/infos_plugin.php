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

// lecture d'un texte ecrit en pseudo-xml issu d'un fichier plugin.xml
// et conversion approximative en tableau PHP.
function plugins_infos_plugin($desc, $plug='', $dir_plugins=_DIR_PLUGINS) {
	include_spip('inc/xml');
	$arbre = spip_xml_parse($desc);

	$verifie_conformite = charger_fonction('verifie_conformite','plugins');
	$verifie_conformite($plug, $arbre, $dir_plugins);

	include_spip('inc/charsets');

	if (isset($arbre['categorie']))
		$ret['categorie'] = trim(spip_xml_aplatit($arbre['categorie']));
	if (isset($arbre['nom']))
		$ret['nom'] = charset2unicode(spip_xml_aplatit($arbre['nom']));
	if (isset($arbre['icon']))
		$ret['logo'] = trim(spip_xml_aplatit($arbre['icon']));
	if (isset($arbre['auteur']))
		$ret['auteur'][] = trim(spip_xml_aplatit($arbre['auteur'])); // garder le 1er niveau en tableau mais traiter le multi possible
	if (isset($arbre['licence']))
		$ret['licence'] = trim(spip_xml_aplatit($arbre['licence']));
	if (isset($arbre['version']))
		$ret['version'] = trim(spip_xml_aplatit($arbre['version']));
	if (isset($arbre['version_base']))
		$ret['schema'] = trim(spip_xml_aplatit($arbre['version_base']));
	if (isset($arbre['etat']))
		$ret['etat'] = trim(spip_xml_aplatit($arbre['etat']));

	$ret['description'] = $ret['slogan'] = "";
	if (isset($arbre['slogan']))
		$ret['slogan'] = trim(spip_xml_aplatit($arbre['slogan']));
	if (isset($arbre['description'])){
		$ret['description'] = trim(spip_xml_aplatit($arbre['description']));
	}

	if (isset($arbre['lien'])){
		$ret['documentation'] = trim(join(' ',$arbre['lien']));
		if ($ret['documentation']) {
			// le lien de doc doit etre une url et c'est tout
			if (!preg_match(',^https?://,iS', $ret['documentation']))
				$ret['documentation'] = "";
		}
	}

	if (isset($arbre['options']))
		$ret['options'] = $arbre['options'];
	if (isset($arbre['fonctions']))
		$ret['fonctions'] = $arbre['fonctions'];
	if (isset($arbre['prefix'][0]))
		$ret['prefix'] = trim(array_pop($arbre['prefix']));
	if (isset($arbre['install']))
		$ret['install'] = $arbre['install'];
	if (isset($arbre['meta']))
		$ret['meta'] = trim(spip_xml_aplatit($arbre['meta']));

	$necessite = info_plugin_normalise_necessite($arbre['necessite']);
	$ret['compatibilite'] = isset($necessite['compatible'])?$necessite['compatible']:'';
	$ret['necessite'] = $necessite['necessite'];
	$ret['lib'] = $necessite['lib'];
	$ret['utilise'] = info_plugin_normalise_utilise($arbre['utilise']);
	$ret['procure'] = $arbre['procure'];

	$ret['chemin'] = $arbre['path'];
	if (isset($arbre['pipeline']))
		$ret['pipeline'] = $arbre['pipeline'];

	$extraire_boutons = charger_fonction('extraire_boutons','plugins');
	$les_boutons = $extraire_boutons($arbre);
	$ret['menu'] = $les_boutons['bouton'];
	$ret['onglet'] = $les_boutons['onglet'];

	$ret['traduire'] = $arbre['traduire'];
		
	if (isset($arbre['config']))
		$ret['config'] = spip_xml_aplatit($arbre['config']);
	if (isset($arbre['noisette']))
		$ret['noisette'] = $arbre['noisette'];

	if (isset($arbre['erreur'])) {
		$ret['erreur'] = $arbre['erreur'];
		if ($plug) spip_log("infos_plugin $plug " . @join(' ', $arbre['erreur']));
	}

	return $ret;
}
// Un attribut de nom "id" a une signification particuliere en XML
// qui ne correspond pas a l'utilissation qu'en font les plugin.xml
// Pour eviter de complexifier la lecture de paquet.xml
// qui n'est pour rien dans cette bevue, on doublonne l'information
// sous les deux index "nom" et "id" dans l'arbre de syntaxe abstraite
// pour compatibilite, mais seul le premier est disponible quand on lit
// un paquet.xml, "id" devant etre considere comme obsolete

function info_plugin_normalise_necessite($necessite) {
	$res = array('necessite' => array(), 'lib' => array());

	if (is_array($necessite)) {
		foreach($necessite as $need) {
			$id = $need['id'];
			$v = $need['version'];
			
			// Necessite SPIP version x ?
			if (strtoupper($id)=='SPIP') {
				$res['compatible'] = $v;
			} else if (preg_match(',^lib:\s*([^\s]*),i', $id, $r)) {
				$res['lib'][] = array('nom' => $r[1], 'id' => $r[1], 'lien' => $need['src']);
			} else $res['necessite'][] = array('id' => $id, 'nom' => $id, 'version' => $v);
		}
	}
	
	return $res;
}

function info_plugin_normalise_utilise($utilise) {
	$res = array();

	if (is_array($utilise)) {
		foreach($utilise as $need){
			$id = $need['id'];
		$res[]= array('nom' => $id, 'id' => $id, 'version' => $need['version']);
		}
	}
	return $res;
}

?>
