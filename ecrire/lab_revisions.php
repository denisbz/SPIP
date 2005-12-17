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

$GLOBALS['agregation_versions'] = 10;

function separer_paras($texte, $paras = "") {
	if (!$paras) $paras = array();
	while (preg_match("/(\r\n?){2,}|\n{2,}/", $texte, $regs)) {
		$p = strpos($texte, $regs[0]) + strlen($regs[0]);
		$paras[] = substr($texte, 0, $p);
		$texte = substr($texte, $p);
	}
	if ($texte) $paras[] = $texte;
	return $paras;
}

function replace_fragment($id_article, $version_min, $version_max, $id_fragment, $fragment) {
	global $flag_gz;

	$fragment = serialize($fragment);
	$compress = 0;
	if ($flag_gz) {
		$s = gzcompress($fragment);
		if (strlen($s) < strlen($fragment)) {
			//echo "gain gz: ".(100 - 100 * strlen($s) / strlen($fragment))."%<br />";
			$compress = 1;
			$fragment = $s;
		}
	}
	// Attention a bien echapper le $fragment qui est en binaire
	return "($id_article, $version_min, $version_max, $id_fragment, $compress, '"
		.mysql_escape_string($fragment)."')";
}

function exec_replace_fragments($replaces) {
	if (count($replaces)) {
		$query = "REPLACE spip_versions_fragments (id_article, version_min, version_max, id_fragment, compress, fragment) ".
			"VALUES ".join(", ", $replaces);
		spip_query($query);
	}
}
function exec_delete_fragments($id_article, $deletes) {
	if (count($deletes)) {
		$query = "DELETE FROM spip_versions_fragments WHERE id_article=$id_article AND ((".
			join(") OR (", $deletes)."))";
		spip_query($query);
	}
}


//
// Ajouter les fragments de la derniere version (tableau associatif id_fragment => texte)
//
function ajouter_fragments($id_article, $id_version, $fragments) {
	global $flag_gz, $agregation_versions;

	$replaces = array();
	foreach ($fragments as $id_fragment => $texte) {
		$nouveau = true;
		// Recuperer la version la plus recente
		$query = "SELECT compress, fragment, version_min, version_max FROM spip_versions_fragments ".
			"WHERE id_article=$id_article AND id_fragment=$id_fragment AND version_min<=$id_version ".
			"ORDER BY version_min DESC LIMIT 0,1";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$fragment = $row['fragment'];
			$version_min = $row['version_min'];
			if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
			$fragment = unserialize($fragment);
			if (is_array($fragment)) {
				unset($fragment[$id_version]);
				// Si le fragment n'est pas trop gros, prolonger celui-ci
				$nouveau = count($fragment) >= $agregation_versions
					&& strlen($row['fragment']) > 1000;
			}
		}
		if ($nouveau) {
			$fragment = array($id_version => $texte);
			$version_min = $id_version;
		}
		else {
			// Ne pas dupliquer les fragments non modifies
			$modif = true;
			for ($i = $id_version - 1; $i >= $version_min; $i--) {
				if (isset($fragment[$i])) {
					$modif = ($fragment[$i] != $texte);
					break;
				}
			}
			if ($modif) $fragment[$id_version] = $texte;
		}
		
		// Preparer l'enregistrement du fragment
		$replaces[] = replace_fragment($id_article, $version_min, $id_version, $id_fragment, $fragment);
	}

	exec_replace_fragments($replaces);
}

//
// Supprimer tous les fragments d'un article lies a un intervalle de versions
// (essaie d'eviter une trop grande fragmentation)
//
function supprimer_fragments($id_article, $version_debut, $version_fin) {
	global $flag_gz, $agregation_versions;

	$replaces = array();
	$deletes = array();

	// D'abord, vider les fragments inutiles
	$query = "DELETE FROM spip_versions_fragments WHERE id_article=$id_article ".
		"AND version_min>=$version_debut AND version_max<=$version_fin";
	spip_query($query);

	// Fragments chevauchant l'ensemble de l'intervalle, s'ils existent
	$query = "SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments ".
		"WHERE id_article=$id_article AND version_min<$version_debut AND version_max>$version_fin";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_fin; $i >= $version_debut; $i--) {
			if (isset($fragment[$i])) {
				// Recopier le dernier fragment si implicite
				if (!isset($fragment[$version_fin + 1]))
					$fragment[$version_fin + 1] = $fragment[$i];
				unset($fragment[$i]);
			}
		}

		$replaces[] = replace_fragment($id_article,
			$row['version_min'], $row['version_max'], $id_fragment, $fragment);
	}

	// Fragments chevauchant le debut de l'intervalle, s'ils existent
	$query = "SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments ".
		"WHERE id_article=$id_article AND version_min<$version_debut ".
		"AND version_max>=$version_debut AND version_max<=$version_fin";
	$result = spip_query($query);

	$deb_fragment = array();
	while ($row = spip_fetch_array($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		$version_min = $row['version_min'];
		$version_max = $row['version_max'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_debut; $i <= $version_max; $i++) {
			if (isset($fragment[$i])) unset($fragment[$i]);
		}

		// Stocker temporairement le fragment pour agregation
		$deb_fragment[$id_fragment] = $fragment;
		// Ajuster l'intervalle des versions
		$deb_version_min[$id_fragment] = $version_min;
		$deb_version_max[$id_fragment] = $version_debut - 1;
	}

	// Fragments chevauchant la fin de l'intervalle, s'ils existent
	$query = "SELECT id_fragment, compress, fragment, version_min, version_max FROM spip_versions_fragments ".
		"WHERE id_article=$id_article AND version_max>$version_fin ".
		"AND version_min>=$version_debut AND version_min<=$version_fin";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$id_fragment = $row['id_fragment'];
		$fragment = $row['fragment'];
		$version_min = $row['version_min'];
		$version_max = $row['version_max'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $version_fin; $i >= $version_min; $i--) {
			if (isset($fragment[$i])) {
				// Recopier le dernier fragment si implicite
				if (!isset($fragment[$version_fin + 1]))
					$fragment[$version_fin + 1] = $fragment[$i];
				unset($fragment[$i]);
			}
		}

		// Virer l'ancien enregistrement (la cle primaire va changer)
		$deletes[] = "id_fragment=$id_fragment AND version_min=$version_min";
		// Essayer l'agregation
		$agreger = false;
		if (isset($deb_fragment[$id_fragment])) {
			$agreger = (count($deb_fragment[$id_fragment]) + count($fragment) <= $agregation_versions);
			if ($agreger) {
				$fragment = $deb_fragment[$id_fragment] + $fragment;
				$version_min = $deb_version_min[$id_fragment];
			}
			else {
				$replaces[] = replace_fragment($id_article,
					$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
					$id_fragment, $deb_fragment[$id_fragment]);
			}
			unset($deb_fragment[$id_fragment]);
		}
		if (!$agreger) {
			// Ajuster l'intervalle des versions
			$version_min = $version_fin + 1;
		}
		$replaces[] = replace_fragment($id_article, $version_min, $version_max, $id_fragment, $fragment);
	}

	// Ajouter fragments restants
	if (is_array($deb_fragment) && count($deb_fragment) > 0) {
		foreach ($deb_fragment as $id_fragment => $fragment) {
			$replaces[] = replace_fragment($id_article,
				$deb_version_min[$id_fragment], $deb_version_max[$id_fragment],
				$id_fragment, $deb_fragment[$id_fragment]);
		}
	}
	
	exec_replace_fragments($replaces);
	exec_delete_fragments($id_article, $deletes);
}

//
// Recuperer les fragments d'une version donnee
// renvoie un tableau associatif (id_fragment => texte)
//
function recuperer_fragments($id_article, $id_version) {
	$fragments = array();

	if ($id_version == 0) return array();

	$query = "SELECT id_fragment, version_min, compress, fragment FROM spip_versions_fragments ".
		"WHERE id_article=$id_article AND version_min<=$id_version AND version_max>=$id_version";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$id_fragment = $row['id_fragment'];
		$version_min = $row['version_min'];
		$fragment = $row['fragment'];
		if ($row['compress'] > 0) $fragment = gzuncompress($fragment);
		$fragment = unserialize($fragment);
		for ($i = $id_version; $i >= $version_min; $i--) {
			if (isset($fragment[$i])) {
				$fragments[$id_fragment] = $fragment[$i];
				break;
			}
		}
	}
	return $fragments;
}


//
// Apparier des paragraphes deux a deux entre une version originale
// et une version modifiee
//
function apparier_paras($src, $dest, $flou = true) {
	$src_dest = array();
	$dest_src = array();
	
	$t1 = $t2 = array();

	$md1 = $md2 = array();
	$gz_min1 = $gz_min2 = array();
	$gz_trans1 = $gz_trans2 = array();
	$l1 = $l2 = array();

	// Nettoyage de la ponctuation pour faciliter l'appariement
	foreach($src as $key => $val) {
		$t1[$key] = preg_replace("/[[:punct:][:space:]]+/", " ", $val);
	}
	foreach($dest as $key => $val) {
		$t2[$key] = preg_replace("/[[:punct:][:space:]]+/", " ", $val);
	}

	// Premiere passe : chercher les correspondance exactes
	foreach($t1 as $key => $val) $md1[$key] = md5($val);
	foreach($t2 as $key => $val) $md2[md5($val)][$key] = $key;
	foreach($md1 as $key1 => $h) {
		if (count($md2[$h])) {
			$key2 = reset($md2[$h]);
			if ($t1[$key1] == $t2[$key2]) {
				$src_dest[$key1] = $key2;
				$dest_src[$key2] = $key1;
				unset($t1[$key1]);
				unset($t2[$key2]);
				unset($md2[$h][$key2]);
			}
		}
	}

	if ($flou) {
		// Deuxieme passe : recherche de correlation par test de compressibilite
		foreach($t1 as $key => $val) {
			$l1[$key] = strlen(gzcompress($val));
		}
		foreach($t2 as $key => $val) {
			$l2[$key] = strlen(gzcompress($val));
		}
		foreach($t1 as $key1 => $s1) {
			foreach($t2 as $key2 => $s2) {
				$r = strlen(gzcompress($s1.$s2));
				$taux = 1.0 * $r / ($l1[$key1] + $l2[$key2]);
				if (!$gz_min1[$key1] || $gz_min1[$key1] > $taux) {
					$gz_min1[$key1] = $taux;
					$gz_trans1[$key1] = $key2;
				}
				if (!$gz_min2[$key2] || $gz_min2[$key2] > $taux) {
					$gz_min2[$key2] = $taux;
					$gz_trans2[$key2] = $key1;
				}
			}
		}
		
		// Depouiller les resultats de la deuxieme passe :
		// ne retenir que les correlations reciproques
		foreach($gz_trans1 as $key1 => $key2) {
			if ($gz_trans2[$key2] == $key1 && $gz_min1[$key1] < 0.9) {
				$src_dest[$key1] = $key2;
				$dest_src[$key2] = $key1;
			}
		}
	}

	// Retourner les mappings
	return array($src_dest, $dest_src);
}

//
// Recuperer les champs d'une version donnee
//
function recuperer_version($id_article, $id_version) {
	$query = "SELECT champs FROM spip_versions ".
		"WHERE id_article=$id_article AND id_version=$id_version";
	$result = spip_query($query);
	
	if (!($row = spip_fetch_array($result))) return false;

	$fragments = recuperer_fragments($id_article, $id_version);
	$champs = unserialize($row['champs']);
	$textes = array();
	foreach ($champs as $nom_champ => $code) {
		$textes[$nom_champ] = "";
		$code = explode(' ', $code);
		foreach ($code as $id_fragment) {
			$textes[$nom_champ] .= $fragments[$id_fragment];
		}
	}
	return $textes;
}

function supprimer_versions($id_article, $version_min, $version_max) {
	$query = "DELETE FROM spip_versions WHERE id_article=$id_article ".
		"AND id_version>=$version_min AND id_version<=$version_max";
	spip_query($query);
	supprimer_fragments($id_article, $version_min, $version_max);
}

//
// Ajouter une version a un article
//
function ajouter_version($id_article, $champs, $titre_version = "", $id_auteur) {

	// Eviter les validations entremelees
	$lock = "ajout_version $id_article";
	spip_get_lock($lock, 10);
	
	// Examiner la derniere version
	$query = "SELECT id_version, (id_auteur=$id_auteur AND date > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND permanent!='oui') AS flag ".
		"FROM spip_versions WHERE id_article=$id_article ".
		"ORDER BY id_version DESC LIMIT 0,1";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$nouveau = !$row['flag'];
		$id_version = $row['id_version'];
		if ($nouveau) $id_version_new = $id_version + 1;
		else $id_version_new = $id_version;
	}
	else {
		$nouveau = true;
		$id_version_new = 1;
	}
	$query = "SELECT id_fragment FROM spip_versions_fragments ".
		"WHERE id_article=$id_article ORDER BY id_fragment DESC LIMIT 0,1";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result))
		$id_fragment_next = $row['id_fragment'] + 1;
	else
		$id_fragment_next = 1;

	// Generer les nouveaux fragments
	$fragments = array();
	$paras_old = recuperer_fragments($id_article, $id_version);
	$paras_new = $paras_champ = array();
	foreach ($champs as $nom_champ => $texte) {
		$codes[$nom_champ] = array();
		$paras_new = separer_paras($texte, $paras_new);
		$paras_champ[$nom_champ] = count($paras_new);
	}

	// Apparier les fragments de maniere optimale
	$n = count($paras_new);
	if ($n) {
		// Tables d'appariement dans les deux sens
		list($trans, $trans_rev) = apparier_paras($paras_old, $paras_new);
		reset($champs);
		$nom_champ = '';
		for ($i = 0; $i < $n; $i++) {
			while ($i >= $paras_champ[$nom_champ]) list($nom_champ, ) = each($champs);
			// Lier au fragment existant si possible, sinon creer un nouveau fragment
			if (isset($trans_rev[$i])) $id_fragment = $trans_rev[$i];
			else $id_fragment = $id_fragment_next++;
			$codes[$nom_champ][] = $id_fragment;
			$fragments[$id_fragment] = $paras_new[$i];
		}
	}
	foreach ($champs as $nom_champ => $t) {
		$codes[$nom_champ] = join(' ', $codes[$nom_champ]);
		if (!strlen($codes[$nom_champ])) unset($codes[$nom_champ]);
	}

	// Enregistrer les modifications
	ajouter_fragments($id_article, $id_version_new, $fragments);
	if (!$codes) $codes = array();
	$codes = addslashes(serialize($codes));
	$permanent = empty($titre_version) ? 'non' : 'oui';
	$titre_version = addslashes($titre_version);
	if ($nouveau) {
		$query = "INSERT spip_versions (id_article, id_version, titre_version, permanent, date, id_auteur, champs) ".
			"VALUES ($id_article, $id_version_new, '$titre_version', '$permanent', NOW(), '$id_auteur', '$codes')";
		spip_query($query);
	}
	else {
		$query = "UPDATE spip_versions SET date=NOW(), id_auteur=$id_auteur, champs='$codes', ".
			"permanent='$permanent', titre_version='$titre_version' ".
		 	"WHERE id_article=$id_article AND id_version=$id_version";
		spip_query($query);
	}
	$query = "UPDATE spip_articles SET id_version=$id_version_new WHERE id_article=$id_article";
	spip_query($query);

	spip_release_lock($lock);

	return $id_version_new;
}

// les textes "diff" ne peuvent pas passer dans propre directement,
// car ils contiennent des <span> et <div> parfois mal places
function propre_diff($texte) {

	$span_diff = array();
	if (preg_match_all(',</?(span|div) (class|rem)="diff-[^>]+>,', $texte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $c => $reg)
			$texte = str_replace($reg[0], '@@@SPIP_DIFF'.$c.'@@@', $texte);
	}

	// [ ...<span diff> -> lien ]
	// < tag <span diff> >
	$texte = preg_replace(',<([^>]*@@@SPIP_DIFF[0-9]+@@@),',
		'&lt;\1', $texte);
	# attention ici astuce seulement deux @@ finals car on doit eviter
	# deux patterns a suivre, afin de pouvoir prendre [ mais eviter [[
	$texte = preg_replace(',(^|[^[])[[]([^[\]]*@@@SPIP_DIFF[0-9]+@@),',
		'\1&#91;\2', $texte);

	$texte = propre($texte);

	// un blockquote mal ferme peut gener l'affichage, et title plante safari
	$texte = preg_replace(',<(/?(blockquote|title)[^>]*)>,i', '&lt;\1>', $texte);

	// Dans les <cadre> c'est un peu plus complique
	if (preg_match_all(',<textarea (.*)</textarea>,Uims', $texte, $area, PREG_SET_ORDER)) {
		foreach ($area as $reg) {
			$remplace = preg_replace(',@@@SPIP_DIFF[0-9]+@@@,', '**', $reg[0]);
			if ($remplace <> $reg[0])
				$texte = str_replace($reg[0], $remplace, $texte);
		}
	}

	// replacer les valeurs des <span> et <div> diff-
	if (is_array($regs))
	foreach ($regs as $c => $reg) {
		$texte = str_replace('@@@SPIP_DIFF'.$c.'@@@', $reg[0], $texte);
		$GLOBALS['les_notes'] = str_replace('@@@SPIP_DIFF'.$c.'@@@', $reg[0], $GLOBALS['les_notes']);
	}

	return $texte;
}

?>
