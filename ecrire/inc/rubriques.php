<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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
	$postdates GROUP BY rub.id_rubrique");
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

	// point d'entree pour permettre a des plugins de gerer le statut
	// autrement (par ex: toute rubrique est publiee des sa creation)
	// Ce pipeline fait ce qu'il veut, mais s'il touche aux statuts/dates
	// c'est statut_tmp/date_tmp qu'il doit modifier
	pipeline('calculer_rubriques', null);

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
