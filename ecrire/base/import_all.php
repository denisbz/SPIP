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

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('inc/meta');

// par defaut tout est importe sauf les tables ci-dessous
// possibiliter de definir cela tables via la meta
global $IMPORT_tables_noimport;
if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
	$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
	if (!is_array($IMPORT_tables_noimport)){
		ecrire_meta('IMPORT_tables_noimport',serialize(array()),'non');
		$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
	}
}
else{
	include_spip('inc/meta');
	ecrire_meta('IMPORT_tables_noimport',
		serialize($IMPORT_tables_noimport),'non');
}

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}

// http://doc.spip.org/@base_import_all_dist
function base_import_all_dist($titre, $reprise=false)
{
	if (!$reprise) import_all_debut();

	$request = unserialize($GLOBALS['meta']['import_all']);
	// au rappel, on commence (voire on continue)
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	// utiliser une version fraiche des metas (ie pas le cache)
	lire_metas();
	include_spip('inc/import');
	@ignore_user_abort(1);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, "accueil", "accueil");

	echo debut_gauche('', true);

	echo debut_droite('', true);
	
	$res = import_all_milieu($request);

	if (!$res AND $request['insertion'] == 'on') {
			$request['insertion'] = 'passe2';
			if ($request['url_site']
			AND substr($request['url_site'],-1) != '/')
				$request['url_site'] .= '/';
			ecrire_meta("import_all", serialize($request),'non');
			import_all_debut();
			$res = import_all_milieu($request);
	}
 
	echo $res, "</body></html>\n";

	if ($charset = $GLOBALS['meta']['charset_restauration']) {
			ecrire_meta('charset', $charset);
	}

	detruit_restaurateur();
	import_all_fin($request);
	include_spip('inc/rubriques');
	calculer_rubriques();

	if (!$res) ecrire_acces();	// Mise a jour du fichier htpasswd
}

// http://doc.spip.org/@import_all_milieu
function import_all_milieu($request)
{
	global $trans;
	if ($request['insertion'] == 'passe2') {
		include_spip('inc/import_insere');
		$trans = translate_init($request);
	} else $trans = array();

	return import_tables($request, $request['dir']);
}

// http://doc.spip.org/@import_all_debut
function import_all_debut() {
	ecrire_meta("status_restauration", "0",'non');
}

// http://doc.spip.org/@import_all_fin
function import_all_fin($request) {

	effacer_meta("charset_restauration");
	effacer_meta("charset_insertion");
	effacer_meta("status_restauration");
	effacer_meta("date_optimisation");
	effacer_meta('version_archive_restauration');
	effacer_meta('tag_archive_restauration');
	effacer_meta('restauration_charset_sql_connexion');
	if ($request['insertion'] == 'passe2') 
		spip_query("DROP TABLE spip_translate");
	 
}
?>
