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

/*
 * REMARQUE IMPORTANTE : SECURITE
 * Ce systeme de reparation doit pouvoir fonctionner meme si
 * la table spip_auteurs est en panne : on n'appelle donc pas
 * inc_auth ; seule l'authentification ftp est exigee
 *
 */

$GLOBALS['connect_statut'] = '0minirezo';

include_spip('inc/admin');
include_spip('inc/texte');
include_spip('inc/minipres');
include_spip('base/db_mysql');

// http://doc.spip.org/@verifier_base
function verifier_base() {
	$res1= spip_query("SHOW TABLES");
	if (!$res1) return false;

	$res = "";
	while ($tab = spip_fetch_array($res1,SPIP_NUM)) {
		$res .= "<p><b>".$tab[0]."</b> ";

		$result_repair = spip_query("REPAIR TABLE ".$tab[0]);
		if (!$result_repair) return false;

		$result = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM ".$tab[0]));
		if (!$result) return false;

		$count = $result['n'];
		if ($count>1)
			$res .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
		else if ($count==1)
			$res .= "("._T('texte_compte_element', array('count' => $count)).")\n";
		else
			$res .= "("._T('texte_vide').")\n";

		$row = spip_fetch_array($result_repair,SPIP_NUM);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			$res .= "<pre><font color='red'><b>".htmlentities(join("\n", $row))."</b></font></pre>\n";
		else
			$res .= " "._T('texte_table_ok')."<br>\n";

	}

	return $res;
}

// http://doc.spip.org/@exec_admin_repair_dist
function exec_admin_repair_dist()
{
	$ok = false;
	$version_mysql = spip_mysql_version();
	if (!$version_mysql)
	  $message = _T('avis_erreur_connexion_mysql');
	else {
	  if ($version_mysql < '3.23.14')
	    $message = _T('avis_version_mysql', array('version_mysql' => $version_mysql));
	  else {
	    $message = _T('texte_requetes_echouent');
	    $ok = true;
	  }
	}

	$action = _T('texte_tenter_reparation');

	if ($ok) {
		debut_admin("admin_repair", $action, $message);

		if (! $res = verifier_base())
			$res = "<br /><br /><font color='red'><b><tt>"._T('avis_erreur_mysql').' '.spip_sql_errno().': '.spip_sql_error() ."</tt></b></font><br /><br /><br />\n";
		fin_admin($action);
		echo minipres(_T('texte_tentative_recuperation'), $res);
	}
	else {
	  echo minipres(_T('titre_reparation'), "<p>$message</p>");
	}
}
?>
