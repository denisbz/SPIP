<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_BARRE")) return;
define("_INC_BARRE", "1");
global $flag_ecrire;
define('_DIR_IMG_ICONES_BARRE', ($flag_ecrire ? "../" : "")."IMG/icones_barre/");

//include_ecrire ("inc_layers.php3"); // (pour memoire)

function test_barre() {
	global $browser_name, $browser_version, $browser_rev;
	
	if ($browser_name == '') verif_butineur();

	if (
	(eregi("msie", $browser_name) AND $browser_version >= 5.5)
	|| (eregi("mozilla", $browser_name) AND $browser_version >= 5 AND $browser_rev >= 1.3)
	)
		return true;
}


function test_claret() {
	global $browser_name;
	if ( test_barre() && (eregi("msie", $browser_name)) ) return true;
}


function afficher_script_barre(){
	global $flag_ecrire, $flag_script_deja_affiche;

	if ($flag_script_deja_affiche != 1) {
		$flag_script_deja_affiche = 1;
		$ret = '<script type="text/javascript" src="'.($flag_ecrire ? "../" : "").'spip_barre.js">';
		$ret .= "</script>\n";
		return $ret;
	}
}

function bouton_barre_racc($action, $img, $help, $formulaire, $texte) {
	global $flag_ecrire;
	$champ = "document.$formulaire.$texte";
	$champhelp = "document.$formulaire.helpbox$texte";
	$retour = "<a href=\"".$action."\" class='spip_barre' tabindex='1000' title=\"".attribut_html($help)."\"";
	if (!$flag_ecrire) $retour .= " onMouseOver=\"helpline('".addslashes(attribut_html($help))."',$champhelp)\" onMouseOut=\"helpline('".attribut_html(_T('barre_aide'))."', $champhelp)\"";
	$retour .= "><img src='".
	  _DIR_IMG_ICONES_BARRE .
	  $img."' border='0' height='16' width='16' align='middle' /></a>";
	return $retour;
}

function afficher_barre($formulaire='',$texte='', $forum=false) {
	global $spip_lang, $flag_ecrire, $options, $spip_lang_right, $spip_lang_left;

	if (test_barre()) {
		$ret = afficher_script_barre();
		$champ = "document.$formulaire.$texte";
		$ret .= "<table class='spip_barre' width='100%' cellpadding='0' cellspacing='0' border='0'>";
		$ret .= "<tr width='100%' class='spip_barre'>";
		$ret .= "<td style='text-align: $spip_lang_left;' valign='middle'>";
		$col++;

		// Italique, gras, intertitres
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{','}',$champ)", "italique.png", _T('barre_italic'), $formulaire, $texte);
		$ret .= bouton_barre_racc ("javascript:barre_raccourci('{{','}}',$champ)", "gras.png", _T('barre_gras'), $formulaire, $texte);
		if (!$forum) {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n{{{','}}}\n\n',$champ)", "intertitre.png", _T('barre_intertitre'), $formulaire, $texte);
		}
		$ret .= "&nbsp;&nbsp;&nbsp;</td><td>";
		$col ++;

		// Lien hypertexte, notes de bas de page, citations
		$ret .= bouton_barre_racc ("javascript:barre_demande('[','->',']', '".addslashes(_T('barre_lien_input'))."', $champ)",
			"lien.png", _T('barre_lien'), $formulaire, $texte);
		if (!$forum) {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('[[',']]',$champ)", "notes.png", _T('barre_note'), $formulaire, $texte);
		}
		if ($forum) {
			$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td><td>";
			$col ++;
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('\n\n&lt;quote&gt;','&lt;/quote&gt;\n\n',$champ)", "quote.png", _T('barre_quote'), $formulaire, $texte);
		}

		if ($options == "avancees") {
			/*$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('[?',']',$champ)", "barre-wiki.png", "Entr&eacute;e du [?glossaire] (Wikipedia)", $formulaire, $texte);
			$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= bouton_barre_racc ("javascript:barre_tableau($champ)", "barre-tableau.png", "Ins&eacute;rer un tableau", $formulaire, $texte);*/
		}
		
		$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		$col++;

		// Insertion de caracteres difficiles a taper au clavier (guillemets, majuscules accentuees...)
		$ret .= "<td style='text-align:$spip_lang_left;' valign='middle'>";
		$col++;
		if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf" OR $spip_lang == "ar" OR $spip_lang == "es") {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&laquo;','&raquo;',$champ)", "guillemets.png", _T('barre_guillemets'), $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets_simples'), $formulaire, $texte);
		}
		else if ($spip_lang == "bg" OR $spip_lang == "de" OR $spip_lang == "pl" OR $spip_lang == "hr" OR $spip_lang == "src") {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&bdquo;','&ldquo;',$champ)", "guillemets-de.png", _T('barre_guillemets'), $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&sbquo;','&lsquo;',$champ)", "guillemets-uniques-de.png", _T('barre_guillemets_simples'), $formulaire, $texte);
		}
		else {
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&ldquo;','&rdquo;',$champ)", "guillemets-simples.png", _T('barre_guillemets'), $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_raccourci('&lsquo;','&rsquo;',$champ)", "guillemets-uniques.png", _T('barre_guillemets_simples'), $formulaire, $texte);
		}
		if ($spip_lang == "fr" OR $spip_lang == "eo" OR $spip_lang == "cpf") {
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Agrave;',$champ)", "agrave-maj.png", _T('barre_a_accent_grave'), $formulaire, $texte);
			$ret .= bouton_barre_racc ("javascript:barre_inserer('&Eacute;',$champ)", "eacute-maj.png", _T('barre_e_accent_aigu'), $formulaire, $texte);
			if ($spip_lang == "fr") {
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&oelig;',$champ)", "oelig.png", _T('barre_eo'), $formulaire, $texte);
				$ret .= bouton_barre_racc ("javascript:barre_inserer('&OElig;',$champ)", "oelig-maj.png", _T('barre_eo_maj'), $formulaire, $texte);
			}
		}
		$ret .= bouton_barre_racc ("javascript:barre_inserer('&euro;',$champ)", "euro.png", _T('barre_euro'), $formulaire, $texte);

		$ret .= "&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		$col++;

		if ($flag_ecrire) {
			$ret .= "<td style='text-align:$spip_lang_right;' valign='middle'>";
			$col++;
		//	$ret .= "&nbsp;&nbsp;&nbsp;";
			$ret .= aide("raccourcis");
			$ret .= "&nbsp;";
			$ret .= "</td>";
		}
		$ret .= "</tr>";

		// Sur les forums publics, petite barre d'aide en survol des icones
		if (!$flag_ecrire)
			$ret .= "<tr><td colspan='$col'><input disabled='disabled' type='text' name='helpbox".$texte."' size='45' maxlength='100' style='width:100%; font-size:11px; color: black; background-color: #e4e4e4; border: 0px solid #dedede;' value=\"".attribut_html(_T('barre_aide'))."\" /></td></tr>";

		$ret .= "</table>";
	}
	return $ret;
}

function afficher_claret() {
	if (test_barre()) return "onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);' ondbclick='storeCaret(this);'";
}
?>
