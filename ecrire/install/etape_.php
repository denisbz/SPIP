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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// http://doc.spip.org/@inc_install_
function install_etape__dist()
{
	global $spip_lang_right;

	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues) {
		include_spip('inc/headers');
		redirige_par_entete(generer_url_action('test_dirs'));
	} else {
		echo install_debut_html();
		echo "<div><img alt='SPIP' src='" . _DIR_IMG_PACK . "logo-spip.gif' /></div>\n",
			"<div class='petit-centre'><p>",info_copyright(),"</p></div>\n",
			"<p>",_T('install_select_langue'),"</p>",
			"<div>",$menu_langues,"</div>\n",
			"<form action='", generer_url_action('test_dirs'),"'>\n",
			'<input type="hidden" name="action" value="test_dirs" />',
			bouton_suivant(),
			"</form>";
		echo install_fin_html();
	}
}

?>
