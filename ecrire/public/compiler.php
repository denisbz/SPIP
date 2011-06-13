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


//
// Fichier principal du compilateur de squelettes
//

if (!defined('_ECRIRE_INC_VERSION')) return;

// reperer un code ne calculant rien, meme avec commentaire
define('CODE_MONOTONE', ",^(\n//[^\n]*\n)?\(?'([^'])*'\)?$,");
// s'il faut commenter le code produit
define('CODE_COMMENTE', true);

// definition des structures de donnees
include_spip('public/interfaces');

// Definition de la structure $p, et fonctions de recherche et de reservation
// dans l'arborescence des boucles
include_spip('public/references');

// production du code qui peut etre securisee
include_spip('public/sandbox');

// definition des boucles
include_spip('public/boucles');

// definition des criteres
include_spip('public/criteres');

// definition des balises
include_spip('public/balises');

// Gestion des jointures
include_spip('public/jointures');

// Les 2 ecritures INCLURE{A1,A2,A3...} et INCLURE(A1){A2}{A3}... sont admises
// Preferer la premiere.
// Les Ai sont de la forme Vi=Ei ou bien Vi qui veut alors dire Vi=Vi
// Le resultat est un tableau indexe par les Vi
// Toutefois, si le premier argument n'est pas de la forme Vi=Ei
// il est conventionnellement la valeur de l'index 1.
// pour la balise #INCLURE
// mais pas pour <INCLURE> dont le fond est defini explicitement.


// http://doc.spip.org/@argumenter_inclure
function argumenter_inclure($params, $rejet_filtres, $p, &$boucles, $id_boucle, $echap=true, $lang = '', $fond1=false){
	$l = array();
	$erreur_p_i_i = '';
	if (!is_array($params)) return $l;
	foreach($params as $k => $couple) {
	// la liste d'arguments d'inclusion peut se terminer par un filtre
		$filtre = array_shift($couple);
		if ($filtre) break;
		foreach($couple as $n => $val) {
			$var = $val[0];
			if ($var->type != 'texte') {
			  if ($n OR $k OR $fond1) {
				$erreur_p_i_i = array('zbug_parametres_inclus_incorrects',
					 array('param' => $var->nom_champ));
				erreur_squelette($erreur_p_i_i, $p);
			  } else $l[1] = calculer_liste($val, $p->descr, $boucles, $id_boucle);
			  break;
			} else {
				preg_match(",^([^=]*)(=?)(.*)$,", $var->texte,$m);
				$var = $m[1];
				$auto = false;;
				if ($m[2]) {
				  $v = $m[3];
				  if (preg_match(',^[\'"](.*)[\'"]$,', $v, $m)) $v = $m[1];
				  $val[0] = new Texte;
				  $val[0]->texte = $v;
				} elseif ($k OR $n OR $fond1) {
				  $auto = true;
				} else $var = 1;

				if ($var == 'lang') {
				  $lang = !$auto 
				    ? calculer_liste($val, $p->descr, $boucles, $id_boucle)
				    : '$GLOBALS["spip_lang"]';
				} else {
				  $val = $auto
				    ? index_pile($id_boucle, $var, $boucles)
				    : calculer_liste($val, $p->descr, $boucles, $id_boucle);
				  if ($var !== 1)
				    $val = ($echap?"\'$var\' => ' . argumenter_squelette(":"'$var' => ")
				    . $val . ($echap? ") . '":" ");
				  else $val = $echap ? "'.$val.'" : $val;
				  $l[$var] = $val;
				}
			}
		}
	}
	if ($erreur_p_i_i) return false;
	// Cas particulier de la langue : si {lang=xx} est definie, on
	// la passe, sinon on passe la langue courante au moment du calcul
	// sauf si on n'en veut pas 
	if ($lang === false) return $l;
	if (!$lang) $lang = '$GLOBALS["spip_lang"]';
	$l['lang'] = ($echap?"\'lang\' => ' . argumenter_squelette(":"'lang' => ")  . $lang . ($echap?") . '":" ");

	return $l;
}

//
// Calculer un <INCLURE()>
// code pour un squelette (aussi pour #INCLURE, #MODELE #LES_AUTEURS)
define('CODE_RECUPERER_FOND', 'recuperer_fond(%s, %s, array(%s), %s)');

// http://doc.spip.org/@calculer_inclure
function calculer_inclure($p, &$boucles, $id_boucle) {

	$_contexte = argumenter_inclure($p->param, false, $p, $boucles, $id_boucle, true, '', true);
	if (is_string($p->texte)) {
		$fichier = $p->texte;
		$code = "\"$fichier\"";

	} else {
		$code = calculer_liste($p->texte, $p->descr, $boucles, $id_boucle);
		if ($code AND preg_match("/^'([^']*)'/s", $code, $r))
			$fichier = $r[1];
		else $fichier = '';
	}
	if (!$code OR $code === '""') {
		$erreur_p_i_i = array('zbug_parametres_inclus_incorrects',
					 array('param' => $code));
		erreur_squelette($erreur_p_i_i, $p);
		return false;
	}
	$compil = texte_script(memoriser_contexte_compil($p));

	if (is_array($_contexte)) {
		// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
		if ($env = (isset($_contexte['env'])|| isset($_contexte['self']))) {
			unset($_contexte['env']);
		}

		// noter les doublons dans l'appel a public.php
		if (isset($_contexte['doublons'])) {
			$_contexte['doublons'] = "\\'doublons\\' => '.var_export(\$doublons,true).'";
		}

		if ($ajax = isset($_contexte['ajax'])){
			$ajax = preg_replace(",=>(.*)$,ims",'=> ($v=(\\1))?$v:true',$_contexte['ajax']);
			unset($_contexte['ajax']);
		}

		$_contexte = join(",\n\t", $_contexte);
	}
	else
		return false; // j'aurais voulu toucher le fond ...
		
	$contexte = 'array(' . $_contexte  .')';

	if ($env) {
		$contexte = "array_merge('.var_export(\$Pile[0],1).',$contexte)";
	}

	// s'il y a une extension .php, ce n'est pas un squelette
	if (preg_match('/^.+[.]php$/s', $fichier)) {
		$code = sandbox_composer_inclure_php($fichier, $p);
	} else 	{
		$_options[] = "\"compil\"=>array($compil)";
		if ($ajax)
			$_options[] = $ajax;
		$code = " ' . argumenter_squelette($code) . '"; 
		$code = "echo " . sprintf(CODE_RECUPERER_FOND, $code, $contexte, implode(',',$_options), "_request(\"connect\")") . ';';
	}

	return	"\n'<'.'".	"?php ". $code . "\n?'." . "'>'";
}


function instituer_boucle(&$boucle, $echapper=true){
	// gerer les statuts si declares pour cette table
	/*
	$show['statut'][] = array(
		'champ'=>'statut',  // champ de la table sur lequel porte le filtrage par le statut
		'publie'=>'publie', // valeur ou liste de valeurs, qui definissent l'objet comme publie.
		'previsu'=>'publie,prop', // valeur ou liste de valeurs qui sont visibles en previsu
		'post_date'=>'date', // un champ de date pour la prise en compte des post_dates, ou rien sinon
	  'exception'=>'statut', // liste des modificateurs qui annulent le filtrage par statut
	                         // si plusieurs valeurs : array('statut','tout','lien')
	);

	Pour 'publier' ou 'previsu', si la chaine commence par un "!" on exclu au lieu de filtrer sur les valeurs donnees
	si la chaine est vide, on ne garde rien si elle est seulement "!" on n'exclu rien

	Si le statut repose sur une jointure, 'champ' est alors un tableau du format suivant :
	'champ'=>array(
	    array(table1, cle1),
	    ...
	    array(tablen, clen),
	    champstatut
	 )

	champstatut est alors le champ statut sur la tablen
	dans les jointures, clen peut etre un tableau pour une jointure complexe : array('id_objet','id_article','objet','article')
	*/

	$id_table = $boucle->id_table;
	$show = $boucle->show;
	if (isset($show['statut']) AND $show['statut']){
		foreach($show['statut'] as $k=>$s){
			// Restreindre aux elements publies si pas de {statut} ou autre dans les criteres
			$filtrer = true;
			if (isset($s['exception'])) {
				foreach(is_array($s['exception'])?$s['exception']:array($s['exception']) as $m) {
					if (isset($boucle->modificateur[$m]) OR isset($boucle->modificateur['criteres'][$m])) {
						$filtrer = false;
						break;
					}
				}
			}

			if ($filtrer) {
				if (is_array($s['champ'])){
					$statut = preg_replace(',\W,','',array_pop($s['champ'])); // securite
					$jointures = array();
					foreach($s['champ'] as $j) {
						$jointures[] = array('',array($id=reset($j)),end($j));
					}
					$jointures[0][0] = $id_table;
					if (!array_search($id, $boucle->from)){
						include_spip('public/jointures');
						fabrique_jointures($boucle, $jointures, true, $boucle->show, $id_table, '', $echapper);
					}
					// trouver l'alias de la table d'arrivee qui porte le statut
					$id = array_search($id, $boucle->from);
				}
				else {
					$id = $id_table;
					$statut = preg_replace(',\W,','',$s['champ']); // securite
				}
				$mstatut = $id .'.'.$statut;

				include_spip('public/quete');
				if (isset($s['post_date']) AND $s['post_date']
					AND $GLOBALS['meta']["post_dates"] == 'non'){
					$date = $id.'.'.preg_replace(',\W,','',$s['post_date']); // securite
					array_unshift($boucle->where,
						$echapper ?
							"\nquete_condition_postdates('$date',"._q($boucle->serveur).")"
						:
							quete_condition_postdates($date,$boucle->serveur)
					);
				}
				array_unshift($boucle->where,
					$echapper ?
						"\nquete_condition_statut('$mstatut',"
							. _q($s['previsu']).","
							._q($s['publie']).","
							._q($boucle->serveur).")"
					:
						quete_condition_statut($mstatut,$s['previsu'],$s['publie'],$boucle->serveur)
				);
			}
		}
	}
}

/**
 * calculer_boucle() produit le corps PHP d'une boucle Spip.
 * ce corps remplit une variable $t0 retournee en valeur.
 * Ici on distingue boucles recursives et boucle a requete SQL
 * et on insere le code d'envoi au debusqueur du resultat de la fonction.
 *
 * http://doc.spip.org/@calculer_boucle
 *
 * @param  $id_boucle
 * @param  $boucles
 * @return string
 */
function calculer_boucle($id_boucle, &$boucles) {

	$boucle = &$boucles[$id_boucle];
	instituer_boucle($boucle);

	$boucles[$id_boucle] = pipeline('post_boucle', $boucles[$id_boucle]);

	// en mode debug memoriser les premiers passages dans la boucle,
	// mais pas tous, sinon ca pete.
	if  (_request('var_mode_affiche') != 'resultat') 
		$trace = '';
	else {
		$trace = $boucles[$id_boucle]->descr['nom'] . $id_boucle;
		$trace = "if (count(@\$GLOBALS['debug_objets']['resultat']['$trace'])<3)
	    \$GLOBALS['debug_objets']['resultat']['$trace'][] = \$t0;";
	}
	return ($boucles[$id_boucle]->type_requete == TYPE_RECURSIF)
	? calculer_boucle_rec($id_boucle, $boucles, $trace) 
	: calculer_boucle_nonrec($id_boucle, $boucles, $trace);
}

// compil d'une boucle recursive. 
// il suffit (ET IL FAUT) sauvegarder les valeurs des arguments passes par
// reference, car par definition un tel passage ne les sauvegarde pas

// http://doc.spip.org/@calculer_boucle_rec
function calculer_boucle_rec($id_boucle, &$boucles, $trace) {
	$nom = $boucles[$id_boucle]->param[0];
	return "\n\t\$save_numrows = (\$Numrows['$nom']);"
	. "\n\t\$t0 = " . $boucles[$id_boucle]->return . ";"
	. "\n\t\$Numrows['$nom'] = (\$save_numrows);"
	. $trace
	. "\n\treturn \$t0;";
}

// Compilation d'une boucle non recursive. 
// Ci-dessous la constante donnant le cadre systematique du code:
// %s1: initialisation des arguments de calculer_select
// %s2: appel de calculer_select en donnant un contexte pour les cas d'erreur
// %s3: initialisation du sous-tableau Numrows[id_boucle]
// %s4: sauvegarde de la langue et calcul des invariants de boucle sur elle
// %s5: boucle while sql_fetch ou str_repeat si corps monotone
// %s6: restauration de la langue
// %s7: liberation de la ressource, en tenant compte du serveur SQL 
// %s8: code de trace eventuel avant le retour

define('CODE_CORPS_BOUCLE', '%s
	$t0 = "";
	// REQUETE
	$iter = IterFactory::create(
		"%s",
		%s,
		array(%s)
	);
	if (!$iter->err()) {
	%s%s$SP++;
	// RESULTATS
	%s
	%s$iter->free();
	}%s
	return $t0;'
);

// http://doc.spip.org/@calculer_boucle_nonrec
function calculer_boucle_nonrec($id_boucle, &$boucles, $trace) {

	$boucle = &$boucles[$id_boucle];
	$return = $boucle->return;
	$type_boucle = $boucle->type_requete;
	$primary = $boucle->primary;
	$constant = preg_match(CODE_MONOTONE, str_replace("\\'",'', $return));
	$flag_cpt = $boucle->mode_partie ||$boucle->cptrows;
	$corps = '';

	// faudrait expanser le foreach a la compil, car y en a souvent qu'un 
	// et puis faire un [] plutot qu'un "','."
	if ($boucle->doublons)
		$corps .= "\n\t\t\tforeach(" . $boucle->doublons . ' as $k) $doublons[$k] .= "," . ' .
		index_pile($id_boucle, $primary, $boucles)
		. "; // doublons\n";

	// La boucle doit-elle selectionner la langue ?
	// - par defaut, les boucles suivantes le font
	//    (sauf si forcer_lang==true ou si le titre contient <multi>).
	// - a moins d'une demande explicite via {!lang_select}
	if (!$constant && $boucle->lang_select != 'non' &&
	    (($boucle->lang_select == 'oui')  ||
		    in_array($type_boucle, array(
		    	'articles', 'rubriques', 'hierarchie', 'breves'
			)))
	) {
		// Memoriser la langue avant la boucle et la restituer apres
		// afin que le corps de boucle affecte la globale directement
		$init_lang = "lang_select(\$GLOBALS['spip_lang']);\n\t";
		$fin_lang = "lang_select();\n\t";

		$corps .= 
			"\n\t\tlang_select_public("
			. index_pile($id_boucle, 'lang', $boucles)
			. ", '".$boucle->lang_select."'"
			. (in_array($type_boucle, array(
				'articles', 'rubriques', 'hierarchie', 'breves'
				)) ? ', '.index_pile($id_boucle, 'titre', $boucles) : '')
			. ');';
	}
	else {
		$init_lang = '';
		$fin_lang = '';
		// sortir les appels au traducteur (invariants de boucle)
		if (strpos($return, '?php') === false
		AND preg_match_all("/\W(_T[(]'[^']*'[)])/", $return, $r)) {
			$i = 1;
			foreach($r[1] as $t) {
				$init_lang .= "\n\t\$l$i = $t;";
				$return = str_replace($t, "\$l$i", $return);
				$i++;
			}
		}
	}

	// gestion optimale des separateurs et des boucles constantes
	if (count($boucle->separateur))
	  $code_sep = ("'" . str_replace("'","\'",join('',$boucle->separateur)) . "'");

	$corps .= 
		((!$boucle->separateur) ? 
			(($constant && !$corps && !$flag_cpt) ? $return :
			 (($return==="''") ? '' :
			  ("\n\t\t" . '$t0 .= ' . $return . ";"))) :
		 ("\n\t\t\$t1 " .
			((strpos($return, '$t1.') === 0) ? 
			 (".=" . substr($return,4)) :
			 ('= ' . $return)) .
		  ";\n\t\t" .
		  '$t0 .= (($t1 && $t0) ? ' . $code_sep . " : '') . \$t1;"));
     
	// Calculer les invalideurs si c'est une boucle non constante et si on
	// souhaite invalider ces elements
	if (!$constant AND $primary) {
		include_spip('inc/invalideur');
		if (function_exists($i = 'calcul_invalideurs'))
			$corps = $i($corps, $primary, $boucles, $id_boucle);
	}

	// gerer le compteur de boucle 
	// avec ou sans son utilisation par les criteres {1/3} {1,4} {n-2,1}...

	if ($boucle->partie OR $boucle->cptrows)
		$corps = "\n\t\t\$Numrows['$id_boucle']['compteur_boucle']++;"
		. $boucle->partie 
		. $corps;

	// si le corps est une constante, ne pas appeler le serveur N fois!

	if (preg_match(CODE_MONOTONE,str_replace("\\'",'',$corps), $r)) {
		if (!isset($r[2]) OR (!$r[2])) {
			if (!$boucle->numrows)
				return "\n\t\$t0 = '';";
			else
				$corps = "";
		} else {
			$boucle->numrows = true;
			$corps = "\n\t\$t0 = str_repeat($corps, \$Numrows['$id_boucle']['total']);";
		}
	} else $corps = "while (\$Pile[\$SP]=\$iter->fetch()) {\n$corps\n	}"; 

	$count = '';
	if (!$boucle->select) {
		if (!$boucle->numrows OR $boucle->limit OR $boucle_mode_partie OR $boucle->group)
			$count = '1';
		else $count = 'count(*)';
		$boucles[$id_boucle]->select[]= $count; 
	}

	if ($flag_cpt)
		$nums = "\n\t// COMPTEUR\n\t"
		. "\$Numrows['$id_boucle']['compteur_boucle'] = 0;\n\t";
	else $nums = '';

	if ($boucle->numrows OR $boucle->mode_partie) {
		$nums .= "\$Numrows['$id_boucle']['total'] = @intval(\$iter->count());"
		. $boucle->mode_partie
		. "\n\t";
	}

	// Ne calculer la requete que maintenant
	// car ce qui precede appelle index_pile qui influe dessus

	$init = (($init = $boucles[$id_boucle]->doublons)
			 ? ("\n\t$init = array();") : '')
	. calculer_requete_sql($boucles[$id_boucle]);

	$contexte = memoriser_contexte_compil($boucle);

	$a = sprintf(CODE_CORPS_BOUCLE,
		$init,
		$boucle->iterateur,
		"\$command",
		$contexte,
		$nums,
		$init_lang,
		$corps,
		$fin_lang,
		$trace
	);

#	var_dump($a);exit;
	return $a;
}


// http://doc.spip.org/@calculer_requete_sql
function calculer_requete_sql($boucle)
{
	$init = array();
	$init[] = calculer_dec('table',  "'" . $boucle->id_table ."'");
	$init[] = calculer_dec('id', "'" . $boucle->id_boucle ."'");
	# En absence de champ c'est un decompte :
	$init[] = calculer_dec('from',  calculer_from($boucle));
	$init[] = calculer_dec('type', calculer_from_type($boucle));
	$init[] = calculer_dec('groupby', 'array(' . (($g=join("\",\n\t\t\"",$boucle->group))?'"'.$g.'"':'') . ")");
	$init[] = calculer_dec('select', 'array("' . join("\",\n\t\t\"", $boucle->select).  "\")");
	$init[] = calculer_dec('orderby', 'array(' . calculer_order($boucle) .	")");
	$init[] = calculer_dec('where', calculer_dump_array($boucle->where));
	$init[] = calculer_dec('join', calculer_dump_join($boucle->join));
	$init[] = calculer_dec('limit',
			(strpos($boucle->limit, 'intval') === false ?
				  "'".$boucle->limit."'"
				:
				  $boucle->limit));
	$init[] = calculer_dec('having', calculer_dump_array($boucle->having));
	$s = $d = "";
	foreach ($init as $i){
		if (reset($i))
			$s .= "\n\t\t".end($i);
	  else
		  $d .= "\n\t".end($i);
	}

	return ($boucle->hierarchie ? "\n\t$boucle->hierarchie" : '')
	  . $boucle->in 
	  . $boucle->hash
		. "\n\t".'if (!isset($command[\'table\'])) {'
		. $s
		. "\n\t}"
		. $d;
}

function memoriser_contexte_compil($p) {
	return join(',', array(
		_q($p->descr['sourcefile']),
		_q($p->descr['nom']),
		@_q($p->id_boucle),
		intval($p->ligne),
		'$GLOBALS[\'spip_lang\']'));
}

function reconstruire_contexte_compil($context_compil)
{
	if (!is_array($context_compil)) return $context_compil;
	$p = new Contexte;
	$p->descr = array('sourcefile' => $context_compil[0],
				  'nom' => $context_compil[1]);
	$p->id_boucle = $context_compil[2];
	$p->ligne = $context_compil[3];
	$p->lang = $context_compil[4];
	return $p;
}

// http://doc.spip.org/@calculer_dec
function calculer_dec($nom, $val)
{
	$static = 'if (!isset($command[\''.$nom.'\'])) ';
	// si une variable apparait dans le calcul de la clause
	// il faut la re-evaluer a chaque passage
	if (
		strpos($val, '$') !== false
		/*
		OR strpos($val, 'sql_') !== false
		OR (
			$test = str_replace(array("array(",'\"',"\'"),array("","",""),$val) // supprimer les array( et les echappements de guillemets
			AND strpos($test,"(")!==FALSE // si pas de parenthese ouvrante, pas de fonction, on peut sortir
			AND $test = preg_replace(",'[^']*',UimsS","",$test) // supprimer les chaines qui peuvent contenir des fonctions SQL qui ne genent pas
			AND preg_match(",\w+\s*\(,UimsS",$test,$regs) // tester la presence de fonctions restantes
		)*/
	)
		$static = "";

	return array($static,'$command[\''.$nom.'\'] = ' . $val . ';');
}

// http://doc.spip.org/@calculer_dump_array
function calculer_dump_array($a)
{
  if (!is_array($a)) return $a ;
  $res = "";
  if ($a AND $a[0] == "'?'") 
    return ("(" . calculer_dump_array($a[1]) .
	    " ? " . calculer_dump_array($a[2]) .
	    " : " . calculer_dump_array($a[3]) .
	    ")");
  else {
    foreach($a as $v) $res .= ", " . calculer_dump_array($v);
    return "\n\t\t\tarray(" . substr($res,2) . ')';
  }
}

// http://doc.spip.org/@calculer_dump_join
function calculer_dump_join($a)
{
  $res = "";
  foreach($a as $k => $v) 
		$res .= ", '$k' => array(".implode(',',$v).")";
  return 'array(' . substr($res,2) . ')';
}

// http://doc.spip.org/@calculer_from
function calculer_from(&$boucle)
{
  $res = "";
  foreach($boucle->from as $k => $v) $res .= ",'$k' => '$v'";
  return 'array(' . substr($res,1) . ')';
}

// http://doc.spip.org/@calculer_from_type
function calculer_from_type(&$boucle)
{
  $res = "";
  foreach($boucle->from_type as $k => $v) $res .= ",'$k' => '$v'";
  return 'array(' . substr($res,1) . ')';
}

// http://doc.spip.org/@calculer_order
function calculer_order(&$boucle)
{
	if (!$order = $boucle->order
	AND !$order = $boucle->default_order)
		$order = array();

	/*if (isset($boucle->modificateur['collate'])){
		$col = "." . $boucle->modificateur['collate'];
		foreach($order as $k=>$o)
			if (strpos($order[$k],'COLLATE')===false)
				$order[$k].= $col;
	}*/
	return join(', ', $order);
}

// Production du code PHP a partir de la sequence livree par le phraseur
// $boucles est passe par reference pour affectation par index_pile.
// Retourne une expression PHP,
// (qui sera argument d'un Return ou la partie droite d'une affectation).

// http://doc.spip.org/@calculer_liste
function calculer_liste($tableau, $descr, &$boucles, $id_boucle='') {
	if (!$tableau) return "''";
	if (!isset($descr['niv'])) $descr['niv'] = 0;
	$codes = compile_cas($tableau, $descr, $boucles, $id_boucle);
	if ($codes === false) return false;
	$n = count($codes);
	if (!$n) return "''";
	$tab = str_repeat("\t", $descr['niv']);
	if (_request('var_mode_affiche') != 'validation') {
		if ($n==1) 
			return $codes[0];
		else {
			$res = '';
			foreach($codes as $code) {
				if (!preg_match("/^'[^']*'$/", $code)
				OR substr($res,-1,1)!=="'")
				  $res .=  " .\n$tab$code";
				else {
				  $res = substr($res,0,-1) . substr($code,1);
				}
			}
			return '(' . substr($res,2+$descr['niv']) . ')';
		}
	} else {
	  $nom = $descr['nom'] . $id_boucle .  ($descr['niv']?$descr['niv']:'');
	  return "join('', array_map('array_shift', \$GLOBALS['debug_objets']['sequence']['$nom'] = array(" .  join(" ,\n$tab", $codes) . ")))";
	}
}

define('_REGEXP_COND_VIDE_NONVIDE',"/^[(](.*)[?]\s*''\s*:\s*('[^']+')\s*[)]$/");
define('_REGEXP_COND_NONVIDE_VIDE',"/^[(](.*)[?]\s*('[^']+')\s*:\s*''\s*[)]$/");
define('_REGEXP_CONCAT_NON_VIDE', "/^(.*)[.]\s*'[^']+'\s*$/");

// http://doc.spip.org/@compile_cas
function compile_cas($tableau, $descr, &$boucles, $id_boucle) {

        $codes = array();
	// cas de la boucle recursive
	if (is_array($id_boucle)) 
	  $id_boucle = $id_boucle[0];
	$type = !$id_boucle ? '' : $boucles[$id_boucle]->type_requete;
	$tab = str_repeat("\t", ++$descr['niv']);
	$mode = _request('var_mode_affiche');
	$err_e_c = '';
	// chaque commentaire introduit dans le code doit commencer
	// par un caractere distinguant le cas, pour exploitation par debug.
	foreach ($tableau as $p) {

		switch($p->type) {
		// texte seul
		case 'texte':
			$code = sandbox_composer_texte($p->texte, $p);
			$commentaire= strlen($p->texte) . " signes";
			$avant='';
			$apres='';
			$altern = "''";
			break;

		case 'polyglotte':
			$code = "";
			foreach($p->traductions as $k => $v) {
			  $code .= ",'" .
			    str_replace(array("\\","'"),array("\\\\","\\'"), $k) .
			    "' => '" .
			    str_replace(array("\\","'"),array("\\\\","\\'"), $v) .
			    "'";
			}
			$code = "choisir_traduction(array(" .
 			  substr($code,1) .
			  "))";
			$commentaire= '&';
			$avant='';
			$apres='';
			$altern = "''";
			break;

		// inclure
		case 'include':
			$p->descr = $descr;
			$code = calculer_inclure($p, $boucles, $id_boucle);
			if ($code === false) {
				$err_e_c = true;
				$code = "''";
			} else {
				$commentaire = '<INCLURE ' . addslashes(str_replace("\n", ' ', $code)) . '>';
				$avant='';
				$apres='';
				$altern = "''";
			}
			break;

		// boucle
		case TYPE_RECURSIF:
			$nom = $p->id_boucle;
			$newdescr = $descr;
			$newdescr['id_mere'] = $nom;
			$newdescr['niv']++;
			$avant = calculer_liste($p->avant,
				$newdescr, $boucles, $id_boucle);
			$apres = calculer_liste($p->apres,
				$newdescr, $boucles, $id_boucle);
			$newdescr['niv']--;
			$altern = calculer_liste($p->altern,
				$newdescr, $boucles, $id_boucle);
			if (($avant === false) OR ($apres === false) OR ($altern === false)) {
				$err_e_c = true;
				$code = "''";
			} else {
				$code = 'BOUCLE' .
				  str_replace("-","_", $nom) . $descr['nom'] .
				  '($Cache, $Pile, $doublons, $Numrows, $SP)';
				$commentaire= "?$nom";
				if (!$boucles[$nom]->milieu
				AND $boucles[$nom]->type_requete <> TYPE_RECURSIF) {
					if ($altern != "''") $code .= "\n. $altern";
					if ($avant<>"''" OR $apres<>"''")
					  spip_log("boucle $nom toujours vide, code superflu dans $id");
					$avant = $apres = $altern = "''";
				} else if ($altern != "''") $altern = "($altern)";
			}
			break;

		case 'idiome':
			$l = array();
			foreach ($p->arg as $k => $v) {
			  if ($k) $l[]= _q($k).' => '.calculer_liste($v,$p->descr,$boucles,$id_boucle);
			}
			$l = !$l ? '' : (", array(".implode(",\n",$l).")");
			$code = "_T('" . $p->module . ":" .$p->nom_champ . "'$l)";
			if ($p->param) {
				$p->id_boucle = $id_boucle;
				$p->boucles = &$boucles;
				$code = compose_filtres($p, $code);
			}
			$commentaire = ":";
			$avant='';
			$apres='';
			$altern = "''";
			break;

		case 'champ':

			// cette structure pourrait etre completee des le phrase' (a faire)
			$p->id_boucle = $id_boucle;
			$p->boucles = &$boucles;
			$p->descr = $descr;
			#$p->interdire_scripts = true;
			$p->type_requete = $type;

			$code = calculer_champ($p);
			$commentaire = '#' . $p->nom_champ . $p->etoile;
			$avant = calculer_liste($p->avant,
				$descr, $boucles, $id_boucle);
			$apres = calculer_liste($p->apres,
				$descr, $boucles, $id_boucle);
			$altern = "''";
			// Si la valeur est destinee a une comparaison a ''
			// forcer la conversion en une chaine par strval
			// si ca peut etre autre chose qu'une chaine
			if (($avant != "''" OR $apres != "''")
			AND $code[0]!= "'"
#			AND (strpos($code,'interdire_scripts') !== 0)
			AND !preg_match(_REGEXP_COND_VIDE_NONVIDE, $code)
			AND !preg_match(_REGEXP_COND_NONVIDE_VIDE, $code)
			AND !preg_match(_REGEXP_CONCAT_NON_VIDE, $code)) 
				$code = "strval($code)";
			break;

		default: 
		  // Erreur de construction de l'arbre de syntaxe abstraite
			$code = "''";
			$p->descr = $descr;
			$err_e_c = array('zbug_erreur_compilation');
			erreur_squelette($err_e_c, $p);
		} // switch

		if ($code != "''") {
			$code = compile_retour($code, $avant, $apres, $altern, $tab, $descr['niv']);
			$codes[]= (($mode == 'validation') ?
				"array($code, '$commentaire', " . $p->ligne . ")"
				: (($mode == 'code') ?
				"\n// $commentaire\n$code" :
				$code));
		}
	} // foreach

	return $err_e_c ? false : $codes;
}

// production d'une expression conditionnelle ((v=EXP) ? (p . v .s) : a)
// mais si EXP est de la forme (t ? 'C' : '') on produit (t ? (p . C . s) : a)
// de meme si EXP est de la forme (t ? '' : 'C')

// http://doc.spip.org/@compile_retour
function compile_retour($code, $avant, $apres, $altern, $tab, $n)
{
	if ($avant == "''") $avant = '';
	if ($apres == "''") $apres = '';
	if (!$avant AND !$apres AND ($altern==="''")) return $code;

	if (preg_match(_REGEXP_CONCAT_NON_VIDE, $code)) {
		$t = $code;
		$cond = '';
	} elseif (preg_match(_REGEXP_COND_VIDE_NONVIDE,$code, $r)) {
		$t = $r[2];
		$cond =  '!' . $r[1];
	} else if  (preg_match(_REGEXP_COND_NONVIDE_VIDE,$code, $r)) {
		$t = $r[2];
		$cond = $r[1];
	} else {
		$t = '$t' . $n;
		$cond = "($t = $code)!==''";
	}

	$res = (!$avant ? "" : "$avant . ") . 
		$t .
		(!$apres ? "" : " . $apres");

	if ($res !== $t) $res = "($res)";
	return !$cond ? $res : "($cond ?\n\t$tab$res :\n\t$tab$altern)";
}


function compile_inclure_doublons($lexemes)
{
	foreach($lexemes as $v)
	  if($v->type === 'include' AND $v->param) 
	    foreach($v->param as $r) 
	      if (trim($r[0]) === 'doublons') 
		return true;
	return false;
}

// Prend en argument le texte d'un squelette, le nom de son fichier d'origine,
// sa grammaire et un nom. Retourne False en cas d'erreur,
// sinon retourne un tableau de fonctions PHP compilees a evaluer,
// notamment une fonction portant ce nom et calculant une page.
// Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
// - 1er: element 'cache' => nom (du fichier ou` mettre la page)
// - 2e: element 0 contenant un environnement ('id_article => $id_article, etc)
// Elle retournera alors un tableau de 5 e'le'ments:
// - 'texte' => page HTML, application du squelette a` l'environnement;
// - 'squelette' => le nom du squelette
// - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
// - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
// - 'entetes' => tableau des entetes http
// En cas d'erreur, elle retournera un tableau des 2 premiers elements seulement

// http://doc.spip.org/@public_compiler_dist
function public_compiler_dist($squelette, $nom, $gram, $sourcefile, $connect=''){
	// Pre-traitement : reperer le charset du squelette, et le convertir
	// Bonus : supprime le BOM
	include_spip('inc/charsets');
	$squelette = transcoder_page($squelette);

	// rendre inertes les echappements de #[](){}
	while(false !== strpos($squelette, $inerte = 'INERTE'.$i)) $i++;
	$squelette = preg_replace_callback(',\\\\([#[()\]{}]),',
		create_function('$a', "return '$inerte-'.ord(\$a[1]).'-';"), $squelette, -1, $esc);

	$descr = array('nom' => $nom,
			'gram' => $gram,
			'sourcefile' => $sourcefile,
			'squelette' => $squelette);

	// Phraser le squelette, selon sa grammaire

	$boucles = array();
	$f = charger_fonction('phraser_' . $gram, 'public');

	$squelette = $f($squelette, '', $boucles, $descr);

	$boucles = compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect);
	// restituer les echappements
	if ($esc) foreach($boucles as $i=>$boucle) {
		$boucles[$i]->return = preg_replace_callback(",$inerte-(\d+)-,", create_function('$a', 'return chr($a[1]);'),
			$boucle->return);
		$boucles[$i]->descr['squelette'] = preg_replace_callback(",$inerte-(\d+)-,", create_function('$a', 'return "\\\\".chr($a[1]);'),
			$boucle->descr['squelette']);
	}
	
	$debug = ($boucles AND defined('_VAR_MODE') AND _VAR_MODE == 'debug');
	if ($debug) {
		include_spip('public/decompiler');
		foreach($boucles as $id => $boucle) {
			if ($id)
			  $decomp = "\n/* BOUCLE " .
			    $boucle->type_requete .
			    " " .
			    str_replace('*/', '* /', public_decompiler($boucle, $gram, 0, 'criteres')) .
			    " */\n";
			else $decomp = ("\n/*\n" . 
				 str_replace('*/', '* /', public_decompiler($squelette, $gram)) 
				 . "\n*/");
			$boucles[$id]->return = $decomp .$boucle->return; 
			$GLOBALS['debug_objets']['code'][$nom.$id] = $boucle->return;
		}
	}

	return $boucles;
}

// Point d'entree pour arbre de syntaxe abstraite fourni en premier argument
// Autres specifications comme ci-dessus

function compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect=''){
	static $trouver_table;
	spip_timer('calcul_skel');

	if (defined('_VAR_MODE') AND _VAR_MODE == 'debug') {
		$GLOBALS['debug_objets']['squelette'][$nom] = $descr['squelette'];
		$GLOBALS['debug_objets']['sourcefile'][$nom] = $sourcefile;

		if (!isset($GLOBALS['debug_objets']['principal']))
			$GLOBALS['debug_objets']['principal'] = $nom;
	}
	foreach ($boucles as $id => $boucle) {
		$GLOBALS['debug_objets']['boucle'][$nom.$id] = $boucle;
	}
	$descr['documents'] = compile_inclure_doublons($squelette);

	// Demander la description des tables une fois pour toutes
	// et reperer si les doublons sont demandes
	// pour un inclure ou une boucle document
	// c'est utile a la fonction champs_traitements
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	foreach($boucles as $id => $boucle) {
		if (!($type = $boucle->type_requete)) continue;
		if (!$descr['documents'] AND (
			(($type == 'documents') AND $boucle->doublons) OR
				compile_inclure_doublons($boucle->avant) OR
				compile_inclure_doublons($boucle->apres) OR
				compile_inclure_doublons($boucle->milieu) OR
				compile_inclure_doublons($boucle->altern)))
			$descr['documents'] = true;  
		if ($type != TYPE_RECURSIF) {
			if (!$boucles[$id]->sql_serveur AND $connect)
				$boucles[$id]->sql_serveur = $connect;
			$show = $trouver_table($type, $boucles[$id]->sql_serveur);
			// si la table n'existe pas avec le connecteur par defaut, 
			// c'est peut etre une table qui necessite son connecteur dedie fourni
			// permet une ecriture allegee (GEO) -> (geo:GEO)
			if (!$show
			AND $show=$trouver_table($type, strtolower($type))) {
				$boucles[$id]->sql_serveur = strtolower($type);
			}

			// chercher dans les iterateurs du repertoire iterateur/
			if ($g = charger_fonction(
			preg_replace('/\W/', '_', $boucle->type_requete), 'iterateur', true)) {
				$boucles[$id] = $g($boucle);

			// sinon, en cas de requeteur d'un type predefini,
			// utiliser les informations donnees par le requeteur
			// cas "php:xx" et "data:xx".
			} else if ($requeteur = charger_fonction($boucle->sql_serveur, 'requeteur', true)) {
				$requeteur($boucles, $boucle, $id);

			// utiliser la description des champs transmis
			} else if ($show) {
				$boucles[$id]->show = $show;
				// recopie les infos les plus importantes
				$boucles[$id]->primary = $show['key']["PRIMARY KEY"];
				$boucles[$id]->id_table = $x = $show['id_table'];
				$boucles[$id]->from[$x] = $nom_table = $show['table'];
				$boucles[$id]->iterateur = 'SQL';

				$boucles[$id]->descr = &$descr;
				if ((!$boucles[$id]->jointures)
				  AND is_array($show['tables_jointures'])
				  AND count($x = $show['tables_jointures']))
					$boucles[$id]->jointures = $x;
				if ($boucles[$id]->jointures_explicites){
					$jointures = preg_split("/\s+/",$boucles[$id]->jointures_explicites);
					while ($j=array_pop($jointures))
						array_unshift($boucles[$id]->jointures,$j);
				}
			} else {
				// Pas une erreur si la table est optionnelle
				if ($boucles[$id]->table_optionnelle)
					$boucles[$id]->type_requete = '';
				else  {
					$boucles[$id]->type_requete = false;
					$boucle = $boucles[$id];
					$x = (!$boucle->sql_serveur ? '' :
					      ($boucle->sql_serveur . ":")) .
					  $type;
					$msg = array('zbug_table_inconnue',
							array('table' => $x));
					erreur_squelette($msg, $boucle);
				}
			}
		}
	}

	// Commencer par reperer les boucles appelees explicitement 
	// car elles indexent les arguments de maniere derogatoire
	foreach($boucles as $id => $boucle) { 
		if ($boucle->type_requete == TYPE_RECURSIF AND $boucle->param) {
			$boucles[$id]->descr = &$descr;
			$rec = &$boucles[$boucle->param[0]];
			if (!$rec) {
				$msg = array('zbug_boucle_recursive_undef',
					array('nom' => $boucle->param[0]));
				erreur_squelette($msg, $boucle);
				$boucles[$id]->type_requete = false;
			} else {
				$rec->externe = $id;
				$descr['id_mere'] = $id;
				$boucles[$id]->return =
						calculer_liste(array($rec),
							 $descr,
							 $boucles,
							 $boucle->param);
			}
		}
	}
	foreach($boucles as $id => $boucle) { 
		$id = strval($id); // attention au type dans index_pile
		$type = $boucle->type_requete;
		if ($type AND $type != TYPE_RECURSIF) {
			if ($boucle->param) {
				$res = calculer_criteres($id, $boucles);
			}
			$descr['id_mere'] = $id;
			$boucles[$id]->return =
			  calculer_liste($boucle->milieu,
					 $descr,
					 $boucles,
					 $id);
			// Si les criteres se sont mal compiles
			// ne pas tenter d'assembler le code final
			// (mais compiler le corps pour detection d'erreurs)
			if (is_array($res))
				$boucles[$id]->type_requete = false;
		}
	}

	// idem pour la racine
	$descr['id_mere'] = '';
	$corps = calculer_liste($squelette, $descr, $boucles);



	// Calcul du corps de toutes les fonctions PHP,
	// en particulier les requetes SQL et TOTAL_BOUCLE
	// de'terminables seulement maintenant

	foreach($boucles as $id => $boucle) {
		$boucle = $boucles[$id] = pipeline('pre_boucle', $boucle);
		if ($boucle->return === false) {$corps = false; continue;}
		// appeler la fonction de definition de la boucle

		if ($req = $boucle->type_requete) {
			$f = 'boucle_'.strtoupper($req);
		// si pas de definition perso, definition spip
			if (!function_exists($f)) $f = $f.'_dist';
			// laquelle a une definition par defaut
			if (!function_exists($f)) $f = 'boucle_DEFAUT';
			if (!function_exists($f)) $f = 'boucle_DEFAUT_dist';
			$req = "\n\n\tstatic \$command = array();\n\t" .
					"static \$connect;\n\t" .
					"\$command['connect'] = \$connect = " .
				_q($boucle->sql_serveur) .
				";" .
				$f($id, $boucles);
		} else $req = ("\n\treturn '';");

		$boucles[$id]->return = 
			"\n\nfunction BOUCLE" . strtr($id,"-","_") . $nom .
			'(&$Cache, &$Pile, &$doublons, &$Numrows, $SP) {' .
			$req .
			"\n}\n";
	}

	// Au final, si le corps ou un critere au moins s'est mal compile
	// retourner False, sinon inserer leur decompilation
	if (is_bool($corps)) return false;

	$principal = "\nfunction " . $nom . '($Cache, $Pile, $doublons=array(), $Numrows=array(), $SP=0) {
'
	// reporter de maniere securisee les doublons inclus
.'
	if (isset($Pile[0]["doublons"]) AND is_array($Pile[0]["doublons"]))
		$doublons = nettoyer_env_doublons($Pile[0]["doublons"]);

	$connect = ' .
	_q($connect) . ';
	$page = ' .
	// ATTENTION, le calcul de l'expression $corps affectera $Cache
	// c'est pourquoi on l'affecte a la variable auxiliaire $page. 
	// avant de referencer $Cache
	$corps . ";

	return analyse_resultat_skel(".var_export($nom,true)
		.", \$Cache, \$page, ".var_export($sourcefile,true).");
}";

	$secondes = spip_timer('calcul_skel');
	spip_log("COMPIL ($secondes) [$sourcefile] $nom.php");

	// Assimiler la fct principale a une boucle anonyme, pour retourner un resultat simple
	$code = new Boucle;
	$code->descr = $descr;
	$code->return = '
//
// Fonction principale du squelette ' . 
	$sourcefile . 
	($connect ? " pour $connect" : '') . 
	(!CODE_COMMENTE ? '' : "\n// Temps de compilation total: $secondes") .
	"\n//\n" .
	$principal;

	$boucles[''] = $code;
	return $boucles;
}


/**
 * Requeteur pour les boucles (php:nom_iterateur)
 * 
 * Analyse si le nom d'iterateur correspond bien a une classe PHP existante
 * et dans ce cas charge la boucle avec cet iterateur.
 * Affichera une erreur dans le cas contraire.
 *
 * @param $boucles Liste des boucles
 * @param $boucle  La boucle parcourue
 * @param $id      L'identifiant de la boucle parcourue
 * 
**/
function requeteur_php_dist(&$boucles, &$boucle, &$id) {
	if (class_exists($boucle->type_requete)) {
		$g = charger_fonction('php', 'iterateur');
		$boucles[$id] = $g($boucle, $boucle->type_requete);
	} else {
		$x = $boucle->type_requete;
		$boucle->type_requete = false;
		$msg = array('zbug_iterateur_inconnu',
				array('iterateur' => $x));
		erreur_squelette($msg, $boucle);
	}
}


/**
 * Requeteur pour les boucles (data:type de donnee)
 * note: (DATA) tout court ne passe pas par ici.
 * 
 * Analyse si le type de donnee peut etre traite
 * et dans ce cas charge la boucle avec cet iterateur.
 * Affichera une erreur dans le cas contraire.
 *
 * @param $boucles Liste des boucles
 * @param $boucle  La boucle parcourue
 * @param $id      L'identifiant de la boucle parcourue
 * 
**/
function requeteur_data_dist(&$boucles, &$boucle, &$id) {
	include_spip('iterateur/data');
	if ($h = charger_fonction($boucle->type_requete . '_to_array' , 'inc', true)) {
		$g = charger_fonction('data', 'iterateur');
		$boucles[$id] = $g($boucle);
		// from[0] stocke le type de data (rss, yql, ...)
		$boucles[$id]->from[] = $boucle->type_requete;
		
	} else {
		$x = $boucle->type_requete;
		$boucle->type_requete = false;
		$msg = array('zbug_requeteur_inconnu',
				array(
				'requeteur' => 'data',
				'type' => $x
		));
		erreur_squelette($msg, $boucle);
	}
}

?>
