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

// http://doc.spip.org/@inc_install_ldap4
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
			"<p class='resultat'><b>"._T('avis_operation_echec')."</b></p><p>"._T('avis_chemin_invalide_1'),
			" (<tt>".htmlspecialchars($base_ldap)."</tt>) "._T('avis_chemin_invalide_2')."</p>";
	}
	else {
		info_etape(_T('info_reglage_ldap'));

		lire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);
		if ($p = strpos($conn, '?'.'>')) 
			$conn = substr($conn, 0, $p);
		if (!strpos($conn, 'spip_connect_ldap')) {
			$conn .= "function spip_connect_ldap() {\n";
			$conn .= "\t\$GLOBALS['ldap_link'] = @ldap_connect(\"$adresse_ldap\",\"$port_ldap\");\n";
			$conn .= "\t@ldap_set_option(\$GLOBALS['ldap_link'],LDAP_OPT_PROTOCOL_VERSION,\"$protocole_ldap\");\n";
 			if ($tls_ldap == 'oui')
				$conn .= "\t@ldap_start_tls(\$GLOBALS['ldap_link']);\n";


			$conn .= "\t@ldap_bind(\$GLOBALS['ldap_link'],\"$login_ldap\",\"$pass_ldap\");\n";
			$conn .= "\treturn \$GLOBALS['ldap_link'];\n";
			$conn .= "}\n";
			$conn .= "\$GLOBALS['ldap_base'] = \"$base_ldap\";\n";
			$conn .= "\$GLOBALS['ldap_present'] = true;\n";
		}
		$conn .= "?".">";
		ecrire_fichier(_FILE_CONNECT_INS . _FILE_TMP . '.php', $conn);
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

	echo info_progression_etape(4,'etape_ldap','install/');
	echo install_fin_html();
}

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
