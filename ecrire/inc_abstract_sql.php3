<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ABSTRACT_SQL")) return;
define("_INC_ABSTRACT_SQL", "1");

// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par spip_abstract_fetch.
// Recoit en argument:
// - le tableau des champs a` ramener
// - le tableau des tables a` consulter
// - le tableau des conditions a` remplir
// - le crite`re de regroupement
// - le crite`re de classement
// - le crite`re de limite
// - une sous-requete e'ventuelle (MySQL > 4.1)
// - un compteur de sous-requete
// - le nom de la table
// - le nom de la boucle (pour le message d'erreur e'ventuel)
// - le serveur sollicite

function spip_abstract_select (
	$select = array(), $from = array(), $where = '',
	$groupby = '', $orderby = '', $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {

	if (!$serveur)
	  // le serveur par defaut est celui de inc_connect.php
	  // tout est deja pret, notamment la fonction suivante:
	  $f = 'spip_mysql_select';
	else {
	  // c'est un autre; est-il deja charge ?
		$f = 'spip_' . $serveur . '_select';
		if (!function_exists($f)) {
		  // non, il est decrit dans le fichier ad hoc
			$d = 'inc_connect-' . $serveur .'.php3';
			if (@file_exists('ecrire/' . $d))
				include_ecrire($d);
			$f = spip_abstract_serveur($f, $serveur);
		}
	}
	return $f($select, $from, $where,
		  $groupby, $orderby, $limit,
		  $sousrequete, $cpt,
		  $table, $id, $serveur);
}

function spip_abstract_serveur($f, $serveur) {
	if (function_exists($f))
		return $f;

	erreur_squelette(_L(' serveur SQL indefini'), $serveur);

	// hack pour continuer la chasse aux erreurs
	return 'array';
}

// Les 3 fonctions suivantes exploitent le resultat de la precedente,
// si l'include ne les a pas definies, erreur immediate

function spip_abstract_fetch($res, $serveur='')
{
  if (!$serveur) return spip_fetch_array($res);
  $f = spip_abstract_serveur('spip_' . $serveur . '_fetch', $serveur);
  return $f($res);
}

function spip_abstract_count($res, $serveur='')
{
  if (!$serveur) return spip_num_rows($res);
  $f = spip_abstract_serveur('spip_' . $serveur . '_count', $serveur);
  return $f($res);
}

function spip_abstract_free($res, $serveur='')
{
  if (!$serveur) return spip_free_result($res);
  $f = spip_abstract_serveur('spip_' . $serveur . '_free', $serveur);
  return $f($res);
}

function spip_abstract_insert($table, $noms, $valeurs, $serveur='')
{
  $f = (!$serveur ? 'spip_insert' :
	spip_abstract_serveur('spip_' . $serveur . '_insert', $serveur));
  return $f($table, $noms, $valeurs);
}

# une composition tellement frequente...
function spip_abstract_fetsel(
	$select = array(), $from = array(), $where = '',
	$groupby = '', $orderby = '', $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {
	return spip_abstract_fetch(spip_abstract_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $cpt, $table, $id, $serveur),
				   $serveur);
}
?>
