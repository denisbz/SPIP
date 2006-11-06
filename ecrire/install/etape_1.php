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

// http://doc.spip.org/@inc_install_1
function install_etape_1_dist()
{
	global $spip_lang_right;

	install_debut_html();

	// stopper en cas de grosse incompatibilite de l'hebergement
	tester_compatibilite_hebergement();

	echo info_etape(_T('info_connexion_mysql'), _T('texte_connexion_mysql').aide ("install1"));

	list($adresse_db, $login_db) = login_hebergeur();
	$pass_db = '';

	$chmod = (isset($_GET['chmod']) AND preg_match(',^[0-9]+$,', $_GET['chmod']))? sprintf('%04o', $_GET['chmod']):'0777';
	// Recuperer les anciennes donnees pour plus de facilite (si presentes)
	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php')) {
		$s = @join('', @file(_FILE_CONNECT_INS . _FILE_TMP . '.php'));
		if (ereg("mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)", $s, $regs)) {
			$adresse_db = $regs[1];
			$login_db = $regs[2];
		}
		else if (ereg("spip_connect_db\('(.*)','(.*)','(.*)','(.*)','(.*)'\)", $s, $regs)) {
			$adresse_db = $regs[1];
			if ($port_db = $regs[2]) $adresse_db .= ':'.$port_db;
			$login_db = $regs[3];
		}
	}
	if(@file_exists(_FILE_CHMOD_INS . _FILE_TMP . '.php')){
		$s = @join('', @file(_FILE_CHMOD_INS . _FILE_TMP . '.php'));
		if(ereg("define\('_SPIP_CHMOD', (.*)\)", $s, $regs)) {
			$chmod = $regs[1]; 
		}
	}
	echo generer_url_post_ecrire('install');
	echo "<input type='hidden' name='etape' value='2' />";
	echo "<input type='hidden' name='chmod' value='$chmod' />";
	echo fieldset(_t('entree_base_donnee_1'),
		array(
			'adresse_db' => array(
				'label' => _T('entree_base_donnee_2'),
				'value' => $adresse_db
			),
		)
	);

	echo fieldset(_T('entree_login_connexion_1'),
		array(
			'login_db' => array(
				'label' => _T('entree_login_connexion_2'),
				'value' => $login_db
			),
		)
	);

	echo fieldset(_T('entree_mot_passe_1'),
		array(
			'pass_db' => array(
				'label' => _T('entree_mot_passe_2'),
				'value' => $pass_db
			),
		)
	);

	echo bouton_suivant();
	echo "</form>";

	install_fin_html();
}

?>
