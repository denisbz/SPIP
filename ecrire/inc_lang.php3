<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LANG")) return;
define("_ECRIRE_INC_LANG", "1");


//
// Ecrire un fichier cache langue
//
function ecrire_cache_lang($lang, $module) {
	include_ecrire('inc_filtres.php3');

	$fichier_lang = $module.'_'.$lang.'.php3';
	if ($t = @fopen('CACHE/lang_'.$fichier_lang.'_'.@getmypid(), "wb")) {
		@fwrite($t, "<"."?php\n\n// Ceci est le CACHE d'un fichier langue spip\n\n");
		if (is_array($cache = $GLOBALS['cache_lang'][$lang])) {
			@fwrite($t, "\$GLOBALS[\$GLOBALS['idx_lang']] = array(\n");
			$texte = '';
			ksort($cache);
			reset($cache);
			while (list($code, ) = each($cache))
				$texte .= ",\n\t'".$code."' => '".texte_script($GLOBALS['i18n_'.$module.'_'.$lang][$code])."'";
			@fwrite($t, substr($texte,2)."\n);\n\n");
			@fwrite($t, "\$GLOBALS['cache_lang']['$lang'] = array(\n");
			$texte = '';
			reset($cache);
			while (list($code, ) = each($cache))
				$texte .= ",\n\t'".$code."' => 1";
			@fwrite($t, substr($texte,2)."\n);\n\n");
		}
		@fwrite($t, "\n\n?".">\n");
		@fclose($t);
		@rename('CACHE/lang_'.$fichier_lang.'_'.@getmypid(), 'CACHE/lang_'.$fichier_lang);
	}
}

function ecrire_caches_langues() {
	global $cache_lang_modifs;
	reset($cache_lang_modifs);
	while(list($module,$cache_module) = each($cache_lang_modifs))
		while(list($lang, ) = each($cache_module))
			ecrire_cache_lang($lang, $module);
}

//
// Charger un fichier langue
//
function charger_langue($lang, $module = 'spip', $forcer = false) {
	global $dir_ecrire, $flag_ecrire;

	$fichier_lang = 'lang/'.$module.'_'.$lang.'.php3';
	$fichier_lang_exists = @file_exists($dir_ecrire.$fichier_lang);

	// chercher dans le fichier cache ?
	if (!$flag_ecrire AND $fichier_lang_exists) {
		if (!$forcer AND @file_exists('CACHE/lang_'.$module.'_'.$lang.'.php3')
		AND (@filemtime('CACHE/lang_'.$module.'_'.$lang.'.php3') > @filemtime('ecrire/lang/'.$module.'_'.$lang.'.php3'))
		AND (@filemtime('CACHE/lang_'.$module.'_'.$lang.'.php3') > @filemtime('ecrire/lang/perso.php3'))) {
			$GLOBALS['idx_lang'] = 'i18n_'.$module.'_'.$lang;
			return include_local('CACHE/lang_'.$module.'_'.$lang.'.php3');
		}
		else $GLOBALS['cache_lang_modifs'][$module][$lang] = true;
	}

	if ($fichier_lang_exists) {
		$GLOBALS['idx_lang']='i18n_'.$module.'_'.$lang;
		include_ecrire ($fichier_lang);
	} else {
		// si le fichier de langue du module n'existe pas, on se rabat sur
		// le francais, qui *par definition* doit exister, et on copie le
		// tableau 'fr' dans la var liee a la langue
		$fichier_lang = 'lang/'.$module.'_fr.php3';
		if (@file_exists($dir_ecrire.$fichier_lang)) {
			$GLOBALS['idx_lang']='i18n_'.$module.'_fr';
			include_ecrire ($fichier_lang);
		}
		$GLOBALS['i18n_'.$module.'_'.$lang] = $GLOBALS['i18n_'.$module.'_fr'];
	}

	// surcharge perso
	if (@file_exists($dir_ecrire.'lang/perso.php3')) {
		include($dir_ecrire.'lang/perso.php3');
	}

}

//
// Changer la langue courante
//
function changer_langue($lang) {
	global $all_langs, $spip_lang_rtl, $spip_lang_right, $spip_lang_left, $spip_lang_dir, $spip_dir_lang, $flag_ecrire;

	$liste_langues = $all_langs.','.lire_meta('langues_multilingue');

 	if ($lang && ereg(",$lang,", ",$liste_langues,")) {
		$GLOBALS['spip_lang'] = $lang;

		$spip_lang_rtl =   lang_dir($lang, '', '_rtl');
		$spip_lang_left =  lang_dir($lang, 'left', 'right');
		$spip_lang_right = lang_dir($lang, 'right', 'left');
		$spip_lang_dir =   lang_dir($lang);
		$spip_dir_lang = " dir='$spip_lang_dir'";

		return true;
	}
	else
		return false;
}

//
// Regler la langue courante selon les infos envoyees par le brouteur
//
function regler_langue_navigateur() {
	global $HTTP_SERVER_VARS, $HTTP_COOKIE_VARS;

	$accept_langs = explode(',', $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE']);
	if (is_array($accept_langs)) {
		while(list(, $s) = each($accept_langs)) {
			if (eregi('^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$', trim($s), $r)) {
				$lang = strtolower($r[1]);
				if (changer_langue($lang)) return $lang;
			}
		}
	}
	return false;
}


//
// Traduire une chaine internationalisee
//
function traduire_chaine($code, $args) {
	global $spip_lang, $flag_ecrire;
	global $cache_lang;

	// liste des modules a parcourir
	$modules = array('spip');
	if (strpos($code, ':')) {
		if (ereg("^([a-z/]+):(.*)$", $code, $regs)) {
			$modules = explode("/",$regs[1]);
			$code = $regs[2];
		}
	}

	// parcourir tous les modules jusqu'a ce qu'on trouve
	while (!$text AND (list(,$module) = each ($modules))) {
		$var = "i18n_".$module."_".$spip_lang;
		if (!$GLOBALS[$var]) charger_langue($spip_lang, $module);
		if (!$flag_ecrire) {
			if (!isset($GLOBALS[$var][$code]))
				charger_langue($spip_lang, $module, $code);
			if (isset($GLOBALS[$var][$code]))
				$cache_lang[$spip_lang][$code] = 1;
		}
		$text = $GLOBALS[$var][$code];
	}

	// langues pas finies ou en retard (eh oui, c'est moche...)
	if ($spip_lang<>'fr') {
		$text = ereg_replace("^<(NEW|MODIF)>","",$text);
		if (!$text) {
			$spip_lang_temp = $spip_lang;
			$spip_lang = 'fr';
			$text = traduire_chaine($code, $args);
			$spip_lang = $spip_lang_temp;
		}
	}

	// inserer les variables
	if (!$args) return $text;
	while (list($name, $value) = each($args))
		$text = str_replace ("@$name@", $value, $text);
	return $text;
}


function traduire_nom_langue($lang) {
	$r = $GLOBALS['codes_langues'][$lang];
	if (!$r) $r = $lang;
	return $r;
}

function init_codes_langues() {
	$GLOBALS['codes_langues'] = array(
	'aa' => "Afar",
	'ab' => "Abkhazian",
	'af' => "Afrikaans",
	'am' => "Amharic",
	'ar' => "&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;",
	'as' => "Assamese",
	'ay' => "Aymara",
	'az' => "&#1040;&#1079;&#1241;&#1088;&#1073;&#1072;&#1112;&#1209;&#1072;&#1085;",
	'ba' => "Bashkir",
	'be' => "&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;",
	'bg' => "&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;",
	'bh' => "Bihari",
	'bi' => "Bislama",
	'bn' => "Bengali; Bangla",
	'bo' => "Tibetan",
	'br' => "breton",
	'ca' => "catal&#224;",
	'co' => "corsu",
	'cpf' => "Kr&eacute;ol r&eacute;yon&eacute;",
	'cpf_dom' => "Krey&ograve;l",
	'cs' => "&#269;e&#353;tina",
	'cy' => "Welsh",
	'da' => "dansk",
	'de' => "Deutsch",
	'dz' => "Bhutani",
	'el' => "&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;",
	'en' => "English",
	'eo' => "Esperanto",
	'es' => "Espa&#241;ol",
	'es_co' => "Colombiano",
	'et' => "eesti",
	'eu' => "euskara",
	'fa' => "&#1601;&#1575;&#1585;&#1587;&#1609;",
	'fi' => "suomi",
	'fj' => "Fiji",
	'fo' => "f&#248;royskt",
	'fr' => "fran&#231;ais",
	'fr_tu' => "fran&#231;ais copain",
	'fy' => "Frisian",
	'ga' => "Irish",
	'gd' => "Scots Gaelic",
	'gl' => "galego",
	'gn' => "Guarani",
	'gu' => "Gujarati",
	'ha' => "Hausa",
	'he' => "&#1506;&#1489;&#1512;&#1497;&#1514;",
	'hi' => "&#2361;&#2367;&#2306;&#2342;&#2368;",
	'hr' => "hrvatski",
	'hu' => "magyar",
	'hy' => "Armenian",
	'ia' => "Interlingua",
	'id' => "Bahasa Indonesia",
	'ie' => "Interlingue",
	'ik' => "Inupiak",
	'is' => "&#237;slenska",
	'it' => "italiano",
	'iu' => "Inuktitut",
	'ja' => "&#26085;&#26412;&#35486;",
	'jw' => "Javanese",
	'ka' => "&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;",
	'kk' => "&#1178;&#1072;&#1079;&#1072;&#1097;b",
	'kl' => "Greenlandic",
	'km' => "Cambodian",
	'kn' => "Kannada",
	'ko' => "&#54620;&#44397;&#50612;",
	'ks' => "Kashmiri",
	'ku' => "Kurdish",
	'ky' => "Kirghiz",
	'la' => "Latin",
	'ln' => "Lingala",
	'lo' => "Laothian",
	'lt' => "lietuvi&#371;",
	'lv' => "latvie&#353;u",
	'mg' => "Malagasy",
	'mi' => "Maori",
	'mk' => "&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;",
	'ml' => "Malayalam",
	'mn' => "Mongolian",
	'mo' => "Moldavian",
	'mr' => "&#2350;&#2352;&#2366;&#2336;&#2368;",
	'ms' => "Bahasa Malaysia",
	'mt' => "Maltese",
	'my' => "Burmese",
	'na' => "Nauru",
	'ne' => "Nepali",
	'nl' => "Nederlands",
	'no' => "norsk",
	'nb' => "norsk bokm&aring;l",
	'nn' => "norsk nynorsk",
	'oc_lnc' => "&ograve;c lengadocian",
	'oc_ni' => "&ograve;c ni&ccedil;ard",
	'oc_prv' => "&ograve;c proven&ccedil;au",
	'oc_gsc' => "&ograve;c gascon",
	'oc_lms' => "&ograve;c lemosin",
	'oc_auv' => "&ograve;c auvernhat",
	'oc_va' => "&ograve;c vivaroaupenc",
	'om' => "(Afan) Oromo",
	'or' => "Oriya",
	'pa' => "Punjabi",
	'pl' => "polski",
	'ps' => "Pashto, Pushto",
	'pt' => "Portugu&#234;s",
	'qu' => "Quechua",
	'rm' => "Rhaeto-Romance",
	'rn' => "Kirundi",
	'ro' => "rom&#226;n&#259;",
	'ru' => "&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;",
	'rw' => "Kinyarwanda",
	'sa' => "&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;",
	'sd' => "Sindhi",
	'sg' => "Sangho",
	'sh' => "srpskohrvastski",
	'sh_lat' => 'srpskohrvastski',
	'sh_cyr' => '&#1057;&#1088;&#1087;&#1089;&#1082;&#1086;&#1093;&#1088;&#1074;&#1072;&#1090;&#1089;&#1082;&#1080;',
	'si' => "Sinhalese",
	'sk' => "sloven&#269;ina",	// (Slovakia)
	'sl' => "sloven&#353;&#269;ina",	// (Slovenia)
	'sm' => "Samoan",
	'sn' => "Shona",
	'so' => "Somali",
	'sq' => "shqipe",
	'sr' => "&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;",
	'ss' => "Siswati",
	'st' => "Sesotho",
	'su' => "Sundanese",
	'sv' => "svenska",
	'sw' => "Kiswahili",
	'ta' => "&#2980;&#2990;&#3007;&#2996;&#3021; - tamil",
	'te' => "Telugu",
	'tg' => "Tajik",
	'th' => "&#3652;&#3607;&#3618;",
	'ti' => "Tigrinya",
	'tk' => "Turkmen",
	'tl' => "Tagalog",
	'tn' => "Setswana",
	'to' => "Tonga",
	'tr' => "T&#252;rk&#231;e",
	'ts' => "Tsonga",
	'tt' => "&#1058;&#1072;&#1090;&#1072;&#1088;",
	'tw' => "Twi",
	'ug' => "Uighur",
	'uk' => "&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1100;&#1089;&#1082;&#1072;",
	'ur' => "&#1649;&#1585;&#1583;&#1608;",
	'uz' => "U'zbek",
	'vi' => "Ti&#7871;ng Vi&#7879;t",
	'vo' => "Volapuk",
	'wo' => "Wolof",
	'xh' => "Xhosa",
	'yi' => "Yiddish",
	'yor' => "Yoruba",
	'za' => "Zhuang",
	'zh' => "&#20013;&#25991;",
	'zu' => "Zulu");
}

//
// Filtres de langue
//

// afficher 'gaucher' si la langue est arabe, hebreu, persan, 'droitier' sinon
// utilise par #LANG_DIR, #LANG_LEFT, #LANG_RIGHT
function lang_dir($lang, $droitier='ltr', $gaucher='rtl') {
	if ($lang=='fa' OR $lang=='ar' OR $lang == 'he')
		return $gaucher;
	else
		return $droitier;
}

function lang_typo($lang) {
	if ($lang == 'eo' OR $lang == 'fr' OR substr($lang, 0, 3) == 'fr_' OR $lang == 'cpf')
		return 'fr';
	else if ($lang)
		return 'en';
	else
		return false;
}

// service pour que l'espace prive reflete la typo et la direction des objets affiches
function changer_typo($lang = '', $source = '') {
	global $lang_typo, $lang_dir, $dir_lang;

	if (ereg("^(article|rubrique|breve|auteur)([0-9]+)", $source, $regs)) {
		$r = spip_fetch_array(spip_query("SELECT lang FROM spip_".$regs[1]."s WHERE id_".$regs[1]."=".$regs[2]));
		$lang = $r['lang'];
	}

	if (!$lang)
		$lang = lire_meta('langue_site');

	$lang_typo = lang_typo($lang);
	$lang_dir = lang_dir($lang);
	$dir_lang = " dir='$lang_dir'";
}

// selectionner une langue
function lang_select ($lang='') {
	global $pile_langues, $spip_lang;
	php3_array_push($pile_langues, $spip_lang);
	changer_langue($lang);
}

// revenir a la langue precedente
function lang_dselect ($rien='') {
	global $pile_langues;
	changer_langue(php3_array_pop($pile_langues));
}


//
// Afficher un menu de selection de langue / var_lang = langue interface privee, var_multi = langue de l'article
//
function menu_langues($nom_select = 'changer_lang', $default = '', $texte = '', $herit = '') {
	global $couleur_foncee, $couleur_claire, $flag_ecrire;

	if ($default == '')
		$default = $GLOBALS['spip_lang'];

	if ($nom_select == 'changer_lang')
		$langues = explode(',', $GLOBALS['all_langs']);
	else
		$langues = explode(',', lire_meta('langues_multilingue'));

	if (count($langues) <= 1) return;

	if (!$couleur_foncee) $couleur_foncee = '#044476';

	$lien = $GLOBALS['clean_link'];
	if ($flag_ecrire) {
		$lien = rawurlencode('ecrire/'.$lien->getUrl());
		$dir = '../';
	} else {
		$lien = rawurlencode($lien->getUrl());
		$dir = '';
	}

	$ret = "<form action='${dir}spip_cookie.php3' method='post' style='margin:0px; padding:0px;'>";
	$ret .= "<input type='hidden' name='url' value='$lien'>";
	$ret .= $texte;

	if (!$flag_ecrire)
		$style = "class='forml' style='vertical-align: top; margin-bottom: 5px; width: 120px;'";
	else if ($nom_select == 'changer_lang') 
		$style = "class='verdana1' style='background-color: $couleur_claire; color: black;'";
	else
		$style = "class='fondl'";

	$ret .= "\n<select name='$nom_select' $style onChange=\"document.location.href='${dir}spip_cookie.php3?url=$lien&$nom_select='+this.options[this.selectedIndex].value\">\n";

	sort($langues);
	while (list(, $l) = each ($langues)) {
		if ($l == $default) {
			$selected = ' selected';
		}
		else {
			$selected = '';
		}
		if ($l == $herit) {
			$ret .= "<option class='maj-debut' style='font-weight: bold;' value='herit'$selected>"
				.traduire_nom_langue($herit)." ("._T('info_multi_herit').")</option>\n";
		}
		else $ret .= "<option class='maj-debut' value='$l'$selected>".traduire_nom_langue($l)."</option>\n";
	}
	$ret .= "</select>\n";
	$ret .= "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='&gt;&gt;' class='spip_bouton' $style></noscript>";
	$ret .= "</form>";
	return $ret;
}

// menu dans l'espace public
function gerer_menu_langues() {
	global $var_lang;
	if ($var_lang) {
		if (changer_langue($var_lang)) {
			spip_setcookie('spip_lang_public', $var_lang, time() + 24 * 3600);
		}
	}
}

//
// Selection de langue haut niveau
//
function utiliser_langue_site() {
	changer_langue($GLOBALS['langue_site']);
}

function utiliser_langue_visiteur() {
	global $HTTP_COOKIE_VARS, $flag_ecrire;

	if (!regler_langue_navigateur())
		utiliser_langue_site();

	if ($GLOBALS['auteur_session']['lang'])
		changer_langue($GLOBALS['auteur_session']['lang']);

	if (!$flag_ecrire AND $cookie_lang = $HTTP_COOKIE_VARS['spip_lang'])
		changer_langue($cookie_lang);

}

//
// Initialisation
//
function init_langues() {
	global $all_langs, $flag_ecrire, $langue_site, $cache_lang, $cache_lang_modifs;
	global $pile_langues, $lang_typo, $lang_dir, $dir_ecrire;

	$all_langs = lire_meta('langues_proposees');
	$langue_site = lire_meta('langue_site');
	$cache_lang = array();
	$cache_lang_modifs = array();
	$pile_langues = array();
	$lang_typo = '';
	$lang_dir = '';

	$toutes_langs = Array();
	if (!$all_langs || !$langue_site || $flag_ecrire) {
		if (!$d = @opendir($dir_ecrire.'lang')) return;
		while ($f = readdir($d)) {
			if (ereg('^spip_([a-z_]+)\.php3?$', $f, $regs))
				$toutes_langs[] = $regs[1];
		}
		closedir($d);
		sort($toutes_langs);
		$all_langs2 = join(',', $toutes_langs);

		// Si les langues n'ont pas change, ne rien faire
		if ($all_langs2 != $all_langs) {
			$all_langs = $all_langs2;
			if (!$langue_site) {
				// Initialisation : le francais par defaut, sinon la premiere langue trouvee
				if (ereg(',fr,', ",$all_langs,")) $langue_site = 'fr';
				else list(, $langue_site) = each($toutes_langs);
				if (defined("_ECRIRE_INC_META"))
					ecrire_meta('langue_site', $langue_site);
			}
			if (defined("_ECRIRE_INC_META")) {
				ecrire_meta('langues_proposees', $all_langs);
				ecrire_metas();
			}
		}
	}
	init_codes_langues();
}

init_langues();
utiliser_langue_site();


//
// array_push et array_pop pour php3 (a virer si on n'a pas besoin de la compatibilite php3
// et a passer dans inc_version si on a besoin de ces fonctions ailleurs qu'ici)
//
/*
 * Avertissement : Cette librairie de fonctions PHP est distribuee avec l'espoir
 * qu'elle sera utile, mais elle l'est SANS AUCUNE GARANTIE; sans meme la garantie de
 * COMMERCIALISATION ou d'UTILITE POUR UN BUT QUELCONQUE.
 * Elle est librement redistribuable tant que la presente licence, ainsi que les credits des
 * auteurs respectifs de chaque fonctions sont laisses ensembles.
 * En aucun cas, Nexen.net ne pourra etre tenu responsable de quelques consequences que ce soit
 * de l'utilisation ou la mesutilisation de ces fonctions PHP.
 */
/****
 * Titre : array_push() et array_pop() pour PHP3
 * Auteur : Cedric Fronteau
 * Email : charlie@nexen.net
 * Url :
 * Description : Implementation de array_push() et array_pop pour PHP3
****/
// Le code qui suit est encore un peu trop leger. Y a personne pour le coder en Java (ou en Flash) ?
function php3_array_push(&$stack,$value){
	if (!is_array($stack))
		return FALSE;
	end($stack);
	do {
		$k = key($stack);
		if (is_long($k));
			break;
	} while(prev($stack));

	if (is_long($k))
		$stack[$k+1] = $value;
	else
		$stack[0] = $value;
	return count($stack);
}

function php3_array_pop(&$stack){
	if (!is_array($stack) || count($stack) == 0)
		return NULL;
	end($stack);
	$v = current($stack);
	$k = key($stack);
	unset($stack[$k]);
	return $v;
}

?>
