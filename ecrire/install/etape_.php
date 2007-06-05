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
	include_spip('inc/headers');
	utiliser_langue_visiteur();
	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues) {
		redirige_par_entete(generer_test_dirs());
	} else {
		include_spip('inc/presentation'); // pour info_copyright
		echo install_debut_html();
		echo "<div><img alt='SPIP' src='" . _DIR_IMG_PACK . "logo-spip2.gif' /></div>\n",
			"<div class='petit-centre'><p>",info_copyright(),"</p></div>\n",
			"<p>",_T('install_select_langue'),"</p>",
			"<div>",$menu_langues,"</div>\n",
			generer_test_dirs('', bouton_suivant());
		echo install_fin_html();
	}
}
?>
