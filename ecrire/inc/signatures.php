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

// http://doc.spip.org/@message_de_signature
function message_de_signature($row)
{
  return propre(echapper_tags($row['message']));
}

// http://doc.spip.org/@inc_signatures_dist
function inc_signatures_dist($script, $id, $debut, $where, $order, $limit='') {

	# filtre de duree (a remplacer par une vraie pagination)
	#$where .= ($where ? " AND " : "") . "date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)";
	if ($id) { 
		$args = "id_article=$id&";
		$where .= " AND id_article=$id";
	}
	else $args = "";

	$evt = (_SPIP_AJAX === 1);

	$a = "editer_signature-$id";

	$q = spip_query("SELECT date_time FROM spip_signatures " . ($where ? "WHERE $where" : '') . " ORDER BY date_time DESC");

	charger_generer_url();

	while ($row = spip_fetch_array($q)) {
		if($c++%$limit==0) {	
			if ($c > 1) $res .= " | ";
			$date = affdate_court($row['date_time']);
			if ($c == ($debut+1))
				$res .= "<span class='spip_medium'><b>$c</b></span>";
			else {
				$h = generer_url_ecrire($script, $args ."debut=".($c-1));
				if ($evt)
					$evt = "\nonclick="
					. ajax_action_declencheur($h,$a);
				$res .= "<a href='$h$a' title='$date'$evt>$c</a>";
			}
		}
	}

	$limit = (!$limit AND !$debut) ? '' : (($debut ? "$debut," : "") . $limit);
#	($limit . ($debut ? " OFFSET $debut" : "")); #PG
	$request = spip_query("SELECT * FROM spip_signatures " .  ($where ? " WHERE $where" : "") .  ($order ? " ORDER BY $order" : "") . (!$limit ? ''  : " LIMIT $limit"));

	$res .= '<br />';

 	while($row=spip_fetch_array($request)){
		$res .= '<br />' . signatures_edit($script, $id, $debut, $row);
	}
	return $res;
}

// http://doc.spip.org/@signatures_edit
function signatures_edit($script, $id, $debut, $row) {

		$id_signature = $row['id_signature'];
		$id_article = $row['id_article'];
		$date_time = $row['date_time'];
		$nom_email= typo(echapper_tags($row['nom_email']));
		$ad_email = echapper_tags($row['ad_email']);
		$nom_site = typo(echapper_tags($row['nom_site']));
		$url_site = echapper_tags($row['url_site']);
		$statut = $row['statut'];
		
		$arg = ($statut=="publie") ? "-$id_signature" : $id_signature;
		$res = "";
		
		if ($statut=="poubelle"){
			$res .= "<table width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td style='background-color: #ff0000'>";
		}
		
		$res .= "<table width='100%' cellpadding='3' cellspacing='0'><tr><td class='verdana2 toile_foncee' style='color: white;'><b>"
 		.  ($nom_site ? "$nom_site / " : "")
		.  $nom_email
		.  "</b></td></tr>"
		.  "<tr><td style='background-color: #ffffff' class='serif'>";
				
		if ($statut=="publie"){
			$res .= icone_inline (_T('icone_supprimer_signature'),
				redirige_action_auteur('editer_signatures', $arg, $script, "id_article=$id&debut=$debut"),
				"forum-interne-24.gif", 
				"supprimer.gif",
				"right",
				false);
		} elseif ($statut=="poubelle"){
			$res .= icone_inline (_T('icone_valider_signature'),
				redirige_action_auteur('editer_signatures', $arg, $script, "id_article=$id&debut=$debut"),
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
		}
		
		$res .= "<span class='spip_small'>".date_interface($date_time)."</span><br />";
		if ($statut=="poubelle"){
			$res .= "<span class='spip_x-small' style='color: red;'>"._T('info_message_efface')."</span><br />";
		}
		if (strlen($url_site)>6) {
			if (!$nom_site) $nom_site = _T('info_site');
			$res .= "<span class='spip_x-small'>"._T('info_site_web')."</span> <a href='$url_site'>$nom_site</a><br />";
		}
		if (strlen($ad_email)>0){
			$res .= "<span class='spip_x-small'>"._T('info_adresse_email')."</span> <a href='mailto:$ad_email'>$ad_email</a><br />";
		}

		$res .= '<br />' . message_de_signature($row);
		
		if (!$id) {
			$r = spip_fetch_array(spip_query("SELECT titre, statut FROM spip_articles WHERE id_article=$id_article"));

			$res .= "<span class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>"
			. _T('info_numero_abbreviation')
			. $id_article
			. " </b></span><a href='"
			  .  (($r['statut'] == 'publie') ? 
			      generer_url_action('redirect', "id_article=$id_article") :
			      generer_url_ecrire('articles', "id_article=$id_article"))
			  . "'>"
			  . typo($r['titre'])
			  . "</a>";
		}
		$res .= "</td></tr></table>";
		
		if ($statut=="poubelle"){
			$res .= "</td></tr></table>";
		}

		return $res;
}
?>
