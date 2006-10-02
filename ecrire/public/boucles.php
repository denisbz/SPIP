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


//
// Ce fichier definit les boucles standard de SPIP
//

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Boucle standard, sans condition rajoutee
//
// http://doc.spip.org/@boucle_DEFAUT_dist
function boucle_DEFAUT_dist($id_boucle, &$boucles) {
	global $table_des_tables;
	$boucle = &$boucles[$id_boucle];
	$type = $boucle->type_requete;
	$id_table = $table_des_tables[$type];
	if (!$id_table)
	  //	  table hors SPIP
	  $boucle->from[$type] =  $type;
	else {
	// les tables declarees par spip ont un prefixe et un surnom 
	  $boucle->from[$id_table] =  'spip_' . $type ;
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(BOUCLE)> boucle dite recursive
//
// http://doc.spip.org/@boucle_BOUCLE_dist
function boucle_BOUCLE_dist($id_boucle, &$boucles) {

	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(ARTICLES)>
//
// http://doc.spip.org/@boucle_ARTICLES_dist
function boucle_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_articles";
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!$boucle->statut) {
		if (!$GLOBALS['var_preview']) {
			$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
			if ($GLOBALS['meta']["post_dates"] == 'non')
				$boucle->where[]= array("'<'", "'$id_table" . ".date'", "'NOW()'");
		} else
			$boucle->where[]= array("'IN'", "'$mstatut'", "'(\"publie\",\"prop\")'");
	}
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(AUTEURS)>
//
// http://doc.spip.org/@boucle_AUTEURS_dist
function boucle_AUTEURS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_auteurs";
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!$boucle->statut) {
		// Si pas de lien avec un article, selectionner
		// uniquement les auteurs d'un article publie
		if (!$GLOBALS['var_preview'])
		if (!isset($boucle->modificateur['lien']) AND !isset($boucle->modificateur['tout'])) {
			$boucle->from["lien"] =  "spip_auteurs_articles";
			$boucle->from["articles"] =  "spip_articles";
			$boucle->where[]= array("'='", "'lien.id_auteur'", "'$id_table.id_auteur'");
			$boucle->where[]= array("'='", "'lien.id_article'", "'articles.id_article'");
			$boucle->where[]= array("'='", "'articles.statut'", "'\"publie\"'");
			$boucle->group[] = $boucle->id_table . '.' . $boucle->primary;  
		}
		// pas d'auteurs poubellises
		$boucle->where[]= array("'!='", "'$mstatut'", "'\"5poubelle\"'");
	}

	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(BREVES)>
//
// http://doc.spip.org/@boucle_BREVES_dist
function boucle_BREVES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_breves";
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!$boucle->statut) {
		if (!$GLOBALS['var_preview'])
			$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
		else
			$boucle->where[]= array("'IN'", "'$mstatut'", "'(\"publie\",\"prop\")'");
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(FORUMS)>
//
// http://doc.spip.org/@boucle_FORUMS_dist
function boucle_FORUMS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_forum";
	$mstatut = $id_table .'.statut';

	// Par defaut, selectionner uniquement les forums sans mere
	// Les criteres {tout} et {plat} inversent ce choix
	if (!isset($boucle->modificateur['tout']) AND !isset($boucle->modificateur['plat'])) {
		$boucle->where[]= array("'='", "'$id_table." ."id_parent'", 0);
	}
	// Restreindre aux elements publies
	if (!$boucle->statut) {
		if ($GLOBALS['var_preview'])
			$boucle->where[]= array("'IN'", "'$mstatut'", "'(\"publie\",\"prive\")'");		
		else
			$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SIGNATURES)>
//
// http://doc.spip.org/@boucle_SIGNATURES_dist
function boucle_SIGNATURES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	$boucle->from[$id_table] =  "spip_signatures";

	// Restreindre aux elements publies
	if (!$boucle->statut) {
		$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
	}
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(DOCUMENTS)>
//
// http://doc.spip.org/@boucle_DOCUMENTS_dist
function boucle_DOCUMENTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_documents";
	// on ne veut pas des fichiers de taille nulle,
	// sauf s'ils sont distants (taille inconnue)
	$boucle->where[]= array("'($id_table.taille > 0 OR $id_table.distant=\"oui\")'");

	return calculer_boucle($id_boucle, $boucles);
}

//
// <BOUCLE(RUBRIQUES)>
//
// http://doc.spip.org/@boucle_RUBRIQUES_dist
function boucle_RUBRIQUES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_rubriques";
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!$boucle->statut) {
		if (!$GLOBALS['var_preview'])
			if (!isset($boucle->modificateur['tout']))
				$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(HIERARCHIE)>
//
// http://doc.spip.org/@boucle_HIERARCHIE_dist
function boucle_HIERARCHIE_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_rubriques";

// Si la boucle mere est une boucle RUBRIQUES il faut ignorer la feuille
// sauf en presence du critere {tout} (vu par phraser_html)

	$boucle->hierarchie = '$hierarchie = calculer_hierarchie('
	. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
	. ', '
	. (isset($boucle->modificateur['tout']) ? 'false' : 'true')
	. ');';

	$boucle->having[]= array("'<>'", "'rang'", 0);
	$boucle->select[]= "FIELD($id_table" . '.id_rubrique, $hierarchie) AS rang';

	if ($boucle->default_order[0] != " DESC")
		$boucle->default_order[] = "'rang'" ;
	else
		$boucle->default_order[0] = "'rang DESC'" ;
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SYNDICATION)>
//
// http://doc.spip.org/@boucle_SYNDICATION_dist
function boucle_SYNDICATION_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_syndic";
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies

	if (!$boucle->statut) {
		if (!$GLOBALS['var_preview']) {
			$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
		} else
			$boucle->where[]= array("'IN'", "'$mstatut'", "'(\"publie\",\"prop\")'");
	}
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(SYNDIC_ARTICLES)>
//
// http://doc.spip.org/@boucle_SYNDIC_ARTICLES_dist
function boucle_SYNDIC_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[$id_table] =  "spip_syndic_articles" ;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies, sauf critere contraire
	if ($boucle->statut) {}
	else if ($GLOBALS['var_preview'])
		$boucle->where[]= array("'IN'", "'$mstatut'", "'(\"publie\",\"prop\")'");
	else {
		$jointure = array_search("spip_syndic", $boucle->from);
		if (!$jointure) {
			$jointure = 'J' . count($boucle->from);
			$boucle->from[$jointure] = 'spip_syndic';
			$boucle->where[]= array("'='", "'$id_table" .".id_syndic'",
						"\"$jointure" . '.id_syndic"');
		}
		$boucle->where[]= array("'='", "'$mstatut'", "'\"publie\"'");
		$boucle->where[]= array("'='", "'$jointure" . ".statut'", "'\"publie\"'");

	}
	return calculer_boucle($id_boucle, $boucles);
}

?>
