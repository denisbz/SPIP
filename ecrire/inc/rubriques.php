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

//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//
// http://doc.spip.org/@calculer_rubriques
function calculer_rubriques() {
	if (!spip_get_lock("calcul_rubriques")) return;

	// Mettre les compteurs a zero
	// Attention, faute de SQL transactionnel on travaille sur
	// des champs temporaires afin de ne pas  casser la base
	// pendant la demi seconde de recalculs
	spip_query("UPDATE spip_rubriques
	SET date_tmp='0000-00-00 00:00:00', statut_tmp='prive'");


	//
	// Publier et dater les rubriques qui ont un article publie
	//

	// Afficher les articles post-dates ?
	include_spip('inc/meta');
	$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
		"AND fille.date <= NOW()" : '';

	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_articles AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND fille.statut='publie'
	$postdates GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont une breve publie
	$r = spip_query("SELECT rub.id_rubrique AS id,
	max(fille.date_heure) AS date_h
	FROM spip_rubriques AS rub, spip_breves AS fille
	WHERE rub.id_rubrique = fille.id_rubrique
	AND rub.date_tmp <= fille.date_heure AND fille.statut='publie'
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un site publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_syndic AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND rub.date_tmp <= fille.date
	AND fille.statut='publie'
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un document publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_documents AS fille,
	spip_documents_rubriques AS lien
	WHERE rub.id_rubrique = lien.id_rubrique
	AND lien.id_document=fille.id_document AND rub.date_tmp <= fille.date
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	
	// Les rubriques qui ont une rubrique fille plus recente
	// on tourne tant que les donnees remontent vers la racine.
	do {
		$continuer = false;
		$r = spip_query("SELECT rub.id_rubrique AS id,
		max(fille.date_tmp) AS date_h
		FROM spip_rubriques AS rub, spip_rubriques AS fille
		WHERE rub.id_rubrique = fille.id_parent
		AND (rub.date_tmp < fille.date_tmp OR rub.statut_tmp<>'publie')
		AND fille.statut_tmp='publie'
		GROUP BY rub.id_rubrique");
		while ($row = spip_fetch_array($r)) {
			spip_query("UPDATE spip_rubriques
			SET statut_tmp='publie', date_tmp='".$row['date_h']."'
			WHERE id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);

	// "Commit" des modifs
	spip_query("UPDATE spip_rubriques SET date=date_tmp, statut=statut_tmp");
	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	ecrire_meta("date_calcul_rubriques", date("U"));
	ecrire_metas();

	// Comme ce calcul est fait apres chaque publication on en profite
	// pour recalculer les langues utilisees sur le site
	include_spip('inc/lang');
	calculer_langues_utilisees();
}

// http://doc.spip.org/@propager_les_secteurs
function propager_les_secteurs()
{
	// fixer les id_secteur des rubriques racines
	spip_query("UPDATE spip_rubriques SET id_secteur=id_rubrique
	WHERE id_parent=0");

	// reparer les rubriques qui n'ont pas l'id_secteur de leur parent
	do {
		$continuer = false;
		$r = spip_query("SELECT fille.id_rubrique AS id,
		maman.id_secteur AS secteur
		FROM spip_rubriques AS fille, spip_rubriques AS maman
		WHERE fille.id_parent = maman.id_rubrique
		AND fille.id_secteur <> maman.id_secteur");
		while ($row = spip_fetch_array($r)) {
			spip_query("UPDATE spip_rubriques
			SET id_secteur=".$row['secteur']." WHERE id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);
	
	// reparer les articles
	$r = spip_query("SELECT fille.id_article AS id, maman.id_secteur AS secteur
	FROM spip_articles AS fille, spip_rubriques AS maman
	WHERE fille.id_rubrique = maman.id_rubrique
	AND fille.id_secteur <> maman.id_secteur");
	while ($row = spip_fetch_array($r)) {
#	  spip_log("change " . $row['id'] . " secteur " . $row['secteur']);
		spip_query("UPDATE spip_articles
		SET id_secteur=".$row['secteur']." WHERE id_article=".$row['id']);
	}
	// reparer les sites
	$r = spip_query("SELECT fille.id_syndic AS id, maman.id_secteur AS secteur
	FROM spip_syndic AS fille, spip_rubriques AS maman
	WHERE fille.id_rubrique = maman.id_rubrique
	AND fille.id_secteur <> maman.id_secteur");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_syndic SET id_secteur=".$row['secteur']."
		WHERE id_syndic=".$row['id']);
	
}

//
// Calculer la langue des sous-rubriques et des articles
//
// http://doc.spip.org/@calculer_langues_rubriques_etape
function calculer_langues_rubriques_etape() {
	$s = spip_query("SELECT fille.id_rubrique AS id_rubrique, mere.lang AS lang
		FROM spip_rubriques AS fille, spip_rubriques AS mere
		WHERE fille.id_parent = mere.id_rubrique
		AND fille.langue_choisie != 'oui' AND mere.lang<>''
		AND mere.lang<>fille.lang");

	while ($row = spip_fetch_array($s)) {
		$id_rubrique = $row['id_rubrique'];
		$t = spip_query("UPDATE spip_rubriques	SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_rubrique=$id_rubrique");
	}

	return $t;
}

// http://doc.spip.org/@calculer_langues_rubriques
function calculer_langues_rubriques() {

	// rubriques (recursivite)
	spip_query("UPDATE spip_rubriques SET lang=" . _q($GLOBALS['meta']['langue_site']) . ", langue_choisie='non'	WHERE id_parent=0 AND langue_choisie != 'oui'");
	while (calculer_langues_rubriques_etape());

	// articles
	$s = spip_query("SELECT fils.id_article AS id_article, mere.lang AS lang
		FROM spip_articles AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = spip_fetch_array($s)) {
		$id_article = $row['id_article'];
		spip_query("UPDATE spip_articles SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_article=$id_article");
	}

	// breves
	$s = spip_query("SELECT fils.id_breve AS id_breve, mere.lang AS lang
		FROM spip_breves AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = spip_fetch_array($s)) {
		$id_breve = $row['id_breve'];
		spip_query("UPDATE spip_breves SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_breve=$id_breve");
	}

	if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
		// Ecrire meta liste langues utilisees dans rubriques
		include_spip('inc/meta');
		$s = spip_query("SELECT lang FROM spip_rubriques
		WHERE lang != '' GROUP BY lang");
		while ($row = spip_fetch_array($s)) {
			$lang_utilisees[] = $row['lang'];
		}
		if ($lang_utilisees) {
			$lang_utilisees = join (',', $lang_utilisees);
			ecrire_meta('langues_utilisees', $lang_utilisees);
		} else {
			ecrire_meta('langues_utilisees', "");
		}
	}
}


// http://doc.spip.org/@enfant_rub
function enfant_rub($collection){
	global $couleur_foncee, $lang_dir;
	global $spip_display, $spip_lang_left, $spip_lang_right, $spip_lang;
	
	$voir_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		
	if ($voir_logo) {
		$voir_logo = "float: $spip_lang_right; margin-$spip_lang_right: -6px; margin-top: -6px;";
		$logo_f = charger_fonction('chercher_logo', 'inc');
	} else $logo ='';

	$les_enfants = "";

	$res = spip_query("SELECT id_rubrique, id_parent, titre, descriptif, lang FROM spip_rubriques WHERE id_parent='$collection' ORDER BY 0+titre,titre");

	while($row=spip_fetch_array($res)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];

		$les_sous_enfants = sous_enfant_rub($id_rubrique);

		changer_typo($row['lang']);

		$descriptif=propre($row['descriptif']);

		if ($spip_display == 4) $les_enfants .= "<li>";


		if ($voir_logo) {
			if ($logo = $logo_f($id_rubrique, 'id_rubrique', 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				$logo = ratio_image($fid, $nom, $format, 48, 36, "alt=''");
				if ($logo)
					$logo =  "<div style='$voir_logo'>$logo</div>";
			}
		}
		$les_enfants .= "<div class='enfants'>" .
			debut_cadre_sous_rub(($id_parent ? "rubrique-24.gif" : "secteur-24.gif"), true) .
		  (is_string($logo) ? $logo : '') .
		  (!$les_sous_enfants ? "" : bouton_block_invisible("enfants$id_rubrique")) .
		  (!acces_restreint_rubrique($id_rubrique) ? "" :
		   http_img_pack("admin-12.gif", '', " width='12' height='12'", _T('image_administrer_rubrique'))) .
		  " <span dir='$lang_dir'><B><A href='" . 
		  generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") .
		  "'><font color='$couleur_foncee'>".
		  typo($titre) .
		  "</font></A></B></span>" .
		  (!$descriptif ? '' : "<div class='verdana1'>$descriptif</div>");

		if ($spip_display != 4) $les_enfants .= $les_sous_enfants;
		
		$les_enfants .= "<div style='clear:both;'></div>"  .
		  fin_cadre_sous_rub(true) .
		  "</div>";

		if ($spip_display == 4) $les_enfants .= "</li>";
	}

	changer_typo($spip_lang); # remettre la typo de l'interface pour la suite
	return (($spip_display == 4) ? "<ul> $les_enfants</ul>" :  $les_enfants);

}

// http://doc.spip.org/@sous_enfant_rub
function sous_enfant_rub($collection2){
	global $lang_dir, $spip_lang_dir, $spip_lang_left;

	$result3 = spip_query("SELECT * FROM spip_rubriques WHERE id_parent='$collection2' ORDER BY 0+titre,titre");

	if (!spip_num_rows($result3)) return '';
	$retour = debut_block_invisible("enfants$collection2")."\n<ul style='margin: 0px; padding: 0px; padding-top: 3px;'>\n";
	while($row=spip_fetch_array($result3)){
			$id_rubrique2=$row['id_rubrique'];
			$id_parent2=$row['id_parent'];
			$titre2=$row['titre'];
			changer_typo($row['lang']);

			$retour.="<div class='arial11' " .
			  http_style_background('rubrique-12.gif', "left center no-repeat; padding: 2px; padding-$spip_lang_left: 18px; margin-$spip_lang_left: 3px") . "><A href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique2") . "'><span dir='$lang_dir'>".typo($titre2)."</span></a></div>\n";
	}
	$retour .= "</ul>\n\n".fin_block()."\n\n";
	
	return $retour;
}

// http://doc.spip.org/@afficher_enfant_rub
function afficher_enfant_rub($id_rubrique, $bouton=false, $return=false) {
	global  $spip_lang_right;
	
	$les_enfants = enfant_rub($id_rubrique);
	$n = strlen($les_enfants);

	$les_enfants2=substr($les_enfants,round($n/2));

	if (strpos($les_enfants2,"<div class='enfants'>")){
		$les_enfants2=substr($les_enfants2,strpos($les_enfants2,"<div class='enfants'>"));
		$n2 = strlen($les_enfants2);
		$les_enfants=substr($les_enfants,0,$n-$n2);
	}else{
		$les_enfants2="";
	}
	
	$res = "<div>&nbsp;</div>"
	. "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>"
	. "\n<tr><td valign='top' width=50% rowspan=2>"
	. $les_enfants
	. "</td>"
	. "\n<td width='20' rowspan='2'>"
	. http_img_pack("rien.gif", ' ', "width='20'")
	. "</td>\n"
	. "\n<td valign='top' width='50%'>"
	. $les_enfants2
	. "&nbsp;"
	. "</td></tr>"
	. "\n<tr><td style='text-align: "
	. $spip_lang_right
	. ";' valign='bottom'><div align='"
	. $spip_lang_right
	. "'>"
	. (!$bouton ? ''
		 : (!$id_rubrique
		    ? icone(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav"), "secteur-24.gif", "creer.gif",'', false)
		    : icone(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui&retour=nav&id_parent=$id_rubrique"), "rubrique-24.gif", "creer.gif",'',false)))
	. "</div></td></tr></table>";
	if ($return) return $res; else echo $res;
}

// http://doc.spip.org/@calcul_generation
function calcul_generation ($generation) {
	include_spip('base/abstract_sql');
	$lesfils = array();
	$result = spip_abstract_select(array('id_rubrique'),
				array('spip_rubriques AS rubriques'),
				array(calcul_mysql_in('id_parent', 
					$generation,
						      '')));
	while ($row = spip_abstract_fetch($result))
		$lesfils[] = $row['id_rubrique'];
	return join(",",$lesfils);
}

// http://doc.spip.org/@calcul_branche
function calcul_branche ($generation) {
	if (!$generation) 
		return '0';
	else {
		$branche[] = $generation;
		while ($generation = calcul_generation ($generation))
			$branche[] = $generation;
		return join(",",$branche);
	}
}

// http://doc.spip.org/@cron_rubriques
function cron_rubriques($t) {
	calculer_rubriques();
	return 1;
}

?>
