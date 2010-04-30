<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// http://doc.spip.org/@install_etape_ldap4_dist
function install_etape_ldap4_dist()
{
	$adresse_ldap = _request('adresse_ldap');
	$login_ldap = _request('login_ldap');
	$pass_ldap = _request('pass_ldap');
	$port_ldap = _request('port_ldap');
	$tls_ldap = _request('tls_ldap');
	$protocole_ldap = _request('protocole_ldap');
	$base_ldap = _request('base_ldap');
	$base_ldap_text = _request('base_ldap_text');

	echo install_debut_html();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect($adresse_ldap, $port_ldap);
	@ldap_bind($ldap_link, $login_ldap, $pass_ldap);

	// Essayer de verifier le chemin fourni
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo info_etape(_T('info_chemin_acces_annuaire')),
			info_progression_etape(3,'etape_ldap','install/', true),
			"<p class='resultat'><b>"._T('avis_operation_echec')."</b></p><p>"._T('avis_chemin_invalide_1'),
			" (<tt>".htmlspecialchars($base_ldap)."</tt>) "._T('avis_chemin_invalide_2')."</p>";
	}
	else {
		info_etape(_T('info_reglage_ldap'));
		echo info_progression_etape(4,'etape_ldap','install/');

		lire_fichier(_FILE_CONNECT_TMP, $conn);
		if ($p = strpos($conn, "'');")) {
			ecrire_fichier(_FILE_CONNECT_TMP, 
				       substr($conn, 0, $p+1) 
				       . _FILE_LDAP
				       . substr($conn, $p+1));
		}
		$adresse_ldap = addcslashes($adresse_ldap,"'\\");
		$login_ldap = addcslashes($login_ldap,"'\\");
		$pass_ldap = addcslashes($pass_ldap,"'\\");
		$port_ldap = addcslashes($port_ldap,"'\\");
		$tls_ldap = addcslashes($tls_ldap,"'\\");
		$protocole_ldap = addcslashes($protocole_ldap,"'\\");
		$base_ldap = addcslashes($base_ldap,"'\\");

		$conn = "\$GLOBALS['ldap_base'] = '$base_ldap';\n"
			. "\$GLOBALS['ldap_link'] = @ldap_connect('$adresse_ldap','$port_ldap');\n"
			. "@ldap_set_option(\$GLOBALS['ldap_link'],LDAP_OPT_PROTOCOL_VERSION,'$protocole_ldap');\n"
			. (($tls_ldap != 'oui') ? '' :
				 "@ldap_start_tls(\$GLOBALS['ldap_link']);\n")
			. "@ldap_bind(\$GLOBALS['ldap_link'],'$login_ldap','$pass_ldap');\n";

		install_fichier_connexion(_DIR_CONNECT . _FILE_LDAP, $conn);
		$statuts = liste_statuts_ldap();
		$statut_ldap = defined('_INSTALL_STATUT_LDAP')
		? _INSTALL_STATUT_LDAP
		  : $GLOBALS['liste_des_statuts']['info_redacteurs'];

		echo generer_form_ecrire('install', (
		"<input type='hidden' name='etape' value='ldap5' />"
		
		. fieldset(_T('info_statut_utilisateurs_1'),
			array(
				'statut_ldap' => array(
					'label' => _T('info_statut_utilisateurs_2').'<br />',
					'valeur' => $statut_ldap,
					'alternatives' => $statuts
					)
				)
			   )
		. bouton_suivant()));
	}

	echo install_fin_html();
}

// http://doc.spip.org/@liste_statuts_ldap
function liste_statuts_ldap() {
	$recom = array("info_administrateurs" => ("<b>" ._T('info_administrateur_1')."</b> "._T('info_administrateur_2')."<br />"),
		       "info_redacteurs" =>  ("<b>"._T('info_redacteur_1')."</b> "._T('info_redacteur_2')."<br />"),
		       "info_visiteurs" => ("<b>"._T('info_visiteur_1')."</b> "._T('info_visiteur_2')."<br />"));
	
	$res = array();
	foreach($GLOBALS['liste_des_statuts'] as $k => $v) {
		if (isset($recom[$k])) $res[$v] = $recom[$k];
	}
	return $res;
}
?>
