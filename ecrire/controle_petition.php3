<?php

include ("inc.php3");



debut_page(_T('titre_page_controle_petition'), "suivi", "suivi-petition");
debut_gauche();


//
// Raccourcis
//
/*
	debut_raccourcis();
	// rien
	fin_raccourcis();
*/

debut_droite();


function controle_forum($request,$adresse_retour) {
	global $debut;
	global $couleur_foncee;
	
	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row=spip_fetch_array($request)){
		$id_signature = $row['id_signature'];
		$id_article = $row['id_article'];
		$date_time = $row['date_time'];
		$nom_email= typo(echapper_tags($row['nom_email']));
		$ad_email = echapper_tags($row['ad_email']);
		$nom_site = typo(echapper_tags($row['nom_site']));
		$url_site = echapper_tags($row['url_site']);
		$message = propre(echapper_tags($row['message']));
		$statut = $row['statut'];
		
		echo "<P>";
		
		if ($statut=="poubelle"){
			echo "<TABLE WIDTH=100% CELLPADDING=2 CELLSPACING=0 BORDER=0><TR><TD BGCOLOR='#FF0000'>";
		}
		
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee' class='verdana2' style='color: white;'><B>$nom_site / $nom_email</B></TD></TR>";
		echo "<TR><TD BGCOLOR='#FFFFFF' class='serif'>";
				
		if ($statut=="publie"){
			icone (_T('icone_supprimer_signature'), "controle_petition.php3?supp_petition=$id_signature&debut=$debut", "forum-interne-24.gif", "supprimer.gif", "right");
		}
		if ($statut=="poubelle"){
			icone (_T('icone_valider_signature'), "controle_petition.php3?add_petition=$id_signature&debut=$debut", "forum-interne-24.gif", "creer.gif", "right");
		}
		
		
		echo "<FONT SIZE=2>".affdate($date_time)."</FONT><BR>";
		if ($statut=="poubelle"){
			echo "<FONT SIZE=1 COLOR='red'>"._T('info_message_efface')."</FONT><BR>";
		}
		if (strlen($url_site)>6 AND strlen($nom_site)>0){
			echo "<FONT SIZE=1>"._T('info_site_web')."</FONT> <A HREF='$url_site'>$nom_site</A><BR>";
		}
		if (strlen($ad_email)>0){
			echo "<FONT SIZE=1>"._T('info_adresse_email')."</FONT> <A HREF='mailto:$ad_email'>$ad_email</A><BR>";
		}
		if (strlen($message)>0) echo "<P>$message";
		
		$query_article="SELECT * FROM spip_articles WHERE id_article=$id_article";
		$result_article=spip_query($query_article);
		while($row=spip_fetch_array($result_article)){
			$id_article = $row['id_article'];
			$titre = typo($row["titre"]);
		}
		echo "<P align='right'><A HREF='../spip_redirect.php3?id_article=$id_article'>$titre</A>";
	
		echo "</TD></TR></TABLE>";
		
		if ($statut=="poubelle"){
			echo "</TD></TR></TABLE>";
		}
	}
}

  
echo "<div class='serif2'>";
 
if ($connect_statut == "0minirezo") {
	gros_titre(_T('titre_suivi_petition'));

	if ($supp_petition){
		$query_forum = "UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$supp_petition";
 		$result_forum = spip_query($query_forum);
	}

	if ($add_petition){
		$query_forum = "UPDATE spip_signatures SET statut='publie' WHERE id_signature=$add_petition";
 		$result_forum = spip_query($query_forum);
	}

	// Invalider les articles correspondant
	if ($id_signature = ($add_petition?$add_petition:$supp_petition)) {
		include_ecrire('inc_invalideur.php3');
		list ($id_article) = spip_fetch_array(spip_query("SELECT id_article
		FROM spip_signatures WHERE id_signature=$id_signature"));
		suivre_invalideur("id='id_article/$id_article'");
	}

	if (!$debut) $debut = 0;

	if ($id_article) {
		$signature_article = " AND id_article=$id_article";
		$url_article = "&id_article=$id_article";
	}
	else
		$signature_article = '';

	$query_forum = "SELECT COUNT(*) AS cnt FROM spip_signatures WHERE (statut='publie' OR statut='poubelle') AND date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)$signature_article";
	$result_forum = spip_query($query_forum);
	$total = 0;
	if ($row = spip_fetch_array($result_forum)) $total = $row['cnt'];

	if ($total > 10) {
		echo "<p>";
		for ($i = 0; $i < $total; $i = $i + 10){
			if ($i > 0) echo " | ";
			if ($i == $debut)
				echo "<FONT SIZE=3><B>$i</B></FONT>";
			else
				echo "<A HREF='controle_petition.php3?debut=$i$url_article'>$i</A>";
		}
	}

	$query_forum = "DELETE FROM spip_signatures WHERE NOT (statut='publie' OR statut='poubelle') AND date_time<DATE_SUB(NOW(),INTERVAL 10 DAY)";
	$result_forum = spip_query($query_forum);

	$query_forum = "SELECT * FROM spip_signatures WHERE (statut='publie' OR statut='poubelle')$signature_article ORDER BY date_time DESC LIMIT $debut,10";
	$result_forum = spip_query($query_forum);
	controle_forum($result_forum, "forum.php3");
}
else {
	echo "<B>"._T('avis_non_acces_page')."</B>";
}


echo "</div>";

fin_page();


?>

