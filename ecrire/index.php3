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
include_ecrire ("inc_calendrier.php");

debut_page(_T('titre_page_index'), "asuivre", "asuivre");

debut_gauche();


if ($spip_display != 4) {
	
	//
	// Infos personnelles : nom, utilisation de la messagerie
	//
	
	
	echo "<p>";
	
	$titre_cadre = afficher_plus("auteurs_edit.php3?id_auteur=$connect_id_auteur");
	$titre_cadre .= majuscules(typo($connect_nom));
	
	debut_cadre_couleur_foncee("fiche-perso-24.gif", false, '', '');
	echo "<center><b>".$titre_cadre."</b></center>";
	fin_cadre_couleur_foncee();
	

	//
	// Supprimer le cookie, se deconnecter...
	//
	
	if ($connect_statut == "0minirezo" AND $cookie_admin) {
		$texte = _T('icone_supprimer_cookie');
		if ($spip_display != 1) $texte .= aide("cookie");
		icone_horizontale( $texte , "../spip_cookie.php3?cookie_admin=non&url=".rawurlencode(_DIR_RESTREINT_ABS), "cookie-24.gif", "");
	}

	$nom_site_spip = propre(lire_meta("nom_site"));
	if (!$nom_site_spip) $nom_site_spip="SPIP";
	
	
	echo "<div>&nbsp;</div>";
	
	echo debut_cadre_relief("racine-site-24.gif", false, "", $nom_site_spip);


	if ($spip_display != 1) {
		include_ecrire("inc_logos.php3");
	
		$logo = decrire_logo("rubon0");
		if ($logo) {
			echo "<div style='text-align:center; margin-bottom: 5px;'><a href='naviguer.php3'>";
			echo $logo[2];
			echo "</a></div>";
		}
	}
	echo "<div class='verdana1'>";

    $res = spip_query("SELECT count(*) AS cnt, statut FROM spip_articles GROUP BY statut");
  
  while($row = spip_fetch_array($res)) {
    $var  = 'nb_art_'.$row['statut'];
    $$var = $row['cnt']; 
  }
  
	if ($nb_art_prepa OR $nb_art_prop OR $nb_art_publie) {

		echo afficher_plus("articles_page.php3")."<b>"._T('info_articles')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_art_prepa) echo "<li>"._T("texte_statut_en_cours_redaction").": ".$nb_art_prepa;
		if ($nb_art_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_art_prop;
		if ($nb_art_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_art_publie."</b>";
		echo "</ul>";

	}

	$res = spip_query("SELECT count(*) AS cnt, statut FROM spip_breves GROUP BY statut");


	while($row = spip_fetch_array($res)) {
		$var  = 'nb_bre_'.$row['statut'];
		$$var = $row['cnt']; 
	}

	if ($nb_bre_prop OR $nb_bre_publie) {
		echo afficher_plus("breves.php3")."<b>"._T('info_breves_02')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_bre_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_bre_prop;
		if ($nb_bre_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_bre_publie."</b>";
		echo "</ul>";
	}

	$result = spip_fetch_array(spip_query("SELECT count(*) AS cnt FROM spip_forum where statut='publie'"));

	$nb_forum = $result['cnt'];

	if ($nb_forum) {
		if ($connect_statut == "0minirezo") echo afficher_plus("controle_forum.php3");
		echo "<b>"._T('onglet_messages_publics')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		echo "<li><b>".$nb_forum."</b>";
		echo "</ul>";
	}

	$res = spip_query("SELECT count(*) AS cnt, statut FROM spip_auteurs GROUP BY statut");


	while($row = spip_fetch_array($res)) {
		$var  = 'nb_aut_'.$row['statut'];
		$$var = $row['cnt']; 
	}

	if ($nb_aut_0minirezo OR $nb_aut_1comite OR $nb_aut_6forum) {
		echo afficher_plus("auteurs.php3")."<b>"._T('icone_auteurs')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_aut_0minirezo) echo "<li>"._T("info_administrateurs").": ".$nb_aut_0minirezo;
		if ($nb_aut_1comite) echo "<li>"._T("info_redacteurs").": ".$nb_aut_1comite;
		if ($nb_aut_6forum) echo "<li>"._T("info_visiteurs").": ".$nb_aut_6forum;
		echo "</ul>";
	}

	echo "</div>";

	echo fin_cadre_relief();


//
// Afficher les raccourcis : boutons de creation d'article et de breve, etc.
//


	creer_colonne_droite();
	echo "<div>&nbsp;</div>";	


	
	//
	// Annonces
	//
	echo    http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces");
	echo    http_calendrier_rv(sql_calendrier_taches_pb(),"pb") ;
	echo    http_calendrier_rv(sql_calendrier_taches_rv(), "rv");

	
	//
	// Afficher le calendrier du mois s'il y a des rendez-vous
	//
	
		$today = getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		$date = date("Y-m-d", mktime(0,0,0,$mois_today, 1, $annee_today));
		$mois = mois($date);
		$annee = annee($date);
		$jour = jour($date);
	
		// rendez-vous personnels dans le mois
		$evt = sql_calendrier_agenda($mois, $annee);
		if ($evt) {
			echo "<p />";
			echo http_calendrier_agenda ($mois_today, $annee_today, $jour_today, $mois_today, $annee_today, false, 'calendrier.php3', $evt);
		}
		// et ceux du jour
		if (spip_num_rows(spip_query("SELECT messages.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
				"WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') ".
					     "AND messages.rv='oui' AND messages.date_heure >='$annee_today-$mois_today-$jour_today' AND messages.date_heure < DATE_ADD('$annee_today-$mois_today-$jour_today', INTERVAL 1 DAY) AND messages.statut='publie' LIMIT 0,1"))) {
			echo "<p />";
			echo http_calendrier_jour($jour_today,$mois_today,$annee_today, "col", $partie_cal, $echelle);
		}
}


debut_droite();



//
// Restauration d'une archive
//

if ($meta["debut_restauration"]) {
	@ignore_user_abort(1);
	include_ecrire("inc_import.php3");
	import_init();
	exit;
 }


//
// Articles post-dates en attente de publication
//

$post_dates = lire_meta("post_dates");

if ($post_dates == "non" AND $connect_statut == '0minirezo' AND $options == 'avancees') {
	echo "<p>";
	afficher_articles(_T('info_article_a_paraitre'), "WHERE statut='publie' AND date>NOW() ORDER BY date");
}



//
// Vos articles en cours de redaction
//

echo "<p>";
$vos_articles = afficher_articles(afficher_plus('articles_page.php3')._T('info_en_cours_validation'),	", spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC");

if ($vos_articles) $vos_articles = ' AND articles.id_article NOT IN ('.join($vos_articles,',').')';


//
// Raccourcis pour malvoyants
//
if ($spip_display == 4) {
	debut_raccourcis();
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 0,1"))) {
		icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?new=oui", "article-24.gif","creer.gif");
	
		$activer_breves = lire_meta("activer_breves");
		if ($activer_breves != "non") {
			icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui", "breve-24.gif","creer.gif");
		}
	}
	else {
		if ($connect_statut == '0minirezo') {
			echo "<div class='verdana11'>"._T('info_ecrire_article')."</div>";
		}
	}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		icone_horizontale(_T('icone_creer_rubrique_2'), "rubriques_edit.php3?new=oui", "rubrique-24.gif","creer.gif");
	}
	fin_raccourcis();
 } else {

	$gadget = "";
		
	$gadget = "<center><table><tr>";
	$id_rubrique = $GLOBALS['id_rubrique'];
	if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			}
	if ($connect_statut == "0minirezo") {
			$gadget .= "<td>";
			$gadget .= icone_horizontale(_T('icone_creer_rubrique'), "rubriques_edit.php3?new=oui", "rubrique-24.gif", "creer.gif", false);
			$gadget .= "</td>";
		}
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 0,1"))) {
			$gadget .= "<td>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?new=oui$dans_rub", "article-24.gif","creer.gif", false);
			$gadget .= "</td>";
			
			$activer_breves = lire_meta("activer_breves");
			if ($activer_breves != "non") {
				$gadget .= "<td>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui$dans_rub", "breve-24.gif","creer.gif", false);
				$gadget .= "</td>";
			}
			
			if (lire_meta("activer_sites") == 'oui') {
				if ($connect_statut == '0minirezo' OR lire_meta("proposer_sites") > 0) {
					$gadget .= "<td>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), "sites_edit.php3?new=oui&target=sites.php3$dans_rub", "site-24.gif","creer.gif", false);
					$gadget .= "</td>";
				}
			}
			
		}
		$gadget .= "</tr></table></center>\n";


	if ($connect_statut != "0minirezo") {
	
		$gadget .= "<center><table><tr>";
	
		$nombre_articles = spip_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article LIMIT 0,1"));
		if ($nombre_articles > 0) {
			$gadget .= "<td>";
			$gadget .= icone_horizontale (_T('icone_tous_articles'), "articles_page.php3", "article-24.gif", "", false);
			$gadget .= "</td>";
		}
	
		$activer_breves=lire_meta("activer_breves");
		if ($activer_breves != "non"){
			$gadget .= "<td>";
			$gadget .= icone_horizontale (_T('icone_breves'), "breves.php3", "breve-24.gif", "", false);
			$gadget .= "</td>";
		}
	
		$articles_mots = lire_meta('articles_mots');
		if ($articles_mots != "non") {
			$gadget .= "<td>";
			$gadget .= icone_horizontale  (_T('icone_mots_cles'), "mots_tous.php3", "mot-cle-24.gif", "", false);
			$gadget .= "</td>";
		}

		$activer_sites = lire_meta('activer_sites');
		if ($activer_sites<>'non') {
			$gadget .= "<td>";
			$gadget .= icone_horizontale  (_T('icone_sites_references'), "sites_tous.php3", "site-24.gif", "", false);
			$gadget .= "</td>";
		}
		$gadget .= "</tr></table></center>\n";
		
	}

}




//
// Modification du cookie
//

if ($connect_statut == "0minirezo" AND $spip_display != 4) {
	if (!$cookie_admin) {
		$gadget .= "<div>&nbsp;</div>";
		$gadget .= "<table width=95%><tr>";
		$gadget .= "<td width=100%>";
		$gadget .= _T('info_activer_cookie');
		$gadget .= aide ("cookie");
		$gadget .= "</td>";
		$gadget .= "<td width=10><img src='" . _DIR_IMG_PACK . "rien.gif' width=10 alt='' />";
		$gadget .= "</td>";
		$gadget .= "<td width='250'>";
		$gadget .= icone_horizontale(_T('icone_activer_cookie'), "../spip_cookie.php3?cookie_admin=".rawurlencode("@$connect_login")."&url=".rawurlencode(_DIR_RESTREINT_ABS), "cookie-24.gif", "", false);
		$gadget .= "</td></tr></table>";
	}
}

if (strlen($gadget) > 0) {
	echo "<div>&nbsp;</div>";
	echo debut_cadre_trait_couleur();
	echo $gadget;
	echo fin_cadre_trait_couleur();
}
echo "<div>&nbsp;</div>";


//
// On utilise ob_start pour ne pas afficher de bloc vide (sinon tant pis)
//
if ($flag_ob)
	ob_start();
else
	debut_cadre_couleur_foncee("",false, "", _T('texte_en_cours_validation'));

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),	"WHERE statut='prop'$vos_articles ORDER BY date DESC");

	//
	// Les breves a valider
	//
	afficher_breves(afficher_plus('breves.php3')._T('info_breves_valider'), "SELECT * FROM spip_breves WHERE statut='prepa' OR statut='prop' ORDER BY date_heure DESC", true);

	//
	// Les sites references a valider
	//
	if (afficher_plus('sites_tous.php3').lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites(afficher_plus('sites_tous.php3')._T('info_site_valider'), "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites(afficher_plus('sites_tous.php3')._T('avis_sites_syndiques_probleme'), "SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");
	}

	// Les articles syndiques en attente de validation
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_syndic_articles WHERE statut='dispo'");
		if (($row = spip_fetch_array($result)) AND $row['compte'])
			echo "<br><small><a href='sites_tous.php3' style='color: black;'>".$row['compte']." "._T('info_liens_syndiques_1')." "._T('info_liens_syndiques_2')."</a></small>";
	}

	// Les forums en attente de moderation
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_forum WHERE statut='prop'");
		if (($row = spip_fetch_array($result)) AND $row['compte']) {
			echo "<br><small> <a href='controle_forum.php3' style='color: black;'>".$row['compte'];
			if ($row['compte']>1)
				echo " "._T('info_liens_syndiques_3')
				." "._T('info_liens_syndiques_4');
			else
				echo " "._T('info_liens_syndiques_5')
				." "._T('info_liens_syndiques_6');
			echo " "._T('info_liens_syndiques_7').".</a></small>";
		}
	}


if ($flag_ob) {
	$a = ob_get_contents();
	ob_end_clean();
	if ($a) {
		debut_cadre_couleur_foncee("",false, "", _T('texte_en_cours_validation'));
		echo $a;
		fin_cadre_couleur_foncee();
	}
} else
	fin_cadre_couleur_foncee();


if ($options == 'avancees') {

	/* Ne plus afficher: il y a la page "Tous vos articles" pour cela
	// Vos articles publies
	echo "<p>";
	afficher_articles(afficher_plus('articles_page.php3')._T('info_derniers_articles_publies'), ", spip_auteurs_articles AS lien ".
		"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"publie\" ORDER BY articles.date DESC", true);
	*/

	// Dernieres modifications d'articles
	include_ecrire("inc_suivi_revisions.php");
	afficher_suivi_versions (0, 0, false, "", true);
}


fin_page("jimmac");


//
// Si necessaire, recalculer les rubriques
//

if (lire_meta('calculer_rubriques') == 'oui') {
	calculer_rubriques();
	effacer_meta('calculer_rubriques');
	ecrire_metas();
}


//
// Renouvellement de l'alea utilise pour valider certaines operations
// (ajouter une image, etc.)
//

$maj_alea = $meta_maj['alea_ephemere'];
$t_jour = substr($maj_alea, 6, 2);
if (abs($t_jour - date('d')) > 2) {
	include_ecrire("inc_session.php3");
	$alea = md5(creer_uniqid());
	ecrire_meta('alea_ephemere_ancien', lire_meta('alea_ephemere'));
	ecrire_meta('alea_ephemere', $alea);
	ecrire_metas();
}

?>
