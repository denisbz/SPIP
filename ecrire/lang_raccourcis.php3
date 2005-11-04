<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/



include ("inc.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_calendrier.php");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_layer.php3");
include_ecrire("inc_rubriques.php3");



if (!$module) $module = "public";


debut_page(_T('module_fichier_langue').": $module", "administration", "langues");

echo "<br><br><br>";
gros_titre(_T('module_fichier_langue').": $module");

barre_onglets("config_lang", "fichiers");



debut_gauche();
	
$modules = array();

if (!$d = @opendir(_DIR_LANG)) return;
while ($f = readdir($d)) {
	if (ereg('^([a-z_]+)\.php3?$', $f, $regs))
		$nom_module = $regs[1];
		if (!ereg('^(spip|ecrire)\_', $nom_module) && ereg("^([a-zA-Z]+)\_".$spip_lang."$", $nom_module, $reps))
			$modules[] = $reps[1];
}
closedir($d);

if (count($modules) > 1) {
	echo debut_cadre_relief();
	echo "<div class='verdana3' style='background-color: $couleur_foncee; color: white; padding: 3px;'><b>"._T('module_fichiers_langues').":</b></div><br>\n";

	reset($modules);
	while (list(, $nom_module) = each($modules)) {
		if ($nom_module == $module) echo "<div style='padding-$spip_lang_left: 10px;' class='verdana3'><b>$nom_module</b></div>";
		else echo "<div style='padding-$spip_lang_left: 10px;' class='verdana3'><a href='lang_raccourcis.php3?module=$nom_module'>$nom_module</a></div>";
	}
	echo fin_cadre_relief();
}


debut_droite();


function afficher_raccourcis($module = "public") {
	global $spip_lang;
	global $couleur_foncee;
	
	$lang = $module.'_'.$spip_lang;
	$fichier_lang = $lang.'.php3';

	if (@file_exists(_DIR_LANG . $fichier_lang)) {
		$GLOBALS['idx_lang'] = 'i18n_' . $lang;
		include_lang($fichier_lang);
	
		$tableau = $GLOBALS['i18n_' . $lang];
		ksort($tableau);
		
		if ($module != "public" AND $module != "local") $aff_nom_module = "$module:";
		
		echo "<div class='arial2'>"._T('module_texte_explicatif')."</div>";
		echo "<div>&nbsp;</div>";

		if (!$d = @opendir(_DIR_LANG)) return;
		while ($f = readdir($d)) {
			if (ereg("^".$module."\_([a-z_]+)\.php3?$", $f, $regs))
				$langue_module[$regs[1]] = traduire_nom_langue($regs[1]);
		}
		if ($langue_module) {
			ksort($langue_module);
			echo "<div class='arial2'>"._T('module_texte_traduction', array('module' => $module));
			echo " ".join(", ", $langue_module).".";
			echo "</div><div>&nbsp;</div>";
		}
		
		closedir($d);
		
		echo "<table cellpadding='3' cellspacing='1' border='0'>";
		echo "<tr bgcolor='$couleur_foncee' style='color:white;'><td class='verdana1'><b>"._T('module_raccourci')."</b></td><td class='verdana2'><b>"._T('module_texte_affiche')."</b></td></tr>\n";
	
		for (reset($tableau); $raccourci = key($tableau); next($tableau)) {
			if ($i == 1) {
				$i = 0;
				$bgcolor = '#eeeeee';	
			} else {
				$i = 1;
				$bgcolor= 'white';
			}
		
			$texte  = pos($tableau);
			echo "<tr bgcolor='$bgcolor'><td class='verdana2'><b><:$aff_nom_module$raccourci:></b></td><td class='arial2'>$texte</td></tr>\n";
		}
		echo "</table>";
	} 
}


afficher_raccourcis($module);


fin_page();

?>

