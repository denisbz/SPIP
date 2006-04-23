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

include_spip('inc/presentation');
include_spip('inc/texte');
charger_generer_url();
include_spip('inc/rubriques');

function encours_accueil($vos_articles)
{
  global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur, $flag_ob;


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


 afficher_articles(_T('info_articles_proposes'), array("WHERE" => "statut='prop'$vos_articles", 'ORDER BY' => "date DESC"));

	//
	// Les breves a valider
	//
 afficher_breves(afficher_plus(generer_url_ecrire('breves'))._T('info_breves_valider'), array("FROM" => 'spip_breves', 'WHERE' => "statut='prepa' OR statut='prop'", 'ORDER BY' => "date_heure DESC"), true);

	//
	// Les sites references a valider
	//
if (afficher_plus(generer_url_ecrire('sites_tous')).$GLOBALS['meta']['activer_syndic'] != 'non') {
		include_spip('inc/sites_voir');
		afficher_sites(afficher_plus(generer_url_ecrire('sites_tous'))._T('info_site_valider'), array("FROM" => 'spip_syndic', 'WHERE' => "statut='prop'", 'ORDER BY'=> "nom_site"));
	}

	//
	// Les sites a probleme
	//
if ($GLOBALS['meta']['activer_syndic'] != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_spip('inc/sites_voir');
		afficher_sites(afficher_plus(generer_url_ecrire('sites_tous'))._T('avis_sites_syndiques_probleme'), array('FROM' => 'spip_syndic', 'WHERE' => "(syndication='off' OR syndication='sus') AND statut='publie'", 'ORDER BY' => 'nom_site'));
	}

	// Les articles syndiques en attente de validation
if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_syndic_articles WHERE statut='dispo'"));
	if ($cpt = $cpt['n'])
		echo "<br /><small><a href='" ,
			generer_url_ecrire("sites_tous","") ,
			"' style='color: black;'>",
			$cpt,
			" ",
			_T('info_liens_syndiques_1'),
			" ",
			_T('info_liens_syndiques_2'),
			"</a></small>";
	}

	// Les forums en attente de moderation
if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_forum WHERE statut='prop'"));
	if ($cpt = $cpt['n']) {
		echo "<br><small> <a href='" , generer_url_ecrire("controle_forum","") , "' style='color: black;'>",$cpt;
		if ($cpt>1)
			echo " ",_T('info_liens_syndiques_3')," ",_T('info_liens_syndiques_4');
		else
			echo " ",_T('info_liens_syndiques_5')," ",_T('info_liens_syndiques_6');
		echo " ",_T('info_liens_syndiques_7'),",</a></small>";
		}
 }
if ($flag_ob) {
	$a = ob_get_contents();
	ob_end_clean();
	if ($a) {
		debut_cadre_couleur_foncee("",false, "", _T('texte_en_cours_validation'));
		echo $a;
	} else
		$non_affiche = true;
 }


 if (!$non_affiche) {
	// Afficher le lien RSS
	$op = 'a-suivre';
	$args = array();
	echo "<div style='text-align: "
		. $GLOBALS['spip_lang_right']
		. ";'>"
		. bouton_spip_rss($op, $args)
		."</div>";
	fin_cadre_couleur_foncee();
 }
}

function colonne_gauche_accueil($id_rubrique, $activer_breves,
				$activer_sites, $articles_mots)
{

  global  $spip_display, $connect_statut, $connect_toutes_rubriques,
    $connect_id_auteur, $connect_login;


//
// Raccourcis pour malvoyants
//
if ($spip_display == 4) {
	debut_raccourcis();
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"))) {
		icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui"), "article-24.gif","creer.gif");
	

		if ($activer_breves != "non") {
			icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui"), "breve-24.gif","creer.gif");
		}
	}
	else {
		if ($connect_statut == '0minirezo') {
			echo "<div class='verdana11'>"._T('info_ecrire_article')."</div>";
		}
	}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		icone_horizontale(_T('icone_creer_rubrique_2'), generer_url_ecrire("rubriques_edit","new=oui"), "rubrique-24.gif","creer.gif");
	}
	fin_raccourcis();
 } else {

	$gadget = "";
		
	$gadget = "<center><table><tr>";

	if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			}
	if ($connect_statut == "0minirezo") {
			$gadget .= "<td>";
			$gadget .= icone_horizontale(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui"), "rubrique-24.gif", "creer.gif", false);
			$gadget .= "</td>";
		}
	if (spip_num_rows(spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1"))) {
			$gadget .= "<td>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui$dans_rub"), "article-24.gif","creer.gif", false);
			$gadget .= "</td>";
			
			if ($activer_breves != "non") {
				$gadget .= "<td>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui$dans_rub"), "breve-24.gif","creer.gif", false);
				$gadget .= "</td>";
			}
			
			if ($activer_sites == 'oui') {
				if ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0) {
					$gadget .= "<td>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), generer_url_ecrire("sites_edit","new=oui$dans_parent"), "site-24.gif","creer.gif", false);
					$gadget .= "</td>";
				}
			} 
			
		}
		$gadget .= "</tr></table></center>\n";


	if ($connect_statut != "0minirezo") {
	
		$gadget .= "<center><table><tr>";
	
		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article LIMIT 1"));
		if ($cpt['n'] > 0) {
			$gadget .= "<td>";
			$gadget .= icone_horizontale (_T('icone_tous_articles'), generer_url_ecrire("articles_page",""), "article-24.gif", "", false);
			$gadget .= "</td>";
		}
	
		if ($activer_breves != "non"){
			$gadget .= "<td>";
			$gadget .= icone_horizontale (_T('icone_breves'), generer_url_ecrire("breves",""), "breve-24.gif", "", false);
			$gadget .= "</td>";
		}
	
		if ($articles_mots != "non") {
			$gadget .= "<td>";
			$gadget .= icone_horizontale  (_T('icone_mots_cles'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "", false);
			$gadget .= "</td>";
		}

		if ($activer_sites<>'non') {
			$gadget .= "<td>";
			$gadget .= icone_horizontale  (_T('icone_sites_references'), generer_url_ecrire("sites_tous",""), "site-24.gif", "", false);
			$gadget .= "</td>";
		}
		$gadget .= "</tr></table></center>\n";
	}
 }


//
// Modification du cookie
//

if (/* $connect_statut == "0minirezo" AND */ $spip_display != 4) {
	if (!$_COOKIE['spip_admin']) {
		$gadget .= "<div>&nbsp;</div>".
			"<table width=95%><tr>".
			"<td width=100%>".
			_T('info_activer_cookie').
			aide ("cookie").
			"</td>".
			"<td width=10>".
			http_img_pack("rien.gif", ' ', "width='10'") .
			"</td>".
			"<td width='250'>".
			icone_horizontale(_T('icone_activer_cookie'), generer_url_public('spip_cookie', "cookie_admin=".rawurlencode("@$connect_login")."&url=".rawurlencode(_DIR_RESTREINT_ABS)), "cookie-24.gif", "", false).
			"</td></tr></table>";
	}
}

if (strlen($gadget) > 0) {
	echo "<div>&nbsp;</div>";
	echo debut_cadre_trait_couleur();
	echo $gadget;
	echo fin_cadre_trait_couleur();
}
echo "<div>&nbsp;</div>";
}

function personnel_accueil()
{
  global $spip_display, $spip_lang_left, $connect_id_auteur, $connect_id_rubrique,
$connect_nom, $connect_statut,  $partie_cal, $echelle;

if ($spip_display != 4) {
	
	//
	// Infos personnelles : nom, utilisation de la messagerie
	//
	
	$titre_cadre = afficher_plus(generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur"));
	$titre_cadre .= majuscules(typo($connect_nom));
	
	debut_cadre_relief("fiche-perso-24.gif", false, '',$titre_cadre);

	if ($connect_statut == '0minirezo') {

		if ($connect_id_rubrique) {

			$rubs = array();
			foreach ($connect_id_rubrique as $id_rubrique)
			  if ($r = spip_fetch_array(spip_query("SELECT titre, descriptif FROM spip_rubriques WHERE id_rubrique=$id_rubrique AND id_parent=0"))) {
			    list($titre, $descr) = $r;
			    $rubs[] = "<a title='" .
			      typo($descr) .
			      "' href='" . generer_url_ecrire('naviguer', "id_rubrique=$id_rubrique") . "'>" .
			      typo($titre) .
			      '</a>';
			  }
			sort($rubs);

			echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>\n<li>", join("</li>\n<li>", $rubs), "\n</li></ul>";
		}
	}

	//
	// Supprimer le cookie, se deconnecter...
	//
	
	if ($_COOKIE['spip_admin']) {
			$texte = _T('icone_supprimer_cookie');
			if ($spip_display != 1) $texte .= aide("cookie");
			icone_horizontale( $texte , generer_url_public("spip_cookie", "cookie_admin=non&url=".rawurlencode(_DIR_RESTREINT_ABS)), "cookie-24.gif", "");
		}
	}

	fin_cadre_relief();
}


function etat_base_accueil()
{
  global $spip_display, $spip_lang_left, $connect_id_auteur, $connect_nom, $connect_statut, $partie_cal, $echelle;

if ($spip_display != 4) {

	$nom_site_spip = propre($GLOBALS['meta']["nom_site"]);
	if (!$nom_site_spip) $nom_site_spip=  _T('info_mon_site_spip');
	
	
	echo "\n<div>&nbsp;</div>";
	
	echo debut_cadre_relief("racine-site-24.gif", false, "", $nom_site_spip);

	if ($spip_display != 1) {
		include_spip('inc/logos');
		if ($logo = baliser_logo("rub", 0, 170, 170,
					 "text-align:center; margin-bottom: 5px;")) {
		  echo "<a href='",
		    generer_url_ecrire("naviguer",""),
		    "'>",
		    $logo,
		    "</a>";
		}
	}
	echo "<div class='verdana1'>";

	if(strlen(propre($GLOBALS['meta']["descriptif_site"])))
	echo "<div>".propre($GLOBALS['meta']["descriptif_site"])."</div><br />";

    $res = spip_query("SELECT COUNT(*) AS cnt, statut FROM spip_articles GROUP BY statut");
  
  while($row = spip_fetch_array($res)) {
    $var  = 'nb_art_'.$row['statut'];
    $$var = $row['cnt']; 
  }
  
	if ($nb_art_prepa OR $nb_art_prop OR $nb_art_publie) {

		echo afficher_plus(generer_url_ecrire("articles_page",""))."<b>"._T('info_articles')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_art_prepa) echo "<li>"._T("texte_statut_en_cours_redaction").": ".$nb_art_prepa;
		if ($nb_art_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_art_prop;
		if ($nb_art_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_art_publie."</b>";
		echo "</ul>";

	}

	$res = spip_query("SELECT COUNT(*) AS cnt, statut FROM spip_breves GROUP BY statut");


	while($row = spip_fetch_array($res)) {
		$var  = 'nb_bre_'.$row['statut'];
		$$var = $row['cnt']; 
	}
 
	if ($nb_bre_prop OR $nb_bre_publie) {
		echo afficher_plus(generer_url_ecrire("breves",""))."<b>"._T('info_breves_02')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_bre_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_bre_prop;
		if ($nb_bre_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_bre_publie."</b>";
		echo "</ul>";
	}

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_forum where statut='publie'"));

	if ($cpt = $cpt['n']) {
		if ($connect_statut == "0minirezo") echo afficher_plus(generer_url_ecrire("controle_forum",""));
		echo "<b>",_T('onglet_messages_publics'),"</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		echo "<li><b>",$cpt , "</b>";
		echo "</ul>";
	}

	$res = spip_query("SELECT COUNT(*) AS cnt, statut FROM spip_auteurs GROUP BY statut");

	while($row = spip_fetch_array($res)) {
		$var  = 'nb_aut_'.$row['statut'];
		$$var = $row['cnt']; 
	}

	if ($nb_aut_0minirezo OR $nb_aut_1comite OR $nb_aut_6forum) {
		echo afficher_plus(generer_url_ecrire("auteurs",""))."<b>"._T('icone_auteurs')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_aut_0minirezo) echo "<li>"._T("info_administrateurs").": ".$nb_aut_0minirezo;
		if ($nb_aut_1comite) echo "<li>"._T("info_redacteurs").": ".$nb_aut_1comite;
		if ($nb_aut_6forum) echo "<li>"._T("info_visiteurs").": ".$nb_aut_6forum;
		echo "</ul>";
	}

	echo "</div>";

	echo fin_cadre_relief();
 }

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
	
	$mois = date("m");
	$annee = date("Y");
	$jour = date("d");

	$evt = sql_calendrier_agenda($annee, $mois);
	if ($evt) 
		echo http_calendrier_agenda ($annee, $mois, $jour, $mois, $annee, false, generer_url_ecrire('calendrier'), '', $evt);

	// et ceux du jour
	$evt = date("Y-m-d");
	$evt = sql_calendrier_interval_rv("'$evt'", "'$evt 23:59:59'");

	if ($evt) {
		echo http_calendrier_ics_titre($annee,$mois,$jour,generer_url_ecrire('calendrier'));
		echo http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 90, array('', $evt));
	}
}



function exec_accueil_dist()
{

  global $id_rubrique, $meta, $connect_statut, $options,  $connect_id_auteur, $flag_ob;

  $id_rubrique =  intval($id_rubrique);
  debut_page(_T('titre_page_index'), "asuivre", "asuivre");

  debut_gauche();

  personnel_accueil();
  etat_base_accueil();
  debut_droite();

//
// Articles post-dates en attente de publication
//

$post_dates = $GLOBALS['meta']["post_dates"];

if ($post_dates == "non" AND $connect_statut == '0minirezo' AND $options == 'avancees') {
	echo "<p>";
	afficher_articles(_T('info_article_a_paraitre'), array("WHERE" => "statut='publie' AND date>NOW()", 'ORDER BY' => "date"));
}

//
// Vos articles en cours de redaction
//

echo "<p>";
 $vos_articles = afficher_articles(afficher_plus(generer_url_ecrire('articles_page'))._T('info_en_cours_validation'),	array('FROM' => "spip_articles AS articles, spip_auteurs_articles AS lien", "WHERE" => "articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa'", "ORDER BY" => "articles.date DESC"));

if ($vos_articles) $vos_articles = ' AND articles.id_article NOT IN ('.join($vos_articles,',').')';


  colonne_gauche_accueil($id_rubrique,
			 $GLOBALS['meta']["activer_breves"],
			 $GLOBALS['meta']["activer_sites"],
			 $GLOBALS['meta']['articles_mots']);
  
  encours_accueil($vos_articles);

  afficher_enfant_rub(0, false);

if ($options == 'avancees') {

	// Dernieres modifications d'articles
	include_spip('inc/suivi_versions');
	afficher_suivi_versions (0, 0, false, "", true);
}


fin_page("jimmac");


//
// Symetrique du debut: apres restauration ou MAJ, recalculer les rubriques
//

	if ($GLOBALS['meta']['calculer_rubriques'] == 'oui') {
		calculer_rubriques();
		effacer_meta('calculer_rubriques');
		ecrire_metas();
	}

}
?>
