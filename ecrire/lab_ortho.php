<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_ORTHO")) return;
define("_ECRIRE_INC_ORTHO", "1");


// Mettre a jour la liste locale des miroirs
function maj_miroirs_ortho() {
	$liste = explode(" ", lire_meta("liste_miroirs_ortho"));
	$miroirs_old = array();
	foreach ($liste as $index) {
		list($url) = explode(" ", lire_meta("miroir_ortho_$index"));
		$miroirs_old[$url] = $index;
	}

	// TODO: recuperer la liste dynamiquement depuis ortho.spip.net
	$urls = array(
		'http://tony.ortho.spip.net/ortho_serveur.php',
		'http://spip-ortho.linagora.org:18080/ortho_serveur.php',
		'http://ortho.spip.net/ortho_serveur.php'
	);
	$liste = array();
	$miroirs_new = array();
	$index = 1;
	foreach ($urls as $url) {
		if ($index_old = $miroirs_old[$url]) {
			$s = lire_meta("miroir_ortho_$index_old");
		}
		else {
			$s = $url." ".time();
		}
		$miroirs_new[$index] = $s;
		$liste[] = $index++;
	}
	foreach ($miroirs_old as $index) {
		effacer_meta("miroir_ortho_$index");
	}
	foreach ($miroirs_new as $index => $s) {
		ecrire_meta("miroir_ortho_$index", $s);
	}
	ecrire_meta("liste_miroirs_ortho", join(" ", $liste));
}

// Lire la liste des miroirs et les langues associees
function lire_miroirs_ortho() {
	global $miroirs_ortho, $index_miroirs_ortho, $duree_cache_miroirs_ortho;

	$miroirs_ortho = array();
	$index_miroirs_ortho = array();

	$t = time();
	$maj = lire_meta("maj_miroirs_ortho");
	if ($maj < $t - $duree_cache_miroirs_ortho) {
		maj_miroirs_ortho();
		ecrire_meta("maj_miroirs_ortho", $t);
		lire_metas();
	}

	$liste = explode(" ", lire_meta("liste_miroirs_ortho"));
	foreach ($liste as $index) {
		$s = explode(" ", lire_meta("miroir_ortho_$index"));
		$url = $s[0];
		$maj = $s[1];
		$langs = explode(",", $s[2]);
		// Reinitialiser periodiquement la liste des langues non-supportees
		if ($maj < $t - $duree_cache_miroirs_ortho) {
			foreach ($langs as $key => $lang) {
				if (substr($lang, 0, 1) == '!') unset($langs[$key]);
			}
			$s[1] = $t;
			$s[2] = join(",", $langs);
			ecrire_meta("miroir_ortho_$index", join(" ", $s));
		}
		$index_miroirs_ortho[$url] = $index;
		$miroirs_ortho[$url] = array();
		foreach ($langs as $lang) {
			if ($lang) $miroirs_ortho[$url][$lang] = $lang;
		}
	}
	lire_metas();
	srand(time());
}

// Sauvegarder les infos de langues pour le miroir
function ecrire_miroir_ortho($url, $langs) {
	global $index_miroirs_ortho;

	$index = $index_miroirs_ortho[$url];
	$s = explode(" ", lire_meta("miroir_ortho_$index"));
	$s[2] = join(",", $langs);
	ecrire_meta("miroir_ortho_$index", join(" ", $s));
}

function ajouter_langue_miroir($url, $lang) {
	global $miroirs_ortho;
	$langs = $miroirs_ortho[$url];
	$langs[$lang] = $lang;
	unset($langs["!$lang"]);
	ecrire_miroir_ortho($url, $langs);
}

function enlever_langue_miroir($url, $lang) {
	global $miroirs_ortho;
	$langs = $miroirs_ortho[$url];
	unset($langs[$lang]);
	$langs["!$lang"] = "!$lang";
	ecrire_miroir_ortho($url, $langs);
}

function reset_miroir($url) {
	global $miroirs_ortho;
	ecrire_miroir_ortho($url, array());
}

//
// Renvoie la liste des miroirs utilisables pour une langue donnee
//
function chercher_miroirs_ortho($lang) {
	global $miroirs_ortho;
	
	$result = array();
	$chercher = true;
	foreach ($miroirs_ortho as $url => $langs) {
		if ($langs[$lang]) {
			$result[] = $url;
		}
		else if ($chercher && !$langs["!$lang"]) {
			//echo "test $lang $url<br />";
			if (verifier_langue_miroir($url, $lang)) $result[] = $url;
			// Ne recuperer la langue d'un miroir qu'une seule fois par requete
			if ($result) $chercher = false;
		}
	}
	return $result;
}

function choisir_miroirs_ortho($lang) {
	$liste = chercher_miroirs_ortho($lang);
	if (!count($liste)) return false;
	foreach ($liste as $url) {
		$miroirs[md5(rand().$url)] = $url;
	}
	ksort($miroirs);
	return $miroirs;
}

//
// Envoyer une requete a un serveur d'orthographe
//
function post_ortho($url, $texte, $lang) {
	$errno = $errstr = "";

	$t = parse_url($url);
	if ($t['scheme'] != 'http') return false;
	$host = $t['host'];
	$port = $t['port'] ? $t['port'] : 80;
	$path = $t['path'] ? $t['path'] : "/";

	// Envoyer la requete en POST
	$f = @fsockopen($host, $port, $errno, $errstr, 2);
	if (!$f) return false;
	fputs($f, "POST $path HTTP/1.0\r\n");

	// Si le texte est petit, l'overhead du multipart est dispendieux
	if (!$GLOBALS['flag_gz'] || strlen($texte) < 200) {
		$gz = false;
		$body = "op=spell&lang=".urlencode($lang)."&texte=".urlencode($texte);
		fputs($f, "Content-Type: application/x-www-form-urlencoded\r\n");
	}
	// Sinon, on passe en multipart pour compresser la chaine a corriger
	else {
		// Il faut eliminer les caracteres 0 sinon PHP ne lit pas la suite du parametre
		// passe en multipart/form-data (gros hack bien sale)
		$texte_gz = gzcompress($texte);
		for ($echap = 255; $echap > 0; $echap--) {
			$str_echap = chr($echap ^ 1).chr($echap).chr($echap).chr($echap ^ 2);
			if (!is_int(strpos($texte_gz, $str_echap))) break;
		}
		$texte_gz = str_replace("\x00", $str_echap, $texte_gz);
		$gz = true;
		$vars = array('op' => 'spell', 'lang' => $lang, 'texte' => $texte_gz, 'gz' => 1, 'nul_echap' => $str_echap);
		$boundary = substr(md5(rand().'ortho'), 0, 8);
		$body = '';
		foreach ($vars as $key => $val) {
			$body .= "\r\n--$boundary\r\n";
			$body .= "Content-Disposition: form-data; name=\"$key\"\r\n";
			$body .= "\r\n";
			$body .= $val;
		}
		$body .= "\r\n--$boundary\r\n";
		fputs($f, "Content-Type: multipart/form-data; boundary=$boundary\r\n");
	}

	// On envoie le contenu
	fputs($f, "Content-Length: ".strlen($body)."\r\n");
	fputs($f, "Host: $host\r\n\r\n");
	fputs($f, $body);

	// Lire les en-tetes HTTP de la reponse et decoder le Content-Length
	$length = 0;
	$s = fgets($f, 1000);
	$statut = 0;
	if (preg_match(',^HTTP/\d+\.\d+ (\d+) ,', $s, $r))
		$statut = intval($r[1]);
	if ($statut != 200) {
		fclose($f);
		return false;
	}
	while ($s = trim(fgets($f, 1000))) {
		if (preg_match(',Content-Length:(.*),i', $s, $r))
			$length = intval($r[1]);
	}
	$r = "";

	// Lire le corps de la reponse HTTP
	if ($length) {
		while (($l = strlen($r)) < $length) $r .= fread($f, $length - $l);
	}
	else while (!feof($f) AND $r .= fread($f, 1024));

	fclose($f);
	if ($gz) $r = gzuncompress($r);
	return $r;
}

//
// Verifier si un serveur gere une langue donnee
//
function verifier_langue_miroir($url, $lang) {
	// Envoyer une requete bidon
	$result = post_ortho($url, " ", $lang);
	if (!preg_match(',<ortho>.*</ortho>,s', $result)) {
		reset_miroir($url);
		return false;
	}
	if (!preg_match(',<erreur>.*<code>E_LANG_ABSENT</code>.*</erreur>,s', $result)) {
		ajouter_langue_miroir($url, $lang);
		return true;
	}
	enlever_langue_miroir($url, $lang);
	return false;
}


//
// Gestion du dictionnaire local
//
function suggerer_dico_ortho(&$mots, $lang) {
	$lang = addslashes($lang);
	$query = "SELECT mot FROM spip_ortho_dico WHERE lang='$lang' ".
		"AND mot IN ('".join("', '", array_map('addslashes', $mots))."')";
	$result = spip_query($query);

	$mots = array_flip($mots);
	$bons = array();
	if (isset($mots[''])) unset($mots['']);
	while ($row = spip_fetch_array($result)) {
		$mot = $row['mot'];
		if (isset($mots[$mot])) {
			unset($mots[$mot]);
			$bons[] = $mot;
		}
	}

	if (count($mots)) $mots = array_flip($mots);
	else $mots = array();
	return $bons;
}

function ajouter_dico_ortho($mot, $lang) {
	global $connect_id_auteur;

	$lang = addslashes($lang);
	$mot = addslashes($mot);
	$id_auteur = intval($connect_id_auteur);
	$query = "INSERT IGNORE INTO spip_ortho_dico (lang, mot, id_auteur) ".
		"VALUES ('$lang', '$mot', '$id_auteur')";
	spip_query($query);
}

function supprimer_dico_ortho($mot, $lang) {
	$lang = addslashes($lang);
	$mot = addslashes($mot);
	$query = "DELETE FROM spip_ortho_dico WHERE lang='$lang' AND mot='$mot'";
	spip_query($query);
}

function gerer_dico_ortho($lang) {
	global $ajout_ortho, $supp_ortho;
	if ($mot = strval($ajout_ortho)) {
		ajouter_dico_ortho($mot, $lang);
	}
	if ($mot = strval($supp_ortho)) {
		supprimer_dico_ortho($mot, $lang);
	}
}


//
// Gestion du cache de corrections
//
function suggerer_cache_ortho(&$mots, $lang) {
	global $duree_cache_ortho;

	$lang = addslashes($lang);
	$query = "SELECT mot, ok, suggest FROM spip_ortho_cache WHERE lang='$lang' ".
		"AND mot IN ('".join("', '", array_map('addslashes', $mots))."') ".
		"AND maj > FROM_UNIXTIME(".(time() - $duree_cache_ortho).")";
	$result = spip_query($query);
	
	$mots = array_flip($mots);
	$suggest = array();
	if (isset($mots[''])) unset($mots['']);
	while ($row = spip_fetch_array($result)) {
		$mot = $row['mot'];
		if (isset($mots[$mot])) {
			unset($mots[$mot]);
			if (!$row['ok']) {
				if (strlen($row['suggest']))
					$suggest[$mot] = explode(",", $row['suggest']);
				else
					$suggest[$mot] = array();
			}
		}
	}
	if (count($mots)) $mots = array_flip($mots);
	else $mots = array();
	return $suggest;
}

function ajouter_cache_ortho($tous, $mauvais, $lang) {
	global $duree_cache_ortho;

	$values = array();
	$lang = addslashes($lang);
	if (count($mauvais)) {
		foreach ($mauvais as $mot => $suggest) {
			$values[] = "('$lang', '".addslashes($mot)."', 0, '".addslashes(join(",", $suggest))."')";
		}
	}
	if (count($tous)) {
		foreach ($tous as $mot) {
			if (!isset($mauvais[$mot]))
				$values[] = "('$lang', '".addslashes($mot)."', 1, '')";
		}
	}
	if (count($values)) {
		$query = "DELETE FROM spip_ortho_cache ".
			"WHERE maj < FROM_UNIXTIME(".(time() - $duree_cache_ortho).")";
		spip_query($query);
		$query = "INSERT IGNORE INTO spip_ortho_cache (lang, mot, ok, suggest) ".
			"VALUES ".join(", ", $values);
		spip_query($query);
	}
}


//
// Cette fonction doit etre appelee pour reecrire le texte en utf-8 "propre"
//
function preparer_ortho($texte, $lang) {
	include_spip("charsets.php");

	$charset = lire_meta('charset');

	if ($charset == 'utf-8')
		return unicode_to_utf_8(html2unicode($texte));
	else
		return unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));
}

function afficher_ortho($texte) {
	$charset = lire_meta('charset');
	if ($charset == 'utf-8') return $texte;

	if (!is_array($texte)) return charset2unicode($texte, 'utf-8');
	foreach ($texte as $key => $val) {
		$texte[$key] = afficher_ortho($val);
	}
	return $texte;
}

//
// Cette fonction envoie le texte prepare a un serveur d'orthographe
// et retourne un tableau de mots mal orthographies associes chacun a un tableau de mots suggeres
//
function corriger_ortho($texte, $lang, $charset = 'AUTO') {
	include_spip("charsets.php");
	include_spip("index.php");
	include_spip("filtres.php");

	$texte = preg_replace(',<code>.*?</code>,is', '', $texte);
	$texte = preg_replace(',<cadre>.*?</cadre>,is', '', $texte);
	$texte = preg_replace(',\[([^][]*)->([^][]*)\],is', '\\1', $texte);
	$texte = supprimer_tags($texte);

	$texte = " ".$texte." ";
	
	// Virer les caracteres non-alphanumeriques
	if (test_pcre_unicode()) {
		$texte = preg_replace(',[^-\''.pcre_lettres_unicode().']+,us', ' ', $texte);
	}
	else {
		// Ici bidouilles si PCRE en mode UTF-8 ne fonctionne pas correctement ...
		// Guillemets francais (plage iso-latin)
		$trans = array(chr(194).chr(171) => ' ', chr(194).chr(187) => ' ');
		$texte = strtr($texte, $trans);
		// Saloperies non-conformes
		$trans = array(chr(194).chr(133) => ' ', chr(194).chr(150) => ' ', chr(194).chr(151) => ' ');
		$texte = strtr($texte, $trans);
		// Poncutation etendue (unicode)
		$texte = preg_replace(",".plage_punct_unicode().",", ' ', $texte);
		// Caracteres ASCII non-alphanumeriques
		$texte = preg_replace(",[^-a-zA-Z0-9\x80-\xFF']+,", ' ', $texte);
	}
	$texte = preg_replace(',\s[-\']+,', ' ', $texte); // tirets de typo
	$texte = preg_replace(',\'\s,', ' ', $texte); // apostrophes utilisees comme guillemets
	$texte = preg_replace(',\s+,', ' ', $texte);

	// Virer les mots contenant au moins un chiffre
	$texte = preg_replace(', ([^ ]*\d[^ ]* )+,', ' ', $texte);

	// Melanger les mots
	$mots = preg_split(',\s+,u', $texte);
	sort($mots);
	$mots = array_unique($mots);

	// 1. Enlever les mots du dico local
	$bons = suggerer_dico_ortho($mots, $lang);

	// 2. Enlever les mots du cache local
	$result_cache = suggerer_cache_ortho($mots, $lang);

	// 3. Envoyer les mots restants a un serveur
	$mauvais = array();
	if (count($mots)) {
		$texte = join(' ', $mots);
		
		// Hack : ligatures en francais pas gerees par aspell
		unset($trans_rev);
		$texte_envoi = $texte;
		if ($lang == 'fr') {
			$trans = array(chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe', 
					chr(195).chr(134) => 'AE', chr(195).chr(166) => 'ae');
			$texte_envoi = strtr($texte_envoi, $trans);
			$trans_rev = array_flip($trans);
		}
		
		// POST de la requete et recuperation du resultat XML
		$urls = choisir_miroirs_ortho($lang);
		if (!$urls) return false;
		$ok = '';
		$erreur = false;
		foreach ($urls as $url) {
			$xml = post_ortho($url, $texte_envoi, $lang);
			if ($xml && preg_match(',<ortho>(.*)</ortho>,s', $xml, $r)) {
				$xml = $r[1];
				if (preg_match(',<erreur>.*<code>(.*)</code>.*</erreur>,s', $xml, $r)) 
					$erreur = $r[1];
				if (preg_match(',<ok>(.*)</ok>,s', $xml, $r))
					$ok = $r[1];
				if ($ok) break;
			}
			reset_miroir($url);
		}
		if (!$ok) return $erreur;

		// Remplir le tableau des resultats (mots mal orthographies)
		if ($trans_rev) {
			$assoc_mots = array_flip($mots);
		}
		while (preg_match(',<mot>(.*?)</mot>(\s*<suggest>(.*?)</suggest>)?,s', $ok, $r)) {
			$p = strpos($ok, $r[0]);
			$ok = substr($ok, $p + strlen($r[0]));
			$mot = $r[1];
			if ($suggest = $r[3]) 
				$s = preg_split('/[\s,]+/', $suggest);
			else 
				$s = array();
			// Hack ligatures
			if ($trans_rev) {
				$mot_rev = strtr($mot, $trans_rev);
				if ($mot != $mot_rev) {
					if ($assoc_mots[$mot]) 
						$mauvais[$mot] = $s;
					if ($assoc_mots[$mot_rev]) 
						$mauvais[$mot_rev] = $s;
				}
				else $mauvais[$mot] = $s;
			}
			else $mauvais[$mot] = $s;
		}
	}
	if (!$erreur) ajouter_cache_ortho($mots, $mauvais, $lang);

	// Retour a l'envoyeur
	$mauvais = array_merge($result_cache, $mauvais);
	$result = array(
		'bons' => $bons,
		'mauvais' => $mauvais
	);
	if ($erreur) $result['erreur'] = $erreur;
	return $result;
}

//
// Fonctions d'affichage HTML
//

function panneau_ortho($ortho_result) {
	global $id_suggest;

	$id_suggest = array();
	$i = 1;

	$mauvais = $ortho_result['mauvais'];
	$bons = $ortho_result['bons'];
	if (!count($mauvais) && !count($bons)) return;
	ksort($mauvais);

	echo "<script type='text/javascript'><!--
	var curr_suggest = null;
	function suggest(id) {
		var menu_box;
		if (curr_suggest)
			document.getElementById('suggest' + curr_suggest).className = 'suggest-inactif';
		if (1 || id!=curr_suggest) {
			document.getElementById('suggest' + id).className = 'suggest-actif';
			curr_suggest = id;
		}
		else curr_suggest = null;
		menu_box = document.getElementById('select_ortho');
		if (menu_box.length > id) menu_box.selectedIndex = id;
	}";
	echo "//--></script>";

	echo "<form class='form-ortho verdana2' action='' method='get'>\n";
	echo "<select name='select_ortho' id='select_ortho' onChange='suggest(this.selectedIndex);'>\n";
	echo "<option value='0'>... "._T('ortho_mots_a_corriger')." ...</option>\n";
	foreach ($mauvais as $mot => $suggest) {
		$id = $id_suggest[$mot] = "$i";
		$i++;
		$mot_html = afficher_ortho($mot);
		echo "<option value='$id'>$mot_html</option>\n";
	}
	foreach ($bons as $mot) {
		$id = $id_suggest[$mot] = "$i";
		$i++;
	}
	echo "</select>\n";
	echo "</form>\n";
	// Mots mal orthographies :
	// liste des suggestions plus lien pour ajouter au dico
	foreach ($mauvais as $mot => $suggest) {
		$id = $id_suggest[$mot];
		$mot_html = afficher_ortho($mot);
		echo "<div class='suggest-inactif' id='suggest$id'>";
		echo "<span class='ortho'>$mot_html</span>\n";
		echo "<div class='detail'>\n";
		if (is_array($suggest) && count($suggest)) {
			echo "<ul>\n";
			$i = 0;
			foreach ($suggest as $sug) {
				if (++$i > 12) {
					echo "<li><i>(...)</i></li>\n";
					break;
				}
				echo "<li>".afficher_ortho(typo($sug))."</li>\n";
			}
			echo "</ul>\n";
		}
		else {
			echo "<i>"._T('ortho_aucune_suggestion')."</i>";
		}
		echo "<br />";
		$link = new Link;
		$link->delVar('supp_ortho');
		$link->addVar('ajout_ortho', $mot);
		icone_horizontale(_T('ortho_ajouter_ce_mot'), $link->getUrl(), "ortho-24.gif", "creer.gif");
		echo "</div>\n";
		echo "</div>\n\n";
	}
	// Mots trouves dans le dico :
	// message plus lien pour retirer du dico
	foreach ($bons as $mot) {
		$id = $id_suggest[$mot];
		$mot_html = afficher_ortho($mot);
		echo "<div class='suggest-inactif' id='suggest$id'>";
		echo "<span class='ortho-dico'>$mot_html</span>";
		echo "<div class='detail'>\n";
		echo "<i>"._T('ortho_ce_mot_connu')."</i>";
		echo "<br />";
		$link = new Link;
		$link->delVar('ajout_ortho');
		$link->addVar('supp_ortho', $mot);
		icone_horizontale(_T('ortho_supprimer_ce_mot'), $link->getUrl(), "ortho-24.gif", "supprimer.gif");
		echo "</div>\n";
		echo "</div>\n";
	}
}

function souligner_ortho($texte, $lang, $ortho_result) {
	global $id_suggest;
	$vu = array();

	$mauvais = $ortho_result['mauvais'];
	$bons = $ortho_result['bons'];

	$texte = " ".$texte." ";
	// Chercher et remplacer les mots un par un
	$t0 = explode(" ", microtime());
	$delim = '[^-\''.pcre_lettres_unicode().']';
	foreach ($mauvais as $mot => $suggest) {
		$pattern = ",$delim".$mot."$delim,us";
		// Recuperer les occurences du mot dans le texte
		if (preg_match_all($pattern, $texte, $regs, PREG_SET_ORDER)) {
			$id = $id_suggest[$mot];
			$mot_html = afficher_ortho($mot);
			foreach ($regs as $r) {
				if ($vu[$cherche = $r[0]]) continue;
				$vu[$cherche] = 1;
				$html = "<a class='ortho' onclick=\"suggest($id);return false;\" href=''>$mot_html</a>";
				$remplace = str_replace($mot, $html, $cherche);
				$table = explode($cherche, $texte);
				unset($avant);
				$texte = '';
				foreach ($table as $s) {
					if (!isset($avant)) {
						$avant = $s;
						continue;
					}
					$ok = true;
					$texte .= $avant;
					if (is_int($deb_tag = strrpos($texte, '<'))) {
						if (strrpos($texte, '>') <= $deb_tag)
							$ok = false;
					}
					if ($ok) {
						$texte .= $remplace;
					}
					else {
						$texte .= $cherche;
					}
					$avant = $s;
				}
				$texte .= $avant;
			}
		}
	}
	foreach ($bons as $mot) {
		$pattern = ",$delim".$mot."$delim,us";
		// Recuperer les occurences du mot dans le texte
		if (preg_match_all($pattern, $texte, $regs, PREG_SET_ORDER)) {
			$id = $id_suggest[$mot];
			$mot_html = afficher_ortho($mot);
			foreach ($regs as $r) {
				if ($vu[$cherche = $r[0]]) continue;
				$vu[$cherche] = 1;
				$html = "<a class='ortho-dico' onclick=\"suggest($id);return false;\" href=''>$mot_html</a>";
				$remplace = str_replace($mot, $html, $cherche);
				$table = explode($cherche, $texte);
				unset($avant);
				$texte = '';
				foreach ($table as $s) {
					if (!isset($avant)) {
						$avant = $s;
						continue;
					}
					$ok = true;
					$texte .= $avant;
					if (is_int($deb_tag = strrpos($texte, '<'))) {
						if (strrpos($texte, '>') <= $deb_tag)
							$ok = false;
					}
					if ($ok) {
						$texte .= $remplace;
					}
					else {
						$texte .= $cherche;
					}
					$avant = $s;
				}
				$texte .= $avant;
			}
		}
	}
	$t1 = explode(" ", microtime());
	$dt = floor(1000 * ($t1[0] + $t1[1] - $t0[0] - $t0[1])) / 1000;
	//echo "<div style='font-weight: bold; color: red;'>$dt s.</div>";
	
	$texte = preg_replace(',(^ | $),', '', $texte);
	$texte = afficher_ortho($texte);
	return $texte;
}

function init_ortho() {
	global $duree_cache_ortho, $duree_cache_miroirs_ortho;
 
 	$duree_cache_ortho = 7 * 24 * 3600;
	$duree_cache_miroirs_ortho = 24 * 3600;
	lire_miroirs_ortho();
}

init_ortho();

?>
