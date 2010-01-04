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

define('_VERSION_ARCHIVE', '1.3');

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('inc/presentation');
include_spip('public/interfaces'); // pour table_jointures

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	@include_once ($f); 
}

if (@is_readable(_CACHE_PLUGINS_FCT)){
	// chargement optimise precompile
	include_once(_CACHE_PLUGINS_FCT);
}

// par defaut tout est exporte sauf les tables ci-dessous

global $EXPORT_tables_noexport;

$EXPORT_tables_noexport= array(
	'spip_caches', // plugin invalideur
	'spip_resultats', // resultats de recherche ... c'est un cache !
	'spip_referers',
	'spip_referers_articles',
	'spip_visites',
	'spip_visites_articles',
	'spip_versions', // le dump des fragments n'est pas robuste
	'spip_versions_fragments' // le dump des fragments n'est pas robuste
	);

if (!$GLOBALS['connect_toutes_rubriques']){
	$EXPORT_tables_noexport[]='spip_messages';
	$EXPORT_tables_noexport[]='spip_auteurs_messages';
}

//var_dump($EXPORT_tables_noexport);
$EXPORT_tables_noexport = pipeline('lister_tables_noexport',$EXPORT_tables_noexport);


// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist()
{
	$rub = intval(_request('id_parent'));
	$meta = "status_dump_"
	  . ($rub ? ($rub .'_') : '')  
	  . $GLOBALS['visiteur_session']['id_auteur'];

	if (!isset($GLOBALS['meta'][$meta]))
		echo exec_export_all_args($rub, _request('gz'));
	else {
		$export = charger_fonction('export', 'inc');
		if (!$export($meta))
			echo exec_export_all_args($rub, _request('gz'));
	}
}

// L'en tete du fichier doit etre cree a partir de l'espace public
// Ici on construit la liste des tables pour confirmation.
// Envoi automatique en cas d'inaction (sauf si appel incorrect $nom=NULL)

function exec_export_all_args($rub, $gz)
{
	$gz = $gz ? '.gz' : '';
	$nom = $gz 
	?  _request('znom_sauvegarde') 
	:  _request('nom_sauvegarde');
	if (!preg_match(',^[\w_][\w_.]*$,', $nom)) $nom = 'dump';
	$archive = $nom . '.xml' . $gz;
	list($tables,) = export_all_list_tables();
	$clic =  _T('bouton_valider');
	$plie = _T('install_tables_base');
	$res = controle_tables_en_base('export', $tables, $rub);
	$res = "\n<ol style='text-align:left'><li>\n" .
			join("</li>\n<li>", $res) .
			"</li></ol>\n";

	$res = block_parfois_visible('export_tables', $plie, $res, '', false)
	. "<div style='text-align: center;'><input type='submit' value='"
	. $clic
	. "' /></div>";

  	$arg = "start,$gz,$archive,$rub," .  _VERSION_ARCHIVE;
	$id = 'form_export';
	$att = " method='post' id='$id'";
	$timeout = 'if (manuel) document.getElementById(manuel).submit()';
	$corps = (($nom !== NULL)
	? http_script("manuel= '$id'; window.setTimeout('$timeout', 60000);")
	: '')
	. generer_action_auteur('export_all', $arg, '', $res,  $att, true);
	include_spip('inc/presentation');
	$r = envoi_link('spip', true);
	$r =  f_jQuery($r);
	include_spip('inc/minipres');
	$res = minipres(_T('info_sauvegarde'), $corps);
	return str_replace('</head>', $r . '</head>', $res);
}

// construction de la liste des tables pour le dump :
// toutes les tables principales
// + toutes les tables auxiliaires hors relations
// + les tables relations dont les deux tables liees sont dans la liste

// http://doc.spip.org/@export_all_list_tables
function export_all_list_tables()
{
	$tables_for_dump = array();
	$tables_pointees = array();
	global $EXPORT_tables_noexport;
	global $tables_principales;
	global $tables_auxiliaires;
	global $tables_jointures;

// on construit un index des tables de liens
// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table => $liste_relations)
		if (is_array($liste_relations))
		{
			$nom = $table;
			if (!isset($tables_auxiliaires[$nom])&&!isset($tables_principales[$nom]))
				$nom = "spip_$table";
			if (isset($tables_auxiliaires[$nom])||isset($tables_principales[$nom])){
				foreach($liste_relations as $link_table){
					if (isset($tables_auxiliaires[$link_table])/*||isset($tables_principales[$link_table])*/){
						$tables_for_link[$link_table][] = $nom;
					}
					else if (isset($tables_auxiliaires["spip_$link_table"])/*||isset($tables_principales["spip_$link_table"])*/){
						$tables_for_link["spip_$link_table"][] = $nom;
					}
				}
			}
		}
	
	$liste_tables = array_merge(array_keys($tables_principales),array_keys($tables_auxiliaires));
	foreach($liste_tables as $table){
	  //		$name = preg_replace("{^spip_}","",$table);
	  if (		!isset($tables_pointees[$table]) 
	  		&&	!in_array($table,$EXPORT_tables_noexport) 
	  		&&	!isset($tables_for_link[$table])){
			$tables_for_dump[] = $table;
			$tables_pointees[$table] = 1;
		}
	}
	foreach ($tables_for_link as $link_table =>$liste){
		$connecte = true;
		foreach($liste as $connect_table)
			if (!in_array($connect_table,$tables_for_dump))
				$connecte = false;
		if ($connecte)
			# on ajoute les liaisons en premier
			# si une restauration est interrompue,
			# cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	return array($tables_for_dump, $tables_for_link);
}

// Fabrique la liste a cocher des tables presentes

function controle_tables_en_base($name, $check, $rub)
{
	$p = '/^' . $GLOBALS['table_prefix'] . '/';
	$res = $check;
	foreach(sql_alltable() as $t) {
		$t = preg_replace($p, 'spip', $t);
		if (!in_array($t, $check)) $res[]= $t;
	}

	$rub = $rub ? " <= "  : '';
	foreach ($res as $k => $t) {

		$c = "type='checkbox'"
		. (in_array($t, $check) ? " checked='checked'" : '')
		. " onclick='manuel=false'";

		$res[$k] = "<input $c value='$t' id='$name_$t' name='$name"
			. "[]' />\n"
			. $t
			. " ($rub"
			.  sql_countsel($t)
	  		. ")";
	}
	return $res;
}
?>
