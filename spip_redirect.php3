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

// redirige vers l'URL canonique de l'article,
// en indiquant recalcul et previsu

define ('_SPIP_REDIRECT', 1);
include ("ecrire/inc_version.php3");
include_ecrire ("inc_session.php3");
verifier_visiteur();

// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3"))
	include_local("inc-urls.php3");
else
	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");

if ($id_article) {
	$url = generer_url_article($id_article);
}
else if ($id_breve) {
	$url = generer_url_breve($id_breve);
}
else if ($id_forum) {
	$url = generer_url_forum($id_forum);
}
else if ($id_rubrique) {
	$url = generer_url_rubrique($id_rubrique);
}
else if ($id_mot) {
	$url = generer_url_mot($id_mot);
}
else if ($id_auteur) {
	$url = generer_url_auteur($id_auteur);
}
else if ($id_syndic OR $id_syndic = $id_site) {
	$url = generer_url_site($id_syndic);
}
else {
	$url = _DIR_RESTREINT_ABS;
}
if (strpos($url,'?')) {
	$super='&';
}
else {
	$super='?';
}
if ($recalcul) $url .= $super."var_mode=recalcul";
else if ($preview) $url .= $super."var_mode=preview";

// Ne pas masquer cette eventuelle erreur (aide a detecter des lignes vides
// dans inc-urls.php3 ou mes_fonctions/mes_options)
header("Location: $url");

?>