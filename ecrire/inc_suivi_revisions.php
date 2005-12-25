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

include_ecrire("inc_presentation");
include_ecrire('inc_rss');
include_ecrire("lab_revisions");
include_ecrire("lab_diff");

function suivi_revisions_dist()
{
  global
    $connect_id_auteur,
    $connect_statut,
    $id_auteur,
    $id_secteur,
    $lang_choisie,
    $uniq_auteur;

$debut = intval($debut);
$id_auteur = ($id_auteur == $connect_id_auteur) ? $id_auteur : false;

debut_page(_T("icone_suivi_revisions"));


//////////////////////////////////////////////////////
// Affichage de la colonne de gauche
//

debut_gauche();


if ($connect_statut == "0minirezo") $req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
else $req_where = " AND articles.statut IN ('prop','publie')"; 

echo "<p>";


debut_cadre_relief();

echo "<div class='arial11'><ul>";
echo "<p>";

if (!$id_auteur AND $id_secteur < 1) echo "<li><b>"._T('info_tout_site')."</b>";
else echo "<li><a href='" . generer_url_ecrire("suivi_revisions","") . "'>"._T('info_tout_site')."</a>";

echo "<p>";

$nom_auteur = $GLOBALS['auteur_session']['nom'];

if ($id_auteur) echo "<li><b>$nom_auteur</b>";
else echo "<li><a href='" . generer_url_ecrire("suivi_revisions","id_auteur=$connect_id_auteur") . "'>$nom_auteur</a>";

echo "<p>";

$query = "SELECT * FROM spip_rubriques WHERE id_parent = 0 ORDER BY 0+titre, titre";
$result = spip_query($query);

while ($row = mysql_fetch_array($result)) {
	$id_rubrique = $row['id_rubrique'];
	$titre = propre($row['titre']);
	
	$query_rub = "
SELECT versions.*, articles.statut, articles.titre
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.id_secteur=$id_rubrique$req_where LIMIT 1";
	$result_rub = spip_query($query_rub);
	
	if ($id_rubrique == $id_secteur)  echo "<li><b>$titre</b>";
	else if (spip_num_rows($result_rub) > 0) echo "<li><a href='" . generer_url_ecrire("suivi_revisions","id_secteur=$id_rubrique") . "'>$titre</a>";
}

if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui')) {
	echo "<p>";
	$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
	
	foreach ($langues as $lang) {
		$titre = traduire_nom_langue($lang);
	
		$query_lang = "
SELECT versions.*
FROM spip_versions AS versions, spip_articles AS articles 
WHERE versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.lang='$lang' $req_where LIMIT 1";
		$result_lang = spip_query($query_lang);
		
		if ($lang == $lang_choisie)  echo "<li><b>$titre</b>";
		else if (spip_num_rows($result_lang) > 0) echo "<li><a href='" . generer_url_ecrire("suivi_revisions","lang_choisie=$lang") . "'>$titre</a>";
	}
}


echo "</ul></div>\n";

// lien vers le rss

$op = 'revisions';
$args = array(
	'id_secteur' => $id_secteur,
	'id_auteur' => $id_auteur,
	'lang_choisie' => $lang_choisie
);
echo "<div style='text-align: "
	. $GLOBALS['spip_lang_right']
	. ";'>"
	. bouton_spip_rss($op, $args)
	."</div>";


fin_cadre_relief();



//////////////////////////////////////////////////////
// Affichage de la colonne de droite
//


debut_droite();

afficher_suivi_versions ($debut, $id_secteur,
	$uniq_auteur ? $connect_id_auteur : '',
	$lang_choisie);

fin_page();
}

function afficher_para_modifies ($texte, $court = false) {
	// Limiter la taille de l'affichage
	if ($court) $max = 200;
	else $max = 2000;
	
	$paras = explode ("\n",$texte);
	for ($i = 0; $i < count($paras) AND strlen($texte_ret) < $max; $i++) {
		if (strpos($paras[$i], '"diff-')) $texte_ret .= $paras[$i]."\n\n";
	}
	$texte = $texte_ret;
	return $texte;
}

function afficher_suivi_versions ($debut = 0, $id_secteur = 0, $id_auteur = false, $lang = "", $court = false, $rss = false) {
	global $dir_lang;
	
	$nb_aff = 10;
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	if ($id_auteur) {
		$req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
		$req_where = " AND versions.id_auteur = $id_auteur";
	} else {
		$req_where = " AND articles.statut IN ('prop','publie')";
	}
	
	if (strlen($lang) > 0) $req_where .= " AND articles.lang='$lang'";

	if ($id_secteur > 0) $req_where .= " AND articles.id_secteur = $id_secteur";

	$query = "
		SELECT versions.*, articles.statut, articles.titre 
		FROM spip_versions AS versions, spip_articles AS articles 
		WHERE versions.id_article = articles.id_article AND versions.id_version > 1 $req_where ";
	
	$result = spip_query($query . " ORDER BY versions.date DESC LIMIT $debut, $nb_aff");
	if (spip_num_rows($result) > 0) {

		// Afficher l'entete de la boite
		if (!$rss) {
			$titre_table = _T('icone_suivi_revisions').aide('suivimodif');
			if ($court)
				$titre_table = afficher_plus(generer_url_ecrire("suivi_revisions",""))
				. $titre_table;

			echo "<div style='height: 12px;'></div>";
			echo "<div class='liste'>";
			bandeau_titre_boite2($titre_table, "historique-24.gif");
	
			$total = spip_num_rows(spip_query($query . "LIMIT 0, 149"));
		
			if ($total > $nb_aff) {
				$nb_tranches = ceil($total / $nb_aff);
			
				echo "<div class='arial2' style='background-color: #dddddd; padding: 5px;'>";
		
				for ($i = 0; $i < $nb_tranches; $i++) {
					if ($i > 0) echo " | ";
					if ($i*$nb_aff == $debut) echo "<b>";
					else echo "<a href='", generer_url_ecrire('suivi_revisions', "debut=".($i * $nb_aff)."&id_secteur=$id_secteur&uniq_auteur=$uniq_auteur&lang_choisie=$lang"),"'>";
					echo (($i * $nb_aff) + 1);
					if ($i*$nb_aff == $debut) echo "</b>";
					else echo "</a>";
				}
				echo "</div>";
			}
		}

		// Afficher les 10 elements
		while ($row = spip_fetch_array($result)) {
			$id_version = $row['id_version'];
			$id_auteur = $row['id_auteur'];
			$date = $row['date'];
			$id_article = $row['id_article'];
			$statut = $row['statut'];
			$titre = propre($row['titre']);
			$query_auteur = "
				SELECT nom,email
				FROM spip_auteurs
				WHERE id_auteur = $id_auteur";
			$row_auteur = spip_fetch_array(spip_query($query_auteur));
			$nom = typo($row_auteur["nom"]);
			$email = $row_auteur['email'];
	
	

			if (!$rss) {
				$logo_statut = "puce-".puce_statut($statut).".gif";
				echo "<div class='tr_liste' style='padding: 5px; border-top: 1px solid #aaaaaa;'>";
	
				echo "<span class='arial2'>";
				if (!$court) echo bouton_block_visible("$id_version-$id_article-$id_auteur");
				echo "<img src='" . _DIR_IMG_PACK . "$logo_statut' border='0'>&nbsp;";
				echo "<a class='$statut' style='font-weight: bold;' href='" . generer_url_ecrire("articles_versions","id_article=$id_article") . "'>$titre</a>";
				echo "</span>";
				echo "<span class='arial1'$dir_lang>";
				echo " ".date_relative($date)." ";
				if (strlen($nom)>0) echo "($nom)";
				echo "</span>";
			} else {
				$item = array(
					'title' => $titre,
					'url' => generer_url_ecrire($GLOBALS['meta']['adresse_site'].'/'._DIR_RESTREINT_ABS."articles_versions","id_article=$id_article&id_version=$id_version"),
					'date' => $date,
					'author' => $nom,
					'email' => $email
				);
			}

			if (!$court) { 
				$query_diff = "
					SELECT id_version
					FROM spip_versions
					WHERE id_article=$id_article AND id_version<$id_version 
					ORDER BY id_version DESC LIMIT 0,1";
					if ($result_diff = spip_query($query_diff)) {
						$row_diff = spip_fetch_array($result_diff);
						$id_diff = $row_diff['id_version'];
				}
		
		
				$query_art = "
					SELECT *
					FROM spip_articles
					WHERE id_article='$id_article'";
				$result_art = spip_query($query_art);
				
				if ($row_art = spip_fetch_array($result_art)) {
					$id_article = $row_art["id_article"];
					$id_rubrique = $row_art["id_rubrique"];
					$date = $row_art["date"];
					$statut_article = $row_art["statut"];
					$maj = $row_art["maj"];
					$date_redac = $row_art["date_redac"];
					$visites = $row_art["visites"];
					$referers = $row_art["referers"];
					$extra = $row_art["extra"];
					$id_trad = $row_art["id_trad"];
				}
				
				$textes = recuperer_version($id_article, $id_version);		
						
				if ($id_version && $id_diff) {		
					if ($id_diff > $id_version) {
						$t = $id_version;
						$id_version = $id_diff;
						$id_diff = $t;
						$old = $textes;
						$new = $textes = recuperer_version($id_article, $id_version);
					}
					else {
						$old = recuperer_version($id_article, $id_diff);
						$new = $textes;
					}		
					$textes = array();			
					foreach ($champs as $champ) {
						if (!$new[$champ] && !$old[$champ]) continue;
						$diff = new Diff(new DiffTexte);
						$textes[$champ] = afficher_para_modifies(afficher_diff($diff->comparer(preparer_diff($new[$champ]), preparer_diff($old[$champ]))), $court);
					}
				}
				
				if (!$rss)
					echo debut_block_visible("$id_version-$id_article-$id_auteur");

				if (is_array($textes))
				foreach ($textes as $var => $t) {
					if (strlen($t) > 0) {
						if (!$rss) echo "<blockquote class='serif1'>";
						$aff = propre_diff($t);
						if ($GLOBALS['les_notes']) {
							$aff .= '<p>'.$GLOBALS['les_notes'];
							$GLOBALS['les_notes'] = '';
						}
						if (!$rss) {
							echo $aff;
							echo "</blockquote>";
						} else
							$item['description'] = $aff;
					}
				}
				if (!$rss) echo fin_block();
			}
			
			if (!$rss) echo "</div>";

			if ($rss)
				$items[] = $item;
		}		
		if (!$rss) echo "</div>";
	}

	if ($rss)
		return $items;
}

?>
