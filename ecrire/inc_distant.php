<?php

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

//
// Cree au besoin la copie locale d'un fichier distant
// mode = 'test' - ne faire que tester
// mode = 'auto' - charger au besoin
// mode = 'force' - charger toujours (mettre a jour)
//
function copie_locale($source, $mode='auto') {

	// Si copie_locale() est appele depuis l'espace prive
	if (!_DIR_RESTREINT
	AND strpos(_DIR_RACINE . $source, _DIR_IMG) === 0)
		return _DIR_RACINE . $source;

	$local = fichier_copie_locale($source);

	if ($source != $local) {
		if (($mode=='auto' AND !@file_exists($local))
		OR $mode=='force') {
			$contenu = recuperer_page($source);
			if ($contenu) {
				ecrire_fichier($local, $contenu);

				// signaler au moteur de recherche qu'il peut reindexer ce doc
				$a = spip_query("SELECT id_document FROM spip_documents
					WHERE fichier='".addslashes($source)."'");
				list($id_document) = spip_fetch_array($a);
				if ($id_document) {
					include_ecrire('inc_index');
					marquer_indexer('document', $id_document);
				}
			}
			else
				return false;
		}
	}

	return $local;
}

//
// Recupere une page sur le net
// et au besoin l'encode dans le charset local
//
// options : get_headers si on veut recuperer les entetes
// taille_max : arreter le contenu au-dela (0 = seulement les entetes)
// Par defaut taille_max = 1Mo.
function recuperer_page($url, $munge_charset=false, $get_headers=false, $taille_max = 1048576) {

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	if ($taille_max == 0)
		$get = 'HEAD';
	else
		$get = 'GET';


	for ($i=0;$i<10;$i++) {	// dix tentatives maximum en cas d'entetes 301...
		list($f, $fopen) = init_http($get, $url);

		// si on a utilise fopen() - passer a la suite
		if ($fopen) {
			spip_log('connexion via fopen');
			break;
		} else {
			// Fin des entetes envoyees par SPIP
			fputs($f,"\r\n");

			// Reponse du serveur distant
			$s = trim(fgets($f, 16384));
			if (ereg('^HTTP/[0-9]+\.[0-9]+ ([0-9]+)', $s, $r)) {
				$status = $r[1];
			}
			else return;

			// Entetes HTTP de la page
			$headers = '';
			while ($s = trim(fgets($f, 16384))) {
				$headers .= $s."\n";
				if (eregi('^Location: (.*)', $s, $r)) {
					include_ecrire('inc_filtres');
					$location = suivre_lien($url, $r[1]);
					spip_log("Location: $location");
				}
				if (preg_match(",^Content-Encoding: .*gzip,i", $s))
					$gz = true;
			}
			if ($status >= 300 AND $status < 400 AND $location)
				$url = $location;
			else if ($status != 200)
				return;
			else
				break; # ici on est content
			fclose($f);
			$f = false;
		}
	}

	// Contenu de la page
	if (!$f) {
		spip_log("ECHEC chargement $url");
		return false;
	}

	$result = '';
	while (!feof($f) AND strlen($result)<$taille_max)
		$result .= fread($f, 16384);
	fclose($f);

	// Decompresser le flux
	if ($gz)
		$result = gzinflate(substr($result,10));

	// Faut-il l'importer dans notre charset local ?
	if ($munge_charset) {
		include_ecrire('inc_charsets');
		$result = transcoder_page ($result, $headers);
	}

	return ($get_headers ? $headers."\n" : '').$result;
}


// Si on doit conserver une copie locale des fichiers distants, autant que ca
// soit a un endroit canonique -- si ca peut etre bijectif c'est encore mieux,
// mais la tout de suite je ne trouve pas l'idee, etant donne les limitations
// des filesystems
function nom_fichier_copie_locale($source, $extension) {
	$dir = _DIR_IMG. creer_repertoire(_DIR_IMG, 'distant'); # IMG/distant/
	$dir2 = $dir . creer_repertoire($dir, $extension); 		# IMG/distant/pdf/
	return $dir2 . substr(basename($source).'-'.md5($source),0,12).
		substr(md5($source),0,4).'.'.$extension;
}

//
// Donne le nom de la copie locale de la source
//
function fichier_copie_locale($source) {
	// Si c'est une image de IMG/ pas de souci
	if (preg_match(',^'._DIR_IMG.',', $source))
		return $source;

	// Si l'extension n'est pas precisee, aller la chercher dans la table
	// des documents -- si la source n'est pas dans la table des documents,
	// on ne fait rien
	if ($t = spip_fetch_array(spip_query("SELECT * FROM spip_documents
	WHERE fichier='".addslashes($source)."' AND distant='oui'")))
		list($extension) = spip_fetch_array(spip_query("SELECT extension
		FROM spip_types_documents WHERE id_type=".$t['id_type']));

	if ($extension)
		return nom_fichier_copie_locale($source, $extension);
}


// Recuperer les infos d'un document distant, sans trop le telecharger
function recuperer_infos_distantes($source, $max=0) {

	$a = array();

	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	if ($headers = recuperer_page($source, false, true, $max)) {
		list($headers, $a['body']) = split("\n\n", $headers, 2);
		if (preg_match(",\nContent-Type: *([^[:space:];]*),i",
			"\n$headers", $regs)
		AND $mime_type = addslashes(trim($regs[1]))
		AND $s = spip_query("SELECT id_type,extension FROM spip_types_documents
			WHERE mime_type='$mime_type'")
		AND $t = spip_fetch_array($s)) {
			spip_log("mime-type $mime_type ok");
			$a['id_type'] = $t['id_type'];
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu");
			$t = spip_fetch_array(spip_query(
				"SELECT id_type,extension FROM spip_types_documents
				WHERE extension='bin'"));
			if (!$t) return false;
			$a['id_type'] = $t['id_type'];
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i",
			"\n$headers", $regs))
			$a['taille'] = intval($regs[1]);
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a AND !$max) {
	spip_log("tente $source");
		$a = recuperer_infos_distantes($source, 1024*1024);
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	if (preg_match(',^image/(jpeg|gif|png|swf),', $mime_type)) {
		if ($max == 0
		    AND $a['taille'] < 1024*1024
		AND ereg(",".$a['extension'].",",
		','.$GLOBALS['meta']['formats_graphiques'].',')){
			$a = recuperer_infos_distantes($source, 1024*1024);
		}
		else if ($a['body']) {
			$a['fichier'] = nom_fichier_copie_locale($source, $a['extension']);
			ecrire_fichier($a['fichier'], $a['body']);
			$size_image = @getimagesize($a['fichier']);
			$a['largeur'] = intval($size_image[0]);
			$a['hauteur'] = intval($size_image[1]);
			$a['type_image'] = true;
		}
	}
	
	if ($mime_type == 'text/html') {
		$page = recuperer_page($source, true, false, 1024*1024);
		if(preg_match(',<title>(.*?)</title>,ims', $page, $regs))
			$a['titre'] = corriger_caracteres(trim($regs[1]));
			if (!$a['taille']) $a['taille'] = strlen($page); # a peu pres
	}

	return $a;
}


//
// Demarre une transaction HTTP (s'arrete a la fin des entetes)
// retourne un descripteur de fichier
//
function init_http($get, $url, $refuse_gz=false) {
	$http_proxy = $GLOBALS['meta']["http_proxy"];
	if (!eregi("^http://", $http_proxy))
		$http_proxy = '';
	else
		$via_proxy = " (proxy $http_proxy)";

	spip_log("http $get $url$via_proxy");

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http'; $scheme_fsock='';
	} else {
		$scheme = $t['scheme']; $scheme_fsock=$scheme.'://';
	}
	if (!($port = $t['port'])) $port = 80;
	$query = $t['query'];
	if (!($path = $t['path'])) $path = "/";

	if ($http_proxy) {
		$t2 = @parse_url($http_proxy);
		$proxy_host = $t2['host'];
		$proxy_user = $t2['user'];
		$proxy_pass = $t2['pass'];
		if (!($proxy_port = $t2['port'])) $proxy_port = 80;
		$f = @fsockopen($proxy_host, $proxy_port);
	} else
		$f = @fsockopen($scheme_fsock.$host, $port);

	if ($f) {
		if ($http_proxy)
			fputs($f, "$get $scheme://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\r\n");
		else
			fputs($f, "$get $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n");

		fputs($f, "Host: $host\r\n");
		fputs($f, "User-Agent: SPIP-".$GLOBALS['spip_version_affichee']." (http://www.spip.net/)\r\n");

		// Proxy authentifiant
		if ($proxy_user) {
			fputs($f, "Proxy-Authorization: Basic "
			. base64_encode($proxy_user . ":" . $proxy_pass) . "\r\n");
		}
		// Referer = c'est nous !
		if ($referer = $GLOBALS['meta']["adresse_site"])
			fputs($f, "Referer: $referer/\r\n");

		// On sait lire du gzip
		if ($GLOBALS['flag_gz'] AND !$refuse_gz)
			fputs($f, "Accept-Encoding: gzip\r\n");

	}
	// fallback : fopen
	else if (!$GLOBALS['tester_proxy']) {
		$f = @fopen($url, "rb");
		$fopen = true;
	}
	// echec total
	else {
		$f = false;
	}

	return array($f, $fopen);
}

?>
