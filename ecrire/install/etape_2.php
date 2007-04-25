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

function install_etape_2_dist()
{
	global $adresse_db, $login_db, $pass_db, $spip_lang_right, $chmod, $table_prefix;
	if (is_null($table_prefix)) {
		$table_prefix = 'spip';
	}

	echo install_debut_html();

	// prenons toutes les dispositions possibles pour que rien ne s'affiche !
	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	if (($db_connect=="0") && $link){

	echo "<p class='resultat'><b>"._T('info_connexion_ok')."</b></p>";
	echo info_etape(_T('info_choix_base')." "._T('menu_aide_installation_choix_base').aide ("install2"));

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();


	$checked = '';
	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		$res = "<label for='choix_db'><b>"._T('texte_choix_base_2')."</b><br />"._T('texte_choix_base_3')."</label>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<li>\n<input name=\"choix_db\" value=\"".$table_nom."\" type='radio' id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label>\n</li>";
			if ($table_nom == $login_db) {
				$bases = "$base checked='checked'$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		$res = "<ul>".$bases."</ul><p>"._T('info_ou')." ";
	}
	else {
		$res = "<b>"._T('avis_lecture_noms_bases_1')."</b>
		"._T('avis_lecture_noms_bases_2')."<p>";
		if ($login_db) {
			// Si un login comporte un point, le nom de la base est plus
			// probablement le login sans le point -- testons pour savoir
			$test_base = $login_db;
			$ok = @mysql_select_db($test_base);
			$test_base2 = str_replace('.', '_', $test_base);
			if (@mysql_select_db($test_base2)) {
				$test_base = $test_base2;
				$ok = true;
			}
			
			if ($ok) {
				$res = _T('avis_lecture_noms_bases_3')
				. "<ul>"
				. "<li><input name=\"choix_db\" value=\"".$test_base."\" type='radio' id='stand' checked='checked' />"
				. "<label for='stand'>".$test_base."</label></li>\n"
				. "</ul>"
				. "<p>"._T('info_ou')." ";
				$checked = true;
			}
		}
	}

	echo generer_post_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='3' />"
	. "\n<input type='hidden' name='chmod' value='$chmod' />"
	. "\n<input type='hidden' name='adresse_db'  value=\"$adresse_db\" />"
	. "\n<input type='hidden' name='login_db' value=\"$login_db\" />"
	. "\n<input type='hidden' name='pass_db' value=\"$pass_db\" />"
	. "\n<fieldset><legend>"._T('texte_choix_base_1')."</legend>\n"
	. $res    
	. "\n<input name=\"choix_db\" value=\"new_spip\" type='radio' id='nou'"
	. ($checked  ? '' : " checked='checked'")
	. " />\n<label for='nou'>"._T('info_creer_base')."</label></p>\n<p>"
	. "\n<input type='text' name='table_new' class='fondl' value=\"spip\" size='20' /></p></fieldset>\n"
	. "<fieldset><legend>"._T('texte_choix_table_prefix')."</legend>\n"
	. "<p><label for='table_prefix'>"._T('info_table_prefix')."</label></p><p>"
	. "\n<input type='text' id='table_prefix' name='table_prefix' class='fondl' value='" .
		$table_prefix . "' size='20' /></p></fieldset>"
	. bouton_suivant()));
	}
	else {
		echo info_etape(_T('info_connexion_base'));
		echo "<p class='resultat'><b>"._T('avis_connexion_echec_1')."</b></p>";
		echo "<p>"._T('avis_connexion_echec_2')."</p>";
		echo "<p style='font-size: small;'>"._T('avis_connexion_echec_3')."</p>";
	}
	
	echo info_progression_etape(2,'etape_','install/');
	echo install_fin_html();
}

?>
