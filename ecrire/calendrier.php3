<?php

include ("inc.php3");


function afficher_mois($jour_today,$mois_today,$annee_today,$nom_mois){
	global $spip_lang_rtl, $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur, $connect_statut;
	global $les_articles;
	global $les_breves;
	global $spip_lang_rtl;
	global $spip_ecran;
	global $couleur_claire, $couleur_foncee;

	// calculer de nouveau la date du jour pour affichage en blanc
	$ce_jour=date("Y-m-d");

	$nom = mktime(1,1,1,$mois_today,1,$annee_today);
	$jour_semaine = date("w",$nom);
	
	
	if ($jour_semaine==0) $jour_semaine=7;
	
	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$largeur_gauche = 130;
	} else {
		$largeur_table = 750;
	$largeur_gauche = 100;
	}
	$largeur_table = $largeur_table - ($largeur_gauche+20);
	$largeur_col = round($largeur_table/7);
	
	
	
	echo "<table cellpadding=0 cellspacing=0 border=0 width='".($largeur_table+10+$largeur_gauche)."'><tr>";
	
	echo "<td width='$largeur_gauche' class='verdana1' valign='top'>";

		// date du jour
		$today=getdate(time());
		$mois=$today["mon"];
		$annee=$today["year"];
		
		if ($mois != $mois_today OR $annee != $annee_today) {
			icone(_T("info_aujourdhui")."<br>".affdate_mois_annee("$annee-$mois-1"), "calendrier.php3", "calendrier-24.gif", "", "center");
			echo "<p />";
		}
		$annee_avant = $annee_today - 1;
		$annee_apres = $annee_today + 1;
		
		
		echo "<div class='verdana1'>";
			echo "<div><b>$annee_avant</b></div>";
			for ($i=$mois_today; $i < 13; $i++) {
				echo "<div style='margin-$spip_lang_left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #cccccc;'><a href='calendrier.php3?mois=$i&annee=$annee_avant'>".nom_mois("$annee_avant-$i-1")."</a></div>";
			}
		
		echo "<div><b>$annee_today</b></div>";
		for ($i=1; $i < 13; $i++) {
			if ($i == $mois_today) {
				echo "<div style='margin-$spip_lang_left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #666666; background-color: white;'><b>".nom_mois("$annee_today-$i-1")."</b></div>";
			}
			else {
				echo "<div style='margin-$spip_lang_left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #cccccc;'><a href='calendrier.php3?mois=$i&annee=$annee_today'>".nom_mois("$annee_today-$i-1")."</a></div>";
			}
		}

			echo "<div><b>$annee_apres</b></div>";
			for ($i=1; $i < $mois_today+1; $i++) {
				echo "<div style='margin-$spip_lang_left: 10px; padding: 2px; -moz-border-radius: 5px; margin-top: 2px; border: 1px solid #cccccc; background-color: #cccccc;'><a href='calendrier.php3?mois=$i&annee=$annee_apres'>".nom_mois("$annee_apres-$i-1")."</a></div>";
		}
		echo "</div>";
	
	echo "</td>";
	echo "<td width='20'>&nbsp;</td>";
	
	echo "<td width='$largeur_table' valign='top'>";
	
	echo "<TABLE border=0 CELLSPACING=0 CELLPADDING=3 WIDTH='$largeur_table'>";

	$mois_suiv=$mois_today+1;
	$annee_suiv=$annee_today;
	$mois_prec=$mois_today-1;
	$annee_prec=$annee_today;

	if ($mois_today==1){
		$mois_prec=12;
		$annee_prec=$annee_today-1;
	}
	if ($mois_today==12){
		$mois_suiv=1;
		$annee_suiv=$annee_today+1;
	}

	// articles du jour
	$query="SELECT * FROM spip_articles WHERE statut='publie' AND date >='$annee_today-$mois_today-0' AND date < DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) ORDER BY date";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$id_article=$row['id_article'];
		$titre=typo($row['titre']);
		$lejour=journum($row['date']);
		$lemois = mois($row['date']);		
		if ($lemois == $mois_today) $les_articles["$lejour"].="<BR><A HREF='articles.php3?id_article=$id_article'><img src='img_pack/puce-verte.gif' width='7' height='7' border='0'> $titre</A>";
	}

	// breves du jour
	$query="SELECT * FROM spip_breves WHERE statut='publie' AND date_heure >='$annee_today-$mois_today-0' AND date_heure < DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) ORDER BY date_heure";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$id_breve=$row['id_breve'];
		$titre=typo($row['titre']);
		$lejour=journum($row['date_heure']);
		$lemois = mois($row['date_heure']);		
		if ($lemois == $mois_today)
			$les_breves["$lejour"].="<BR><A HREF='breves_voir.php3?id_breve=$id_breve'><img src='img_pack/puce-blanche.gif' width='7' height='7' border='0'> <i>$titre</i></A>";
	}


	// rendez-vous personnels
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee_today-$mois_today-1' AND messages.date_heure <= DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$titre=typo($row["titre"]);
		$type=$row["type"];
		$lejour=journum($row['date_heure']);

		if ($type=="normal") {
			$la_couleur = "#0A9C60";
			$couleur_fond = "#BDF0DB";
		}
		elseif ($type=="pb") {
			$la_couleur = "#0000ff";
			$couleur_fond = "#ccccff";
		}
		elseif ($type=="affich") {
			$la_couleur = "#ccaa00";
			$couleur_fond = "#ffffee";
		}
		else {
			$la_couleur="black";
			$couleur_fond="#aaaaaa";
		}
		$les_rv["$lejour"][]="<div style='padding: 2px; margin-top: 2px; background-color: $couleur_fond; border: 1px solid $la_couleur; -moz-border-radius: 3px;' class='arial0'><font color='$la_couleur'><b>".heures($date_heure).":".minutes($date_heure)."</b></font> <a href='message.php3?id_message=$id_message' style='color: black;'>$titre</a></div>";
	}


	echo "<TR><TD style='text-align:$spip_lang_left;'><A HREF='calendrier.php3?mois=$mois_prec&annee=$annee_prec'><img src='img_pack/fleche-$spip_lang_left.png' alt='&lt;&lt;&lt;' width='12' height='12' border='0'></A></TD>";
	echo "<TD style='text-align:center;' COLSPAN=5><FONT FACE='arial,helvetica,sans-serif' SIZE=3><B>".affdate_mois_annee("$annee_today-$mois_today-1")." ".aide ("messcalen")."</B></FONT></TD>";
	echo "<TD style='text-align:$spip_lang_right;'><A HREF='calendrier.php3?mois=$mois_suiv&annee=$annee_suiv'><img src='img_pack/fleche-$spip_lang_right.png' alt='&gt;&gt;&gt;' width='12' height='12' border='0'></A></TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_2')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_3')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_4')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_5')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_6')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_7')."</B></TD>";
	echo "<TD ALIGN='center' width='$largeur_col' style='border-bottom: 1px solid black; border-right: 1px solid black; border-left: 1px solid $couleur_claire; border-top: 1px solid $couleur_claire;'  BGCOLOR='$couleur_foncee'><font class='verdana2' color='#FFFFFF'><B>"._T('date_jour_1')."</B></TD>";

	echo "</TR><TR>";
	
	for ($i=1;$i<$jour_semaine;$i++){
	
		echo "<TD></TD>";
	
	}

	for ($j=1; $j<32;$j++){
		$jour = sprintf("%02d", $j);

		$nom = mktime(1,1,1,$mois_today,$jour,$annee_today);
		$jour_semaine = date("w",$nom);

		if (checkdate($mois_today,$jour,$annee_today)){
			if ("$annee_today-$mois_today-$jour"==$ce_jour) {
				$couleur_lien = "red";
				$couleur_fond = "white";
			}
			else {
				$couleur_lien = "black";
				$couleur_fond = "#e4e4e4";
			}
		
			if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
				echo "<td width='$largeur_col' HEIGHT=80 BGCOLOR='$couleur_fond' VALIGN='top' style='border-bottom: 1px solid white; border-right: 1px solid white; border-left: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa;'><a href='calendrier_jour.php3?jour=$jour&mois=$mois_today&annee=$annee_today'><font face='arial,helvetica,sans-serif' SIZE=3 color='$couleur_lien'><b>$jour</b></a></font>";
			} else {
				echo "<td width='$largeur_col' HEIGHT=80 BGCOLOR='$couleur_fond' VALIGN='top' style='border-bottom: 1px solid white; border-right: 1px solid white; border-left: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa;'><font face='arial,helvetica,sans-serif' SIZE=3 color='$couleur_lien'><b>$jour</b></font>";
			}

			$activer_messagerie = lire_meta("activer_messagerie");
			$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];
			if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=pb' title='"._T("lien_nouvea_pense_bete")."'><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>";
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=normal' title='"._T("lien_nouveau_message")."'><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>";
			}
			if ($connect_statut == "0minirezo")
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=affich' title='"._T("lien_nouvelle_annonce")."'><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>\n";
			echo "<FONT FACE='arial,helvetica,sans-serif' SIZE=1>";
			
			if (count($les_rv[$j])>0){
				echo join($les_rv[$j],"\n");
			}

			echo $les_articles[$j];

			echo $les_breves[$j];
			
			echo "</FONT></TD>";
			
			if ($jour_semaine==0) echo "</TR><TR>";
		}

	}

	echo "</TR></TABLE>";
	echo "</td></tr></table>";

}


// date du jour
$today=getdate(time());
$jour=$today["mday"];

// sans arguments => mois courant
if (!$mois){
	$mois=$today["mon"];
	$annee=$today["year"];
}

$nom_mois = nom_mois('2000-'.sprintf("%02d", $mois).'-01');

debut_page(d_apostrophe(_T('titre_page_calendrier', array('nom_mois' => $nom_mois, 'annee' => $annee)), "asuivre", "calendrier"));
$activer_messagerie = lire_meta("activer_messagerie");
$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];

//echo "<BR><BR><BR>";
if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
	barre_onglets("calendrier", "calendrier");
	echo "<br /><br />";
}

// marges et pied de page supprimes pour prendre toute la largeur
// debut_gauche();
// debut_droite();

afficher_mois($jour,sprintf("%02d", $mois),$annee,$nom_mois);

if (strlen($les_breves["0"]) > 0 OR $les_articles["0"] > 0){
	echo "<table width=200 background=''><tr width=200><td><FONT FACE='arial,helvetica,sans-serif' SIZE=1>";
	echo "<b>"._T('info_mois_courant')."</b>";
	echo $les_breves["0"];
	echo $les_articles["0"];
	echo "</font></td></tr></table>";
}
	
if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
	echo "<br><br><br><table width='700' background=''><tr width='700'><td><FONT FACE='arial,helvetica,sans-serif' SIZE=2>";
	echo "<b>"._T('info_aide')."</b>";
	echo "<br><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_bleu')."\n";
	echo "<br><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_vert')."\n";
	echo "<br><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_jaune')."\n";
	echo "</font></td></tr></table>";
}

// fin_page();

?>
