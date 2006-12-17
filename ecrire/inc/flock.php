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

// http://doc.spip.org/@spip_file_get_contents
function spip_file_get_contents ($fichier) {
	if (substr($fichier, -3) != '.gz') {
		if (function_exists('file_get_contents')
		AND os_serveur != 'windows') # windows retourne ''
			return @file_get_contents ($fichier);
		else
			return join('', @file($fichier));
	} else
			return join('', @gzfile($fichier));
}

// options = array(
// 'phpcheck' => 'oui' # verifier qu'on a bien du php
// dezippe automatiquement les fichiers .gz
// http://doc.spip.org/@lire_fichier
function lire_fichier ($fichier, &$contenu, $options=false) {
	$contenu = '';
	if (!@file_exists($fichier))
		return false;

	#spip_timer('lire_fichier');

	if ($fl = @fopen($fichier, 'r')) {

		// verrou lecture
		@flock($fl, LOCK_SH);

		// a-t-il ete supprime par le locker ?
		if (!@file_exists($fichier)) {
			@fclose($fl);
			return false;
		}

		// lire le fichier
		$contenu = spip_file_get_contents($fichier);

		// liberer le verrou
		@flock($fl, LOCK_UN);
		@fclose($fl);

		// Verifications
		$ok = true;
		if ($options['phpcheck'] == 'oui')
			$ok &= (ereg("[?]>\n?$", $contenu));

		#spip_log("$fread $fichier ".spip_timer('lire_fichier'));
		if (!$ok)
			spip_log("echec lecture $fichier");

		return $ok;
	}
}


//
// Ecrire un fichier de maniere un peu sure
//
// zippe les fichiers .gz
// http://doc.spip.org/@ecrire_fichier
function ecrire_fichier ($fichier, $contenu, $ecrire_quand_meme = false, $truncate=true) {

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	if ((
		(isset($GLOBALS['var_preview'])&&$GLOBALS['var_preview'])
		OR (isset($GLOBALS['var_mode'])&&($GLOBALS['var_mode'] == 'debug'))
		OR defined('spip_interdire_cache'))
	AND !$ecrire_quand_meme)
		return;

	$gzip = (substr($fichier, -3) == '.gz');

	#spip_timer('ecrire_fichier');

	// verrouiller le fichier destination
	if ($fp = @fopen($fichier, 'a'))
		@flock($fp, LOCK_EX);
	else
		return false;

	// ecrire les donnees, compressees le cas echeant
	// (on ouvre un nouveau pointeur sur le fichier, ce qui a l'avantage
	// de le recreer si le locker qui nous precede l'avait supprime...)
	if ($gzip) $contenu = gzencode($contenu);
	if ($truncate)
		@ftruncate($fp,0);
	$s = @fputs($fp, $contenu, $a = strlen($contenu));

	$ok = ($s == $a);

	// liberer le verrou et fermer le fichier
	@flock($fp, LOCK_UN);
	@fclose($fp);

	if (!$ok) {
		spip_log("echec ecriture fichier $fichier");
		@unlink($fichier);
	}

	return $ok;
}

//
// Supprimer le fichier de maniere sympa (flock)
//
// http://doc.spip.org/@supprimer_fichier
function supprimer_fichier($fichier) {
	if (!@file_exists($fichier))
		return;

	// verrouiller le fichier destination
	if ($fp = @fopen($fichier, 'a'))
		@flock($fp, LOCK_EX);
	else
		return;

	// liberer le verrou
	@flock($fp, LOCK_UN);
	@fclose($fp);

	// supprimer
	@unlink($fichier);
}


//
// Retourne $base/${subdir}/ si le sous-repertoire peut etre cree,
// $base/${subdir}_ sinon ; le flag $nobase signale qu'on ne veut pas de $base/
//
// http://doc.spip.org/@sous_repertoire
function sous_repertoire($base, $subdir, $nobase = false) {
	if (!preg_match(',[/_]$,', $base)) $base .= '/';
	$base = str_replace("//", "/", $base);
	$baseaff = $nobase ? '' : $base;
	# $base = 'IMG/distant/' ou 'IMG/distant_'

	if (!strlen($subdir)) return $baseaff;

	$subdir = str_replace("/", "", "$subdir");

	if (@file_exists("$base${subdir}.plat"))
		return "$baseaff${subdir}_";; 

	$path = $base.$subdir; # $path = 'IMG/distant/pdf' ou 'IMG/distant_pdf'

	if (@file_exists("$path/.ok"))
		return "$baseaff$subdir/";

	@mkdir($path, _SPIP_CHMOD);
	@chmod($path, _SPIP_CHMOD);

	$ok = false;
	if ($f = @fopen("$path/dir_test.php", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		@include("$path/dir_test.php");
		@unlink("$path/dir_test.php");
	}
	if ($ok) {
		@touch ("$path/.ok");
		spip_log("creation $base$subdir/");
		return "$baseaff$subdir/";
	}

	$f = @fopen("$base${subdir}.plat", "w");
	if ($f)
		fclose($f);
	else {
		spip_log("echec creation $base${subdir}");
		include_spip('inc/headers');
		redirige_par_entete(
			generer_url_action('test_dirs',"test_dir=$base${subdir}",true));
	}
	spip_log("faux sous-repertoire $base${subdir}");
	return "$baseaff${subdir}";
}

//
// Cette fonction parcourt recursivement le repertoire $dir, et renvoie les
// fichiers dont le chemin verifie le pattern (preg) donne en argument.
// En cas d'echec retourne un array() vide
//
// Usage: array preg_files('ecrire/data/', '[.]lock$');
//
// Attention, afin de conserver la compatibilite avec les repertoires '.plat'
// si $dir = 'rep/sous_rep_' au lieu de 'rep/sous_rep/' on scanne 'rep/' et on
// applique un pattern '^rep/sous_rep_'
// si $recurs vaut false, la fonction ne descend pas dans les sus repertoires
//
// http://doc.spip.org/@preg_files
function preg_files($dir, $pattern=-1 /* AUTO */, $maxfiles = 10000, $recurs=array()) {
	$nbfiles = 0;
	if ($pattern == -1)
		$pattern = "^$dir";
	$fichiers = array();
	// revenir au repertoire racine si on a recu dossier/truc
	// pour regarder dossier/truc/ ne pas oublier le / final
	$dir = preg_replace(',/[^/]*$,', '', $dir);
	if ($dir == '') $dir = '.';

	if (@is_dir($dir) AND is_readable($dir) AND $d = @opendir($dir)) {
		while (($f = readdir($d)) !== false && ($nbfiles<$maxfiles)) {
			if ($f[0] != '.' # ignorer . .. .svn etc
			AND $f != 'CVS'
			AND $f != 'remove.txt'
			AND is_readable($f = "$dir/$f")) {
				if (is_file($f)) {
					if (preg_match(";$pattern;iS", $f))
					{
						$fichiers[] = $f;
						$nbfiles++;
					}
				} 
				else if (is_dir($f) AND is_array($recurs)){
					$rp = @realpath($f);
					if (!is_string($rp) OR !strlen($rp)) $rp=$f; # realpath n'est peut etre pas autorise
					if (!isset($recurs[$rp])) {
						$recurs[$rp] = true;
						$beginning = $fichiers;
						$end = preg_files("$f/", $pattern,
							$maxfiles-$nbfiles, $recurs);
						$fichiers = array_merge((array)$beginning, (array)$end);
						$nbfiles = count($fichiers);
					}
				}
			}
		}
		closedir($d);
	}
	else {
		spip_log("repertoire $dir absent ou illisible");
	}
	sort($fichiers);
	return $fichiers;
}

?>
