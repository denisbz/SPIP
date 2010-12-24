<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include_spip('iterateur/data');


//
// creer une boucle sur un iterateur POUR
// annonce au compilo les "champs" disponibles
//
function iterateur_CONDITION_dist($b) {
	$b->iterateur = 'CONDITION'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array()
	);
	return $b;
}

include_spip('iterateur/data');
class IterateurCONDITION extends IterateurData {
	protected function select() {
		$this->tableau = array(0=>1);
	}
}
