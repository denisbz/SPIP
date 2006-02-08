<?

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

// librairie pour parametrage plugin
//
define('_FILE_PLUGIN_CONFIG', "plugin.xml");

// besoin de inc_meta (et aussi de version mais on suppose qu'il est cahrg� par ailleurs ...)
include_ecrire ("inc_db_mysql");
include_ecrire ("inc_meta");

// lecture des sous repertoire plugin existants
function liste_plugin_files(){	
	//unset $plugin_files;
	$plugin_files=array();// tableau des repertoire de plugin
  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
		if ($handle = opendir(_DIR_PLUGINS)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if (@file_exists(_DIR_PLUGINS."$file/"._FILE_PLUGIN_CONFIG)) {
						// verif de disponibilite des infos minimu
						// nom, version, class
						$infos = plugin_get_infos($file);
						if (isset($infos['nom'])&&isset($infos['version'])&&isset($infos['class']))
							$plugin_files[]=$file; //le plugin est "valide"
					}
				}
			}
			closedir($handle);
		}
	}
	return $plugin_files;
}

//  � utiliser pour initialiser ma variable globale $plugin
function liste_plugin_actifs(){
  $meta_plugin = lire_meta('plugin');
  if (strlen($meta_plugin)>0)
		return explode(",",lire_meta('plugin')); // mieux avec un unserialize ?
	else
		return array();
}

function ecrire_plugin_actifs($plugin){

	$plugin_valides = array();
	if (is_array($plugin)){
		// charger les infos de plugin en memoire
		$infos = array();
		foreach ($plugin as $plug) {
			$infos[$plug] = plugin_get_infos($plug);
			if (!isset($infos[$plug]['erreur']))
				$plugin_valides[] = $plug;
			else
				unset($infos[$plug]);
		}
	}

	ecrire_meta('plugin',implode(",", $plugin_valides)); // mieux avec un serialize ?

	$start_file = "<"."?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\n";
	$end_file = "\n?".">";
	
	// generer les fichier 
	// charger_plugins_options.php
	// charger_plugins_fonctions.php
	foreach(array('options','fonctions') as $charge){
		$s = "";
		if (is_array($infos)){
			foreach($infos as $plug=>$info){
				if (isset($info[$charge])){
					foreach($info[$charge] as $file)
						$s .= "include_once _DIR_PLUGINS.'$plug/".trim($file)."';\n";
				}
				if ($charge=='options')
					$s .= '$GLOBALS[\'plugins\'][]=\''.$plug.'\';'."\n";
			}
		}
		$filename = _DIR_SESSIONS."charger_plugins_$charge.php";
		if ($handle = fopen($filename, 'wb')) {
			@fwrite($handle, $start_file . $s . $end_file);
			@fclose($handle);
		}
	}

	if (is_array($infos)){
		// construire tableaux de pipelines et matrices
		// $GLOBALS['spip_pipeline']
		// $GLOBALS['spip_matrice']
		foreach($infos as $plug=>$info){
			$class = trim(array_pop($info['class']));
			foreach($info['pipeline'] as $pipe){
				$nom = trim(array_pop($pipe['nom']));
				if (isset($pipe['action']))
					$action = trim(array_pop($pipe['action']));
				else
					$action = $nom;
				$GLOBALS['spip_pipeline'][$nom] .= "|$class::$action";
				if (isset($pipe['inclure'])){
					$GLOBALS['spip_matrice']["$class::$action"] = 
						"_DIR_PLUGINS$plug/".array_pop($pipe['inclure']);
				}
			}
		}
	}

	pipeline_precompile();
}

// precompilsation des pipelines
function pipeline_precompile(){
	global $spip_pipeline, $spip_matrice;
	$nouveaux_pipe=array();
	
	$start_file = "<"."?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\n";
	$end_file = "\n?".">";
	foreach($spip_pipeline as $action=>$pipeline){
		$s_inc = "";
		$s_call = "function execute_pipeline_$action(\$val){\n";
		$pipe = array_filter(explode('|',$pipeline));
		// Eclater le pipeline en filtres et appliquer chaque filtre
		foreach ($pipe as $fonc) {
			$s_call .= '$val = minipipe(\''.$fonc.'\', $val);'."\n";
			if (isset($spip_matrice[$fonc])){
				$file = $spip_matrice[$fonc];
				$s_inc .= 'include_once(';
				// si _DIR_PLUGINS est dans la chaine, on extrait la constante
				if (($p = strpos($file,'_DIR_PLUGINS'))!==FALSE){
					if ($p)
						$s_inc .= "'".substr($file,0,$p)."'.";
					$s_inc .= "_DIR_PLUGINS.";
					$s_inc .= "'".substr($file,$p+12)."'";
				}
				else
					$s_inc .= "'$file'";
				$s_inc .= ');'."\n";
			}
		}
		$s_inc .= "\n";
		$s_call .= "return \$val;\n}\n";
		$filename = _DIR_SESSIONS."charger_pipeline_$action.php";
		if ($handle = fopen($filename, 'wb')) {
			@fwrite($handle, $start_file . $s_inc . $s_call . $end_file);
			@fclose($handle);
		}
		$nouveaux_pipe[] = "charger_pipeline_$action.php";
	}

	// nettoyer les anciens fichiers pipeline obsoletes
	if ($handle = opendir(_DIR_SESSIONS)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (preg_match(",^charger_pipeline_(.*).php$,",$file)){
					if (!in_array($file,$nouveaux_pipe))
						unlink(_DIR_SESSIONS.$file);
				}
			}
		}
		closedir($handle);
	}
}

// pas sur que �a serve juste au cas o�
function liste_plugin_inactifs(){
	return array_diff (liste_plugin_files(),liste_plugin_actifs());
}

// mise � jour du meta en fonction de l'�tat du r�pertoire
// penser � faire une maj du cache =>  ecrire_meta()
// en principe cela doit aussi initialiser la valeur � vide si elle n'esite pas 
// risque de pb en php5 � cause du typage ou de null (v�rifier dans la doc php)
function verif_plugin(){
	$plugin_actifs = liste_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new);
	ecrire_metas();
}

// mise � jour des donn�es si envoi via formulaire
function enregistre_modif_plugin(){
  // recuperer les plugins dans l'ordre des $_POST
  $test = array();
	foreach(liste_plugin_files() as $file){
	  $test["statusplug_$file"] = $file;
	}
	$plugin=array();
	foreach($_POST as $choix=>$val){
	  if (isset($test[$choix])&&$val=='O')
			$plugin[]=$test[$choix];
	}
	ecrire_plugin_actifs($plugin);
	ecrire_metas();
	//echo "mise � jour ok";
}

function ordonne_plugin(){
	$liste = liste_plugin_actifs();
	$liste_triee = array();
	$i=2;
	foreach($liste as $plug){
		$index = $i;
		$i = $i+2;
		if ($_GET['monter']==$plug) $index = $index-3;
		if ($_GET['descendre']==$plug) $index = $index+3;
		$liste_triee[$index] = $plug;
	}
	ksort($liste_triee);
	ecrire_plugin_actifs($liste_triee);
	ecrire_metas();
}

function parse_plugin_xml($texte){
	$out = array();
  // enlever les commentaires
  $txt = preg_replace(',<!--(.*?)-->,is','',$texte);

	// tant qu'il y a des tags
	while(preg_match("{<([^>]*?)>}s",$txt)){
		// tag ouvrant
		$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_OFFSET_CAPTURE|PREG_SPLIT_DELIM_CAPTURE);
	
		// $before doit etre vide ou des espaces uniquements!
		$before = trim($chars[0][0]);

		if (strlen($before)>0)
			return $texte; // before non vide, donc on est dans du texte
	
		$tag = $chars[1][0];
		$txt = $chars[2][0];
	
		// tag fermant
		$chars = preg_split("{(</$tag>)}s",$txt,2,PREG_SPLIT_OFFSET_CAPTURE|PREG_SPLIT_DELIM_CAPTURE);
		if (!isset($chars[1])) { // tag fermant manquant
			$out[$tag][]="erreur : tag fermant $tag manquant::$txt"; 
			return $out;
		}
		$content = $chars[0][0];
		$txt = trim($chars[2][0]);
		$out[$tag][]=parse_plugin_xml($content);
	}
	if (count($out))
		return $out;
	else{
		return $txt;
	}
}

function chaines_lang($texte){
	// TODO : prendre en charge le fichier langue specifique du plugin
	// meme si pas encore charge
	$regexp = "|<:([^>]*):>|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		_T($regs[1]), $texte);
	return $texte;
}

// lecture du fichier de configuration d'un plugin
function plugin_get_infos($plug){
  $ret = array();
  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
		if (@file_exists(_DIR_PLUGINS."$plug/plugin.xml")) {
			$texte = file_get_contents(_DIR_PLUGINS."$plug/plugin.xml");
			$arbre = parse_plugin_xml($texte);
			if (!isset($arbre['plugin'])&&is_array($arbre['plugin']))
				$arbre = array('erreur' => array(_T('plugin:erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
		}
		else {
			// pour arriver ici on l'a vraiment cherche...
			$arbre = array('erreur' => array(_T('plugin:erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
		}

		plugin_verifie_conformite($plug,$arbre);
		
		$ret['nom'] = join(' ',$arbre['nom']);
		$ret['version'] = array_pop($arbre['version']);
		if (isset($arbre['auteur']))
			$ret['auteur'] = join(',',$arbre['auteur']);
		if (isset($arbre['description']))
			$ret['description'] = chaines_lang(join(' ',$arbre['description']));
		if (isset($arbre['lien']))
			$ret['lien'] = join(' ',$arbre['lien']);
		if (isset($arbre['options']))
			$ret['options'] = $arbre['options'];
		if (isset($arbre['fonctions']))
			$ret['fonctions'] = $arbre['fonctions'];
		$ret['class'] = $arbre['class'];
		if (isset($arbre['pipeline']))
			$ret['pipeline'] = $arbre['pipeline'];
		if (isset($arbre['erreur']))
			$ret['erreur'] = $arbre['erreur'];
	}
	return $ret;
}

function plugin_verifie_conformite($plug,&$arbre){
	$silence = false;
	if (isset($arbre['plugin'])&&is_array($arbre['plugin']))
		$arbre = end($arbre['plugin']); // derniere def plugin
	else{
		$arbre = array('erreur' => array(_T('plugin:erreur_plugin_tag_plugin_absent')." : $plug/plugin.xml"));
		$silence = true;
	}
  // verification de la conformite du plugin avec quelques
  // precautions elementaires
  if (!isset($arbre['nom'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('plugin:erreur_plugin_nom_manquant');
		$arbre['nom'] = array("");
	}
  if (!isset($arbre['version'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('plugin:erreur_plugin_version_manquant');
		$arbre['version'] = array("");
	}
  if (!isset($arbre['class'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('plugin:erreur_plugin_class_manquant');
		$arbre['class'] = array("");
	}
	else{
		$class = trim(end($arbre['class']));
		if (isset($arbre['options'])){
			foreach($arbre['options'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('plugin:erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		if (isset($arbre['fonctions'])){
			foreach($arbre['fonctions'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('plugin:erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		$fonctions = array();
		if (isset($arbre['fonctions']))
			$fonctions = $arbres['fonctions'];
	  $liste_methodes_reservees = array('__construct','__destruct','plugin','install',strtolower($class));
		foreach($arbre['pipeline'] as $pipe){
			$nom = trim(end($pipe['nom']));
			if (isset($pipe['action']))
				$action = trim(end($pipe['action']));
			else
				$action = $nom;
			// verif que la methode a un nom autorise
			if (in_array(strtolower($action),$liste_methodes_reservees)){
				if (!$silence)
					$arbre['erreur'][] = _T("plugin:erreur_plugin_nom_fonction_interdit")." : $action";
			}
			else{
				// verif que le fichier de def est bien present
				if (isset($pipe['inclure'])){
					$inclure = _DIR_PLUGINS."$plug/".end($pipe['inclure']);
					if (!@is_readable($inclure))
	  				if (!$silence)
							$arbre['erreur'][] = _T('plugin:erreur_plugin_fichier_absent')." : $inclure";
				}
			}
		}
	}
}
?>