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


if (!defined("_ECRIRE_INC_VERSION")) return;


// Definition des noeuds de l'arbre de syntaxe abstraite

class Texte {
	var $type = 'texte';
	var $texte;
	var $avant, $apres = ""; // s'il y avait des guillemets autour
	var $ligne = 0; 
}

class Inclure {
	var $type = 'include';
	var $texte;
	var $avant, $apres; // inutilises mais generiques
	var $ligne = 0; 
	var $param = array();  //  valeurs des params
}

//
// encodage d'une boucle SPIP en un objet PHP
//
class Boucle {
	var $type = 'boucle';
	var $id_boucle;
	var $id_parent ='';
	var $avant, $milieu, $apres, $altern;
	var $lang_select;
	var $type_requete;
	var $sql_serveur;
	var $jointures;
	var $param = array();
	var $criteres = array();
	var $separateur = array();
	var $doublons;
	var $partie, $total_parties,$mode_partie;
	var $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	var $tout = false;
	var $plat = false;
	var $select = array();
	var $from = array();
	var $where = array();
	var $join = array();
	var $having = 0;
	var $limit;
	var $group = array();
	var $order = array();
	var $default_order = array();
	var $date = 'date' ;
	var $hash = "" ;
	var $lien = false;
	var $sous_requete = false;
	var $hierarchie = '';
	var $statut = false;
	// champs pour la construction du corps PHP
	var $id_table;
	var $primary;
	var $return;
	var $numrows = false; 
	var $cptrows = false; 
	var $ligne = 0; 
}

// sous-noeud du precedent

class Critere {
	var $op;
	var $not;	
	var $param = array();
	var $ligne = 0; 
}

class Champ {
	var $type = 'champ';
	var $nom_champ;
	var $nom_boucle= ''; // seulement si boucle explicite
	var $avant, $apres; // tableaux d'objets
	var $etoile;
	var $param = array();  // filtre explicites
	var $fonctions = array();  // source des filtres (compatibilite)
	// champs pour la production de code
	var $id_boucle;
	var $boucles;
	var $type_requete;
	var $code;	// code du calcul
	var $statut;	// 'numerique, 'h'=texte (html) ou 'p'=script (php) ?
			// -> definira les pre et post-traitements obligatoires
	// tableau pour la production de code dependant du contexte
	// id_mere;  pour TOTAL_BOUCLE hors du corps
	// document; pour embed et img dans les textes
	// sourcefile; pour DOSSIER_SQUELETTE
	var $descr = array();
	// pour localiser les erreurs
	var $ligne = 0; 
}


class Idiome {
	var $type = 'idiome';
	var $nom_champ = ""; // la chaine a traduire
	var $module = ""; // son module de definition
	var $param = array(); // les filtres a appliquer au resultat
	var $fonctions = array(); // source des filtres  (compatibilite)
	var $avant, $apres; // inutilises mais faut = ci-dessus
	// champs pour la production de code, cf ci-dessus
	var $id_boucle;
	var $boucles;
	var $type_requete;
	var $code;
	var $statut;
	var $descr = array();
	var $ligne = 0; 
}

class Polyglotte {
	var $type = 'polyglotte';
	var $traductions = array(); // les textes ou choisir
	var $ligne = 0; 
}
//
// Globales de description de la base

//ces variabales ne sont pas initialisees par "$var = array()"
// afin de permettre leur extension dans mes_options.php etc

global $tables_des_serveurs_sql, $tables_principales; // (voir inc_serialbase)
global $exceptions_des_tables, $table_des_tables;
global $tables_relations,  $table_date;

 # cf. fonction table_objet dans inc_version
$table_des_tables['articles']='articles';
$table_des_tables['auteurs']='auteurs';
$table_des_tables['breves']='breves';
$table_des_tables['forums']='forum';
$table_des_tables['signatures']='signatures';
$table_des_tables['documents']='documents';
$table_des_tables['types_documents']='types_documents';
$table_des_tables['mots']='mots';
$table_des_tables['groupes_mots']='groupes_mots';
$table_des_tables['rubriques']='rubriques';
$table_des_tables['syndication']='syndic';
$table_des_tables['syndic_articles']='syndic_articles';
$table_des_tables['hierarchie']='rubriques';

$exceptions_des_tables['breves']['id_secteur']='id_rubrique';
$exceptions_des_tables['breves']['date']='date_heure';
$exceptions_des_tables['breves']['nom_site']='lien_titre';
$exceptions_des_tables['breves']['url_site']='lien_url';

$exceptions_des_tables['forums']['date']='date_heure';
$exceptions_des_tables['forums']['nom']='auteur';
$exceptions_des_tables['forums']['email']='email_auteur';

$exceptions_des_tables['signatures']['date']='date_time';
$exceptions_des_tables['signatures']['nom']='nom_email';
$exceptions_des_tables['signatures']['email']='ad_email';

$exceptions_des_tables['documents']['type_document']=array('spip_types_documents'
, 'titre');
$exceptions_des_tables['documents']['extension_document']=array('spip_types_documents', 'extension');
$exceptions_des_tables['documents']['mime_type']=array('spip_types_documents'
, 'mime_type');

# ne sert plus ? verifier balise_URL_ARTICLE
$exceptions_des_tables['syndic_articles']['url_article']='url';
# ne sert plus ? verifier balise_LESAUTEURS
$exceptions_des_tables['syndic_articles']['lesauteurs']='lesauteurs'; 
$exceptions_des_tables['syndic_articles']['url_site']=array('spip_syndic',
'url_site');
$exceptions_des_tables['syndic_articles']['nom_site']=array('spip_syndic',
'nom_site');

$table_date['articles']='date';
$table_date['auteurs']='date';
$table_date['breves']='date_heure';
$table_date['forums']='date_heure';
$table_date['signatures']='date_time';
$table_date['documents']='date';
$table_date['types_documents']='date';
$table_date['groupes_mots']='date';
$table_date['mots']='date';
$table_date['rubriques']='date';
$table_date['syndication']='date';
$table_date['syndic_articles']='date';

//
// tableau des tables de jointures
// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)

global $tables_jointures;

$tables_jointures['spip_articles'][]= 'mots_articles';
$tables_jointures['spip_articles'][]= 'auteurs_articles';
$tables_jointures['spip_articles'][]= 'documents_articles';
$tables_jointures['spip_articles'][]= 'mots';

$tables_jointures['spip_auteurs'][]= 'auteurs_articles';
$tables_jointures['spip_auteurs'][]= 'mots';

$tables_jointures['spip_breves'][]= 'mots_breves';
$tables_jointures['spip_breves'][]= 'documents_breves';
$tables_jointures['spip_breves'][]= 'mots';

$tables_jointures['spip_documents'][]= 'documents_articles';
$tables_jointures['spip_documents'][]= 'documents_rubriques';
$tables_jointures['spip_documents'][]= 'documents_breves';
$tables_jointures['spip_documents'][]= 'documents_syndic';
$tables_jointures['spip_documents'][]= 'mots_documents';
$tables_jointures['spip_documents'][]= 'types_documents';
$tables_jointures['spip_documents'][]= 'mots';

$tables_jointures['spip_forum'][]= 'mots_forum';
$tables_jointures['spip_forum'][]= 'mots';

$tables_jointures['spip_rubriques'][]= 'mots_rubriques';
$tables_jointures['spip_rubriques'][]= 'documents_rubriques';
$tables_jointures['spip_rubriques'][]= 'mots';

$tables_jointures['spip_syndic'][]= 'mots_syndic';
$tables_jointures['spip_syndic'][]= 'documents_syndic';
$tables_jointures['spip_syndic'][]= 'mots';

$tables_jointures['spip_syndic_articles'][]= 'syndic';
$tables_jointures['spip_syndic_articles'][]= 'documents_syndic';
$tables_jointures['spip_syndic_articles'][]= 'mots';

$tables_jointures['spip_mots'][]= 'mots_articles';
$tables_jointures['spip_mots'][]= 'mots_breves';
$tables_jointures['spip_mots'][]= 'mots_forum';
$tables_jointures['spip_mots'][]= 'mots_rubriques';
$tables_jointures['spip_mots'][]= 'mots_syndic';
$tables_jointures['spip_mots'][]= 'mots_documents';

$tables_jointures['spip_groupes_mots'][]= 'mots';

global  $exceptions_des_jointures;
$exceptions_des_jointures['titre_mot'] = 'titre';
$exceptions_des_jointures['type_mot'] = 'type';

global  $table_des_traitements;
$table_des_traitements['BIO'][]= 'traiter_raccourcis(%s)';
$table_des_traitements['CHAPO'][]= 'traiter_raccourcis(nettoyer_chapo(%s))';
$table_des_traitements['DATE'][]= 'vider_date(%s)';
$table_des_traitements['DATE_MODIF'][]= 'vider_date(%s)';
$table_des_traitements['DATE_NOUVEAUTES'][]= 'vider_date(%s)';
$table_des_traitements['DATE_REDAC'][]= 'vider_date(%s)';
$table_des_traitements['DESCRIPTIF'][]= 'traiter_raccourcis(%s)';
$table_des_traitements['LIEN_TITRE'][]= 'typo(%s)';
$table_des_traitements['LIEN_URL'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['MESSAGE'][]= 'traiter_raccourcis(%s)';
$table_des_traitements['NOM_SITE_SPIP'][]= 'typo(%s)';
$table_des_traitements['NOM_SITE'][]= 'typo(%s)';
$table_des_traitements['NOM'][]= 'typo(%s)';
$table_des_traitements['PARAMETRES_FORUM'][]= 'htmlspecialchars(lang_parametres_forum(%s))';
$table_des_traitements['PS'][]= 'traiter_raccourcis(%s)';
$table_des_traitements['SOURCE'][]= 'typo(%s)';
$table_des_traitements['SOUSTITRE'][]= 'typo(%s)';
$table_des_traitements['SURTITRE'][]= 'typo(%s)';
$table_des_traitements['TAGS'][]= '%s';
$table_des_traitements['TEXTE'][]= 'traiter_raccourcis(%s)';
$table_des_traitements['TITRE'][]= 'typo(%s)';
$table_des_traitements['TYPE'][]= 'typo(%s)';
$table_des_traitements['URL_ARTICLE'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_BREVE'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_DOCUMENT'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_FORUM'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_MOT'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_RUBRIQUE'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_SITE_SPIP'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_SITE'][]= 'htmlspecialchars(calculer_url(%s))';
$table_des_traitements['URL_SOURCE'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['URL_SYNDIC'][]= 'htmlspecialchars(vider_url(%s))';
$table_des_traitements['ENV'][]= 'entites_html(%s)';


// Securite supplementaire pour certaines tables

// Articles syndiques : remplacer les filtres par safehtml()
foreach(array('TITRE','DESCRIPTIF','SOURCE') as $balise)
	if (!isset($table_des_traitements[$balise]['syndic_articles']))
		$table_des_traitements[$balise]['syndic_articles'] = 'safehtml(%s)';

// Forums & petitions : ajouter safehtml aux filtres existants
foreach(array('TITRE','TEXTE','AUTEUR','EMAIL_AUTEUR','NOM_SITE') as $balise)
	if (!isset($table_des_traitements[$balise]['forums']))
		$table_des_traitements[$balise]['forums'] =
			'safehtml('.$table_des_traitements[$balise][0].')';
foreach(array('NOM','NOM_SITE','MESSAGE','AD_EMAIL') as $balise)
	if (!isset($table_des_traitements[$balise]['signatures']))
		$table_des_traitements[$balise]['signatures'] =
			'safehtml('.$table_des_traitements[$balise][0].')';

?>
