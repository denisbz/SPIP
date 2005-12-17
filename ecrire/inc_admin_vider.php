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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation.php3");

function admin_vider_dist()
{
  global
    $connect_id_auteur,
    $connect_statut,
    $connect_toutes_rubriques,
    $hash,
    $purger_index,
    $quota_cache,
    $spip_lang;

debut_page(_T('onglet_vider_cache'), "administration", "cache");


echo "<br><br><br>";
gros_titre(_T('titre_admin_vider'));
// barre_onglets("administration", "vider");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_vider');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

// toujours vrai

if ($purger_index == "oui") {
	if (verifier_action_auteur("purger_index", $hash)) {
		include_ecrire('inc_index.php3');
		purger_index();
		creer_liste_indexation();
	}
}




//
// Purger le cache
//


debut_cadre_trait_couleur("cache-24.gif", false, "", _T('texte_vider_cache'));


echo "\n<p align='justify'>"._T('texte_suppression_fichiers')."</p>",
	"<p align='justify'>"._T('texte_recalcul_page')."</p>";


echo "\n<div>&nbsp;</div>";



//
// Quota et taille du cache
//
debut_cadre_relief("", false, "", _T('taille_repertoire_cache'));

list ($taille) = spip_fetch_array(spip_query("SELECT SUM(taille) FROM spip_caches WHERE type='t'"));

if ($taille>0) {
	$info = _T('taille_cache_octets', array('octets' => taille_en_octets($taille)));
} else
	$info = _T('taille_cache_vide');

echo "<p align='justify'><b>$info</b></p>\n";

echo "\n<p align='justify'>";
if ($quota_cache) {
	echo _T('taille_cache_maxi',
		array('octets' => taille_en_octets($quota_cache*1024*1024)));
} else {
	echo _T('taille_cache_infinie');
}
echo ' ('._T('cache_modifiable_webmestre').')</p>';

echo "\n<FORM ACTION='../spip_cache.php3' METHOD='post'>";
echo "\n<INPUT TYPE='hidden' NAME='id_auteur' VALUE='$connect_id_auteur'>";
echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='" . calculer_action_auteur("purger_cache") . "'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_cache' VALUE='oui'>";
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='" . _DIR_RESTREINT_ABS . "admin_vider.php3'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\"".str_replace('"', '&quot;', _T('bouton_vider_cache'))."\"></FORM></DIV>";

fin_cadre_relief();


debut_cadre_relief("image-24.gif", false, "", _T('info_images_auto'));
	echo "<div style='text-align: center;'>";
	echo "<iframe width='530px' height='65px' src='../spip_cache.php3?id_auteur=$connect_id_auteur&hash=".calculer_action_auteur("afficher_cache_images")."&afficher_cache_images=oui&lang=$spip_lang'></iframe>";
	echo "</div>";

echo "\n<FORM ACTION='../spip_cache.php3' METHOD='post'>";
echo "\n<INPUT TYPE='hidden' NAME='id_auteur' VALUE='$connect_id_auteur'>";
echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='" . calculer_action_auteur("purger_cache_images") . "'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_cache_images' VALUE='oui'>";
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='" . _DIR_RESTREINT_ABS . "admin_vider.php3'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\"".str_replace('"', '&quot;', _T('bouton_vider_cache'))."\"></FORM></DIV>";
fin_cadre_relief();

fin_cadre_trait_couleur();

//
// Purger la base d'indexation
//
debut_cadre_trait_couleur("racine-site-24.gif", false, "", _T('texte_effacer_donnees_indexation'));

	echo "\n<p align='justify'>";
	if ($GLOBALS['meta']['activer_moteur'] == 'oui')
		echo _T('texte_moteur_recherche_active');
	else {
		echo "<b>"._T('texte_moteur_recherche_non_active')."</b> ";
		$row = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_index_articles"));
		if ($row['cnt'])
			echo _T('texte_commande_vider_tables_indexation');
		else
			echo _T('texte_tables_indexation_vides');
	
	}
	
	echo "\n<FORM ACTION='admin_vider.php3' METHOD='post'>";
	
	$hash = calculer_action_auteur("purger_index");
	
	echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash'>";
	echo "\n<INPUT TYPE='hidden' NAME='purger_index' VALUE='oui'>";
	echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\""._T('bouton_effacer_index')."\"></FORM></DIV>";

fin_cadre_trait_couleur();

echo "<BR>";

fin_page();
}

?>

