<?php

include ("inc.php3");


debut_page(_T('titre_page_admin_effacer'), "administration", "base");


echo "<br><br><br>";
gros_titre(_T('titre_admin_effacer'));
barre_onglets("administration", "effacer");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_effacer');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}



//
// Effacement total
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('texte_effacer_base')."</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

echo "\n<p align='justify'>";
echo '<img src="img_pack/warning.gif" alt="'._T('info_avertissement').'" width="48" height="48" align="right">';
echo _T('texte_admin_effacer_01');

echo "<CENTER>";

debut_boite_alerte();

echo "\n<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
echo "\n<p align='justify'><b>"._T('avis_suppression_base')."&nbsp;!</b>";

echo "\n<FORM ACTION='delete_all.php3' METHOD='get'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='"._T('bouton_effacer_tout')."'></FORM></DIV>";

echo "\n</FONT>";

fin_boite_alerte();

echo "</CENTER>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";

fin_cadre_relief();

echo "<BR>";




fin_page();


?>

