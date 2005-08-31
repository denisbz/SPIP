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


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_EXPORT")) return;
define("_ECRIRE_INC_EXPORT", "1");



$GLOBALS['version_archive'] = '1.2';


// Conversion timestamp MySQL (format ascii) en Unix (format integer)
function mysql_timestamp_to_time($maj)
{
	$t_an = substr($maj, 0, 4);
	$t_mois = substr($maj, 4, 2);
	$t_jour = substr($maj, 6, 2);
	$t_h = substr($maj, 8, 2);
	$t_min = substr($maj, 10, 2);
	$t_sec = substr($maj, 12, 2);
	return mktime ($t_h, $t_min, $t_sec, $t_mois, $t_jour, $t_an, 0);
}

function build_begin_tag($tag) {
	return "<$tag>";
}

function build_end_tag($tag) {
	return "</$tag>";
}

// Conversion texte -> xml (ajout d'entites)
function text_to_xml($string) {
	return str_replace('<', '&lt;', str_replace('&', '&amp;', $string));
}


//
// Exportation generique d'objets (fichier ou retour de fonction)
//
function export_objets($query, $type, $file = 0, $gz = false, $etape_en_cours="", $etape_actuelle="", $nom_etape="") {
	global $debut_limit;
	if ($etape_en_cours < 1 OR $etape_en_cours == $etape_actuelle){
		if ($etape_en_cours > 0) {
			echo "<li><b>$nom_etape</b>";
		}
	
		$result = spip_query($query);

		if ($etape_en_cours > 0){
			if ($type == "forum"){
				$total = spip_num_rows($result);
				if ($total > 5000){
					$result = spip_query($query." LIMIT $debut_limit OFFSET  5000");
					$debut_limit = $debut_limit + 5000;
					if ($debut_limit > $total) {
						$debut_limit = 0;
						echo " "._T('info_tous_resultats_enregistres');
					}
					else {
						echo " "._T('info_premier_resultat', array('debut_limit' => $debut_limit, 'total' => $total));
					}
				} 
				else {
					$debut_limit = 0;
				}
			}
			if ($type == "article"){
				$total = spip_num_rows($result);
				if ($total > 500){
					$result = spip_query($query." LIMIT $debut_limit OFFSET  500");
					$debut_limit = $debut_limit + 500;
					if ($debut_limit > $total) {
						$debut_limit = 0;
						echo " "._T('info_tous_resultats_enregistres');
					}
					else {
						echo " "._T('info_premier_resultat_sur', array('debut_limit' => $debut_limit, 'total' => $total));
					}
				} 
				else {
					$debut_limit = 0;
				}
			}
		
		}
		
		$_fputs = ($gz) ? gzputs : fputs;
		$nfields = mysql_num_fields($result);
		// Recuperer les noms des champs
		for ($i = 0; $i < $nfields; ++$i) $fields[$i] = mysql_field_name($result, $i);
		while ($row = spip_fetch_array($result)) {
			$string .= build_begin_tag($type) . "\n";
			// Exporter les champs de la table
			for ($i = 0; $i < $nfields; ++$i) {
				$string .= '<'.$fields[$i].'>' . text_to_xml($row[$i]) . '</'.$fields[$i].'>' . "\n";
			}
			// Exporter les relations
			if ($type == 'article') {
				$query = 'SELECT id_auteur FROM spip_auteurs_articles WHERE id_article='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_document FROM spip_documents_articles WHERE id_article='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'message') {
				$query = 'SELECT id_auteur FROM spip_auteurs_messages WHERE id_message='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'breve') {
				$query = 'SELECT id_document FROM spip_documents_breves WHERE id_breve='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'rubrique') {
				$query = 'SELECT id_document FROM spip_documents_rubriques WHERE id_rubrique='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_rubrique='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'auteur') {
				$query = 'SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:rubrique>' . $row2['id_rubrique'] . '</lien:rubrique>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'mot') {
				$query = 'SELECT id_article FROM spip_mots_articles WHERE id_mot='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:article>' . $row2['id_article'] . '</lien:article>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_breve FROM spip_mots_breves WHERE id_mot='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:breve>' . $row2['id_breve'] . '</lien:breve>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_forum FROM spip_mots_forum WHERE id_mot='.$row[0];
				$res3 = spip_query($query);
				while($row3 = spip_fetch_array($res3)) {
					$string .= '<lien:forum>' . $row3['id_forum'] . '</lien:forum>' . "\n";
				}
				spip_free_result($res3);
				$query = 'SELECT id_rubrique FROM spip_mots_rubriques WHERE id_mot='.$row[0];
				$res4 = spip_query($query);
				while($row4 = spip_fetch_array($res4)) {
					$string .= '<lien:rubrique>' . $row4['id_rubrique'] . '</lien:rubrique>' . "\n";
				}
				spip_free_result($res4);
				$query = 'SELECT id_syndic FROM spip_mots_syndic WHERE id_mot='.$row[0];
				$res4 = spip_query($query);
				while($row4 = spip_fetch_array($res4)) {
					$string .= '<lien:syndic>' . $row4['id_syndic'] . '</lien:syndic>' . "\n";
				}
				spip_free_result($res4);
			}
			$string .= build_end_tag($type) . "\n\n";
			if ($file) {
				$_fputs($file, $string);
				$string = '';
			}
		}
		spip_free_result($result);
		if (!$file) return $string;
	}
	else if ($etape_actuelle < $etape_en_cours) {
		echo "<li> $nom_etape";
	} else {
		echo "<li> <font color='#999999'>$nom_etape</font>";
	}
}



?>
