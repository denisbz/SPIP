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



include ("inc.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_rubriques.php3");


if ($new == "oui") {
	if (($connect_statut=='0minirezo') AND acces_rubrique($id_parent)) {
		$id_parent = intval($id_parent);
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$descriptif = "";
		$texte = "";
	}
	else {
		echo _T('avis_acces_interdit');
		exit;
	}
}
else {
	$query = "SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$id_secteur = $row['id_secteur'];
		$extra = $row["extra"];
	}
}

debut_page(_T('info_modifier_titre', array('titre' => $titre)), "documents", "rubriques");

if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";

if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
else {
	$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_parent'";
 	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$parent_parent=$row['id_parent'];
	}
	if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
	else $logo_parent = "rubrique-24.gif";
}



debut_grand_cadre();

afficher_hierarchie($id_parent);

fin_grand_cadre();

debut_gauche();
//////// parents



debut_droite();

debut_cadre_formulaire();

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";

if ($id_rubrique) icone(_T('icone_retour'), "naviguer.php3?id_rubrique=$id_rubrique", $ze_logo, "rien.gif");
else icone(_T('icone_retour'), "naviguer.php3?id_rubrique=$id_parent", $ze_logo, "rien.gif");

echo "</td>";
echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
echo "<td width='100%'>";
echo _T('info_modifier_rubrique');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

if ($id_rubrique > 0)
	echo "<FORM ACTION='naviguer.php3?id_rubrique=$id_rubrique' METHOD='post'>";
else
	echo "<FORM ACTION='naviguer.php3' METHOD='post'>";

$titre = entites_html($titre);

echo _T('entree_titre_obligatoire');
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40' $onfocus><P>";


debut_cadre_couleur("$logo_parent", false, '', _T('entree_interieur_rubrique').aide ("rubrub"));

// selecteur de rubriques
include_ecrire('inc_rubriques.php3');
$restreint = ($GLOBALS['statut'] == 'publie');
echo selecteur_rubrique($id_parent, 'rubrique', $restreint, $id_rubrique);


// si c'est une rubrique-secteur contenant des breves, demander la
// confirmation du deplacement
$query = "SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique='$id_rubrique'";
$row = spip_fetch_array(spip_query($query));
$contient_breves = $row['cnt'];
if ($contient_breves > 0) {
	$scb = ($contient_breves>1? 's':'');
	echo "<div><font size='2'><input type='checkbox' name='confirme_deplace'
	value='oui' id='confirme-deplace'
	><label for='confirme-deplace'>&nbsp;"
	._T('avis_deplacement_rubrique',
		array('contient_breves' => $contient_breves,
			'scb' => $scb))
	."</font></label></div>\n";
} else
	echo "<input type='hidden' name='confirme_deplace' value='oui' />\n";


fin_cadre_couleur();

echo "<P>";


if ($options == "avancees" OR $descriptif) {
	echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
	echo _T('entree_contenu_rubrique')."<BR>";
	echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo $descriptif;
	echo "</TEXTAREA><P>\n";
}
else {
	echo "<INPUT TYPE='Hidden' NAME='descriptif' VALUE=\"".entites_html($descriptif)."\" />";
}

echo "<B>"._T('info_texte_explicatif')."</B>";
echo aide ("raccourcis");
echo "<BR><TEXTAREA NAME='texte' ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
echo $texte;
echo "</TEXTAREA>\n";

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($extra, 'rubriques', $id_secteur);
	}

echo "<input type='hidden' name='action' value='",
	  (($new == "oui") ? 'creer' : 'modifier'),
	  "' />";

echo "\n<p align='right'><input type='submit' value='"._T('bouton_enregistrer')."' CLASS='fondo' />\n</p></form>";

fin_cadre_formulaire();

fin_page();

?>
