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



// fonctions de recherche et de reservation
// dans l'arborescence des boucles

if (!defined("_ECRIRE_INC_VERSION")) return;

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

function index_pile($idb, $nom_champ, &$boucles, $explicite='') {
  global $exceptions_des_tables, $table_des_tables, $tables_des_serveurs_sql;

	$i = 0;
	if (strlen($explicite)) {
	// Recherche d'un champ dans un etage superieur
	  while (($idb != $explicite) && ($idb !='')) {
#		spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$nom_champ = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while ($boucles[$idb]) {
		list ($t, $c) = index_tables_en_pile($idb, $nom_champ, $boucles);
		if ($t) {
		  if (!in_array($t, $boucles[$idb]->select))
		    $boucles[$idb]->select[] = $t;
		  return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
		}
#		spip_log("On remonte vers $i");
		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	return('$Pile[0][\''. strtolower($nom_champ) . '\']');
}

function index_tables_en_pile($idb, $nom_champ, &$boucles)
{
	global $exceptions_des_tables, $table_des_tables, $tables_des_serveurs_sql;
	$r = $boucles[$idb]->type_requete;
	$s = $boucles[$idb]->sql_serveur;
	if (!$s) 
		{ $s = 'localhost';
    // indirection (pour les rares cas ou le nom de la table!=type)
		    $t = $table_des_tables[$r];
		  }
		// pour les tables non Spip
	if (!$t) {$nom_table = $t = $r; } else $nom_table = 'spip_' . $t;

	$desc = $tables_des_serveurs_sql[$s][$nom_table];
#		spip_log("Go: idb='$idb' r='$r' nom='$nom_champ' s=$s t=$t desc=" . array_keys($desc));

	if (!isset($desc['field'])) {
		$desc = $table_des_tables[$r] ?  (($GLOBALS['table_prefix'] ? $GLOBALS['table_prefix'] : 'spip') . '_' . $t) : $nom_table;

		$desc = spip_abstract_showtable($desc, $boucles[$idb]->sql_serveur);
		if (!isset($desc['field'])) {
			erreur_squelette(_T('zbug_table_inconnue', array('table' => $r)),
					   "'$idb'");
# continuer pour chercher l'erreur suivante
			return  array("'#" . $r . ':' . $nom_champ . "'",'');
		}
		$tables_des_serveurs_sql[$s][$nom_table] = $desc;
	}
	
	$excep = $exceptions_des_tables[$r][$nom_champ];
	if ($excep) {
			// entite SPIP alias d'un champ SQL
		if (!is_array($excep)) {
			$e = $excep;
			$c = $excep;
		} 
			// entite SPIP alias d'un champ dans une jointure
		else {
			if (!$t = array_search($excep[0], $boucles[$idb]->from)) {
			    $t = 'J' . count($boucles[$idb]->from);
			    $boucles[$idb]->from[$t] = $excep[0];
			}
			$e = $excep[1];
			if ($e != $nom_champ) $e .= ' AS '.$c;
		}
		return array("$t.$e", $c);

	} else {
		if ($desc['field'][$nom_champ])
			return array("$t.$nom_champ", $nom_champ);
		else {
		  if ($boucles[$idb]->jointures_explicites) {
		    $t = trouver_champ_exterieur($nom_champ, 
						 $boucles[$idb]->jointures,
						 $boucles[$idb]);
		    if ($t) $t = array_search($t[0], $boucles[$idb]->from);
		    if ($t) return array($t .'.' . $nom_champ, $nom_champ);
		  }
		  return array('','');
		}
	}
}

// cette fonction sert d'API pour demander le champ '$champ' dans la pile
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
}

// cette fonction sert d'API pour demander une balise Spip avec filtres

function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);
	return applique_filtres($p);
}

// Cette fonction sert d'API pour demander une balise SPIP sans filtres.
// Pour une balise nommmee NOM, elle essaye successivement d'appeler
// des fonctions balise_NOM ou balise_NOM_dist, de faire le calcul via
// un fichier inc-nom, de traiter le cas d'une balise LOGO ou de voir
// si c'est une reference a une colonne de table connue
// Pour chacune des premieres etapes, si la fonction existe mais qu'elle
// retourne NULL, c'est qu'elle n'a pas traite la balise => on passe a la suite
// comme si on n'avait rien trouve. Cela permet de ne surcharger une balise
// que dans des cas precis.
function calculer_balise($nom, $p) {

	// regarder s'il existe une fonction personnalisee balise_NOM()
	$f = 'balise_' . $nom;
	if (function_exists($f)) {
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	// regarder s'il existe une fonction standard balise_NOM_dist()
	$f = 'balise_' . $nom . '_dist';
	if (function_exists($f)) {
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	// regarder s'il existe un fichier d'inclusion au nom de la balise
	// contenant une fonction balise_NOM_collecte
	$file = 'inc-' . strtolower($nom) . _EXTENSION_PHP;
	if ($file = find_in_path($file)) {
		include_local($file);
		# une globale ?? defined ou function_exists(..._dyn) serait mieux ?
		$f = $GLOBALS['balise_' . $nom . '_collecte'];
		if (is_array($f)) {
			$res = calculer_balise_dynamique($p, $nom, $f);
			if ($res !== NULL)
				return $res;
		}
	}

	// S'agit-il d'un logo ? Une fonction speciale les traite tous
	if (ereg('^LOGO_', $nom)) {
		$res = calculer_balise_logo($p);
		if ($res !== NULL)
			return $res;
	}

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	// ne pas passer le filtre securite sur les id_xxx
	if (strpos($nom, 'ID_') === 0)
		$p->interdire_scripts = false;

	// Compatibilite ascendante avec les couleurs html (#FEFEFE) :
	// SI le champ SQL n'est pas trouve
	// ET si la balise a une forme de couleur
	// ET s'il n'y a ni filtre ni etoile
	// ALORS retourner la couleur.
	// Ca permet si l'on veut vraiment de recuperer [(#ACCEDE*)]
	if (preg_match("/^[A-F]{1,6}$/i", $nom)
	AND !$p->etoile
	AND !$p->fonctions) {
		$p->code = "'#$nom'";
		$p->interdire_scripts = false;
	}

	return $p;
}

//
// Traduction des balises dynamiques, notamment les "formulaire_*"
// Inclusion du fichier associe a son nom.
// Ca donne les arguments a chercher dans la pile,on compile leur localisation
// Ensuite on delegue a une fonction generale definie dans inc-calcul-outils
// qui recevra a l'execution la valeur des arguments, 
// ainsi que les pseudo filtres qui ne sont donc pas traites a la compil
// mais on traite le vrai parametre si present.

function calculer_balise_dynamique($p, $nom, $l) {
	balise_distante_interdite($p);
	$param = "";
	if ($a = $p->param) {
		$c = array_shift($a);
		if  (!array_shift($c)) {
		  $p->fonctions = $a;
		  array_shift( $p->param );
		  $param = compose_filtres_args($p, $c, ',');
		}
	}
	$collecte = join(',',collecter_balise_dynamique($l, $p));
	$p->code = "executer_balise_dynamique('" . $nom . "',\n\tarray("
	  . $collecte
	  . ($collecte ? $param : substr($param,1)) # virer la virgule
	  . "),\n\tarray("
	  . argumenter_balise($p->param, "', '")
	  . "), \$GLOBALS['spip_lang'],"
	  . $p->ligne
	  . ')';
	$p->interdire_scripts = false;
	$p->fonctions = array();
	$p->param = array();

	// Cas particulier de #FORMULAIRE_FORUM : inserer l'invalideur
	if ($nom == 'FORMULAIRE_FORUM')
		$p->code = code_invalideur_forums($p, $p->code);

	return $p;
}

// construire un tableau des valeurs interessant un formulaire

function collecter_balise_dynamique($l, $p) {
	$args = array();
	foreach($l as $c) { $x = calculer_balise($c, $p); $args[] = $x->code;}
	return $args;
}


// il faudrait savoir traiter les formulaires en local
// tout en appelant le serveur SQL distant.
// En attendant, cette fonction permet de refuser une authentification
// sur qqch qui n'a rien a voir.

function balise_distante_interdite($p) {
	$nom = $p->id_boucle;
	if ($p->boucles[$nom]->sql_serveur) {
		erreur_squelette($p->nom_champ .' '._T('zbug_distant_interdit'), $nom);
	}
}


//
// Traitements standard de divers champs
// definis par $table_des_traitements, cf. inc-compilo-api.php3
//
function champs_traitements ($p) {
	global $table_des_traitements;

	if (!is_array($table_des_traitements[$p->nom_champ]))
	  // old style
		$ps = $table_des_traitements[$p->nom_champ];
	else {
		if ($p->nom_boucle)
			$type = $p->boucles[$p->nom_boucle]->type_requete;
		else
			$type = $p->type_requete;
		$ps = $table_des_traitements[$p->nom_champ][$type];
		if (!$ps)
			$ps = $table_des_traitements[$p->nom_champ][0];
	}

	if (!$ps) return $p->code;

	// Si une boucle sous-jacente (?) traite les documents, on insere ici
	// une fonction de remplissage du tableau des doublons -- mais seulement
	// si on rencontre le filtre propre (qui traite les
	// raccourcis <docXX> qui nous interessent)
	if ($p->descr['documents']
	AND preg_match(',propre,', $ps))
		$ps = 'traiter_doublons_documents($doublons, '.$ps.')';

	// De meme, en cas de sql_serveur, on supprime les < IMGnnn > tant
	// qu'on ne rapatrie pas les documents distants joints..
	// il faudrait aussi corriger les raccourcis d'URL locales
	if ($p->boucles[$p->id_boucle]->sql_serveur)
		$p->code = 'supprime_img(' . $p->code . ')';


	// Passer |safehtml sur les boucles "sensibles"
	switch ($p->type_requete) {
		case 'forums':
		case 'signatures':
		case 'syndic_articles':
			$ps = 'safehtml('.$ps.')';
			break;
		default:
			break;
	}

	// Remplacer enfin le placeholder %s par le vrai code de la balise
	return str_replace('%s', $p->code, $ps);
}


//
// Appliquer les filtres a un champ [(#CHAMP|filtre1|filtre2)]
// retourne un code php compile exprimant ce champ filtre et securise
//  - une etoile => pas de processeurs standards
//  - deux etoiles => pas de securite non plus !
//
function applique_filtres($p) {

	// Traitements standards (cf. supra)
	if ($p->etoile == '')
		$code = champs_traitements($p);
	else
		$code = $p->code;

	// Appliquer les filtres perso
	if ($p->param)
		$code = compose_filtres($p, $code);

	// Securite
	if ($p->interdire_scripts
	AND $p->etoile != '**')
		$code = "interdire_scripts($code)";

	return $code;
}

function compose_filtres($p, $code) {
	foreach($p->param as $filtre) {
		$fonc = array_shift($filtre);
		if ($fonc) {
			$arglist = compose_filtres_args($p, $filtre,
				($fonc == '?' ? ':' : ','));

			// le filtre existe sous forme de fonction ou de methode
			if (function_exists($fonc)
				OR (preg_match("/^(\w*)::(\w*)$/", $fonc, $regs)                            
					AND is_callable(array($regs[1], $regs[2]))
			))
				$code = "$fonc($code$arglist)";
			// est-ce un test ?
			else if (strpos("x < > <= >= == === != !== <> ? ", " $fonc "))
				$code = "($code $fonc " . substr($arglist,1) . ')';
			else
				$code = "erreur_squelette('"
				.texte_script(_T('zbug_erreur_filtre', array('filtre'=>$fonc)))
				."','" . $p->id_boucle . "')";
		}
	}
	return $code;
}

function compose_filtres_args($p, $args, $sep)
{
	$arglist = "";
	foreach ($args as $arg) {
		$arglist .= $sep . 
		  calculer_liste($arg, $p->descr, $p->boucles, $p->id_boucle);
	}
	return $arglist;
}

//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe)
		index_pile ($idb, $nom_champ, $boucles); 
	// retourner $Pile[$SP] et pas $Pile[0] (bug recursion en 1ere boucle)
	$prec = $boucles[$idb]->id_parent;
	return (($prec==="") ? ('$Pile[$SP][\''.$nom_champ.'\']') : 
		index_pile($prec, $nom_champ, $boucles));
}

// a documenter svp !!
//
// en gros : ca ne sert que pour la balise #POINTS ; l'idee est que,
// si on trouve le motif "recherche" dans les parametres de la boucle,
// alors on prend $Pile[$SP]['points'] (qui vient du "SELECT XXXX AS points"
// de la requete qu'on est en train de composer)
function rindex_pile($p, $champ, $motif) 
{
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
	if ($s = $p->boucles[$b]->param) {
	  foreach($s as $v) {
		if (strpos($v[1][0]->texte,$motif) !== false) {
		  $p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
			"]['$champ']";
		  $b = '';
		  break;
		}
	  }
	}
	$n++;
	$b = $p->boucles[$b]->id_parent;
	}
	if (!$p->code) {
		erreur_squelette(_T('zbug_champ_hors_motif',
			array('champ' => '#' . strtoupper($champ),
				'motif' => $motif)
		), $p->id_boucle);
	}
	$p->interdire_scripts = false;
	return $p;
}

?>
