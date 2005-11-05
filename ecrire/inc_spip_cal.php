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

include_ecrire("inc_lang.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_charsets.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_acces.php3");

// avec le nouveau compilateur tout ceci me semble faisable en squelette.

function ligne ($texte) {
	echo filtrer_ical($texte)."\n";
}

function spip_cal_dist($id_auteur, $cle)
{
	  if (verifier_low_sec($id_auteur, $cle, 'ical')) {
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur";
	$result 	= spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$id_utilisateur=$row['id_auteur'];
			$nom_utilisateur=$row['nom'];
			$statut_utilisateur=$row['statut'];
			$langue_utilisateur=$row['lang'];
		}
	  }
	if (!$id_utilisateur) {
		echo _T('info_acces_interdit');
		exit;
	}
	lang_select($langue_utilisateur);
	$nom_site = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");

	@header("Content-Type: text/calendar; charset=utf-8");
	ligne ("BEGIN:VCALENDAR");
	ligne ("CALSCALE:GREGORIAN");
	ligne ("X-WR-CALNAME;VALUE=TEXT:$nom_site / $nom_utilisateur");
	ligne ("X-WR-RELCALID:cal$id_utilisateur @ $adresse_site");
	spip_ical_rendez_vous($id_utilisateur, $nom_site, $adresse_site);
	spip_ical_taches($id_utilisateur, $nom_site, $adresse_site);
	$nb_articles = spip_ical_articles($nom_site, $adresse_site);
	$nb_breves = spip_ical_breves($nom_site, $adresse_site);
	if ($nb_articles || $nb_breves) {
		if ($nb_articles > 0) $titre_prop[] = _T('info_articles_proposes').": ".$nb_articles;
		if ($nb_breves > 0) $titre_prop[] = _T('info_breves_valider').": ".$nb_breves;
		$titre = join($titre_prop," / ");
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:[$nom_site] $titre");
		ligne ("UID:prop @ $adresse_site");
		$texte = join($titres," / ");
		ligne ("DESCRIPTION:$texte");
	
		$today=getdate(time());
		$jour = $today["mday"];
		$mois=$today["mon"];
		$annee=$today["year"];
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
		ligne ("CATEGORIES:"._T('icone_a_suivre'));
		ligne ("URL:$adresse_site/ecrire/");
		ligne ("END:VTODO");
	}
	spip_ical_messages($id_utilisateur, $nom_site, $adresse_site);
	if ($statut_utilisateur == "0minirezo") {
		spip_ical_forums($id_utilisateur, $nom_site, $adresse_site);
	}
	ligne ("END:VCALENDAR");
}

function spip_ical_rendez_vous($id_utilisateur, $nom_site, $adresse_site)
{
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$id_utilisateur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$date_heure_fin=$row["date_fin"];
		//$titre=typo($row["titre"]);
		$titre = $row["titre"];
		$texte = $row["texte"];
		$type=$row["type"];

		if ($type == 'normal') {
			$le_type = _T('info_message_2');
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND lien.id_auteur=auteurs.id_auteur)");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur=$row_auteur['nom'];
				$email = $row_auteur ['email'];

				if ($id_auteur != $id_utilisateur) $titre = $titre." - ".$nom_auteur;
			
				if ($id_auteur == $id_utilisateur) ligne ("ORGANIZER:$nom_auteur <$email>");
				else  ligne ("ATTENDEE:$nom_auteur <$email>");
			}
		}
		else if ($type == 'pb') {
			$le_type = _T('info_pense_bete');
		}
		else if ($type == 'affich') {
			$le_type = _T('info_annonce');
			$titre = "[$nom_site] $titre";
		}

		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:".$titre);
		ligne ("DESCRIPTION:$texte");
		ligne ("UID:mess$id_message @ $adresse_site");
		ligne ("DTSTAMP:".date_ical($date_heure));
		ligne ("DTSTART:".date_ical($date_heure));
		if ($date_heure_fin > $date_heure) ligne ("DTEND:".date_ical($date_heure_fin));
		
		ligne ("CATEGORIES:$le_type");
		ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
		
		ligne ("END:VEVENT");
	}
}

function spip_ical_taches($id_utilisateur, $nom_site, $adresse_site)
{
	$result_messages=spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$id_utilisateur' AND lien.id_message=messages.id_message AND messages.type='pb' AND messages.rv!='oui' AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$titre = $row["titre"];
		$texte = $row["texte"];
		$type=$row["type"];

		if ($type == 'normal') {
			$le_type = _T('info_message_2');
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND lien.id_auteur=auteurs.id_auteur)");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur=$row_auteur['nom'];
				$email = $row_auteur ['email'];

				if ($id_auteur != $id_utilisateur) $titre = $titre." - ".$nom_auteur;
			
				if ($id_auteur == $id_utilisateur) ligne ("ORGANIZER:$nom_auteur <$email>");
				else  ligne ("ATTENDEE:$nom_auteur <$email>");
			}
		}
		else if ($type == 'pb') {
			$le_type = _T('info_pense_bete');
		}
		else if ($type == 'affich') {
			$le_type = _T('info_annonce');
			$titre = "[$nom_site] $titre";
		}
	
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:".$titre);
		ligne ("DESCRIPTION:$texte");
		ligne ("UID:mess$id_message @ $adresse_site");
		ligne ("DTSTAMP:".date_ical($date_heure));
		ligne ("DTSTART:".date_ical($date_heure));
		ligne ("CATEGORIES:$le_type");
		ligne ("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
		ligne ("END:VTODO");
	}
}

function spip_ical_articles($nom_site, $adresse_site)
{
	$titres = Array();
	$result_articles = spip_query("SELECT id_article, titre, date FROM spip_articles WHERE statut = 'prop'");
	while($row=spip_fetch_array($result_articles)){
		$id_article=$row['id_article'];
		$titre = supprimer_numero($row['titre']);
		$titres[] = $titre;
		$date_heure = $row['date'];
		$nb_articles ++;
		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:[$nom_site] $titre ("._T('info_article_propose').")");
		ligne ("UID:article$id_article @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART;VALUE=DATE:".date ("Ymd", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:"._T('info_article_propose'));
		ligne("URL:$adresse_site/ecrire/articles.php3?id_article=$id_article");
		ligne ("END:VEVENT");
	}
	return $nb_articles;
}


function spip_ical_breves($nom_site, $adresse_site)
{
	$result = spip_query("SELECT id_breve, titre, date_heure FROM spip_breves WHERE statut = 'prop'");
	while($row=spip_fetch_array($result)){
		$id_breve=$row['id_breve'];
		$titre = supprimer_numero($row['titre']);
		$titres[] = $titre;
		$date_heure = $row['date_heure'];
		$nb_breves++;
		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:[$nom_site] $titre ("._T('item_breve_proposee').")");
		ligne ("UID:breve$id_breve @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART;VALUE=DATE:".date ("Ymd", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:"._T('item_breve_proposee'));
		ligne ("URL:$adresse_site/ecrire/breves_voir.php3?id_breve=$id_breve");
		ligne ("END:VEVENT");
	}
	return $nb_breves;
}


function spip_ical_messages($id_utilisateur, $nom_site, $adresse_site)
{
	$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$id_utilisateur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
	while($row=spip_fetch_array($result_messages)){
		$id_message=$row['id_message'];
		$date_heure=$row["date_heure"];
		$titre = $row["titre"];
		$texte = $row["texte"];
		$type=$row["type"];

		if ($type == 'normal') {
			$le_type = _T('info_message_2');
			$result_auteurs=spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE (lien.id_message='$id_message' AND lien.id_auteur=auteurs.id_auteur)");
			while($row_auteur=spip_fetch_array($result_auteurs)){
				$id_auteur=$row_auteur['id_auteur'];
				$nom_auteur = $row_auteur['nom'];
				$email = $row_auteur ['email'];

				if ($id_auteur != $id_utilisateur) $titre = $nom_auteur." - ".$titre;
			
				if ($id_auteur == $id_utilisateur) ligne ("ORGANIZER:$nom_auteur <$email>");
				else  ligne ("ATTENDEE:$nom_auteur <$email>");
			}
			$query_forum = "SELECT * FROM spip_forum WHERE statut='perso' AND id_message='$id_message' ORDER BY date_heure DESC LIMIT 1";
			$result_forum = spip_query($query_forum);
			if ($row_forum = spip_fetch_array($result_forum)) {
				$date_heure = $row_forum["date_heure"];
				$texte = $row_forum["texte"];
				$titre = $row_forum["titre"];
				$id_auteur = $row_forum["id_auteur"];

				$result_auteurs2 = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur = $id_auteur");
				if ($row_auteur2 = spip_fetch_array($result_auteurs2)){
					$nom_auteur = $row_auteur2['nom'];
					$email = $row_auteur2 ['email'];
				
					$titre = $nom_auteur." - ".$titre;
				}
			}
		}
		else if ($type == 'pb') {
			$le_type = _T('info_pense_bete');
		}
		else if ($type == 'affich') {
			$le_type = _T('info_annonce');
			$titre = "[$nom_site] $titre";
		}
	
	ligne ("BEGIN:VTODO");
	ligne ("SUMMARY:".$titre);
	ligne ("DESCRIPTION:$texte");
	ligne ("UID:nouv_mess$id_message @ $adresse_site");
	ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
	ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
	ligne ("CATEGORIES:$le_type");
	ligne("URL:$adresse_site/ecrire/message.php3?id_message=$id_message");
	ligne ("END:VTODO");
	}	
}

function spip_ical_forums($id_utilisateur, $nom_site, $adresse_site)
{
	$query_forum = "SELECT * FROM spip_forum WHERE statut = 'prop'";
	$result_forum = spip_query($query_forum);

	while($row=spip_fetch_array($result_forum)){
		$nb_forum ++;
	
		$id_forum=$row['id_forum'];
		$date_heure = $row['date_heure'];
		$titre = $row['titre'];
		$texte = $row['texte'];
		$auteur = $row['auteur'];
		$email_auteur = $row['email_auteur'];
		if ($email_auteur) $email_auteur = "<$email_auteur>";
		
		ligne ("BEGIN:VEVENT");
		ligne ("SUMMARY:[$nom_site] $titre "._T('icone_forum_suivi'));
		ligne ("DESCRIPTION:$texte\r$auteur $email_auteur");
		ligne ("UID:forum$id_forum @ $adresse_site");
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure),0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("DTEND:".date ("Ymd\THis", mktime (heures($date_heure),minutes($date_heure)+60,0,mois($date_heure),jour($date_heure),annee($date_heure))));
		ligne ("CATEGORIES:"._T('icone_forum_suivi'));
		ligne("URL:$adresse_site/ecrire/controle_forum.php3");
		ligne ("END:VEVENT");
	}

	if ($nb_forum > 0) {
		ligne ("BEGIN:VTODO");
		ligne ("SUMMARY:[$nom_site] "._T('icone_forum_suivi').": $nb_forum");
		ligne ("UID:forum @ $adresse_site");
		
		$today=getdate(time());
		$jour = $today["mday"];
		$mois=$today["mon"];
		$annee=$today["year"];
		ligne ("DTSTAMP:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
		ligne ("DTSTART:".date ("Ymd\THis", mktime (12,0,0,$mois,$jour,$annee)));
		ligne ("CATEGORIES:"._T('icone_forum_suivi'));
		ligne("URL:$adresse_site/ecrire/controle_forum.php3");
		ligne ("END:VTODO");
	}
}