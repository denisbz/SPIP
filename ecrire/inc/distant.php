<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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
// Prend en argument un chemin relatif au rep racine, ou une URL
// Renvoie un chemin relatif au rep racine, ou false
//
// http://doc.spip.org/@copie_locale
function copie_locale($source, $mode='auto') {
	$local = fichier_copie_locale($source);

	// test d'existence du fichier
	if ($mode == 'test')
		return @file_exists(_DIR_RACINE.$local) ? $local : '';

	// si $local = '' c'est un fichier refuse par fichier_copie_locale(),
	// par exemple un fichier qui ne figure pas dans nos documents ;
	// dans ce cas on n'essaie pas de le telecharger pour ensuite echouer
	if (!$local) return false;

	// sinon voir si on doit le telecharger
	if ($local != $source
	AND preg_match(',^\w+://,', $source)) {
		if (($mode=='auto' AND !@file_exists(_DIR_RACINE.$local))
		OR $mode=='force') {
			$contenu = recuperer_page($source);
			if (!$contenu) return false;
			ecrire_fichier(_DIR_RACINE.$local, $contenu);

			// pour une eventuelle indexation
			pipeline('post_edition',
				array(
					'args' => array(
						'operation' => 'copie_locale',
						'source' => $source,
						'fichier' => $local
					),
					'data' => null
				)
			);
		}
	}

	return $local;
}

// http://doc.spip.org/@prepare_donnees_post
function prepare_donnees_post($donnees, $boundary = '') {

	// permettre a la fonction qui a demande le post de formater elle meme ses donnees
	// pour un appel soap par exemple
	// l'entete est separe des donnees par un double retour a la ligne
	// on s'occupe ici de passer tous les retours lignes (\r\n, \r ou \n) en \r\n
	if (is_string($donnees) && strlen($donnees)){
		$entete = "";
		// on repasse tous les \r\n et \r en simples \n
		$donnees = str_replace("\r\n","\n",$donnees);
		$donnees = str_replace("\r","\n",$donnees);
		// un double retour a la ligne signifie la fin de l'entete et le debut des donnees
		$p = strpos($donnees,"\n\n");
  	if ($p!==FALSE){
  		$entete = str_replace("\n","\r\n",substr($donnees,0,$p+1));
  		$donnees = substr($donnees,$p+2);
  	}
		$chaine = str_replace("\n","\r\n",$donnees);
  }
  else {
	  /* boundary automatique */
	  // Si on a plus de 500 octects de donnees, on "boundarise"
	  if($boundary == '') {
	    $taille = 0;
	    foreach ($donnees as $cle => $valeur) {
	  		if (is_array($valeur)) {
	  			foreach ($valeur as $val2) {
	          $taille += strlen($val2);
	        }
	      } else {
	        // faut-il utiliser spip_strlen() dans inc/charsets ?
	        $taille += strlen($valeur);
	      }
	    }
	    if($taille>500) {
	      $boundary = substr(md5(rand().'spip'), 0, 8);
	    }
	  }
	
		if($boundary) {
			// fabrique une chaine HTTP pour un POST avec boundary
			$entete = "Content-Type: multipart/form-data; boundary=$boundary\r\n";
			$chaine = '';
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					$chaine .= "\r\n--$boundary\r\n";
					$chaine .= "Content-Disposition: form-data; name=\"$cle\"\r\n";
					$chaine .= "\r\n";
					$chaine .= $valeur;
				}
				$chaine .= "\r\n--$boundary\r\n";
			}
		} else {
			// fabrique une chaine HTTP simple pour un POST
			$entete = 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$chaine = array();
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					if (is_array($valeur)) {
						foreach ($valeur as $val2) {
							$chaine[] = rawurlencode($cle).'='.rawurlencode($val2);
						}
					} else {
						$chaine[] = rawurlencode($cle).'='.rawurlencode($valeur);
					}
				}
				$chaine = implode('&', $chaine);
			} else {
				$chaine = $donnees;
			}
		}
  }
	return array($entete, $chaine);
}

//
// Recupere une page sur le net
// et au besoin l'encode dans le charset local
//
// options : get_headers si on veut recuperer les entetes
// taille_max : arreter le contenu au-dela (0 = seulement les entetes ==>HEAD)
// Par defaut taille_max = 1Mo.
// datas, une chaine ou un tableau pour faire un POST de donnees
// boundary, pour forcer l'envoi par cette methode
// et refuser_gz pour forcer le refus de la compression (cas des serveurs orthographiques)
// date_verif, un timestamp unix pour arreter la recuperation si la page distante n'a pas ete modifiee depuis une date donnee
// uri_referer, preciser un referer different 
// http://doc.spip.org/@recuperer_page
function recuperer_page($url, $munge_charset=false, $get_headers=false,
	$taille_max = 1048576, $datas='', $boundary='', $refuser_gz = false,
	$date_verif = '', $uri_referer = '') {
  	$gz = false;
  // Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	if ($taille_max == 0)
		$get = 'HEAD';
	else
		$get = 'GET';

	if (!empty($datas)) {
		$get = 'POST';
		$datas = prepare_donnees_post($datas);
	}

	// dix tentatives maximum en cas d'entetes 301...
	for ($i=0;$i<10;$i++) {
		$url = recuperer_lapage($url, $munge_charset, $get, $taille_max, $datas, $boundary, $refuser_gz, $date_verif, $uri_referer);
		if (!$url) return false;
		if (is_array($url)) {
			list($headers,  $result) = $url;
			return ($get_headers ? $headers."\n" : '').$result;
		}
	}
}

// args comme ci-dessus (presque)
// retourne l'URL en cas de 301, un tableau (entete, corps) si ok, false sinon

// http://doc.spip.org/@recuperer_lapage
function recuperer_lapage($url, $trans=false, $get='GET', $taille_max = 1048576, $datas='', $boundary='', $refuser_gz = false, $date_verif = '', $uri_referer = '')
{
	list($f, $fopen) = init_http($get, $url, $refuser_gz, $uri_referer);
	$gz = false;

	// si on a utilise fopen() - passer a la suite
	if (!$fopen) {
			// Fin des entetes envoyees par SPIP
			if($get == 'POST') {
				list($content_type, $postdata) = $datas;
				@fputs($f, $content_type);
				@fputs($f, 'Content-Length: '.strlen($postdata)."\r\n");
				@fputs($f, "\r\n".$postdata);
			} else {
				@fputs($f,"\r\n");
			}

			// Reponse du serveur distant
			$s = @trim(fgets($f, 16384));
			if (preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)) {
				$status = $r[1];
			}
			else return false;

			// Entetes HTTP de la page
			$headers = '';
			while ($s = trim(fgets($f, 16384))) {
				$headers .= $s."\n";
				if (preg_match(',^Location: (.*),i', $s, $r)) {
					include_spip('inc/filtres');
					$location = suivre_lien($url, $r[1]);
				}
				if ($date_verif AND preg_match(',^Last-Modified: (.*),', $s, $r)) {
					if(strtotime($date_verif)>=strtotime($r[1])) {
						//Cas ou la page distante n'a pas bouge depuis
						//la derniere visite
						return $status;
					}
				}
				if (preg_match(",^Content-Encoding: .*gzip,i", $s))
					$gz = true;
			}
			if ($status >= 300 AND $status < 400 AND $location) {
				fclose($f);
				return $location;
			} else if ($status != 200)
				return false;
			; # sinon on est content
		}
		
	// Contenu de la page
	if (!$f) {
		spip_log("ECHEC chargement $url");
		return false;
	}

	if ($trans === NULL) return array($headers, '');
	$result = '';
	while (!feof($f) AND strlen($result)<$taille_max)
		$result .= fread($f, 16384);
	fclose($f);

	// Decompresser le flux
	if ($gz AND $result)
		$result = gzinflate(substr($result,10));

	// Faut-il l'importer dans notre charset local ?
	if ($trans) {
		include_spip('inc/charsets');
		$result = transcoder_page ($result, $headers);
	}

	return array($headers, $result);
}


// Si on doit conserver une copie locale des fichiers distants, autant que ca
// soit a un endroit canonique -- si ca peut etre bijectif c'est encore mieux,
// mais la tout de suite je ne trouve pas l'idee, etant donne les limitations
// des filesystems
// http://doc.spip.org/@nom_fichier_copie_locale
function nom_fichier_copie_locale($source, $extension) {

	include_spip('inc/getdocument');
	$d = creer_repertoire_documents('distant'); # IMG/distant/
	$d = sous_repertoire($d, $extension); # IMG/distant/pdf/

	// on se place tout le temps comme si on etait a la racine
	if (_DIR_RACINE)
		$d = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $d);

	$m = md5($source);

	return $d
	. substr(preg_replace(',[^\w-],', '', basename($source)).'-'.$m,0,12)
	. substr($m,0,4)
	. ".$extension";
}

//
// Donne le nom de la copie locale de la source
//
// http://doc.spip.org/@fichier_copie_locale
function fichier_copie_locale($source) {
	// Si c'est deja local pas de souci
	if (!preg_match(',^\w+://,', $source)) {
		if (_DIR_RACINE)
			$source = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $source);
		return $source;
	}

	// Si c'est deja dans la table des documents,
	// ramener le nom de sa copie potentielle
	$ext = sql_getfetsel("extension", "spip_documents", "fichier=" . sql_quote($source) . " AND distant='oui' AND extension <> ''");

	if ($ext) return nom_fichier_copie_locale($source, $ext);

	// voir si l'extension indiquee dans le nom du fichier est ok
	// et si il n'aurait pas deja ete rapatrie

	$path_parts = pathinfo($source);
	$ext = $path_part ? $path_parts['extension'] : '';

	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=".sql_quote($ext))) {
		$f = nom_fichier_copie_locale($source, $ext);
		if (file_exists(_DIR_RACINE  . $f))
		  return $f;
	}
	// Ping  pour voir si son extension est connue et autorisee
	$path_parts = recuperer_infos_distantes($source,0,false) ;
	$ext = $path_part ? $path_parts['extension'] : '';
	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=".sql_quote($ext))) {
		return nom_fichier_copie_locale($source, $ext);
	}
}


// Recuperer les infos d'un document distant, sans trop le telecharger
#$a['body'] = chaine
#$a['type_image'] = booleen
#$a['titre'] = chaine
#$a['largeur'] = intval
#$a['hauteur'] = intval
#$a['taille'] = intval
#$a['extension'] = chaine
#$a['fichier'] = chaine

// http://doc.spip.org/@recuperer_infos_distantes
function recuperer_infos_distantes($source, $max=0, $charger_si_petite_image = true) {

	# charger les alias des types mime
	include_spip('base/typedoc');
	global $mime_alias;

	$a = array();
	$mime_type = '';
	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	if ($headers = recuperer_page($source, false, true, $max)) {
		list($headers, $a['body']) = split("\n\n", $headers, 2);

		if (preg_match(",\nContent-Type: *([^[:space:];]*),i", "\n$headers", $regs))
			$mime_type = (trim($regs[1]));
		else
			$mime_type = ''; // inconnu

		// Appliquer les alias
		while (isset($mime_alias[$mime_type]))
			$mime_type = $mime_alias[$mime_type];

		// Si on a text/plain, c'est peut-etre que le serveur ne sait pas
		// ce qu'il sert ; on va tenter de detecter via l'extension de l'url
		$t = null;
		if (($mime_type == 'text/plain' OR $mime_type == '')
		AND preg_match(',\.([a-z0-9]+)(\?.*)?$,', $source, $rext)) {
			$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1]));
		}

		// Autre mime/type (ou text/plain avec fichier d'extension inconnue)
		if (!$t)
			$t = sql_fetsel("extension", "spip_types_documents", "mime_type=" . sql_quote($mime_type));

		// Toujours rien ? (ex: audio/x-ogg au lieu de application/ogg)
		// On essaie de nouveau avec l'extension
		if (!$t
		AND $mime_type != 'text/plain'
		AND preg_match(',\.([a-z0-9]+)(\?.*)?$,', $source, $rext)) {
			$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1]));
		}


		if ($t) {
			spip_log("mime-type $mime_type ok, extension ".$t['extension']);
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu");
			$t = sql_fetsel("extension", "spip_types_documents", "extension='bin'");
			if (!$t) return false;
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i",
			"\n$headers", $regs))
			$a['taille'] = intval($regs[1]);
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a AND !$max) {
		spip_log("tenter GET $source");
		$a = recuperer_infos_distantes($source, 1024*1024);
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	if (preg_match(',^image/(jpeg|gif|png|swf),', $mime_type)) {
		if ($max == 0
		AND $a['taille'] < 1024*1024
		AND (strpos($GLOBALS['meta']['formats_graphiques'],$a['extension'])!==false)
		AND $charger_si_petite_image) {
			$a = recuperer_infos_distantes($source, 1024*1024);
		}
		else if ($a['body']) {
			$a['fichier'] = _DIR_RACINE . nom_fichier_copie_locale($source, $a['extension']);
			ecrire_fichier($a['fichier'], $a['body']);
			$size_image = @getimagesize($a['fichier']);
			$a['largeur'] = intval($size_image[0]);
			$a['hauteur'] = intval($size_image[1]);
			$a['type_image'] = true;
		}
	}

	// Fichier swf, si on n'a pas la taille, on va mettre 425x350 par defaut
	// ce sera mieux que 0x0
	if ($a['extension'] == 'swf'
	AND !$a['largeur']) {
		$a['largeur'] = 425;
		$a['hauteur'] = 350;
	}

	if ($mime_type == 'text/html') {
		include_spip('inc/filtres');
		$page = recuperer_page($source, true, false, 1024*1024);
		if(preg_match(',<title>(.*?)</title>,ims', $page, $regs)) 
			$a['titre'] = corriger_caracteres(trim($regs[1]));
			if (!$a['taille']) $a['taille'] = strlen($page); # a peu pres
	}

	return $a;
}


function need_proxy($host)
{
	$http_proxy = $GLOBALS['meta']["http_proxy"];
	$http_noproxy = $GLOBALS['meta']["http_noproxy"];

	$domain = substr($host,strpos($host,'.'));

	return ($http_proxy
	AND (strpos(" $http_noproxy ", " $host ") === false
	     AND (strpos(" $http_noproxy ", " $domain ") === false)))
	? $http_proxy : '';
}

//
// Demarre une transaction HTTP (s'arrete a la fin des entetes)
// retourne un descripteur de fichier
//
// http://doc.spip.org/@init_http
function init_http($get, $url, $refuse_gz=false, $uri_referer = '') {
	$via_proxy = ''; $proxy_user = ''; $fopen = false;

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http'; $scheme_fsock='';
	} else {
		$scheme = $t['scheme']; $scheme_fsock=$scheme.'://';
	}
	if (!isset($t['port']) || !($port = $t['port'])) $port = 80;
	$query = $t['query'];
	if (!isset($t['path']) || !($path = $t['path'])) $path = "/";

	$http_proxy = need_proxy($host);

	if ($http_proxy) {
		spip_log("connexion vers $url via $http_proxy");
		$t2 = @parse_url($http_proxy);
		$proxy_host = $t2['host'];
		$proxy_user = $t2['user'];
		$proxy_pass = $t2['pass'];
		if (!($proxy_port = $t2['port'])) $proxy_port = 80;
		$f = @fsockopen($proxy_host, $proxy_port);
		$req = "$get $scheme://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\r\n";
	} else {
		$f = @fsockopen($scheme_fsock.$host, $port);
		spip_log("connexion vers $url sans proxy");
		$req = "$get $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n";
	}
	if ($f) {
		fputs($f, $req);
		fputs($f, "Host: $host\r\n");
		fputs($f, "User-Agent: SPIP-".$GLOBALS['spip_version_affichee']." (http://www.spip.net/)\r\n");

		// Proxy authentifiant
		if ($proxy_user) {
			fputs($f, "Proxy-Authorization: Basic "
			. base64_encode($proxy_user . ":" . $proxy_pass) . "\r\n");
		}
		// Referer = c'est nous !
		if ($referer = $GLOBALS['meta']["adresse_site"]) {
			$referer .= '/'.$uri_referer;
			fputs($f, "Referer: $referer\r\n");
		}

		// On sait lire du gzip
		if ($GLOBALS['flag_gz'] AND !$refuse_gz)
			fputs($f, "Accept-Encoding: gzip\r\n");

	}
	// fallback : fopen
	else if (!$GLOBALS['tester_proxy']) {
		$f = @fopen($url, "rb");
		spip_log("connexion vers $url par simple fopen");
		$fopen = true;
	}
	// echec total
	else {
		$f = false;
	}

	return array($f, $fopen);
}

?>
