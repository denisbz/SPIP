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

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * lecture d'un texte conforme a la DTD paquet.dtd
 * et conversion en tableau PHP identique a celui fourni par plugin.xml
 * manque la description
 *
 * @param $desc
 * @param string $plug
 * @param string $dir_plugins
 * @return array
 */
function plugins_infos_paquet($desc, $plug = '', $dir_plugins = _DIR_PLUGINS) {
	static $process = array( // tableau constant
		'debut' => 'paquet_debutElement',
		'fin' => 'paquet_finElement',
		'text' => 'paquet_textElement'
	);

	$valider_xml = charger_fonction('valider', 'xml');
	$vxml = $valider_xml($desc, false, $process, 'paquet.dtd');
	if (!$vxml->err){
		// On veut toutes les variantes selon la version de SPIP
		if (!$plug)
			return $vxml->versions;

		// compatibilite avec l'existant:
		$tree = $vxml->versions['0'];

		$tree['slogan'] = $tree['prefix']."_slogan";
		$tree['description'] = $tree['prefix']."_description";
		paquet_readable_files($tree, "$dir_plugins$plug/");
		if (!$tree['chemin'])
			$tree['chemin'] = array(array('dir' => '')); // initialiser par defaut
		return $tree;
	}
	// Prendre les messages d'erreur sans les numeros de lignes
	$msg = array_map('array_shift', $vxml->err);
	// Construire le lien renvoyant sur l'application du validateur XML 
	$h = $GLOBALS['meta']['adresse_site'].'/'
	     .substr("$dir_plugins$plug/", strlen(_DIR_RACINE)).'paquet.xml';

	$h = generer_url_ecrire('valider_xml', "var_url=$h");
	$t = _T('plugins_erreur', array('plugins' => $plug));
	array_unshift($msg, "<a href='$h'>$t</a>");
	return array('erreur' => $msg);
}

/**
 * Verifier le presence des fichiers remarquables
 * options/actions/administrations et peupler la description du plugin en consequence
 *
 * @param array $tree
 * @param string $dir
 * @return void
 */
function paquet_readable_files(&$tree, $dir){
	$prefix = $tree['prefix'];

	$tree['options'] = (is_readable($dir.$f = ($prefix.'_options.php'))) ? array($f) : array();

	$tree['fonctions'] = (is_readable($dir.$f = ($prefix.'_fonctions.php'))) ? array($f) : array();

	$tree['install'] = (is_readable($dir.$f = ($prefix.'_administrations.php'))) ? array($f) : array();
}

/**
 * Appeler le validateur, qui memorise le texte dans le tableau "versions"
 * On  memorise en plus dans les index de numero de version de SPIP
 * les attributs de la balise rencontree
 * qu'on complete par des entrees nommees par les sous-balises de "paquet",
 * et initialisees par un tableau vide, rempli a leur rencontre.
 * La sous-balise "spip", qui ne peut apparaitre qu'apres les autres,
 * reprend les valeurs recuperees precedement (valeurs par defaut)
 *
 * @param object $phraseur
 * @param string $name
 * @param array $attrs
 */
function paquet_debutElement($phraseur, $name, $attrs) {
	xml_debutElement($phraseur, $name, $attrs);
	if ($phraseur->err) return;
	if (($name=='paquet') OR ($name=='spip')){
		if ($name=='spip'){
			$n = $attrs['compatible'];
			$attrs = $phraseur->contenu['paquet'];
			$attrs['compatible'] = $n;
		} else {
			$n = '0';
			$phraseur->contenu['paquet'] = $attrs;
		}
		$phraseur->contenu['compatible'] = $n;
		$attrs['lib'] = array();
		$attrs['onglet'] = array();
		$attrs['menu'] = array();
		$attrs['chemin'] = array();
		$attrs['utilise'] = array();
		$attrs['pipeline'] = array();
		$attrs['necessite'] = array();
		$attrs['auteur'] = array();
		$phraseur->versions[$phraseur->contenu['compatible']] = $attrs;
	}
	else
		$phraseur->versions[$phraseur->contenu['compatible']][$name][0] = $attrs;
	$phraseur->versions[$phraseur->contenu['compatible']][''] = '';
}

/**
 * Appeler l'indenteur pour sa gestion de la profondeur,
 * et memoriser les attributs dans le tableau avec l'oppose de la profondeur
 * comme index, avec '' comme sous-index (les autres sont les attributs)
 *
 * @param pbject $phraseur
 * @param string $data
 */
function paquet_textElement($phraseur, $data) {
	xml_textElement($phraseur, $data);
	if ($phraseur->err OR !($data = trim($data))) return;
	$phraseur->versions[$phraseur->contenu['compatible']][''] .= $data;
}

/**
 * Si on sait deja que le texte n'est pas valide on ne fait rien.
 * Pour une balise sans attribut, le traitement est forcement toujours le meme.
 * Pour une balise sans texte, idem mais parce que la DTD est bien fichue
 *
 * @param object $phraseur
 * @param string $name
 */
function paquet_finElement($phraseur, $name) {
	if ($phraseur->err) return;
	$n = $phraseur->contenu['compatible'];

	if (is_array($phraseur->versions[$n][$name][0])){
		$attrs = $phraseur->versions[$n][$name][0];
		unset($phraseur->versions[$n][$name][0]);
	}
	$texte = $phraseur->versions[$n][''];
	$phraseur->versions[$n][''] = '';

	$f = 'info_paquet_'.$name;
	if (function_exists($f))
		$f($phraseur, $attrs, $texte);
	elseif (!$attrs)
		$phraseur->versions[$n][$name] = $texte;
	else {
		$phraseur->versions[$n][$name][$attrs['nom']] = $attrs;
		#	  echo("<br>pour $name $n " . $attrs['nom']); var_dump($phraseur->versions[$n]);
	}
	xml_finElement($phraseur, $name, $attrs);
}

/**
 * Cas particulier de la balise licence :
 * transformer en lien sur url fournie dans l'attribut lien
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_licence($phraseur, $attrs, $texte) {
	if (isset($attrs['lien']))
		$texte = "<a href='".$attrs['lien']."'>".$texte."</a>";
	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['licence'] = $texte;
}

/**
 * Cas particulier de la balise auteur
 * peupler le mail si besoin (en le protegeant, mais est-ce bien la place pour cela ?)
 * et le lien vers le site de l'auteur si fournit
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_auteur($phraseur, $attrs, $texte) {
	#  echo 'auteur ', $texte;  var_dump($attrs);
	if (isset($attrs['mail'])){
		if (strpos($attrs['mail'], '@'))
			$attrs['mail'] = str_replace('@', ' AT ', $attrs['mail']);
		$mail = ' ('.$attrs['mail'].')';
	}
	else
		$mail = '';

	if (isset($attrs['lien']))
		$lien = "<a href='".$attrs['lien']."' class='spip_out'>".$texte."</a>";
	else
		$lien = $texte;

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['auteur'][$texte] = $lien.$mail;
}

/**
 * Cas particulier de la balise credit
 * peupler le lien vers le site externe si necessaire
 *
 * @param object $phraseur
 * @param array $attrs
 * @param string $texte
 */
function info_paquet_credit($phraseur, $attrs, $texte) {

	if (isset($attrs['lien']))
		$lien = "<a href='".$attrs['lien']."' class='spip_out'>".$texte."</a>";
	else
		$lien = $texte;

	$n = $phraseur->contenu['compatible'];
	$phraseur->versions[$n]['credit'][$texte] = $lien;
}

?>
