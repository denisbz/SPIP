<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_INDEX")) return;
define("_ECRIRE_INC_INDEX", "1");


function separateurs_indexation($requete = false) {
	// Merci a Herve Lefebvre pour son apport sur cette fonction
	$liste = "],:;*\"!\r\n\t\\/){}[|@<>$%";

	// en vietnamien ne pas eliminer les accents de translitteration
	if (!(lire_meta('langue_site') == 'vi' AND $requete))
		$liste .= "'`?\~.^+(-";

	// windowzeries iso-8859-1
	$charset = lire_meta('charset');
	if ($charset == 'iso-8859-1')
		$liste .= chr(187).chr(171).chr(133).chr(145).chr(146).chr(180).chr(147).chr(148);

	return $liste;
}

function spip_split($reg, $texte) {
	global $flag_pcre;
	if ($flag_pcre)
		return preg_split("/$reg/", $texte);
	else
		return split($reg, $texte);
}

function nettoyer_chaine_indexation($texte) {
	include_ecrire("inc_charsets.php3");

	$texte = translitteration($texte);

	if (lire_meta('langue_site') == 'vi')
		$texte = strtr($texte, "'`?~.^+(-", "123456789");

	return $texte;
}

function indexer_chaine($texte, $val = 1, $min_long = 3) {
	global $index, $mots;

	// Nettoyer les tags, entites HTML, signes diacritiques...
	$texte = ' '.ereg_replace("<[^>]*>"," ",$texte).' ';
	$texte = nettoyer_chaine_indexation($texte);

	// Nettoyer les caracteres non-alphanumeriques
	$regs = separateurs_indexation();
	$texte = strtr($texte, $regs, ereg_replace('.', ' ', $regs));

	// Cas particulier : sigles d'au moins deux lettres
	$texte = ereg_replace(" ([A-Z][0-9A-Z]{1,".($min_long - 1)."}) ", ' \\1___ ', $texte);
	$texte = strtolower($texte);

	// Separer les mots
	$table = spip_split(" +", $texte);

	while (list(, $mot) = each($table)) {
		if (strlen($mot) > $min_long) {
			$h = substr(md5($mot), 0, 16);
			$index[$h] += $val;
			$mots .= ",(0x$h,'$mot')";
		}
	}
}

function deja_indexe($type, $id_objet) {
	$table_index = 'spip_index_'.table_objet($type);
	$col_id = 'id_'.$type;
	$query = "SELECT $col_id FROM $table_index WHERE $col_id=$id_objet LIMIT 0,1";
	$n = @spip_num_rows(@spip_query($query));
	return ($n > 0);
}

function indexer_objet($type, $id_objet, $forcer_reset = true, $full = true /* full : inutilise ? */) {
	global $index, $mots;

	$table = 'spip_'.table_objet($type);
	$table_index = 'spip_index_'.table_objet($type);
	$col_id = 'id_'.$type;

	if (!$id_objet) return;
	if (!$forcer_reset AND deja_indexe($type, $id_objet)) {
		spip_log ("$type $id_objet deja indexe");
		spip_query("UPDATE $table SET idx='oui' WHERE $col_id=$id_objet");
		return;
	}
	// marquer "en cours d'indexation"
	spip_query("UPDATE $table SET idx='idx' WHERE $col_id=$id_objet");

	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");

	spip_log("indexation $type $id_objet");
	$index = '';
	$mots = '';

	switch($type) {
	case 'article':
		$query = "SELECT * FROM spip_articles WHERE id_article=$id_objet";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['soustitre'], 5);
			indexer_chaine($row['surtitre'], 5);
			indexer_chaine($row['descriptif'], 4);
			if ($full) {
				indexer_chaine($row['chapo'], 3);
				indexer_chaine($row['texte'], 1);
				indexer_chaine($row['ps'], 1);
				indexer_chaine($row['nom_site'], 1);
				indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
				$r = spip_query("SELECT doc.titre, doc.descriptif FROM spip_documents AS doc, spip_documents_articles AS lien WHERE lien.id_article=$id_objet AND doc.id_document=lien.id_document");
				while ($row_doc = spip_fetch_array($r)) {
					indexer_chaine($row_doc[0],2);
					indexer_chaine($row_doc[1],1);
				}
			}
		}
	
		$query2 = "SELECT mots.* FROM spip_mots AS mots, spip_mots_articles AS lien WHERE lien.id_article=$id_objet AND mots.id_mot=lien.id_mot";
		$result2 = spip_query($query2);
		while ($row = spip_fetch_array($result2)) {
			indexer_chaine($row['titre'], 12);
			indexer_chaine($row['descriptif'], 3);
		}
	
		$query3 = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_objet AND auteurs.id_auteur=lien.id_auteur";
		$result3 = spip_query($query3);
		while ($row = spip_fetch_array($result3)) {
			indexer_chaine($row['nom'], 10);
		}
		break;

	case 'breve':
		$query = "SELECT * FROM spip_breves WHERE id_breve=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			if ($full) {
				indexer_chaine($row['texte'], 2);
				indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
			}
		}
		break;

	case 'rubrique':
		$query = "SELECT * FROM spip_rubriques WHERE id_rubrique=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['descriptif'], 5);
			if ($full) {
				indexer_chaine($row['texte'], 1);
				indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
				$r = spip_query("SELECT doc.titre, doc.descriptif FROM spip_documents AS doc, spip_documents_rubriques AS lien WHERE lien.id_rubrique=$id_objet AND doc.id_document=lien.id_document");
				while ($row_doc = spip_fetch_array($r)) {
					indexer_chaine($row_doc[0],2);
					indexer_chaine($row_doc[1],1);
				}
			}
		}
		break;

	case 'auteur':	
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)){
			indexer_chaine($row['nom'], 5);
			if ($full) {
				indexer_chaine($row['bio'], 1);
				indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
			}
		}
		break;

	case 'mot':
		$query = "SELECT * FROM spip_mots WHERE id_mot=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)){
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['descriptif'], 5);
			if ($full) {
				indexer_chaine($row['texte'], 1);
				indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
			}
		}
		break;

	case 'forum':
		$query = "SELECT * FROM spip_forum WHERE id_forum=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)){
			indexer_chaine($row['titre'], 3);
			indexer_chaine($row['texte'], 1);
			indexer_chaine($row['auteur'], 2);
			indexer_chaine($row['email_auteur'], 2);
			indexer_chaine($row['nom_site'], 2);
			indexer_chaine($row['url_site'], 1);
		}
		break;

	case 'signature':
		$query = "SELECT * FROM spip_signatures WHERE id_signature=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)){
			indexer_chaine($row['nom_email'], 2);
			indexer_chaine($row['ad_email'], 2);
			indexer_chaine($row['nom_site'], 2);
			indexer_chaine($row['url_site'], 1);
			indexer_chaine($row['message'], 1);
		}
		break;

	case 'syndic':
		$query = "SELECT * FROM spip_syndic WHERE id_syndic=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
			indexer_chaine($row['nom_site'], 50);
			indexer_chaine($row['descriptif'], 30);

			if ($full) {
				// Ajouter les titres des articles syndiques de ce site, le cas echeant
				if ($row['syndication'] = "oui") {
					$query_syndic = "SELECT titre FROM spip_syndic_articles WHERE id_syndic=$id_objet ORDER BY date DESC LIMIT 0,100";
					$result_syndic = spip_query($query_syndic);
					while ($row_syndic = spip_fetch_array($result_syndic)) {
						indexer_chaine($row_syndic['titre'], 5);
					}
				}
				// Aller chercher la page d'accueil
				if (lire_meta("visiter_sites") == "oui") {
					include_ecrire ("inc_sites.php3");
					spip_log ("indexation contenu syndic ".$row['url_site']);
					indexer_chaine(supprimer_tags(substr(recuperer_page($row['url_site']), 0, 50000)), 1);
				}
			}
		}
		break;

	} // switch

	$query = "DELETE FROM $table_index WHERE $col_id=$id_objet";
	$result = spip_query($query);

	if ($index) {
		if ($mots) {
			$mots = "INSERT IGNORE INTO spip_index_dico (hash, dico) VALUES ".substr($mots,1);	// supprimer la virgule du debut
			spip_query($mots);
		}
		reset($index);
		unset($q);
		while (list($hash, $points) = each($index)) $q[] = "(0x$hash,$points,$id_objet)";
		spip_query("INSERT INTO $table_index (hash, points, $col_id) VALUES ".join(',',$q));
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

// API pour l'espace prive
function marquer_indexer ($objet, $id_objet) {
	spip_log ("demande indexation $objet $id_objet");
	$table = 'spip_'.table_objet($objet);
	spip_query ("UPDATE $table SET idx='1' WHERE id_$objet=$id_objet");
}
function indexer_article($id_article) {
	marquer_indexer('article', $id_article);
}
function indexer_auteur($id_auteur) {
	marquer_indexer('auteur', $id_auteur);
}
function indexer_breve($id_breve) {
	marquer_indexer('breve', $id_breve);
}
function indexer_mot($id_mot) {
	marquer_indexer('mot', $id_mot);
}
function indexer_rubrique($id_rubrique) {
	marquer_indexer('rubrique', $id_rubrique);
}
function indexer_syndic($id_syndic) {
	marquer_indexer('syndic', $id_syndic);
}

function effectuer_une_indexation($nombre_indexations = 1) {

	// chercher un objet a indexer dans chacune des tables d'objets
	$vu = array();
	$types = array('article','auteur','breve','mot','rubrique','forum','signature','syndic');
	while (list(,$type) = each($types)) {
		$table_objet = 'spip_'.table_objet($type);
		$table_index = 'spip_index_'.table_objet($type);

		// limiter aux objets publies
		switch ($type) {
			case 'article':
			case 'breve':
			case 'rubrique':
			case 'syndic':
			case 'forum':
			case 'signature':
				$critere = "AND statut='publie'";
				break;
			case 'auteur':
				$critere = "AND statut IN ('0minirezo', '1comite')";
				break;
			case 'mot':
			default:
				$critere = '';
				break;
		}

		if ($type == 'syndic')
			$limit = 1;
		else
			$limit = $nombre_indexations;

		$s = spip_query("SELECT id_$type, idx FROM $table_objet WHERE idx IN ('','1','idx') $critere ORDER BY (idx='idx') LIMIT 0,$limit");
		while ($t = spip_fetch_array($s)) {
			$vu[$type] .= $t[0].", ";
			indexer_objet($type, $t[0], $t[1]);
		}
	}
	return $vu;
}

function executer_une_indexation_syndic() {
	$visiter_sites = lire_meta("visiter_sites");
	if ($visiter_sites == "oui") {
		$query = "SELECT id_syndic FROM spip_syndic WHERE statut='publie' ".
			"AND date_index < DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date_index LIMIT 0,1";
		if ($result = spip_query($query)) {
			while ($row = spip_fetch_array($result)) {
				$id_syndic = $row['id_syndic'];
				spip_query("UPDATE spip_syndic SET date_index=NOW() WHERE id_syndic=$id_syndic");
				indexer_syndic($id_syndic);
			}
		}
	}
}

function creer_liste_indexation() {
	$types = array('article','auteur','breve','mot','rubrique','syndic','forum','signature');
	while (list(,$type) = each($types)) {
		$table = 'spip_'.table_objet($type);
		spip_query("UPDATE $table SET idx='1'");
	}
}

function purger_index() {
		spip_query("DELETE FROM spip_index_articles");
		spip_query("DELETE FROM spip_index_auteurs");
		spip_query("DELETE FROM spip_index_breves");
		spip_query("DELETE FROM spip_index_mots");
		spip_query("DELETE FROM spip_index_rubriques");
		spip_query("DELETE FROM spip_index_syndic");
		spip_query("DELETE FROM spip_index_forum");
		spip_query("DELETE FROM spip_index_signatures");
		spip_query("DELETE FROM spip_index_dico");
}

// cree la requete pour une recherche en txt integral
function requete_txt_integral($objet, $hash_recherche) {
	$table = "spip_".table_objet($objet);
	$index_table = "spip_index_".table_objet($objet);
	$id_objet = "id_".$objet;
	return "SELECT objet.*, SUM(rec.points) AS points
		FROM $table AS objet, $index_table AS rec
		WHERE objet.$id_objet = rec.$id_objet
		AND rec.hash IN ($hash_recherche)
		GROUP BY objet.$id_objet
		ORDER BY points DESC
		LIMIT 0,10";
}

// rechercher un mot dans le dico
// retourne deux methodes : lache puis strict
function requete_dico($val) {
	$min_long = 3;

	// cas particulier translitteration vietnamien
	if (lire_meta('langue_site') == 'vi') {
		// 1. recuperer des accents passes sous la forme a`
		$val = strtr($val, "'`?~.^+(-","123456789");
		// 2. translitterer les accents passes en unicode
		$val = nettoyer_chaine_indexation($val);
		// 3. composer la regexp pour les caracteres accentuables mais non accentues
		while (ereg("([aeiouyd])([a-z])", $val.' ', $match))
			$val = str_replace ($match[0], $match[1].'[-1-9]?[-1-9]?'.$match[2], $val);
		return array("dico REGEXP '^$val'", "dico REGEXP '^$val$'");
	}

	// cas normal
	if (strlen($val) > $min_long) {
		return array("dico LIKE '$val%'", "dico = '$val'");
	} else
		return array("dico = '".$val."___'", "dico = '".$val."___'");
}


// decode la chaine recherchee et la traduit en hash
function requete_hash ($rech) {
	// recupere les mots de la recherche
	$rech = nettoyer_chaine_indexation($rech);
	$regs = separateurs_indexation(true)." ";
	$rech = strtr($rech, $regs, ereg_replace('.', ' ', $regs));
	$s = spip_split(" +", $rech);
	unset($dico);
	unset($h);

	// cherche les mots dans le dico
	while (list(, $val) = each($s)) {
		list($rq, $rq_strict) = requete_dico ($val);
		if ($rq)
			$dico[] = $rq;
		if ($rq_strict)
			$dico_strict[] = $rq_strict;
	}

	// compose la recherche dans l'index
	if ($dico_strict) {
		$query2 = "SELECT HEX(hash) AS hx FROM spip_index_dico WHERE ".join(" OR ", $dico_strict);
		$result2 = spip_query($query2);
		while ($row2 = spip_fetch_array($result2))
			$h_strict[] = "0x".$row2["hx"];
	}
	if ($dico) {
		$query2 = "SELECT HEX(hash) AS hx FROM spip_index_dico WHERE ".join(" OR ", $dico);
		$result2 = spip_query($query2);
		while ($row2 = spip_fetch_array($result2))
			$h[] = "0x".$row2["hx"];
	}
	if ($h_strict)
		$hash_recherche_strict = join(",", $h_strict);
	else
		$hash_recherche_strict = "0";

	if ($h)
		$hash_recherche = join(",", $h);
	else
		$hash_recherche = "0";

	return array($hash_recherche, $hash_recherche_strict);
}

?>
