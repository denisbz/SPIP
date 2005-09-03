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


// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CRON")) return;
define("_ECRIRE_INC_CRON", "1");

// --------------------------
// Gestion des taches de fond
// --------------------------

// Deux difficultes:
// - la plupart des hebergeurs ne fournissent pas le Cron d'Unix
// - les scripts usuels standard sont limites a 30 secondes

// Solution:
// les scripts usuels les plus brefs, en plus de livrer la page demandee,
// s'achevent  par un appel a la fonction spip_cron.
// Celle-ci prend dans la liste des taches a effectuer la plus prioritaire.
// Une seule tache est executee pour eviter la guillotine des 30 secondes.
// Une fonction executant une tache doit retourner un nombre:
// - nul, si la tache n'a pas a etre effecutee
// - positif, si la tache a ete effectuee
// - negatif, si la tache doit etre poursuivie ou recommencee
// Elle recoit en argument la date de la derniere execution de la tache.

// On peut appeler spip_cron avec d'autres taches (pour etendre Spip)
// specifiee par des fonctions respectant le protocole ci-dessus
// On peut modifier la frequence de chaque tache et leur ordre d'analyse
// en modifiant les variables ci-dessous.

//----------

// Les taches sont dans un array ('nom de la tache' => periodicite)
// Cette fonction execute la tache la plus urgente (celle dont la date
// de derniere execution + la periodicite est minimale) sous reserve que
// le serveur MySQL soit actif.
// La date de la derniere intervention est donnee par un fichier homonyme,
// de suffixe ".lock", modifie a chaque intervention et des le debut
// de celle-ci afin qu'un processus concurrent ne la demarre pas aussi.
// Les taches les plus longues sont tronconnees, ce qui impose d'antidater
// le fichier de verrouillage (avec la valeur absolue du code de retour).
// La fonction executant la tache est un homonyme de prefixe "cron_"
// Le fichier homonyme de prefixe "inc_" et de suffixe _EXTENSION_PHP
// est automatiquement charge et est supposee la definir si ce n'est fait ici.

function spip_cron($taches = array()) {
	$t = time();

	if (@file_exists(_FILE_MYSQL_OUT)
	AND ($t - @filemtime(_FILE_MYSQL_OUT) < 300))
		return;

	include(_FILE_CONNECT);
	if (!$GLOBALS['db_ok']) {
		spip_log('pas de connexion DB pour taches de fond (cron)');
		return;
	}

	if (!$taches)
		$taches = taches_generales();

	var_dump($taches);

	include_ecrire("inc_meta.php3");

	// Quelle est la tache la plus urgente ?
	$tache = '';
	clearstatcache();
	foreach ($taches as $nom => $periode) {
		$lock = _DIR_SESSIONS . $nom . '.lock';
		$date_lock = @filemtime($lock);
		if ($date_lock + $periode < $t) {
			$t = $date_lock + $periode;
			$tache = $nom;
			$last = $date_lock;
		}
	}
	if (!$tache) return;


	// On opere un double lock : un dans _DIR_SESSIONS, pour les hits
	// (en parallele sur le meme site) ; et un autre dans la base de
	// donnees, de maniere a eviter toute concurrence entre deux SPIP
	// differents partageant la meme base (replication de serveurs Web)
	$lock = _DIR_SESSIONS . $tache . '.lock';
	if (spip_touch($lock, $taches[$tache])
	AND spip_get_lock('cron'.$tache)) {

		// preparer la tache
		spip_timer('tache');
		include_ecrire('inc_' . $tache . _EXTENSION_PHP);
		$fonction = 'cron_' . $tache;

		// l'appeler
		$code_de_retour = $fonction($last);

		// si la tache a eu un effet : log
		if ($code_de_retour) {
			spip_log("cron: $tache (" . spip_timer('tache') . ")");
			// eventuellement modifier la date du fichier
			if ($code_de_retour < 0) @touch($lock, (0 - $code_de_retour));
		}

		// relacher le lock mysql
		spip_release_lock('cron'.$tache);
	}
}

//
// Construction de la liste des taches.
// la cle est la tache, la valeur le temps minimal, en secondes, entre
// deux memes taches
// NE PAS METTRE UNE VALEUR INFERIEURE A 30 (cf ci-dessus)
//
function taches_generales() {
	$taches_generales = array();

	// MAJ des rubriques publiques (cas de la publication post-datee)
	$taches_generales['rubriques'] = 3600;

	// Optimisation de la base
	$taches_generales['optimiser'] = 3600*48;

	// cache
	if (_DIR_RESTREINT)
		$taches_generales['invalideur'] = 3600;

	// nouveautes
	if (lire_meta('adresse_neuf') AND lire_meta('jours_neuf')
	AND (lire_meta('quoi_de_neuf') == 'oui') AND _DIR_RESTREINT)
		$taches_generales['mail']= 3600 * 24 * lire_meta('jours_neuf');

	// Stat. Attention: la popularite DOIT preceder les visites
	if (lire_meta("activer_statistiques") == "oui") {
		$taches_generales['statistiques'] = 3600;
		$taches_generales['popularites'] = 1800;
		$taches_generales['visites'] = 3600 * 24;
	}

	// syndication
	if (lire_meta("activer_syndic") == "oui") 
		$taches_generales['sites'] = 90;

	// indexation
	if (lire_meta("activer_moteur") == "oui") 
		$taches_generales['index'] = 60;
		
	// ajax
		$taches_generales['ajax'] = 3600 * 2;

	return $taches_generales;
}

// Fonctions effectivement appelees.
// Elles sont destinees a migrer dans leur fichier homonyme.

function cron_rubriques($t) {
	calculer_rubriques();
	return 1;
}

function cron_optimiser($t) {
	optimiser_base ();
	return 1;
}

function cron_index($t) {
	return count(effectuer_une_indexation());
}

function cron_sites($t) {
	$r = executer_une_syndication();
	if ((lire_meta('activer_moteur') == 'oui') &&
	    (lire_meta("visiter_sites") == 'oui')) {
		include_ecrire("inc_index.php3");
		$r2 = executer_une_indexation_syndic();
		$r = $r && $r2;
	}
	return $r;
}

// calcule les stats en plusieurs etapes par tranche de 100

function cron_statistiques($t) {
	$ref = calculer_n_referers(100);

	// Si ce n'est pas fini on redonne la meme date au fichier .lock
	// pour etre prioritaire lors du cron suivant
	if ($ref == 100) return (0 - $t);

	// Supprimer les referers trop vieux
	supprimer_referers();
	supprimer_referers("article");
	return 1;
}

function cron_popularites($t) {
	// Si c'est le premier appel (fichier .lock absent), ne pas calculer
	if ($t == 0) return 0;
	calculer_popularites($t);
	return 1;
}

function cron_visites($t) {
	// Si le fichier .lock est absent, ne pas calculer (mais reparer la date
	// du .lock de maniere a commencer a 00:00:01 demain).
	if ($t)
		calculer_visites();

	// il vaut mieux le lancer peu apres minuit, 
	// donc on pretend avoir ete execute precisement "ce matin a 00:00:01"
	// pour etre appele demain a la meme heure
	return 0 - (strtotime(date("d F Y", time()))+60);
}

function cron_mail($t) {
	$adresse_neuf = lire_meta('adresse_neuf');
	$jours_neuf = lire_meta('jours_neuf');
	// $t = 0 si le fichier de lock a ete detruit
	if (!$t) $t = time() - (3600 * 24 * $jours_neuf);

	include_local("inc-calcul.php3");
	$page= cherche_page('',
			    array('date' => date('Y-m-d H:i:s', $t),
				  'jours_neuf' => $jours_neuf),
				'nouveautes',
				'',
				lire_meta('langue_site'));
	$page = $page['texte'];
	if (substr($page,0,5) == '<'.'?php') {
# ancienne version: squelette en PHP avec affection des 2 variables ci-dessous
# 1 passe de plus a la sortie
				$mail_nouveautes = '';
				$sujet_nouveautes = '';
				$headers = '';
				eval ('?' . '>' . $page);
	} else {
# nouvelle version en une seule passe avec un squelette textuel:
# 1ere ligne = sujet
# lignes suivantes jusqu'a la premiere blanche: headers SMTP

				$page = stripslashes(trim($page));
				$p = strpos($page,"\n\n");
				$s = strpos($page,"\n");
				if ($p AND $s) {
					if ($p>$s)
						$headers = substr($page,$s+1,$p-$s);
					$sujet_nouveautes = substr($page,0,$s);
					$mail_nouveautes = trim(substr($page,$p+2));
				}
	}

	if (strlen($mail_nouveautes) > 10)
		envoyer_mail($adresse_neuf, $sujet_nouveautes, $mail_nouveautes, '', $headers);
	else
		spip_log("mail nouveautes : rien de neuf depuis $jours_neuf jours");
	return 1;
}

function cron_ajax ($t) {
	nettoyer_ajax();
	return 1;
}

function cron_invalideur($t) {
	//
	// menage des vieux fichiers du cache
	// marques par l'invalideur 't' = date de fin de fichier
	//

	retire_vieux_caches();

	// En cas de quota sur le CACHE/, nettoyer les fichiers les plus vieux

	// A revoir: il semble y avoir une desynchro ici.
	
		list ($total_cache) = spip_fetch_array(spip_query("SELECT SUM(taille)
		FROM spip_caches WHERE type IN ('t', 'x')"));
		spip_log("Taille du CACHE: $total_cache octets");

		global $quota_cache;
		$total_cache -= $quota_cache*1024*1024;
		if ($quota_cache > 0 AND $total_cache > 0) {
			$q = spip_query("SELECT id, taille FROM spip_caches
			WHERE type IN ('t', 'x') ORDER BY id");
			while ($r = spip_fetch_array($q)
			AND ($total_cache > $taille_supprimee)) {
				$date_limite = $r['id'];
				$taille_supprimee += $r['taille'];
			}
			spip_log ("Quota cache: efface $taille_supprimee octets");
			include_ecrire('inc_invalideur.php3');
			suivre_invalideur("id <= $date_limite AND type in ('t', 'x')");
		}
	return 1;
}

?>
