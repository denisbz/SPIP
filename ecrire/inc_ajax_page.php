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

# gerer un charset minimaliste en convertissant tout en unicode &#xxx;

function ajax_page_dist($fonction, $id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	global $flag_ob;
	$var_nom = 'ajax_page_' . $fonction;
	if (!function_exists($var_nom))
		spip_log("fonction $var_nom indisponible");
	else {
		if ($flag_ob) {
			ob_start();
			$charset = lire_meta("charset");
		}
		@header('Content-type: text/html; charset=$charset');
		echo "<"."?xml version='1.0' encoding='$charset'?".">\n";
		$var_nom($id, $exclus, $col, $id_ajax_fonc, $type, $rac);

		if ($flag_ob) {
			$a = ob_get_contents();
			ob_end_clean();
			include_ecrire('inc_charsets.php3');
			echo charset2unicode($a, 'AUTO', true);
		}
	}
}

# Une fonction stockee en base de donnees ?

function ajax_page_sql($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	global $connect_id_auteur;
	$res = spip_query("SELECT variables FROM spip_ajax_fonc	WHERE id_ajax_fonc ='"
			  . addslashes($id_ajax_fonc) 
			  . "' AND id_auteur=$connect_id_auteur");
	if ($row = spip_fetch_array($res)) {
		
		$variables = unserialize($row["variables"]);
		while (list($i, $k) = each($variables)) {
			$$i = $k;
			
		}
		include_ecrire("inc_presentation.php3");		
		// Appliquer la fonction
		if ($fonction == "afficher_articles") {
			afficher_articles ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}

		elseif ($fonction == "afficher_articles_trad") {
			afficher_articles_trad ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}
		elseif ($fonction == "afficher_groupe_mots") {
			include_ecrire("inc_texte.php3");
			include_ecrire("inc_mots.php3");
			afficher_groupe_mots ($id_groupe);
		}
		
	}
}

function ajax_page_test($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	# tester si ca fonctionne pour ce brouteur
	// (si on arrive la c'est que c'est bon, donc poser le cookie)
	spip_setcookie('spip_accepte_ajax', 1);

}

# Un moteur de recherche ?
function ajax_page_recherche($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{

		include_ecrire("inc_texte.php3");
		$recherche = addslashes(str_replace("%","\%",$type));
		$rech2 = split("[[:space:]]+", $recherche);
		if ($rech2) {
			$where_titre = " (titre LIKE '%".join("%' AND titre LIKE '%", $rech2)."%') ";
			$where_desc = " (descriptif LIKE '%".join("%' AND descriptif LIKE '%", $rech2)."%') ";
			$where_id = " (id_rubrique = '".join("' AND id_rubrique = '", $rech2)."') ";
		}
		else {
			$where_titre = " 1=2";
			$where_desc = " 1=2";
			$where_id = " 1=2";
		}

		if ($exclus) {
			include_ecrire('inc_rubriques.php3');
			$where_exclus = " AND id_rubrique NOT IN (".calcul_branche($exclus).")";
		} else
			$where_exclus = '';

		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_id$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 3;
		}
		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_titre$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 2;
		}
		$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_desc$where_exclus");
		while ($row = spip_fetch_array($res)) {
			$id_rubrique = $row["id_rubrique"];
			$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
			$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
			$points[$id_rubrique] = $points[$id_rubrique] + 1;
		}
		
		if ($points) {
			arsort($points);
			while (list($id,$pts) = each($points)) {
				
				$id_rubrique = $id;
				$titre = $rub[$id]["titre"];
				$id_parent = $rub[$id]["id_parent"];
				
				// Eviter une premiere fois d'afficher la rubrique exclue
					if ($id_parent == 0) $style = "style='background-image: url(" . _DIR_IMG_PACK . "secteur-12.gif)'";
					else $style = "";
					$onClick = " aff_selection('rubrique','" .
					  htmlentities($rac) .
					  "','$id_rubrique');";
	
					$ret .= "<div class='pashighlight' onClick=\"changerhighlight(this); $onClick\"><div class='arial11 petite-rubrique'$style>";
					$ret .= "&nbsp; $titre";
					$ret .= "</div></div>";
			}
				
		}
		if ($ret)
			echo $ret;
		else
			echo "<div style='padding: 5px; color: red;'><b>"
			.htmlentities($type)
			."</b> :  "._T('avis_aucun_resultat')."</div>";

}

	# afficher un mini-navigateur de rubriques

function ajax_page_aff_rubrique($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_mini_nav.php");
		echo mini_nav ($id, "choix_parent", "this.form.id_rubrique.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", $exclus);

}

# afficher les sous-rubriques d'une rubrique (composant du mini-navigateur)

function ajax_page_aff_rub($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_mini_nav.php");
		echo mini_afficher_rubrique ($id, 
					     htmlentities($rac),
					     "", $col, $exclus);
}

	# petit moteur de recherche sur les rubriques

function ajax_page_aff_nav_recherche($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_mini_nav.php");
	echo mini_nav ($id, "aff_nav_recherche", "document.location.href='naviguer.php3?id_rubrique=::sel::'", 0, true);
}

# Affiche les infos d'une rubrique selectionnee dans le mini navigateur

function ajax_page_aff_info($id, $exclus, $col, $id_ajax_fonc, $type, $rac)
{
  global $couleur_foncee,$spip_display,$spip_lang_right ;
		include_ecrire("inc_texte.php3");
		if ($type == "rubrique") {
			$res = spip_query("SELECT titre, descriptif FROM spip_rubriques WHERE id_rubrique = $id");
			if ($row = spip_fetch_array($res)) {
				$titre = typo($row["titre"]);
				$descriptif = propre($row["descriptif"]);
			} else {
				$titre = _T('info_racine_site');
			}
		} else
			$titre = '';
		
		$rac = htmlentities($rac);
		echo "<div style='display: none;'>";
		echo "<input type='text' id='".$rac."_sel' value='$id' />";
		echo "<input type='text' id='".$rac."_sel2' value=\"".entites_html($titre)."\" />";
		echo "</div>";

		include_ecrire ("inc_logos.php3");


		echo "<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>";
		if ($type == "rubrique" AND $spip_display != 1 AND $spip_display!=4 AND lire_meta('image_process') != "non") {
			include_ecrire("inc_logos.php3");
			$logo = decrire_logo("rubon$id");
			if ($logo) {
				$fichier = $logo[0];
					echo  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>";
					echo reduire_image_logo(_DIR_IMG.$fichier, 100, 48);
					echo "</div>";
			}
		}

		echo "<div><p><b>$titre</b></p></div>";
		if (strlen($descriptif) > 0) echo "<div>$descriptif</div>";

		echo "</div>";

}
?>
