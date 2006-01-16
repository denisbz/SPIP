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

if (!defined("_ECRIRE_INC_VERSION")) return;
function cadre_auteur_infos($id_auteur, $auteur)
{
  global $connect_statut;

  if ($id_auteur) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_cadre_numero_auteur')."&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
	echo "</CENTER>";


// "Voir en ligne" si l'auteur a un article publie
// seuls les admins peuvent "previsualiser" une page auteur
if (spip_num_rows(spip_query("SELECT lien.id_article
FROM spip_auteurs_articles AS lien,
spip_articles AS articles
WHERE lien.id_auteur=$id_auteur
AND lien.id_article=articles.id_article
AND articles.statut='publie'")))
	voir_en_ligne ('auteur', $id_auteur, 'publie');
else if ($connect_statut == '0minirezo')
	voir_en_ligne ('auteur', $id_auteur, 'prop');

	fin_boite_info();
  }
}

//  affiche le statut de l'auteur dans l'espace prive

function afficher_formulaire_statut_auteur ($id_auteur, $statut, $post='') {
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;
	global $spip_lang_right;


	// S'agit-il d'un admin restreint ?
	if ($statut == '0minirezo') {
		$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
		$result_admin = spip_query($query_admin);
		$admin_restreint = (spip_num_rows($result_admin) > 0);
	}

	$droit = ( ($connect_toutes_rubriques OR $statut != "0minirezo")
		   && ($connect_id_auteur != $id_auteur));

	if ($post && $droit) {
		$url_self = $post;
		echo "<p />";
		echo generer_url_post_ecrire($post, "id_auteur=$id_auteur");
	} else
		$url_self = "auteur_infos";

	// les admins voient et peuvent modifier les droits
	// les admins restreints les voient mais 
	// ne peuvent les utiliser que pour mettre un auteur a la poubelle
	if ($connect_statut == "0minirezo") {
		debut_cadre_relief();

		if ($droit) {
		  /* Neutralisation momentanee des couches. A revoir.
		  $couches = $admin_restreint ? 
		    bouton_block_visible("statut$id_auteur") :
		    bouton_block_invisible("statut$id_auteur");
		  echo $couches;
		  */
		  echo "<b>"._T('info_statut_auteur')." </b> ";
		  echo choix_statut_auteur($statut);
		}

		// si pas admin au chargement, rien a montrer. 
		echo "<div id='changer_statut_auteur'",
		  (($statut == '0minirezo') ? '' : " style='visibility: hidden'"),
		  '>';

		echo "\n<p /><div style='arial2'>";
		// si pas admin restreint au chargement, rien a calculer
		if (!$admin_restreint) {
			if ($statut == '0minirezo') {
				echo _T('info_admin_gere_toutes_rubriques');
			}
		} else {
				echo _T('info_admin_gere_rubriques')."\n";
				echo "<ul style='list-style-image: url(" . _DIR_IMG_PACK . "rubrique-12.gif)'>";
				while ($row_admin = spip_fetch_array($result_admin)) {
					$id_rubrique = $row_admin["id_rubrique"];
					echo "<li><a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "'>", typo($row_admin["titre"]), "</a>";

					if ($connect_toutes_rubriques
					AND $connect_id_auteur != $id_auteur) {
					  echo "&nbsp;&nbsp;&nbsp;&nbsp;<font size='1'>[<a href='", generer_url_ecrire($url_self, "id_auteur=$id_auteur&supp_rub=$id_rubrique"), '">',
					    _T('lien_supprimer_rubrique'),
					    "</a>]</font>";
					}
					echo '</li>';
					$toutes_rubriques .= "$id_rubrique,";
				}
				$toutes_rubriques = ",$toutes_rubriques";

				echo "</ul>";
		}
		echo "</div>\n";

		// Ajouter une rubrique a un administrateur restreint
		if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
			echo debut_block_visible("statut$id_auteur");
			echo "\n<div id='ajax_rubrique' class='arial1'><br />\n";
			if (spip_num_rows($result_admin) == 0)
				echo "<b>"._T('info_restreindre_rubrique')."</b><br />";
			else
				echo "<b>"._T('info_ajouter_rubrique')."</b><br />";
			echo "\n<input name='id_auteur' value='$id_auteur' TYPE='hidden' />";

			// selecteur de rubrique
			include_ecrire('inc_rubriques');
			echo selecteur_rubrique(0, 'auteur', false);

			echo "</div>\n";
			echo fin_block();
		}

		echo '</div>'; // fin de la balise a visibilite conditionnelle

		if ($post && $droit) {
		  echo "<div align='",
		    $spip_lang_right,
		    "'><input type='submit' class='fondo' value=\"",
		    _T('bouton_valider'),
		    "\" /></div>",
		    "</form>\n";
		}

		fin_cadre_relief();
	}
}

function statut_modifiable_auteur($id_auteur, $auteur)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

// on peut se changer soi-meme
	  return  (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et pas admin restreint pour changer un autre admin
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($auteur['statut'] != "0minirezo"))));
}

function modifier_statut_auteur (&$auteur, $statut, $add_rub='', $supp_rub='') {
	global $connect_statut, $connect_toutes_rubriques;
	// changer le statut ?
	$id_auteur= $auteur['id_auteur'];
	if (statut_modifiable_auteur($id_auteur, $auteur) &&
	    ereg("^(0minirezo|1comite|5poubelle|6forum)$",$statut)) {
			$auteur['statut'] = $statut;
			spip_query("UPDATE spip_auteurs SET statut='".$statut."'
			WHERE id_auteur=". intval($id_auteur));
	}

	// modif auteur restreint, seulement pour les admins
	if ($connect_toutes_rubriques) {
		if ($add_rub=intval($add_rub))
			spip_query("INSERT INTO spip_auteurs_rubriques
			(id_auteur,id_rubrique)
			VALUES(".$auteur['id_auteur'].", $add_rub)");

		if ($supp_rub=intval($supp_rub))
			spip_query("DELETE FROM spip_auteurs_rubriques
			WHERE id_auteur=".$auteur['id_auteur']."
			AND id_rubrique=$supp_rub");
	}
}

// Menu de choix d'un statut d'auteur
function choix_statut_auteur($statut) {
	global $connect_toutes_rubriques;

	$menu = "<select name='statut' size=1 class='fondl'
		onChange=\"setvisibility('changer_statut_auteur', this.selectedIndex ? 'hidden' : 'visible');\">";

	// Si on est admin restreint, on n'a pas le droit de modifier un admin
	if ($connect_toutes_rubriques)
		$menu .= "\n<option" .
			mySel("0minirezo",$statut) .
			">" . _T('item_administrateur_2')
			. '</option>';

	// Ajouter le choix "comite"
	$menu .=
		"\n<option" .
		mySel("1comite",$statut) .
		">" .
		_T('intem_redacteur') .
		'</option>';

	// Ajouter le choix "visiteur" si :
	// - l'auteur est visiteur
	// - OU, on accepte les visiteurs (ou forums sur abonnement)
	// - OU il y a des visiteurs dans la base
	if (($statut == '6forum')
	OR ($GLOBALS['meta']['accepter_visiteurs'] == 'oui')
	OR ($GLOBALS['meta']['forums_publics'] == 'abo')
	OR spip_num_rows(spip_query("SELECT statut FROM spip_auteurs
	WHERE statut='6forum'")))
		$menu .= "\n<option" .
			mySel("6forum",$statut) .
			">" .
			_T('item_visiteur') .
			'</option>';

	// Ajouter l'option "nouveau" si l'auteur n'est pas confirme
	if ($statut == 'nouveau')
		$menu .= "\n<option" .
			mySel('nouveau',$statut) .
			">" .
			_L('Inscription &agrave; confirmer') .
			'</option>';

	// Ajouter l'option "autre" si le statut est inconnu
	if (!in_array($statut, array('nouveau', '0minirezo', '1comite', '6forum')))
		$menu .= "\n<option" .
			mySel('autre','autre') .
			">" .
			_L('Autre statut&nbsp;: ').htmlentities($statut).
			'</option>';



	$menu .= "\n<option" .
		mySel("5poubelle",$statut) .
		" style='background:url(" . _DIR_IMG_PACK . "rayures-sup.gif)'>&gt; "
		._T('texte_statut_poubelle') .
		'</option>' .
		"</select>\n";

	return $menu;
}
?>
