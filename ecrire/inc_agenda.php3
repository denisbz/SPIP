<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_AGENDA")) return;
define("_ECRIRE_INC_AGENDA", "1");



//
// Afficher un agenda (un mois) sous forme de petit tableau
//

function agenda ($mois, $annee, $jour_ved, $mois_ved, $annee_ved, $semaine = false) {
	global $couleur_foncee, $couleur_claire;
	global $connect_id_auteur;
	global $spip_lang_left, $spip_lang_right;

	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];


	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);


	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$date_heure=$row["date_heure"];
		$lejour=journum($row['date_heure']);
		$les_rv[$lejour] ++;
	}

	
	$nom = mktime(1,1,1,$mois,1,$annee);
	$jour_semaine = date("w",$nom);
	$nom_mois = nom_mois('2000-'.sprintf("%02d", $mois).'-01');
	if ($jour_semaine==0) $jour_semaine=7;
	
	if ($semaine) {
		$jour_valide = mktime(1,1,1,$mois_ved,$jour_ved,$annee_ved);
		$jour_semaine_valide = date("w",$jour_valide);
		if ($jour_semaine_valide==0) $jour_semaine_valide=7;
		$debut = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+1,$annee_ved);
		$fin = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+7,$annee_ved);
	}
	
	echo "<div align='center' style='padding: 5px;'><b class='verdana1'><a href='calendrier.php3?mois=$mois&&annee=$annee' style='color: black;'>".affdate_mois_annee("$annee-$mois-1")."</a></b></div>";
	
	echo "<table width='100%' cellspacing='0' cellpadding='0'>";

	echo "<tr>";
	for ($i=1;$i<$jour_semaine;$i++){
		echo "<td></td>";
	}

	for ($j=1; $j<32; $j++) {
		$jour_j = sprintf("%02d", $j);
		$nom = mktime(1,1,1,$mois,$jour_j,$annee);
		$jour_semaine = date("w",$nom);
		if ($jour_semaine==0) $jour_semaine=7;
		
		if (checkdate($mois,$j,$annee)){

			if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
				if ($semaine) $lien = "calendrier_jour.php3";
				else $lien = "calendrier_semaine.php3";
				echo "<td class='arial2' style='margin: 1px; padding: 2px; background-color: white; border: 1px solid $couleur_foncee; text-align: center; -moz-border-radius: 5px;'>";
				echo "<a href='$lien?jour=$j&mois=$mois&annee=$annee' style='color: black'><b>$j</b></a>";
				echo "</td>";
			} else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
				if ($jour_semaine==1) {
					$style = "-moz-border-radius-top$spip_lang_left: 10px; -moz-border-radius-bottom$spip_lang_left: 10px;";
				}
				else if ($jour_semaine==7) {
					$style = "-moz-border-radius-top$spip_lang_right: 10px; -moz-border-radius-bottom$spip_lang_right: 10px;";
				}
				else {
					$style = "";
				}
				echo "<td class='arial2' style='margin: 0px; padding: 3px; background-color: white; text-align: center; $style'>";
				echo "<a href='calendrier_semaine.php3?jour=$j&mois=$mois&annee=$annee' style='color: black'><b>$j</b></a>";
				echo "</td>";
			} else {
				if ($j == $jour_today AND $mois == $mois_today AND $annee == $annee_today) {
					$couleur_fond = $couleur_foncee;
					$couleur = "white";
				}
				else {
					if ($jour_semaine == 7) {
						$couleur_fond = "#aaaaaa";
						$couleur = "white";
					} else {
						$couleur_fond = "#ffffff";
						$couleur = "#aaaaaa";
					}
					if ($les_rv[$j] > 0) {
						$couleur = "black";
					}
				}
				echo "<td>";
				echo "<div class='arial2' style='margin-left: 1px; margin-top: 1px; padding: 2px; background-color: $couleur_fond; text-align: center; -moz-border-radius: 5px;'>";
				if ($semaine) echo "<a href='calendrier_semaine.php3?jour=$j&mois=$mois&annee=$annee' style='color: $couleur;'>$j</a>";
				else echo "<a href='calendrier_jour.php3?jour=$j&mois=$mois&annee=$annee' style='color: $couleur;'>$j</a>";
				echo "</div>";
				echo "</td>";
			}			
			
			if ($jour_semaine==7) echo "</tr>\n<tr>";

		}	
	
	}
	echo "</tr>\n";
	echo "</table>";

}



function calendrier_jour($jour,$mois,$annee,$large = "large", $le_message = 0) {
	global $spip_lang_rtl, $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur, $connect_statut;
	global $couleur_claire;


	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);


	if ($large == "large") {
		$largeur = 300;
		$modif_decalage = 40;
		$debut_gauche = 40;
	} else if ($large == "col") {
		$largeur = 120;
		$modif_decalage = 15;
		$debut_gauche = 20;
	} else {
		$largeur = 80;
		$modif_decalage = 5;
		$debut_gauche = 5;
	}
	
	$bgcolor = "white";
	
	if ($large == "etroit") {
		$bgcolor = "#eeeeee";
		
		$today=getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		
		if ($jour == $jour_today AND $mois == $mois_today AND $annee == $annee_today) $bgcolor = "white";
	}
	
	$nom = mktime(1,1,1,$mois,$jour,$annee);
	$jour_semaine = date("w",$nom);
	if ($jour_semaine == 0) $bgcolor = "#e0e0e0";

	if ($large == "col" ) {
		echo "<div align='center' style='padding: 5px;'><b class='verdana1'><a href='calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee' style='color:black;'>".affdate_jourcourt("$annee-$mois-$jour")."</a></b></div>";
	}
	else if ($large == "large") {
		echo "<div align='center' style='padding: 5px;'>";
		echo " <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=pb' class='arial1' style='color: blue;'><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0' alt='' /> "._T("lien_nouvea_pense_bete")."</a>";
		echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=normal' class='arial1' style='color: green;'><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0' alt='' /> "._T("lien_nouveau_message")."</a>";

		if ($connect_statut == "0minirezo")
			echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=affich' class='arial1' style='color: #ff9900;'><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0' alt='' /> "._T("lien_nouvelle_annonce")."</a>\n";
		echo "</div>";
	}
	if ($large != "etroit") echo "<div style='background-color: $bgcolor; border-left: 1px solid #aaaaaa; border-right: 1px solid #aaaaaa; border-bottom: 1px solid #aaaaaa;'>"; // bordure
	else echo "<div style='background-color: $bgcolor;'>"; // bordure

	echo "<div style='position: relative; width: 100%; height: 450px; background: url(img_pack/fond-calendrier.gif);'>";
	
	echo "<div style='position: absolute; $spip_lang_left: 2px; top: 2px; color: #666666;' class='arial0'><b class='arial0'>0:00<br />7:00</b></div>";
	for ($i = 7; $i < 20; $i++) {
		echo "<div style='position: absolute; $spip_lang_left: 2px; top: ".(($i-6)*30+2)."px; color: #666666;' class='arial0'><b class='arial0'>$i:00</b></div>";
	}
	echo "<div style='position: absolute; $spip_lang_left: 2px; top: 422px; color: #666666;' class='arial0'><b class='arial0'>20:00<br />23:59</b></div>";


	// articles du jour
	$query="SELECT * FROM spip_articles WHERE statut='publie' AND date >='$annee-$mois-$jour' AND date < DATE_ADD('$annee-$mois-$jour', INTERVAL 1 DAY) ORDER BY date";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$id_article=$row['id_article'];
		$titre=typo($row['titre']);
		$lejour=journum($row['date']);
		$lemois = mois($row['date']);		
		$les_articles.="<div><a href='articles.php3?id_article=$id_article' class='arial1'><img src='img_pack/puce-verte-breve.gif' width='8' height='9' border='0'> $titre</a></div>";
	}

	// breves du jour
	$query="SELECT * FROM spip_breves WHERE statut='publie' AND date_heure >='$annee-$mois-$jour' AND date_heure < DATE_ADD('$annee-$mois-$jour', INTERVAL 1 DAY) ORDER BY date_heure";
	$result=spip_query($query);
	while($row=spip_fetch_array($result)){
		$id_breve=$row['id_breve'];
		$titre=typo($row['titre']);
		$lejour=journum($row['date_heure']);
		$lemois = mois($row['date_heure']);		
		$les_breves.="<div><a href='breves_voir.php3?id_breve=$id_breve' class='arial1'><img src='img_pack/puce-blanche-breve.gif' width='8' height='9' border='0'> $titre</a></div>";
	}

	if ($large == "large") {
		if ($les_articles OR $les_breves) {
			if ($les_articles) $les_articles = "<div><b class='verdana1'>"._T('info_articles')."</b></div>".$les_articles;
			if ($les_breves) $les_breves = "<div><b class='verdana1'>"._T('info_breves_02')."</b></div>".$les_breves;
			echo "<div style='position: absolute; $spip_lang_left: 355px; top: 32px; width: 140px;'>";
			echo $les_articles;
			echo $les_breves;
			echo "</div>";
		}
	}

	// rendez-vous personnels
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure <='$annee-$mois-$jour 23:59:00' AND messages.date_fin > '$annee-$mois-$jour 00:00:00' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
				// Note: le $date_fin est strictement superieur a minuit
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$titre=propre($row["titre"]);
		$texte = propre($row["texte"]);
		$type=$row["type"];
		$lejour=journum($row['date_heure']);

		if ($type=="normal") {
			$la_couleur = "#02531B";
			$couleur_fond = "#CFFEDE";
		}
		elseif ($type=="pb") {
			$la_couleur = "#3874B0";
			$couleur_fond = "#EDF3FE";
		}
		elseif ($type=="affich") {
			$la_couleur = "#ccaa00";
			$couleur_fond = "#ffffee";
		}
		else {
			$la_couleur="black";
			$couleur_fond="#aaaaaa";
		}



		$heure_debut = heures($date_heure);
		$minutes_debut = minutes($date_heure);
		$jour_debut = journum($date_heure);
		$mois_debut = mois($date_heure);
		$annee_debut = annee($date_heure);

		// Verifier si debut est jour precedent
		$unix_debut = date("U", mktime($heures_debut,$minutes_debut,0,$mois_debut, $jour_debut, $annee_debut));
		$unix_debut_today = date("U", mktime(0,0,0,$mois, $jour, $annee));

		if ($unix_debut < $unix_debut_today) {
			$heure_debut = 0;
			$minutes_debut = 0;
		}

		// Verifier si fin est jour suivant
		$heure_fin = heures($date_fin);
		$minutes_fin = minutes($date_fin);
		$jour_fin = journum($date_fin);
		$mois_fin = mois($date_fin);
		$annee_fin = annee($date_fin);


		$unix_fin = date("U", mktime($heures_fin,$minutes_fin,0,$mois_fin, $jour_fin, $annee_fin));
		$unix_fin_today = date("U", mktime(23,59,0,$mois, $jour, $annee));

		if ($unix_fin > $unix_fin_today) {
			$heure_fin = 23;
			$minutes_fin = 59;
		}

		// Corriger pour l'affichage dans le tableau (debut et fin de tableau sont reduits)
		if ($heure_debut < 6) {
			$heure_debut = 6;
			$minutes_debut = 0;	
		}
		if ($heure_fin < 7) {
			$heure_fin = 7;
			$minutes_fin = 00;
		}
		
		if ($heure_debut > 20) {
			$heure_debut = 20;
			$minutes_debut = 0;
		}
		if ($heure_fin > 20) {
			$heure_fin = 21;
			$minutes_fin = 00;
		}
		
		$haut = floor((($heure_debut - 6)*60 + $minutes_debut)/2);
		$bas = floor((($heure_fin - 6)*60 + $minutes_fin)/2);
		
		$hauteur = ($bas-$haut) - 7;
		if ($hauteur < 23) $hauteur = 23;
		
		if ($bas_prec > $haut) $decalage = $decalage + $modif_decalage;
		else $decalage = $debut_gauche;
		
		if ($bas > $bas_prec) $bas_prec = $bas;		
		
		if ($le_message == $id_message)	$couleur_cadre = "black";
		else $couleur_cadre = "$la_couleur";
		
		
		echo "<div class='dessous'  style='position: absolute; $spip_lang_left: ".$decalage."px; top: ".$haut."px; height: ".($hauteur+8)."px; width: ".($largeur+8)."px; ' onClick=\"document.location='message.php3?id_message=$id_message'\" onMouseOver=\"changeclass(this, 'dessus');\" onMouseOut=\"changeclass(this, 'dessous');\">";
		echo "<div style='position: absolute;  height: ".$hauteur."px; width: ".$largeur."px;  border: 1px solid $la_couleur; padding: 3px; background-color: $couleur_fond; -moz-border-radius: 5px;'>";
		echo "</div>";
		echo "<div style='position: absolute; overflow: hidden; height: ".$hauteur."px; width: ".$largeur."px;  border: 1px solid $couleur_cadre; padding: 3px; -moz-border-radius: 5px;'>";
		echo "<div><b><a href='message.php3?id_message=$id_message' class='verdana1' style='color: $la_couleur;'>$titre</a></b></div>";
		
		if ($type == "normal") {
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND (auteurs.id_auteur!='$connect_id_auteur' AND lien.id_auteur=auteurs.id_auteur))");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur=$row_auteur['nom'];
				$les_auteurs[$id_message][] = $nom_auteur;
			}
			if (count($les_auteurs[$id_message]) > 0) {
				echo "<div><font class='verdana1'>".join($les_auteurs[$id_message],", ")."</font></div>";
			}
		}
		
		if ($large) echo "<div><a href='message.php3?id_message=$id_message' class='arial1' style='color: #333333; text-decoration: none;'>$texte</a></div>";
		echo "</div>";
		echo "</div>";
	}

	echo "</div>";
	echo "</div>";
	
	if ($large != "large") {
		if ($les_articles OR $les_breves) {
			if ($les_articles) $les_articles = "<div><b class='verdana1'>"._T('info_articles')."</b></div>".$les_articles;
			if ($les_breves) $les_breves = "<div><b class='verdana1'>"._T('info_breves_02')."</b></div>".$les_breves;
			echo "<div style='padding: 5px;'>";
			echo $les_articles;
			echo $les_breves;
			echo "</div>";
		}
	}

	
}

function liste_rv($query, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right;
	
	if ($type == annonces) {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == pb) {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == rv) {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	$result = spip_query($query);
	if (spip_num_rows($result) > 0){
		
		debut_cadre_enfonce("", false, "", $titre);

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>";
		while ($row = spip_fetch_array($result)) {
		
			if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
				$url = $match[1];
			else
				$url = "message.php3?id_message=".$row['id_message'];
				$type=$row['type'];
				$rv = $row['rv'];
				$date = $row['date_heure'];
				$date_fin = $row['date_fin'];

				if ($type=="normal") $bouton = "message";
				elseif ($type=="pb") $bouton = "pense-bete";
				elseif ($type=="affich") $bouton = "annonce";
				else $bouton = "message";
						
			$titre = typo($row['titre']);
			
			if ($rv == "oui") {
				echo "<tr><td colspan='2'>";
				$date_jour = affdate_jourcourt($date);
				if ($date_jour != $date_rv) echo "<div class='arial11'><b>$date_jour</b></div>";
				echo "</td></tr>";
			}
			
			echo "<tr>";
			echo "<td width='24' valign='middle'>";
				echo "<a href='$url'>";
				if ($rv == "oui") echo "<img src='img_pack/rv.gif' style='background: url(img_pack/$bouton.gif) no-repeat;' border='0'>";
				else echo "<img src='img_pack/$bouton.gif' border='0'>";
				echo "</a>";
			echo "</td>";
			
			echo "<td valign='middle'>";
				if ($rv == "oui") {
					if (affdate($date) == affdate($date_fin)) 
						echo "<div class='arial0' style='float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px; background: url(img_pack/fond-agenda.gif) $spip_lang_right center no-repeat;'>".heures($date).":".minutes($date)."<br />".heures($date_fin).":".minutes($date_fin)."</div>";
					else {
						echo "<div class='arial0' style='float: $spip_lang_left; line-height: 12px; color: #666666; margin-$spip_lang_right: 3px; padding-$spip_lang_right: 4px; background: url(img_pack/fond-agenda.gif) $spip_lang_right center no-repeat; text-align: center;'>".heures($date).":".minutes($date)."<br />...</div>";
					}
				}
			
				echo "<div><b><a href='$url' class='arial1' style='color: #333333;'>$titre</a></b></div>";
			echo "</td>";
			echo "</tr>\n";
			
			$date_rv = $date_jour;
			
		}
		echo "</table>";
		fin_cadre_enfonce();
	}
}

function afficher_annonces () {
	global $connect_id_auteur, $options;
	$query = "SELECT * FROM spip_messages WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC";
	liste_rv($query, "annonces");
}

function afficher_taches () {
	global $connect_id_auteur, $options;
	$query = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'";
	liste_rv($query, "pb");

	$query = "SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND ( (messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH)) OR (messages.date_heure < NOW() AND messages.date_fin > NOW() ))  AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure";
	liste_rv($query, "rv");
}


// afficher l'encadre "lien iCal"
function afficher_ical($id) {
	echo debut_cadre_enfonce();
	echo "<div class='verdana1'>"._T("calendrier_synchro")."</div>";
	icone_horizontale (_T("icone_suivi_activite"), "synchro.php3", "synchro-24.gif");
	echo fin_cadre_enfonce();
}

?>
