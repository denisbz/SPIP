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

// http://doc.spip.org/@action_editer_site_dist
function action_editer_site_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$resyndiquer = false;

	include_spip('inc/filtres'); // pour vider_url()

	if (preg_match(',options/(\d+),',$arg, $r)) {
		$id_syndic = $r[1];
		$resyndiquer = editer_site_options($id_syndic);
	// Envoi depuis le formulaire d'edition d'un site existant
	} else if ($id_syndic = intval($arg)) {
		// reload si on change une des valeurs de syndication
		if (
		(_request('url_syndic') OR _request('resume') OR _request('syndication'))
		AND $t = sql_fetsel('url_syndic,syndication,resume', 'spip_syndic', "id_syndic="._q($id_syndic))
		AND (
			(_request('url_syndic') AND _request('url_syndic') != $t['url_syndic'])
			OR
			(_request('syndication') AND _request('syndication') != $t['syndication'])
			OR
			(_request('resume') AND _request('resume') != $t['resume'])
			)
		)
			set_request('reload', 'oui');
		revisions_sites($id_syndic);
	
	// Envoi normal depuis le formulaire de creation d'un site
	} else if (!strlen(vider_url(_request('url_auto')))) {
		if (strlen(vider_url(_request('url_site')))
		AND strlen(_request('nom_site'))) {
			set_request('reload', 'oui');
			$id_syndic = insert_syndic(_request('id_parent'));
			revisions_sites($id_syndic);
		} else {
			redirige_par_entete(
				generer_url_ecrire('sites_edit', 'id_rubrique='._request('id_parent'),'&')
			);
		}
	}
	// Envoi depuis le formulaire d'analyse automatique d'un site
	else if (strlen(vider_url(_request('url_auto')))) {
		if ($auto = analyser_site(_request('url_auto'))) {
			$id_syndic = insert_syndic(_request('id_parent'));
			revisions_sites($id_syndic, $auto);
			if ($auto['syndication'] == 'oui')
				set_request('reload', 'oui');

			// Enregistrer le logo s'il existe
			if ($auto['logo'] AND $auto['format_logo'])
				@rename($auto['logo'],
				_DIR_IMG . 'siteon'.$id_syndic.'.'.$auto['format_logo']);
		}
		else
			redirige_par_entete(
				generer_url_ecrire('sites_edit', 'id_rubrique='._request('id_parent'),'&')
			);
	}
	// Erreur
	else {
		redirige_par_entete(generer_url_ecrire());
	}

	// Re-syndiquer le site
	if (_request('reload') == 'oui') {
		// Effacer les messages si on supprime la syndication
		if (_request('syndication') == 'non')
			sql_delete("spip_syndic_articles", "id_syndic="._q($id_syndic));

		$t = sql_getfetsel('descriptif', 'spip_syndic', "id_syndic=$id_syndic AND syndication IN ('oui', 'sus', 'off')", '','', 1);
		if ($t !== NULL) {

			// Si descriptif vide, chercher le logo si pas deja la
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (!$logo = $chercher_logo($id_syndic, 'id_syndic', 'on')
			OR !$t) {
				$auto = analyser_site(_request('url_syndic'));
				revisions_sites($id_syndic,
					array('descriptif' => $auto['descriptif'])
				);
				if (!$logo
				AND $auto['logo'] AND $auto['format_logo'])
					@rename($auto['logo'],
					_DIR_IMG . 'siteon'.$id_syndic.'.'.$auto['format_logo']);
			}
			$resyndiquer = true;
		}
	}

	if ($resyndiquer) {
	  // ah si PHP connaisait les fermetures...
	  // A la place, une constante utilisee exclusivement
	  // dans la fct suivante.
		define('_GENIE_SYNDIC_NOW', $id_syndic);
		// forcer l'execution immediate de cette tache
		// (i.e. appeler la fct suivante avec gestion du verrou)
		cron(0, array('syndic' => -91));
	}
	// Rediriger le navigateur
	$redirect = parametre_url(urldecode(_request('redirect')),
		'id_syndic', $id_syndic, '&');
	redirige_par_entete($redirect);
}

// Cette fonction redefinit la tache standard de syndication
// pour la forcer a syndiquer le site dans la globale genie_syndic_now

// http://doc.spip.org/@genie_syndic
function genie_syndic($t) {
	include_spip('genie/syndic');
	define('_GENIE_SYNDIC', 2); // Pas de faux message d'erreur
	$t = syndic_a_jour(_GENIE_SYNDIC_NOW);
	return $t ? 0 : _GENIE_SYNDIC_NOW;
}

// http://doc.spip.org/@insert_syndic
function insert_syndic($id_rubrique) {

	include_spip('inc/rubriques');

	// Si id_rubrique vaut 0 ou n'est pas definie, creer le site
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = sql_fetsel("id_rubrique", "spip_rubriques", "id_parent=0",'', '0+titre,titre', "1");
		$id_rubrique = $row['id_rubrique'];
	}


	// Le secteur a la creation : c'est le secteur de la rubrique
	$row = sql_fetsel("id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
	$id_secteur = $row['id_secteur'];

	$id_syndic = sql_insert("spip_syndic",
		"(id_rubrique, id_secteur, statut, date)",
		"($id_rubrique, $id_secteur, 'prop', NOW())");

	return $id_syndic;
}


// Enregistre une revision de syndic
// $c est un contenu (par defaut on prend le contenu via _request())
// http://doc.spip.org/@revisions_sites
function revisions_sites ($id_syndic, $c=false) {

	include_spip('inc/filtres');
	include_spip('inc/autoriser');
	include_spip('inc/rubriques');

	$id_auteur = isset($c['id_auteur']) ? $c['id_auteur'] : NULL;   

	// Ces champs seront pris nom pour nom (_POST[x] => spip_syndic.x)
	$champs_normaux = array('nom_site', 'url_site', 'descriptif', 'url_syndic', 'syndication');

	// ne pas accepter de titre vide
	if (_request('nom_site', $c) === '')
		$c = set_request('nom_site', _T('ecrire:info_sans_titre'), $c);

	$champs = array();
	foreach ($champs_normaux as $champ) {
		$val = _request($champ, $c);
		if ($val !== NULL)
			$champs[$champ] = corriger_caracteres($val);
	}

	$s = sql_select("statut, id_rubrique, id_secteur", "spip_syndic", "id_syndic=$id_syndic");
	$row = sql_fetch($s);
	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $row['statut'];
	$id_secteur_old = $row['id_secteur'];

	$statut = _request('statut', $c);

	if ($statut
	AND $statut != $statut_ancien
	AND autoriser('publierdans','rubrique',$id_rubrique, $id_auteur)) {
		$champs['statut'] = $statut;
		if ($statut == 'publie') {
			if ($d = _request('date', $c)) {
				$champs['date'] = $d;
			} else {
				# on prend la date de MySQL pour eviter un decalage cf. #975
				$d = sql_fetsel('NOW() AS d');
				$champs['date'] = $d['d'];
			}
		}
	} else
		$statut = $statut_ancien;

	// Changer de rubrique ?
	// Verifier que la rubrique demandee est differente de l'actuelle,
	// et qu'elle existe. Recuperer son secteur

	if ($id_parent = intval(_request('id_parent', $c))
	AND $id_parent != $id_rubrique
	AND ($id_secteur = sql_getfetsel('id_secteur', 'spip_rubriques', "id_rubrique=$id_parent"))) {
		$champs['id_rubrique'] = $id_parent;
		if ($id_secteur_old != $id_secteur)
			$champs['id_secteur'] = $id_secteur;
		// si le site est publie
		// et que le demandeur n'est pas admin de la rubrique
		// repasser le site en statut 'prop'.
		if ($statut == 'publie') {
			if (!autoriser('publierdans','rubrique',$id_parent, $id_auteur))
				$champs['statut'] = $statut = 'prop';
		}
	}

	// recuperer les extras
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		if ($extra = extra_update('syndic', $id_syndic, $c))
			$champs['extra'] = $extra;
	}

	if (!$champs) return;

	// Enregistrer les modifications
	sql_updateq('spip_syndic', $champs, "id_syndic=$id_syndic");

	// Envoyer aux plugins
	include_spip('inc/modifier'); # temporaire pour eviter un bug
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_syndic',
				'id_objet' => $id_syndic
			),
			'data' => $champs
		)
	);

	// marquer le fait que le site est travaille par toto a telle date
	// une alerte sera donnee aux autres redacteurs sur exec=sites
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		signale_edition ($id_syndic, $GLOBALS['auteur_session'], 'syndic');
	}


	//
	// Post-modifications
	//

	// Invalider les caches
	if ($statut == 'publie') {
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_syndic/$id_syndic'");
	}

	calculer_rubriques_if($id_rubrique, $champs, $statut_ancien);

	// Notification ?
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_syndic',
				'id_objet' => $id_syndic
			),
			'data' => $champs
		)
	);
}


// http://doc.spip.org/@analyser_site
function analyser_site($url) {
	include_spip('inc/filtres');
	include_spip('inc/distant');

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	$texte = recuperer_page($url, true);
	if (!$texte) return false;

	if (preg_match(',<(channel|feed)([:[:space:]][^>]*)?'
	.'>(.*)</\1>,ims', $texte, $regs)) {
		$result['syndication'] = 'oui';
		$result['url_syndic'] = $url;
		$channel = $regs[3];

		// Pour recuperer l'entete, on supprime tous les items
		$b = array_merge(
			extraire_balises($channel, 'item'),
			extraire_balises($channel, 'entry')
		);
		$header = str_replace($b,array(),$channel);

		if ($t = extraire_balise($header, 'title'))
			$result['nom_site'] = supprimer_tags($t);
		if ($t = extraire_balises($header, 'link')) {
			foreach ($t as $link) {
				$u = supprimer_tags(filtrer_entites($link));
				if (!strlen($u))
					$u = extraire_attribut($link, 'href');
				if (strlen($u)) {
					// on installe l'url comme url du site
					// si c'est non vide, en donnant la priorite a rel=alternate
					if (preg_match(',\balternate\b,', extraire_attribut($link, 'rel'))
					OR !isset($result['url_site']))
						$result['url_site'] = filtrer_entites($u);
				}
			}
		}
		$result['url_site'] = url_absolue($result['url_site'], $url);

		if ($a = extraire_balise($header, 'description')
		OR $a = extraire_balise($header, 'tagline')) {
			$result['descriptif'] = supprimer_tags($a);
		}

		if (preg_match(',<image.*<url.*>(.*)</url>.*</image>,Uims',
		$header, $r)
		AND preg_match(',(https?://.*/.*(gif|png|jpg)),ims', $r[1], $r)
		AND $image = recuperer_infos_distantes($r[1])) {
			if (in_array($image['extension'], array('gif', 'jpg', 'png'))) {
				$result['format_logo'] = $image['extension'];
				$result['logo'] = $image['fichier'];
			}
			else if ($image['fichier']) {
				spip_unlink($image['fichier']);
			}
		}
	}
	else {
		$result['syndication'] = 'non';
		$result['url_site'] = $url;
		if (preg_match(',<head>(.*(description|title).*)</head>,Uims', $texte, $regs)) {
			$head = filtrer_entites($regs[1]);
		} else
			$head = $texte;
		if (preg_match(',<title[^>]*>(.*),i', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(preg_replace(',</title>.*,i', '', $regs[1])));
		if ($a = array_merge(
			extraire_balises($head, 'meta'),
			extraire_balises($head, 'http-equiv')
		)) {
			foreach($a as $meta) {
				if (extraire_attribut($meta, 'name') == 'description') {
					$desc = trim(extraire_attribut($meta, 'content'));
					if (!strlen($desc))
						$desc = trim(extraire_attribut($meta, 'value'));
					$result['descriptif'] = $desc;
				}
			}
		}

		// Cherchons quand meme un backend
		include_spip('inc/distant');
		include_spip('inc/feedfinder');
		$feeds = get_feed_from_url($url, $texte);
		// si on a a trouve un (ou plusieurs) on le note avec select:
		// ce qui constitue un signal pour exec=sites qui proposera de choisir
		// si on syndique, et quelle url.
		if (count($feeds)>=1) {
			spip_log("feedfinder.php :\n".join("\n", $feeds));
			$result['url_syndic'] = "select: ".join(' ',$feeds);
		}
	}

	return $result;
}

// Enregistrre les options et retourne True s'il faut syndiquer.

// http://doc.spip.org/@editer_site_options
function editer_site_options($id_syndic)
{
	$moderation = _request('moderation');
	$miroir = _request('miroir');
	$oubli = _request('oubli');
	$resume = _request('resume');

	if ($moderation == 'oui' OR $moderation == 'non')
		sql_updateq("spip_syndic", array("moderation" => $moderation), "id_syndic=$id_syndic");
	if ($miroir == 'oui' OR $miroir == 'non')
		sql_updateq("spip_syndic", array("miroir" => $miroir	), "id_syndic=$id_syndic");
	if ($oubli == 'oui' OR $oubli == 'non')
		sql_updateq("spip_syndic", array("oubli" => $oubli), "id_syndic=$id_syndic");

	if (!($resume == 'oui' OR $resume == 'non')) return false;

	sql_updateq("spip_syndic", array("resume" => $resume	), "id_syndic=$id_syndic");
	return true;
}

?>
