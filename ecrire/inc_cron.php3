<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CRON")) return;
define("_ECRIRE_INC_CRON", "1");


// --------------------------
// Gestion des taches de fond
// --------------------------


//
// Calcul des referers
//

// demarrer le calcul
function cron_referers($t) {
	ecrire_meta("date_stats_referers", $t);
	ecrire_meta('calculer_referers_now', 'oui');
	ecrire_metas();
}

// poursuivre le calcul
function cron_referers_suite() {
	if (timeout('archiver_stats')) {
		include_ecrire("inc_statistiques.php3");
		ecrire_meta('calculer_referers_now', 'non');
		ecrire_metas();
		calculer_referers();
	}
}


//
// Archiver les stats du jour
//
function cron_archiver_stats($last_date) {
	if (timeout('archiver_stats')) {
		spip_log("Archivage des statistiques de $last_date");
		include_ecrire("inc_meta.php3");
		include_ecrire("inc_statistiques.php3");
		ecrire_meta("date_statistiques", date("Y-m-d"));
		ecrire_metas();
		calculer_visites($last_date);

		// purger les referers du jour
		spip_query("UPDATE spip_referers SET visites_jour=0");
		// poser un message pour traiter les referers au prochain hit
		ecrire_meta('calculer_referers_now','oui');
		ecrire_metas();
	}
}

//
// La fonction de base qui distribue les taches
//
function spip_cron() {
	global $flag_ecrire, $dir_ecrire, $db_ok;

	include_ecrire("inc_connect.php3");
	if (!$db_ok) {
		@touch($dir_ecrire.'data/mysql_out');
		spip_log('pas de connexion DB pour taches de fond (cron)');
		return;
	}

	@touch($dir_ecrire.'data/cron.lock');

	include_ecrire("inc_meta.php3");
	$t = time();


	//
	// Envoi du mail quoi de neuf
	
	$adresse_neuf = lire_meta('adresse_neuf');
	$jours_neuf = lire_meta('jours_neuf');
	if (!$flag_ecrire
	AND $adresse_neuf
	AND $jours_neuf
	AND (lire_meta('quoi_de_neuf') == 'oui') AND
	(time() - ($majnouv = lire_meta('majnouv'))) > 3600 * 24 * $jours_neuf) {
		if (timeout('quoide_neuf')) { 
			ecrire_meta('majnouv', time());
			ecrire_metas();

			include_local("inc-calcul.php3");
			$page= cherche_page('',
				array('date' => date('Y-m-d H:i:s', $majnouv)),
				'nouveautes',
				'',
				lire_meta('langue_site'));
			$page = $page['texte'];
			if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affections. 1 passe de +
				unset ($mail_nouveautes);
				unset ($sujet_nouveautes);
				eval ('?' . '>' . $page);
			} else {
# nouvelle version: squelette en mode texte, 1ere ligne = sujet
# il faudrait ge'ne'raliser en produisant les Headers standars SMTP
# a` passer en 4e argument de mail. Surtout utile pour le charset.
				$page = stripslashes($page);
				$p = strpos($page,"\n");
				$sujet_nouveautes = substr($page,0,$p);
				$mail_nouveautes = ereg_replace('\$jours_neuf',
					"$jours_neuf",
					substr($page,$p+1));
			}

			// envoi
			if ($mail_nouveautes) {
				spip_log("envoi mail nouveautes");
				include_ecrire('inc_mail.php3');
				envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes);
			} else
				spip_log("envoi mail nouveautes : pas de nouveautes");
		}
	}

	//
	// Statistiques
	//
	if (lire_meta("activer_statistiques") != "non") {
		if ($t - lire_meta('date_stats_referers') > 3600)
			cron_referers($t);
		else if (lire_meta('calculer_referers_now') == 'oui')
			cron_referers_suite();
	
		if (date("Y-m-d") <> ($last_date = lire_meta("date_statistiques")))
			cron_archiver_stats($last_date);
	
		if ($t - lire_meta('date_stats_popularite') > 1800) {
			if (timeout('archiver_stats')) {
				include_ecrire("inc_statistiques.php3");
				calculer_popularites();
			}
		}
	}

	// recalcul des rubriques publiques (cas de la publication post-datee)
	if (($t - lire_meta('calcul_rubriques') > 3600)
	AND timeout('calcul_rubriques')) {
		ecrire_meta('calcul_rubriques', $t);
		ecrire_metas();
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
	}


	//
	// Gerer l'indexation
	//
	if (lire_meta('activer_moteur') == 'oui') {
		if (timeout('indexation')) {
			include_ecrire("inc_index.php3");
			effectuer_une_indexation();
		}
	}


	//
	// Toutes les heures, menage des vieux fichiers du cache
	// marques par l'invalideur 't' = date de fin de fichier
	//
	if ($t - lire_meta('date_purge_cache') > 3600) {
		ecrire_meta('date_purge_cache', $t);
		ecrire_metas();
		include_ecrire('inc_invalideur.php3');
		retire_vieux_caches();
	}

	//
	// Mise a jour d'un (ou de zero) site syndique
	//
	if (lire_meta("activer_syndic") == "oui") {
		if (timeout()) {
			include_ecrire("inc_sites.php3");
			executer_une_syndication();
			if (lire_meta('activer_moteur') == 'oui') {
				include_ecrire("inc_index.php3");
				executer_une_indexation_syndic();
			}
		}
	}


	//
	// Effacement de la poubelle (documents supprimes)
	//
	if (@file_exists($fichier_poubelle = $dir_ecrire.'data/.poubelle')) {
		if (timeout('poubelle')) {
			if ($s = sizeof($suite = file($fichier_poubelle))) {
				$s = $suite[$n = rand(0, $s)];
				$s = trim($s);

				// Verifier qu'on peut vraiment effacer le fichier...
				$query = "SELECT id_document FROM spip_documents
					WHERE fichier='$s'";
				$result = spip_query($query);

				if (spip_num_rows($result) OR !ereg('^IMG/', $s)
				OR strpos($s, '..'))
					spip_log("Tentative d'effacement interdit: $s");
				else
					@unlink($s);

				unset($suite[$n]);
				$f = fopen($fichier_poubelle, 'wb');
				fwrite($f, join("", $suite));
				fclose($f);
			}
		}
		else @unlink($fichier_poubelle);
	}
}


?>
