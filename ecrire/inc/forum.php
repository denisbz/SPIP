<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/actions');

// http://doc.spip.org/@affiche_navigation_forum
function affiche_navigation_forum($script, $args, $debut, $i, $pack, $ancre, $query)
{
	$nav = ($i <=0) ? '' : ("<a href='" . generer_url_ecrire($script, $args) ."'>0</a> ... |\n");

	$e = (_SPIP_AJAX === 1 );

	$n = sql_count($query);

	for (;$n;$n--){

		if ($i == $pack*floor($i/$pack)) {
			if ($i == $debut)
				$nav .= "<span class='spip_medium'><b>$i</b></span> |\n";
			else {
				$h = generer_url_ecrire($script, $args . "&debut=$i");
				if ($e)	$e = "\nonclick=" . ajax_action_declencheur($h,$ancre);
				$nav .= "<a href='$h'$e>$i</a> |\n";
			}
		}
		$i ++;
	}

	$h = generer_url_ecrire($script, $args . "&debut=$i");

	if ($e)	$e = "\nonclick=" . ajax_action_declencheur($h,$ancre);

	return "$nav<a href='$h'$e>...</a> |";
}


// tous les boutons de controle d'un forum
// nb : les forums prives (privrac ou prive), une fois effaces
// (privoff), ne sont pas revalidables ; le forum d'admin (privadm)
// n'est pas effacable

// http://doc.spip.org/@boutons_controle_forum
function boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur=0, $ref, $forum_ip) {
	$controle = '';


	// selection du logo et des boutons correspondant a l'etat du forum
	switch ($forum_stat) {
		# forum sous un article dans l'espace prive
		case "prive":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'privoff';
			break;
		# forum des administrateurs
		case "privadmin":
			$logo = "forum-admin-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum de l'espace prive, supprime (non revalidable,
		# d'ailleurs on ne sait plus a quel type de forum il appartenait)
		case "privoff":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum general de l'espace prive
		case "privrac":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'privoff';
			break;

		# forum publie sur le site public
		case "publie":
			$logo = "forum-public-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'off';
			break;
		# forum supprime sur le site public
		case "off":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$suppression = false;
			$controle = "<br /><span style='color: red; font-weight: bold;'>"._T('info_message_supprime')." $forum_ip</span>";
			if($forum_id_auteur)
				$controle .= " - <a href='" . generer_url_ecrire('auteur_infos', "id_auteur=$forum_id_auteur") .
				  "'>" ._T('lien_voir_auteur'). "</a>";
			break;
		# forum propose (a moderer) sur le site public
		case "prop":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = true;
			$suppression = 'off';
			break;
		# forum signale comme spam sur le site public
		case "spam":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$suppression = false;
			$spam = true;
			break;
		# forum original (reponse a un forum modifie) sur le site public
		case "original":
			$logo = "forum-public-24.gif";
			$original = true;
			break;
		default:
			return;
	}

	$lien = self('&') . "#id$id_forum";
	$boutons ='';
	if ($suppression)
	  $boutons .= icone_inline(_T('icone_supprimer_message'), generer_action_auteur('instituer_forum',"$id_forum-$suppression", _DIR_RESTREINT_ABS . $lien),
			$logo,
			"supprimer.gif", 'right', 'non');

	if ($valider)
	  $boutons .= icone_inline(_T('icone_valider_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", _DIR_RESTREINT_ABS . $lien),
			$logo,
			"creer.gif", 'right', 'non');

	if ($valider_repondre) {
	  $dblret =  rawurlencode(_DIR_RESTREINT_ABS . $lien);
	  $boutons .= icone_inline(_T('icone_valider_message') . " &amp; " .   _T('lien_repondre_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", generer_url_public('forum', "$ref&id_forum=$id_forum&retour=$dblret", true)),
			     $logo,
			     "creer.gif", 'right', 'non');
	}

	if ($boutons) $controle .= "<div style='float:".$GLOBALS['spip_lang_right'] ."; width: 80px;'>". $boutons . "</div>";

	// TODO: un bouton retablir l'original ?
	if ($original) {
		$controle .= "<div style='float:".$GLOBALS['spip_lang_right'].";color:green'>"
		."("
		._T('forum_info_original')
		.")</div>";
	}

	if ($spam) {
		$controle .= "<div style='float:".$GLOBALS['spip_lang_right'].";color:red'>"
		."("
		._T('spam') // Marque' comme spam ?
		.")</div>";
	}


	return $controle;
}

// recuperer le critere SQL qui selectionne nos forums
// http://doc.spip.org/@critere_statut_controle_forum
function critere_statut_controle_forum($type, $id_rubrique=0, $recherche='') {

	if (is_array($id_rubrique))   $id_rubrique = join(',',$id_rubrique);
	if (!$id_rubrique) {
		$from = 'spip_forum AS F';
		$where = "";
		$and = "";
	} else {
		if (strpos($id_rubrique,','))
		  $eq = " IN ($id_rubrique)";
		else $eq = "=$id_rubrique";
	      
		$from = 'spip_forum AS F, spip_articles AS A';
		$where = "A.id_secteur$eq AND F.id_article=A.id_article";
		$and = ' AND ';
	}
   
	switch ($type) {
	case 'public':
		$and .= "F.statut IN ('publie', 'off', 'prop', 'spam') AND F.texte!=''";
		break;
	case 'prop':
		$and .= "F.statut='prop'";
		break;
	case 'spam':
		$and .= "F.statut='spam'";
		break;
	case 'interne':
		$and .= "F.statut IN ('prive', 'privrac', 'privoff', 'privadm') AND F.texte!=''";
		break;
	case 'vide':
		$and .= "F.statut IN ('publie', 'off', 'prive', 'privrac', 'privoff', 'privadm') AND F.texte=''";
		break;
	default:
		$where = '0=1';
		$and ='';
		break;
	}

	if ($recherche) {
		include_spip('inc/rechercher');
		if ($a = recherche_en_base($recherche, 'forum'))
			$and .= " AND ".sql_in('id_forum',
				array_keys(array_pop($a)));
		else
			$and .= " 0=1";
	}

	return array($from, "$where$and");
}

// Index d'invalidation des forums
// http://doc.spip.org/@calcul_index_forum
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic) {
	if ($id_article) return 'a'.$id_article; 
	if ($id_breve) return 'b'.$id_breve;
	if ($id_rubrique) return 'r'.$id_rubrique;
	if ($id_syndic) return 's'.$id_syndic;
}

//
// Recalculer tous les threads
//
// http://doc.spip.org/@calculer_threads
function calculer_threads() {
	// fixer les id_thread des debuts de discussion
	sql_update('spip_forum', array('id_thread'=>'id_forum'), "id_parent=0");
	// reparer les messages qui n'ont pas l'id_secteur de leur parent
	do {
		$discussion = "0";
		$precedent = 0;
		$r = sql_select("fille.id_forum AS id,	maman.id_thread AS thread", 'spip_forum AS fille, spip_forum AS maman', "fille.id_parent = maman.id_forum AND fille.id_thread <> maman.id_thread",'', "thread");
		while ($row = sql_fetch($r)) {
			if ($row['thread'] == $precedent)
				$discussion .= "," . $row['id'];
			else {
				if ($precedent)
					sql_updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
				$precedent = $row['thread'];
				$discussion = $row['id'];
			}
		}
		sql_updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
	} while ($discussion != "0");
}

// Calculs des URLs des forums (pour l'espace public)
// http://doc.spip.org/@racine_forum
function racine_forum($id_forum){
	if (!$id_forum = intval($id_forum)) return;
	$result = sql_select("id_parent, id_rubrique, id_article, id_breve, id_syndic, id_message, id_thread", "spip_forum", "id_forum=".$id_forum);

	if (!$row = sql_fetch($result))
		return false;

	if ($row['id_parent']
	AND $row['id_thread'] != $id_forum) // eviter boucle infinie
		return racine_forum($row['id_thread']);

	if ($row['id_message'])
		return array('message', $row['id_message'], $id_forum);
	if ($row['id_rubrique'])
		return array('rubrique', $row['id_rubrique'], $id_forum);
	if ($row['id_article'])
		return array('article', $row['id_article'], $id_forum);
	if ($row['id_breve'])
		return array('breve', $row['id_breve'], $id_forum);
	if ($row['id_syndic'])
		return array('site', $row['id_syndic'], $id_forum);

	// On ne devrait jamais arriver ici, mais prevoir des cas de forums
	// poses sur autre chose que les objets prevus...
	spip_log("erreur racine_forum $id_forum");
	return false;
} 


// http://doc.spip.org/@parent_forum
function parent_forum($id_forum) {
	if (!$id_forum = intval($id_forum)) return;
	$result = sql_select("id_parent, id_rubrique, id_article, id_breve, id_syndic", "spip_forum", "id_forum=".$id_forum);
	if($row = sql_fetch($result)){
		if($row['id_parent']) return array('forum', $row['id_parent']);
		if($row['id_rubrique']) return array('rubrique', $row['id_rubrique']);
		if($row['id_article']) return array('article', $row['id_article']);
		if($row['id_breve']) return array('breve', $row['id_breve']);
		if($row['id_syndic']) return array('site', $row['id_syndic']);
	}
} 


// http://doc.spip.org/@generer_url_forum_dist
function generer_url_forum_dist($id_forum, $args='', $ancre='') {
	if (!$id_forum) return '';
	list($type, $id, $id_thread) = racine_forum($id_forum);
	if (!$ancre) $ancre = "forum$id_forum";
	if (function_exists($f = 'generer_url_'.$type))
		return $f($id, $args, $ancre);
}


// http://doc.spip.org/@generer_url_forum_parent
function generer_url_forum_parent($id_forum) {
	if (!$id_forum = intval($id_forum)) return;
	list($type, $id) = parent_forum($id_forum);
	if (function_exists($f = 'generer_url_'.$type))
		return $f($id);
} 


// Quand on edite un forum, on tient a conserver l'original
// sous forme d'un forum en reponse, de statut 'original'
// http://doc.spip.org/@conserver_original
function conserver_original($id_forum) {
	$s = sql_select("id_forum", "spip_forum", "id_parent=".sql_quote($id_forum)." AND statut='original'");

	if (sql_count($s))
		return ''; // pas d'erreur

	// recopier le forum
	$t = sql_fetsel("*", "spip_forum", "id_forum=".sql_quote($id_forum));

	if ($t) {
		unset($t['id_forum']);
		include_spip('base/abstract_sql');
		$id_copie = sql_insertq('spip_forum', $t);
		if ($id_copie) {
			sql_updateq('spip_forum', array('id_parent'=> $id_forum, 'statut'=>'original'), "id_forum=$id_copie");
			return ''; // pas d'erreur
		}
	}

	return '&erreur';
}

// appelle conserver_original(), puis modifie le contenu via l'API inc/modifier
// http://doc.spip.org/@enregistre_et_modifie_forum
function enregistre_et_modifie_forum($id_forum, $c=false) {
	if ($err = conserver_original($id_forum)) {
		spip_log("erreur de sauvegarde de l'original, $err");
		return;
	}

	include_spip('inc/modifier');
	return revision_forum($id_forum, $c);
}

?>
