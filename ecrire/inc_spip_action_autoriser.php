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

include_ecrire("inc_charsets");	# pour le nom de fichier
include_ecrire("inc_session");	# verifier_action_auteur
include_ecrire("inc_abstract_sql");# spip_insert / spip_fetch...

//  acces aux documents joints securise
//  est appelee avec arg comme parametre CGI
//  mais peu aussi etre appele avec le parametre file directement 
//  il verifie soit que le demandeur est authentifie
// soit que le fichier est joint a au moins 1 article, breve ou rubrique publie

function spip_action_autoriser_dist()
{
  global $file, $arg;

  $file = urldecode($file);

  $refus = false;
  if (strpos($file,'../') !== false)
    $refus = 1;
  else
  {
    if ($cookie_session = $_COOKIE['spip_session']) 
      {
	include_ecrire("inc_session");
	global $auteur_session;

	if (verifier_session($cookie_session)) 
	  {

	    if ($auteur_session['statut'] == '0minirezo' 
		OR $auteur_session['statut'] == '1comite') 
	      $auth_login = $auteur_session['login'];
	  }
      }

    if (!$arg) {
      $arg = spip_query("select id_document from spip_documents as documents where documents.fichier='".$file."'");
	$arg = spip_fetch_array($arg);
      if (!$arg) $refus = 2;
      $arg = $arg['id_document'];
    } else {
      $file = spip_query("select fichier from spip_documents as documents where id_document='". $arg ."'");
      $file = spip_fetch_array($file);
      if (!$file) $refus = 3;
      $file = $file['fichier'];
    }
  }
  spip_log("arg $arg $auth_login");
if (!$auth_login && !$refus) { 
    if (!spip_num_rows(spip_query("select articles.id_article
from spip_documents_articles as rel_articles, spip_articles as articles 
where rel_articles.id_article = articles.id_article AND
articles.statut = 'publie' AND rel_articles.id_document ='".
			       $arg .
				"' LIMIT 1"))) {
      if (!spip_num_rows(spip_query("select rubriques.id_rubrique
from spip_documents_rubriques as rel_rubriques, spip_rubriques as rubriques 
where rel_rubriques.id_rubrique = rubriques.id_rubrique AND
rubriques.statut = 'publie' AND rel_rubriques.id_document ='".
			       $arg .
				  "' LIMIT 1"))) {
	if (!spip_num_rows(spip_query("select breves.id_breve
from spip_documents_breves as rel_breves, spip_breves as breves 
where rel_breves.id_breve = breves.id_breve AND
breves.statut = 'publie' AND rel_breves.id_document ='".
			       $arg .
				  "' LIMIT 1")))
	  $refus = 4; } } }

  if (is_int($refus)) {
    spip_log("Acces refuse ($refus) au document " . $arg . ': ' . $file);
    global $fond;
    $fond = 404;
    include_local("inc-public");
  }
  else
    {
      if (!function_exists('mime_content_type')) {
	function mime_content_type($f) {preg_match("/\.(\w+)/",$f,$r); return $r[1];}
 }
      spip_log("envoi $file");
      $ct = mime_content_type($file);
      $cl = filesize($file);
      $filename = basename($file);
      header("Content-Type: ". $ct);
      header("Content-Disposition: attachment; filename=\"". $filename ."\";");
      if ($file) header("Content-Description: " . $dcc);
      if ($cl) header("Content-Length: ". $cl);

      header("Content-Transfer-Encoding: binary");
      readfile($file);
    }
}

// pour envoyer un article proprement
// spip_action.php?action=telecharger&arg=$id_article

function spip_action_telecharger_dist()
{
  global $$arg;
  $r = spip_query("
SELECT	texte, soustitre, titre, date
FROM	spip_articles
WHERE	id_article=" . intval($arg)
				 );
  $r = spip_fetch_array($r);
  if (!$r)
    return 0;
  else
    {
      $titre = $r['titre'];
      $text =ereg_replace("^<code>[[:space:]]*",'',
			  ereg_replace('</code>$','',$r['texte']));
      header("Content-Type: text/plain; charset='iso-8859-1'");
      if ($titre) header("Content-Description: $titre");
      header("Content-Disposition: attachment; filename=" .
            ($r['soustitre'] ? $r['soustitre'] : ($arg . ".txt")) .
             ";" );
      header("Content-Length: ". strlen($text)+1);
      print $text;
    }
}
     
?>
