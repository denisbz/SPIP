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

/* Ce fichier contient des fonctions, globales ou constantes	*/
/* qui ont fait partie des fichiers de configurations de Spip	*/
/* mais en ont ete retires ensuite.				*/
/* Ce fichier n'est donc jamais charge par la presente version	*/
/* mais est present pour que les contributions � Spip puissent	*/
/* fonctionner en chargeant ce fichier, en attendant d'etre	*/
/* reecrites conformement a la nouvelle interface.		*/


function debut_raccourcis() {
        global $spip_display;
        echo "<div>&nbsp;</div>";
        creer_colonne_droite();

        debut_cadre_enfonce();
        if ($spip_display != 4) {
                echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
                echo "<b>"._T('titre_cadre_raccourcis')."</b><p />";
        } else {
                echo "<h3>"._T('titre_cadre_raccourcis')."</h3>";
                echo "<ul>";
        }
}

function fin_raccourcis() {
        global $spip_display;
        
        if ($spip_display != 4) echo "</font>";
        else echo "</ul>";
        
        fin_cadre_enfonce();
}

function include_ecrire($file, $silence=false) {
	preg_match('/^((inc_)?([^.]*))(\.php[3]?)?$/', $file, $r);

	// Version new style, surchargeable
	# cas speciaux
	if ($r[3] == 'index') return include_spip('inc/indexation');
	if ($r[3] == 'db_mysql') return include_spip('base/db_mysql');
	if ($r[3] == 'connect') { spip_connect(); return; }

	# cas general
	if ($f=include_spip('inc/'.$r[3]))
		return $f;

	// fichiers old-style, ecrire/inc_truc.php
	if (is_readable($f = _DIR_RESTREINT . $r[1] . '.php'))
		return include_once($f);
}

function lire_meta($nom) { global $meta; return $meta[$nom];}

function afficher_script_layer(){echo $GLOBALS['browser_layer'];}

function test_layer(){return $GLOBALS['browser_layer'];}

?>
