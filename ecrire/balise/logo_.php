<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Fonction des balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//

// http://doc.spip.org/@balise_LOGO__dist
function balise_LOGO__dist ($p) {

	preg_match(",^LOGO_([A-Z]+)(_.*)?$,i", $p->nom_champ, $regs);
	$type_objet = strtolower($regs[1]);
	$suite_logo = @$regs[2];	

	// cas de #LOGO_SITE_SPIP
	if (preg_match(",^_SPIP(.*)$,", $suite_logo, $regs)) {
		$type_objet = 'site';
		$suite_logo = $regs[1];
		$_id_objet = "\"'0'\"";
		$id_objet = 'id_syndic'; # parait faux mais donne bien "siteNN"
	} else {
		if ($type_objet == 'site')
			$id_objet = "id_syndic";
		else
			$id_objet = "id_". $type_objet;
		$_id_objet = champ_sql($id_objet, $p);
	}

	// analyser les faux filtres
	$flag_fichier = $align = $lien = $params = '';

	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $couple) {
			$nom = trim($couple[0]);

			// double || signifie "on passe aux vrais filtres"
			if ($nom == '') {
				if ($couple[1]) {
					$params = $couple[1]; // recuperer #LOGO_DOCUMENT{20,30}
					array_shift($p->param);
				} else break;
			} else {
				array_shift($p->param);
				$r = logo_faux_filtres($nom);
				if ($r === 0)
					$align = $nom;
				else {
					if ($r === 1)
						$lien = true;
					elseif ($r === 2)
						$flag_fichier = 1;
					else	$lien = $nom;
					break;
				}
			}
		}
	}

	if (!$flag_fichier) {
		if ($lien == true) {
			include_spip('balise/url_');
			$lien = '($lien = '
			  . generer_generer_url_arg($type_objet, $p, $_id_objet)
			  . ') ? $lien : ""';

		} else if ($lien) {
			$lien = "'".texte_script(trim($lien))."'";
			while (preg_match(",^([^#]*)#([A-Za-z_]+)(.*)$,", $lien, $match)) {
				$c = new Champ();
				$c->nom_champ = $match[2];
				$c->id_boucle = $p->id_boucle;
				$c->boucles = &$p->boucles;
				$c->descr = $p->descr;
				$c = calculer_champ($c);
				$lien = str_replace('#'.$match[2], "'.".$c.".'", $lien);
			}
			// supprimer les '' disgracieux
			$lien = preg_replace("@^''\.|\.''$@", "", $lien);
		}
	}
	if (!$lien) $lien = "''";
	$connect = $p->id_boucle ?$p->boucles[$p->id_boucle]->sql_serveur :'';
	if ($type_objet == 'document') {
		$p->code = "calcule_logo_document($_id_objet, '" .
			$p->descr['documents'] .
			'\', $doublons, '. intval($flag_fichier).", $lien, '"
		  	. $align . "','" .
			// #LOGO_DOCUMENT{x,y} donne la taille maxi
			texte_script($params)
			."'," . _q($connect) .")";
	}
	elseif ($connect) {
		$p->code = "''";
		spip_log("Les logos distants ne sont pas prevus");
	} else {
		$p->code = "affiche_logos(calcule_logo('$id_objet', '" .
			(($suite_logo == '_SURVOL') ? 'off' : 
			(($suite_logo == '_NORMAL') ? 'on' : 'ON')) .
			"', $_id_objet," .
			(($suite_logo == '_RUBRIQUE') ? 
			champ_sql("id_rubrique", $p) :
			(($type_objet == 'rubrique') ? "quete_parent($_id_objet)" : "''")) .
			",  '$flag_fichier'), $lien, '". $align . "')";
	}

	$p->interdire_scripts = false;
	return $p;
}

function logo_faux_filtres($nom)
{
	switch($nom) {
	case 'top':
	case 'left':
	case 'right':
	case 'center':
	case 'bottom':  return 0;
	case 'lien':    return 1;
	case 'fichier': return 2;
	default: return $nom;
	}
}

?>
