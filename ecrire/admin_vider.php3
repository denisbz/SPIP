<?php

include ("inc.php3");

include_ecrire ("inc_admin.php3");

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

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('texte_vider_cache')."</FONT></B></TD></TR>";

echo "<TR><TD class='serif'>";

echo "\n<p align='justify'>"._T('texte_suppression_fichiers'),
	"<p align='justify'>"._T('texte_recalcul_page');

echo "\n<FORM ACTION='../spip_cache.php3' METHOD='post'>";
echo "\n<INPUT TYPE='hidden' NAME='id_auteur' VALUE='$connect_id_auteur'>";
echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='" . calculer_action_auteur("purger_cache") . "'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_cache' VALUE='oui'>";
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='" . _DIR_RESTREINT_ABS . "admin_vider.php3'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='"._T('bouton_vider_cache')."'></FORM></DIV>";
echo "</TD></TR>";
echo "</TABLE>";


//
// Quota et taille du cache
//
echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#000000'>";
echo _T('taille_repertoire_cache')."</FONT></B></TD></TR>";


echo "<TR><TD class='serif'>";

list ($taille) = spip_fetch_array(spip_query(
"SELECT SUM(taille) FROM spip_caches WHERE type='t'"));

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

	echo "\n";
	echo $compresser_cache? _T('cache_mode_compresse') :
		_T('cache_mode_non_compresse');
	echo ' ('._T('cache_modifiable_webmestre').')</p>';

echo "</TD></TR>";
echo "</TABLE>";



//
// Purger la base d'indexation
//

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('texte_effacer_donnees_indexation')."</FONT></B></TD></TR>";

echo "<TR><TD class='serif'>";

echo "\n<p align='justify'>";
if (lire_meta('activer_moteur') == 'oui')
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

echo "</TD></TR>";
echo "</TABLE>";


fin_cadre_relief();

echo "<BR>";

fin_page();


?>

