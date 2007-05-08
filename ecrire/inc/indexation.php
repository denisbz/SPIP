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
include_spip('inc/meta');
include_spip('base/create');
include_spip('base/abstract_sql');
include_spip('public/interfaces');

// Quels formats sait-on extraire ?
$GLOBALS['extracteur'] = array (
	'txt'   => 'extracteur_txt',
	'pas'   => 'extracteur_txt',
	'c'     => 'extracteur_txt',
	'css'   => 'extracteur_txt',
	'html'  => 'extracteur_html'
);

// tables que l'on ne doit pas indexer
global $INDEX_tables_interdites;
$INDEX_tables_interdites=array();

// Indexation des elements de l'objet principal
// 'champ'=>poids, ou 'champ'=>array(poids,min_long)
$reindex = false;
global $INDEX_elements_objet;
$INDEX_elements_objet = FALSE;
if (isset($GLOBALS['meta']['INDEX_elements_objet']))
	$INDEX_elements_objet = unserialize($GLOBALS['meta']['INDEX_elements_objet']);
if (!$INDEX_elements_objet) {
	$INDEX_elements_objet['spip_articles'] = array('titre'=>8,'soustitre'=>5,'surtitre'=>5,'descriptif'=>4,'chapo'=>3,'texte'=>1,'ps'=>1,'nom_site'=>1,'extra|unserialize_join'=>1);
	$INDEX_elements_objet['spip_breves'] = array('titre'=>8,'texte'=>2,'extra|unserialize_join'=>1);
	$INDEX_elements_objet['spip_rubriques'] = array('titre'=>8,'descriptif'=>5,'texte'=>1,'extra|unserialize_join'=>1);
	$INDEX_elements_objet['spip_auteurs'] = array('nom'=>array(5,2),'bio'=>1,'extra|unserialize_join'=>1);
	$INDEX_elements_objet['spip_mots'] = array('titre'=>8,'descriptif'=>5,'texte'=>1,'extra|unserialize_join'=>1);
	$INDEX_elements_objet['spip_signatures'] = array('nom_email'=>array(2,2),'ad_email'=>2,'nom_site'=>2,'url_site'=>1,'message'=>1);
	$INDEX_elements_objet['spip_syndic'] = array('nom_site'=>50,'descriptif'=>30,'url_site|contenu_page_accueil'=>1);
	$INDEX_elements_objet['spip_syndic_articles'] = array('titre'=>5);
	$INDEX_elements_objet['spip_forum'] = array('titre'=>3,'texte'=>2,'auteur'=>array(2,2),'email_auteur'=>2,'nom_site'=>2,'url_site'=>1);
	$INDEX_elements_objet['spip_documents'] = array('titre'=>20,'descriptif'=>10,'fichier|nettoie_nom_fichier'=>1);
	ecrire_meta('INDEX_elements_objet',serialize($INDEX_elements_objet));
	ecrire_metas();
	$reindex = true;
}

// Indexation des objets associes
// 'objet'=>poids
global $INDEX_objet_associes;
$INDEX_objet_associes = FALSE;
if (isset($GLOBALS['meta']['INDEX_objet_associes']))
	$INDEX_objet_associes = unserialize($GLOBALS['meta']['INDEX_objet_associes']);
if (!$INDEX_objet_associes) {
	$INDEX_objet_associes['spip_articles'] = array('spip_documents'=>1,'spip_auteurs'=>10,'spip_mots'=>3);
	$INDEX_objet_associes['spip_breves'] = array('spip_documents'=>1,'spip_mots'=>3);
	$INDEX_objet_associes['spip_rubriques'] = array('spip_documents'=>1,'spip_mots'=>3);
	$INDEX_objet_associes['spip_documents'] = array('spip_mots'=>3);
	ecrire_meta('INDEX_objet_associes',serialize($INDEX_objet_associes));
	ecrire_metas();
	$reindex = true;
}

// Indexation des elements des objets associes
// 'champ'=>poids, ou 'champ'=>array(poids,min_long)
global $INDEX_elements_associes;
$INDEX_elements_associes = FALSE;
if (isset($GLOBALS['meta']['INDEX_elements_associes']))
	$INDEX_elements_associes = unserialize($GLOBALS['meta']['INDEX_elements_associes']);
if (!$INDEX_elements_associes){
	$INDEX_elements_associes['spip_documents'] = array('titre'=>2,'descriptif'=>1);
	$INDEX_elements_associes['spip_auteurs'] = array('nom'=>1);
	$INDEX_elements_associes['spip_mots'] = array('titre'=>4,'descriptif'=>1);
	ecrire_meta('INDEX_elements_associes',serialize($INDEX_elements_associes));
	ecrire_metas();
	$reindex = true;
}
// Criteres d'indexation
global $INDEX_critere_indexation;
$INDEX_critere_indexation = FALSE;
if (isset($GLOBALS['meta']['INDEX_critere_indexation']))
	$INDEX_critere_indexation = unserialize($GLOBALS['meta']['INDEX_critere_indexation']);
if (!$INDEX_critere_indexation){
	$INDEX_critere_indexation['spip_articles']="statut='publie'";
	$INDEX_critere_indexation['spip_breves']="statut='publie'";
	$INDEX_critere_indexation['spip_rubriques']="statut='publie'";
	$INDEX_critere_indexation['spip_syndic']="statut='publie'";
	$INDEX_critere_indexation['spip_forum']="statut='publie'";
	$INDEX_critere_indexation['spip_signatures']="statut='publie'";
	ecrire_meta('INDEX_critere_indexation',serialize($INDEX_critere_indexation));
	ecrire_metas();
	$reindex = true;
}

// Criteres de des-indexation (optimisation dans base/optimiser)
global $INDEX_critere_optimisation;
$INDEX_critere_optimisation = FALSE;
if (isset($GLOBALS['meta']['INDEX_critere_optimisation']))
	$INDEX_critere_optimisation = unserialize($GLOBALS['meta']['INDEX_critere_optimisation']);
if (!$INDEX_critere_optimisation) {
	$INDEX_critere_optimisation['spip_articles']="statut<>'publie'";
	$INDEX_critere_optimisation['spip_breves']="statut<>'publie'";
	$INDEX_critere_optimisation['spip_rubriques']="statut<>'publie'";
	$INDEX_critere_optimisation['spip_syndic']="statut<>'publie'";
	$INDEX_critere_optimisation['spip_forum']="statut<>'publie'";
	$INDEX_critere_optimisation['spip_signatures']="statut<>'publie'";
	ecrire_meta('INDEX_critere_optimisation',serialize($INDEX_critere_optimisation));
	ecrire_metas();
	$reindex = true;
}

// Nombre d'elements maxi a indexer a chaque iteration
global $INDEX_iteration_nb_maxi;
$INDEX_iteration_nb_maxi = FALSE;
if (isset($GLOBALS['meta']['INDEX_iteration_nb_maxi']))
	$INDEX_iteration_nb_maxi = unserialize($GLOBALS['meta']['INDEX_iteration_nb_maxi']);
if (!$INDEX_iteration_nb_maxi) {
	$INDEX_iteration_nb_maxi['spip_documents']=10;
	$INDEX_iteration_nb_maxi['spip_syndic']=1;
	ecrire_meta('INDEX_iteration_nb_maxi',serialize($INDEX_iteration_nb_maxi));
	ecrire_metas();
	$reindex = true;
}
if ($reindex) creer_liste_indexation();


// Filtres d'indexation
// http://doc.spip.org/@unserialize_join
function unserialize_join($extra){
	return @join(' ', unserialize($extra));
}
// http://doc.spip.org/@contenu_page_accueil
function contenu_page_accueil($url){
	$texte = '';
	if ($GLOBALS['meta']["visiter_sites"] == "oui") {
		include_spip('inc/distant');
		spip_log ("indexation contenu syndic $url");
		$texte = supprimer_tags(recuperer_page($url, true, false, 50000));
	}
	return $texte;
}
// http://doc.spip.org/@nettoie_nom_fichier
function nettoie_nom_fichier($fichier){
	return preg_replace(',^(IMG/|.*://),', '', $fichier);
}

// Renvoie la liste des "mots" d'un texte (ou d'une requete adressee au moteur)
// http://doc.spip.org/@mots_indexation
function mots_indexation($texte, $min_long = 3) {
	include_spip('inc/charsets');
	include_spip('inc/texte');

	// Recuperer les parametres des modeles
	$texte = traiter_modeles($texte, true);
	
	// Supprimer les tags HTML
	$texte = preg_replace(',<.*>,Ums',' ',$texte);

	// Translitterer (supprimer les accents, recuperer les &eacute; etc)
	// la translitteration complexe (vietnamien, allemand) duplique
	// le texte, en mettant bout a bout une translitteration simple +
	// une translitteration riche
	if ($GLOBALS['translitteration_complexe'])
		$texte_c = ' '.translitteration_complexe ($texte, 'AUTO', true);
	else
		$texte_c = '';
	$texte = translitteration($texte);
	if($texte!=trim($texte_c)) $texte .= $texte_c;
	# NB. tous les caracteres non translitteres sont retournes en utf-8

	// OPTIONNEL //  Gestion du tiret '-' :
	// "vice-president" => "vice"+"president"+"vicepresident"
#	$texte = preg_replace(',(\w+)-(\w+),', '\1 \2 \1\2', $texte);

	// Supprimer les caracteres de ponctuation, les guillemets...
	$e = "],:;*\"!\r\n\t\\/)}{[|@<>$%'`?\~.^(";
	$texte = strtr($texte, $e, preg_replace('/./', ' ', $e));

	//delete  +\- not at the beginning of a word
	$texte = preg_replace(",(?:\S)[\-+],"," ",$texte);
	// Cas particulier : sigles d'au moins deux lettres
	$texte = preg_replace("/ ([A-Z][0-9A-Z]{1,".($min_long - 1)."}) /",
		' \\1___ ', $texte.' ');

	// Tout passer en bas de casse
	$texte = strtolower($texte);

	// Retourner sous forme de table
	return pipeline('mots_indexation',array('args'=>array('min_long'=>$min_long),'data'=>preg_split("/ +/", trim($texte))));
}

// http://doc.spip.org/@indexer_chaine
function indexer_chaine($texte, $val = 1, $min_long = 3) {
	global $index, $mots;
	global $translitteration_complexe;

	// Point d'entree pour traiter le texte avant indexation 
	$texte = pipeline('pre_indexation', $texte);

	$table = mots_indexation($texte, $min_long);

	foreach ($table as $mot) {
		if (strlen($mot) > $min_long) {
			$h = substr(md5($mot), 0, 16);
			if (!isset($index[$h]))
				$index[$h] = 0;
			$index[$h] += $val/(1+$translitteration_complexe);
			$mots .= ",(0x$h,'$mot')";
		}
	}
}

// id_table = 1...9 pour les tables spip, et ensuite 100, 101, 102...
// pour les tables additionnelles, selon l'ordre dans lequel
// elles sont reperees. On stocke le tableau de conversion table->id_table
// dans spip_meta
// http://doc.spip.org/@update_index_tables
function update_index_tables(){
	global $tables_principales;
	global $INDEX_tables_interdites;
	
	$old_liste_tables = liste_index_tables();
	$rev_old_liste_tables = array_flip($old_liste_tables);

	$liste_tables = array();
	// les tables SPIP conventionnelles en priorite
	$liste_tables[1]='spip_articles';
	$liste_tables[2]='spip_auteurs';
	$liste_tables[3]='spip_breves';
	$liste_tables[4]='spip_documents';
	$liste_tables[5]='spip_forum';
	$liste_tables[6]='spip_mots';
	$liste_tables[7]='spip_rubriques';
	$liste_tables[8]='spip_signatures';
	$liste_tables[9]='spip_syndic';

	// detection des nouvelles tables
	$id_autres = 100;
	foreach(array_keys($tables_principales) as $new_table){
		if (	(!in_array($new_table,$INDEX_tables_interdites))
				&&(!in_array($new_table,$liste_tables))
				&&($id_autres<254) ){
			// on utilise abstract_showtable car cela permet d'activer l'indexation
			// en ajoutant simplement le champ idx, sans toucher au core
			$desc = spip_abstract_showtable($new_table, '', true);
			if (isset($desc['field']['idx'])){
			  // la table a un champ idx pour gerer l'indexation
			  if ( 	(isset($rev_old_liste_tables[$new_table]))
						&&(!isset($liste_tables[$rev_old_liste_tables[$new_table]])) )
			  		$liste_tables[$rev_old_liste_tables[$new_table]] = $new_table; // id conserve
				else{
					while (isset($liste_tables[$id_autres])&&($id_autres<254)) $id_autres++;
					$liste_tables[$id_autres] = $new_table;
				}
			}
		}
	}

	// Cas de l'ajout d'une nouvelle table a indexer :
	// mise en coherence de la table d'indexation avec les nouveaux id
	$rev_old_liste_tables = array_flip($old_liste_tables);
	foreach($liste_tables as $new_id=>$new_table){
		if (isset($rev_old_liste_tables[$new_table])){
		  if (	($old_id = ($rev_old_liste_tables[$new_table]))
					&&($old_id!=$new_id)){
				$temp_id = 254;
				// liberer le nouvel id
				spip_query("UPDATE spip_index SET id_table='$temp_id' WHERE id_table='$new_id'");
				// deplacer les indexation de l'ancien id sous le nouvel id
				spip_query("UPDATE spip_index SET id_table='$new_id' WHERE id_table='$old_id'");
				// remettre les indexation deplacees sous l'id qui vient d'etre libere
				spip_query("UPDATE spip_index SET id_table='$old_id' WHERE id_table='$temp_id'");
	
				$old_liste_tables[$old_id] = $old_liste_tables[$new_id]; 
				unset($old_liste_tables[$new_id]);
				$rev_old_liste_tables = array_flip($old_liste_tables);
			}
		}
	} 

	ecrire_meta('index_table',serialize($liste_tables));
	ecrire_metas();
}

// http://doc.spip.org/@liste_index_tables
function liste_index_tables() {
	$liste_tables = array();
	if (!isset($GLOBALS['meta']['index_table'])) {
		lire_metas();
	}
	if (isset($GLOBALS['meta']['index_table']))
		$liste_tables = unserialize($GLOBALS['meta']['index_table']);
	return $liste_tables;
}

// http://doc.spip.org/@id_index_table
function id_index_table($table){
	/*global $table_des_tables;
	$t = $table_des_tables[$table];
	// pour les tables non Spip
	if (!$t) $t = $table; else $t = "spip_$t";*/
	$t = $table;
	$id_table = 0;
	$l = liste_index_tables();
	$l = @array_flip($l);
	if (isset($l[$t]))
		$id_table=$l[$t];
	return $id_table;
}

// http://doc.spip.org/@primary_index_table
function primary_index_table($table){
	global $tables_principales;
	/*global $table_des_tables;
	$t = $table_des_tables[$table];
	// pour les tables non Spip
	if (!$t) $t = $table; else $t = "spip_$t";*/
	$t = $table;
	$p = $tables_principales["$t"]['key']["PRIMARY KEY"];
	if (!$p){
		$p = preg_replace("{^spip_}","",$table);
		$p = "id_" . $p;
		if (substr($p,-1,1)=='s')
		  $p = substr($p,0,strlen($p)-1);
	}
	return $p;
}

// http://doc.spip.org/@deja_indexe
function deja_indexe($table, $id_objet) {
	$table_index = 'spip_index';
	$id_table = id_index_table($table);
	$n = @spip_num_rows(@spip_query("SELECT id_objet FROM $table_index WHERE id_objet=$id_objet AND id_table=$id_table LIMIT 1"));
	return ($n > 0);
}

// Extracteur des documents 'txt'
// http://doc.spip.org/@extracteur_txt
function extracteur_txt($fichier, &$charset) {
	lire_fichier($fichier, $contenu);

	// Reconnaitre le BOM utf-8 (0xEFBBBF)
	include_spip('inc/charsets');
	if (bom_utf8($contenu))
		$charset = 'utf-8';

	return $contenu;
}

// Extracteur des documents 'html'
// http://doc.spip.org/@extracteur_html
function extracteur_html($fichier, &$charset) {
	lire_fichier($fichier, $contenu);

	// Importer dans le charset local
	include_spip('inc/charsets');
	$contenu = transcoder_page($contenu);
	$charset = $GLOBALS['meta']['charset'];

	return $contenu;
}



// Indexer le contenu d'un document
// http://doc.spip.org/@indexer_contenu_document
function indexer_contenu_document ($row, $min_long=3) {
	global $extracteur;

	if ($row['mode'] == 'vignette') return;
	$extension = spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE id_type = ".$row['id_type']));
	$extension = $extension['extension'];

	// Voir si on sait lire le contenu (eventuellement en chargeant le
	// fichier extract/pdf.php dans find_in_path() )
	include_spip('extract/'.$extension);
	if (function_exists($lire = $extracteur[$extension])) {
		// Voir si on a deja une copie du doc distant
		// Note: si copie_locale() charge le doc, elle demande une reindexation
		include_spip('inc/distant');
		if (!$fichier = copie_locale($row['fichier'], 'test')) {
			spip_log("pas de copie locale de '$fichier'");
			return;
		}
		// par defaut, on pense que l'extracteur va retourner ce charset
		$charset = 'iso-8859-1'; 
		// lire le contenu
		$contenu = $lire(_DIR_RACINE.$fichier, $charset);
		if (!$contenu) {
			spip_log("Echec de l'extraction de '$fichier'");
		} else {
			// Ne retenir que les 50 premiers ko
			$contenu = substr($contenu, 0, 50000);
			// importer le charset
			$contenu = importer_charset($contenu, $charset);
			// Indexer le texte
			indexer_chaine($contenu, 1, $min_long);
		}
	} else {
		spip_log("pas d'extracteur '$extension' fonctionnel");
	}
}



// http://doc.spip.org/@indexer_les_champs
function indexer_les_champs(&$row,&$index_desc,$ponderation = 1, $min_long=3){
	reset($index_desc);
	while (list($quoi,$poids) = each($index_desc)){
		$pipe=array();
		if (strpos($quoi,"|")){
			$pipe = explode("|",$quoi);
			$quoi = array_shift($pipe);
		}
		if (isset($row[$quoi])){
			$texte = $row[$quoi];
			if (count($pipe)){
				foreach ($pipe as $func){
					$func = trim($func);
					if (!function_exists($func)) {
						spip_log("Erreur - $func n'est pas definie (indexation)");
					}
					else
						// appliquer le filtre
						$texte = $func($texte);
				}
			}
			//spip_log(":$quoi:$poids:$texte");
			if (is_array($poids))
				indexer_chaine($texte,array_shift($poids) * $ponderation,array_shift($poids));
			else
				indexer_chaine($texte,$poids * $ponderation, $min_long);
		}
	}
}

// Indexer les documents, auteurs, mots-cles associes a l'objet
// http://doc.spip.org/@indexer_elements_associes
function indexer_elements_associes($table, $id_objet, $table_associe, $valeur, $min_long=3) {
	global $INDEX_elements_associes, $tables_jointures, $tables_auxiliaires, $tables_principales;

	if (isset($INDEX_elements_associes[$table_associe])){
		$table_abreg = preg_replace("{^spip_}","",$table);
		$col_id = primary_index_table($table);
		$col_id_as = primary_index_table($table_associe);
		if (is_array($rel = $tables_jointures[$table])) {
			foreach($rel as $joint) {
				if (@in_array($col_id_as, @array_keys($tables_auxiliaires['spip_' . $joint]['field'])))
					{$table_rel = $joint; break;}
				if (@in_array($col_id_as, @array_keys($tables_principales['spip_' . $joint]['field'])))
					{$table_rel = $joint; break;}
			}
			if (!$table_rel){
				spip_log("Indexation de $table echouee : element associe $table_associe, jointure sur $col_id_as introuvable");
				return;
			}

			$select="assoc.$col_id_as";
			foreach(array_keys($INDEX_elements_associes[$table_associe]) as $quoi)
				$select.=',assoc.' . $quoi;
			$r = spip_query("SELECT $select FROM $table_associe AS assoc,	spip_$table_rel AS lien	WHERE lien.$col_id=$id_objet AND assoc.$col_id_as=lien.$col_id_as");

			while ($row = spip_fetch_array($r)) {
				indexer_les_champs($row,$INDEX_elements_associes[$table_associe],$valeur);
			}
		}
 	}
}

// http://doc.spip.org/@indexer_objet
function indexer_objet($table, $id_objet, $forcer_reset = true) {
	global $index, $mots, $translitteration_complexe;
	global $INDEX_elements_objet;
	global $INDEX_objet_associes;

	$min_long = isset($GLOBALS['INDEX_mots_min_long'])?intval($GLOBALS['INDEX_mots_min_long']):3;

	$table_index = 'spip_index';
	$col_id = primary_index_table($table);
	$id_table = id_index_table($table);

	if (!$id_objet) return;
	if (!$forcer_reset AND deja_indexe($table, $id_objet)) {
		spip_log ("$table $id_objet deja indexe");
		spip_query("UPDATE $table SET idx='oui' WHERE $col_id=$id_objet");
		return;
	}
	// marquer "en cours d'indexation"
	spip_query("UPDATE $table SET idx='idx' WHERE $col_id=$id_objet");

	include_spip('inc/texte');

	spip_log("indexation $table $id_objet");
	$index = '';
	$mots = '';

	$result = spip_query("SELECT * FROM $table WHERE $col_id=$id_objet");

	$row = spip_fetch_array($result);

	if (!$row) return;

	// translitteration complexe ?
	if (!$lang = $row['lang']) $lang = $GLOBALS['meta']['langue_site'];
	if ($lang == 'de' OR $lang=='vi') {
		$translitteration_complexe = 1;
		spip_log ('-> translitteration complexe');
	} else $translitteration_complexe = 0;

	if (isset($INDEX_elements_objet[$table])){

		// Cas tres particulier du forum :
		// on indexe le thread comme un tout
		if ($table=='spip_forum') {

			// 1. chercher la racine du thread
			$id_forum = $id_objet;
			while ($row['id_parent']) {
				$id_forum = $row['id_parent'];
				$s = spip_query("SELECT id_forum,id_parent FROM spip_forum WHERE id_forum=$id_forum");
				$row = spip_fetch_array($s);
			}

			// 2. chercher tous les forums du thread
			// (attention le forum de depart $id_objet n'appartient pas forcement
			// a son propre thread car il peut etre le fils d'un forum non 'publie')
			$thread="$id_forum";
			$fini = false;
			while (!$fini) {
				$s = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($thread) AND id_forum NOT IN ($thread) AND statut='publie'");
				if (spip_num_rows($s) == 0) $fini = true;
				while ($t = spip_fetch_array($s))
					$thread.=','.$t['id_forum'];
			}

			// 3. marquer le thread comme "en cours d'indexation"
			spip_log("-> indexation thread $thread");
			spip_query("UPDATE spip_forum SET idx='idx' WHERE id_forum IN ($thread,$id_objet) AND idx!='non'");

			// 4. Indexer le thread
			$s = spip_query("SELECT * FROM spip_forum WHERE id_forum IN ($thread) AND idx!='non'");
			while ($row = spip_fetch_array($s)) {
		    indexer_les_champs($row,$INDEX_elements_objet[$table],1,$min_long);
		    if (isset($INDEX_objet_associes[$table]))
		      foreach($INDEX_objet_associes[$table] as $quoi=>$poids)
						indexer_elements_associes($table, $id_objet, $quoi, $poids, $min_long);
				break;
			}

			// 5. marquer le thread comme "indexe"
			spip_query("UPDATE spip_forum SET idx='oui' WHERE id_forum IN ($thread,$id_objet) AND idx!='non'");

			// 6. Changer l'id_objet en id_forum de la racine du thread
			$id_objet = $id_forum;
		} else {
			indexer_les_champs($row,$INDEX_elements_objet[$table],1,$min_long);
			if (isset($INDEX_objet_associes[$table]))
				foreach($INDEX_objet_associes[$table] as $quoi=>$poids)
					indexer_elements_associes($table, $id_objet, $quoi, $poids, $min_long);

			if ($table=='spip_syndic'){
				// 2. Indexer les articles syndiques
				if (($row['syndication'] = "oui")&&(isset($INDEX_elements_objet['syndic_articles']))) {
					$result_syndic = spip_query("SELECT titre FROM spip_syndic_articles WHERE id_syndic=$id_objet AND statut='publie' ORDER BY date DESC LIMIT 100");

					while ($row_syndic = spip_fetch_array($result_syndic)) {
		    		indexer_les_champs($row,$INDEX_elements_objet['syndic_articles'],1,$min_long);
					}
				}
			}
			if ($table=='spip_documents'){
				// 2. Indexer le contenu si on sait le lire
				indexer_contenu_document($row,$min_long);
			}
	 	}
	} else
		spip_log("je ne sais pas indexer '$table'");

	$result = spip_query("DELETE FROM $table_index WHERE id_objet=$id_objet AND id_table=$id_table");

	if ($index) {
		if ($mots) {
	// supprimer la virgule du debut
			spip_query("INSERT IGNORE INTO spip_index_dico (hash, dico) VALUES ".substr($mots,1));

		}
		reset($index);
		while (list($hash, $points) = each($index)) {
		  spip_query("INSERT INTO $table_index (hash, points, id_objet, id_table) VALUES (0x$hash,".ceil($points).",$id_objet,$id_table)");
		}
	}

	// marquer "indexe"
	spip_query("UPDATE $table SET idx='oui' WHERE $col_id=$id_objet");
}

/*
	Valeurs du champ 'idx' de la table spip_objet(s)
	'' ne sait pas
	'1' � (re)indexer
	'oui' deja indexe
	'idx' en cours
	'non' ne jamais indexer
*/

// API pour l'espace prive pour marquer un objet d'une table a reindexer
// http://doc.spip.org/@marquer_indexer
function marquer_indexer ($table, $id_objet) {
	if (!isset($GLOBALS['INDEX_elements_objet'][$table]))
		return;
	spip_log ("demande indexation $table id=$id_objet");
	$id = primary_index_table($table);
	spip_query("UPDATE $table SET idx='1' WHERE $id=$id_objet AND idx!='non'");
}

// A garder pour compatibilite bouton memo...
// http://doc.spip.org/@indexer_article
function indexer_article($id_article) {
	marquer_indexer('spip_articles', $id_article);
}

// n'indexer que les objets publies
// http://doc.spip.org/@critere_indexation
function critere_indexation($table) {
	global $INDEX_critere_indexation;
	if (isset($INDEX_critere_indexation[$table]))
	  return $INDEX_critere_indexation[$table];
	else
	  return '1=1'; // indexation par defaut
}

// ne desindexer que les objets non-publies
// http://doc.spip.org/@critere_optimisation
function critere_optimisation($table) {
	global $INDEX_critere_optimisation;
	if (isset($INDEX_critere_optimisation[$table]))
	  return $INDEX_critere_optimisation[$table];
	else
	  return '1=0';  // pas de indexation par defaut
}


// http://doc.spip.org/@effectuer_une_indexation
function effectuer_une_indexation($nombre_indexations = 1) {
	global $INDEX_iteration_nb_maxi;
	$vu = array();

	// chercher un objet a indexer dans chacune des tables d'objets
	foreach (liste_index_tables() as $table) {

		$table_primary = primary_index_table($table);
		$critere = critere_indexation($table);

		$limit = $nombre_indexations;
		if (isset($INDEX_iteration_nb_maxi[$table]))
			$limit = min($limit,$INDEX_iteration_nb_maxi[$table]);

		// indexer en priorite les '1' (a reindexer), ensuite les ''
		// (statut d'indexation inconnu), enfin les 'idx' (ceux dont
		// l'indexation a precedemment echoue, p. ex. a cause d'un timeout)
		foreach (array('1', '', 'idx') as $mode) {
			$s = spip_query("SELECT $table_primary AS id FROM $table WHERE idx='$mode' AND $critere LIMIT $limit");
			while ($t = spip_fetch_array($s)) {
				$vu[$table] .= $t['id'].", ";
				indexer_objet($table, $t['id'], $mode);
			}
			if ($vu[$table]) break;
		}
	}
	return $vu;
}

// http://doc.spip.org/@executer_une_indexation_syndic
function executer_une_indexation_syndic() {
	$id_syndic = 0;
	$row = spip_fetch_array(spip_query("SELECT id_syndic FROM spip_syndic WHERE statut='publie' AND date_index < DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date_index LIMIT 1"));
	if ($row) {
		$id_syndic = $row['id_syndic'];
		spip_query("UPDATE spip_syndic SET date_index=NOW() WHERE id_syndic=$id_syndic");
		marquer_indexer('spip_syndic', $id_syndic);
	}
	return $id_syndic;
}

// http://doc.spip.org/@creer_liste_indexation
function creer_liste_indexation() {
	foreach (liste_index_tables() as $table)
		spip_query("UPDATE $table SET idx='1' WHERE idx!='non'");
}

// http://doc.spip.org/@purger_index
function purger_index() {
		spip_query("DELETE FROM spip_index");
		spip_query("DELETE FROM spip_index_dico");
}

// cree la requete pour une recherche en txt integral
// http://doc.spip.org/@requete_txt_integral
function requete_txt_integral($table, $hash_recherche) {
	$index_table = "spip_index";
	$id_objet = primary_index_table($table);
	$id_table = id_index_table($table);
	return array(
		     'SELECT' => "objet.*, SUM(rec.points) AS points",
		     'FROM' => "$table AS objet, $index_table AS rec",
		     'WHERE' => "objet.$id_objet = rec.id_objet
AND rec.hash IN ($hash_recherche)
AND rec.id_table = $id_table",
		     'GROUP BY' => "objet.$id_objet",
		     'ORDER BY' => "points DESC",
		     'LIMIT' => "10");
}

// rechercher un mot dans le dico
// retourne deux methodes : lache puis strict
// http://doc.spip.org/@requete_dico
function requete_dico($val, $min_long = 3) {
	
	preg_match(",^([+\-]?)(.*),",$val,$mod);
	switch($mod[1]) {
		case '':
			$mode = "OR";
			break;
		case '+':
			$mode = "AND";
			break;
		case '-':
			$mode = "NOT";
			break;
	}
	//set logical operator between the various where parts
	$val = $mod[2];
	// cas normal
	$res = array();
	if (strlen($val) > $min_long)
	  $res = array("dico LIKE "._q($val. "%"), "dico = " . _q($val),$mode);
	else
		$res = array("dico = "._q($val."___"), "dico = "._q($val."___"),$mode);
	return pipeline('requete_dico',array('args'=>array('val'=>$val,'min_long'=>$min_long),'data'=>$res));
}


// decode la chaine recherchee et la traduit en hash
// http://doc.spip.org/@requete_hash
function requete_hash ($rech) {
	$min_long = isset($GLOBALS['INDEX_mots_min_long'])?intval($GLOBALS['INDEX_mots_min_long']):3;

	// recupere les mots de la recherche
	$GLOBALS['translitteration_complexe'] = true;
	$s = mots_indexation($rech,$min_long);
	unset($dico);
	unset($h);
	
	// cherche les mots dans le dico
	while (list(, $val) = each($s)) {
		list($rq, $rq_strict,$mode) = requete_dico ($val,$min_long);
		if ($rq)
			$dico[$mode][$val] = $rq;
		if ($rq_strict)
			$dico_strict[$mode][$val] = $rq_strict;
	}
	
	// Attention en MySQL 3.x il faut passer par HEX(hash)
	// alors qu'en MySQL 4.1 c'est interdit !
	$vers = spip_query("SELECT VERSION() AS v");
	$vers = spip_fetch_array($vers);
	if (($vers['v']{0} == 4 AND $vers['v']{2} >= 1)
		OR $vers['v']{0} > 4) {
		$hex_fmt = '';
		$select_hash = 'hash AS h';
	} else {
		$hex_fmt = '0x';
		$select_hash = 'HEX(hash) AS h';
	}

	// compose la recherche dans l'index
	$cond = "";
	if ($dico_strict["OR"]) $cond = join(" OR ", $dico_strict["OR"]);
		
	if ($cond) {	
		$result2 = spip_query("SELECT $select_hash FROM spip_index_dico WHERE ".$cond);

		while ($row2 = spip_fetch_array($result2))
			$h_strict[] = $hex_fmt.$row2['h'];
	}

	$cond = "";
	if ($dico_strict["AND"])	$cond = join(" OR ", $dico_strict["AND"]);
	if ($cond) {	
		$result2 = spip_query("SELECT $select_hash FROM spip_index_dico WHERE ".$cond);

		while ($row2 = spip_fetch_array($result2))
			$h_strict_and[] = $hex_fmt.$row2['h'];
		
	}

	$cond = "";
	if ($dico["OR"]) $cond = join(" OR ", $dico["OR"]);
	if ($cond) {	
		$result2 = spip_query("SELECT $select_hash FROM spip_index_dico WHERE ".$cond);

		while ($row2 = spip_fetch_array($result2))
			$h[] = $hex_fmt.$row2['h'];
	}
	
	
	$cond = "";
	if ($dico["AND"])	$cond = join(" OR ", $dico["AND"]);
	if ($cond) {	
		$result2 = spip_query("SELECT $select_hash,dico FROM spip_index_dico WHERE ".$cond);

		while ($row2 = spip_fetch_array($result2)) {
			//store the condition that selected the hash (the word typed by the user)  
			foreach($dico["AND"] as $key=>$val) {
				$mot_and = substr($key,1);
				if(strpos($row2['dico'],$mot_and)===0) 
					$h_and[$mot_and][] = $hex_fmt.$row2['h'];
			}
		}
		//store hash 0 for words without any hashes
		foreach($dico["AND"] as $key=>$val) {
			$mot_and = substr($key,1);
			if(!$h_and[$mot_and]) $h_and[$mot_and][] = 0;
		}
	}
		
	$cond = "";
	if ($dico["NOT"]) $cond = join(" OR ", $dico["NOT"]);
		
	if ($cond) {	
		$result2 = spip_query("SELECT $select_hash FROM spip_index_dico WHERE ".$cond);

		while ($row2 = spip_fetch_array($result2))
			$h_not[] = $hex_fmt.$row2['h'];
	}
	
	if ($h_strict)
		$hash_recherche_strict = join(",", $h_strict);
	else
		$hash_recherche_strict = "0";
		
	if ($h_strict_and)
		$hash_recherche_strict_and = join(",", $h_strict_and);
	else
		$hash_recherche_strict_and = "0";

	if ($h)
		$hash_recherche = join(",", $h);
	else
		$hash_recherche = "0";

	if ($h_and) { 
		foreach($h_and as $key=>$val)
			$hash_recherche_and[$key] = join(",", $h_and[$key]);
	} else
		$hash_recherche_and = "0";

	if ($h_not) 
		$hash_recherche_not = join(",", $h_not);
	else
		$hash_recherche_not = "0";

	return array($hash_recherche, $hash_recherche_strict, $hash_recherche_not, $hash_recherche_and, $hash_recherche_strict_and);
}

//
// Preparer les elements pour le critere {recherche}
// Note : si le critere est optionnel {recherche?}, ne pas s'activer
// si la recherche est vide
//
// http://doc.spip.org/@prepare_recherche
function prepare_recherche($recherche, $primary = 'id_article', $id_table='articles',$nom_table='spip_articles', $cond=false) {
	static $cache = array();
	static $fcache = array();

	// si recherche n'est pas dans le contexte, on va prendre en globals
	// ca permet de faire des inclure simple.
	if (!isset($recherche) AND isset($GLOBALS['recherche']))
		$recherche = $GLOBALS['recherche'];

	// traiter le cas {recherche?}
	if ($cond AND !strlen($recherche))
		return array("''" /* as points */, /* where */ '1');

	// Premier passage : chercher eventuel un cache des donnees sur le disque
	if (!$cache[$recherche]['hash']) {
		$dircache = sous_repertoire(_DIR_CACHE,'rech');
		$fcache[$recherche] =
			$dircache . substr(md5($recherche),0,10).'.txt';
		if (lire_fichier($fcache[$recherche], $contenu))
			$cache[$recherche] = @unserialize($contenu);
	}

	// si on n'a pas encore traite les donnees dans une boucle precedente
	if (!$cache[$recherche][$primary]) {
		if (!$cache[$recherche]['hash'])
			$cache[$recherche]['hash'] = requete_hash($recherche);
		list($hash_recherche, $hash_recherche_strict, $hash_recherche_not, $hash_recherche_and, $hash_recherche_strict_and)
			= $cache[$recherche]['hash'];

		$strict = array();
		if ($hash_recherche_strict)
			foreach (split(',',$hash_recherche_strict) as $h)
				$strict[$h] = 99;
		
		if ($hash_recherche_strict_and)
			foreach (split(',',$hash_recherche_strict_and) as $h)
				$strict[$h] = 99;

		$index_id_table = id_index_table($nom_table);
		$points = array();
		
		$objet_and = array();
		$object_not = array();
		if($hash_recherche_and) {
			//$hash_recherche_and is an array of mots=>comma separated hashes
			$list_hashes = join(",",$hash_recherche_and);
			$pow = 1;
			foreach($hash_recherche_and as $key=>$val) {
				$hash_groupes[] = "$pow*".calcul_mysql_in("hash",$val);
				$pow *= 2; 
			}
			$count_groupes = join(" + ",$hash_groupes);
			
			$s = spip_query("SELECT id_objet as id,COUNT(DISTINCT $count_groupes) as count_groupes FROM spip_index WHERE id_table='$index_id_table' AND hash IN ($list_hashes) GROUP BY id HAVING count_groupes=".count($hash_recherche_and));
			//if no ids are found, pass at least id = 0 in order to exclude any result
			$objet_and[] = 0;
			while ($r = spip_fetch_array($s)) 
				$objet_and[]=$r['id'];
		}
		if($hash_recherche_not) {
			$s = spip_query("SELECT DISTINCT id_objet as id FROM spip_index WHERE hash IN ($hash_recherche_not) AND id_table='$index_id_table'");
			while ($r = spip_fetch_array($s))
				$objet_not[]=$r['id'];														
		}
		if(count($objet_and))
			$list_and = " AND id_objet IN (".join(",",$objet_and).")";
		if(count($objet_not))
			$list_not = " AND id_objet NOT  IN (".join(",",$objet_not).")";
		if($hash_recherche) {
			$list_hash = " AND hash IN (".$hash_recherche.")";
		}
		
		if($list_hash || $list_and || $list_not) {
			$query = "SELECT hash,points,id_objet as id FROM spip_index WHERE id_table='$index_id_table'".$list_and.$list_not.$list_hash;  
			
			$s = spip_query($query);
				
			while ($r = spip_fetch_array($s))
				$points[$r['id']]
				+= (1 + $strict[$r['hash']]) * $r['points'];
			spip_free_result($s);
			arsort($points, SORT_NUMERIC);
		}

		# calculer le {id_article IN()} et le {... as points}
		if (!count($points)) {
			$cache[$recherche][$primary] = array("''", 0);
		} else {
			$ids = array();
			$select = '0';
			foreach ($points as $id => $p)
				$listes_ids[$p] .= ','.$id;
			foreach ($listes_ids as $p => $liste_ids)
				$select .= "+$p*(".
					calcul_mysql_in("$id_table.$primary", substr($liste_ids, 1))
					.") ";

			$cache[$recherche][$primary] = array($select,
				'('.calcul_mysql_in("$id_table.$primary",
					join(',',array_keys($points))).')'
				);
		}

		// ecrire le cache de la recherche sur le disque
		ecrire_fichier($fcache[$recherche], serialize($cache[$recherche]));
		// purger le petit cache
		nettoyer_petit_cache('rech', 300);
	}
	return $cache[$recherche][$primary];
}


// http://doc.spip.org/@cron_indexation
function cron_indexation($t) {
	$c = count(effectuer_une_indexation());
	// si des indexations ont ete effectuees, on passe la periode a 0 s
	## note : (time() - 90) correspond en fait a :
	## time() - $taches_generales['indexation']
	if ($c)
		return (0 - (time() - 90));
	else
		return 0;
}


// Si la liste des correspondances tables/id_table n'est pas la, la creer
if ((isset($GLOBALS['meta']))&&(!isset($GLOBALS['meta']['index_table'])))
	update_index_tables();

?>
