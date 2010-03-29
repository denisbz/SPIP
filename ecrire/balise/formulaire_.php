<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/filtres');

function protege_champ($texte){
	if (is_array($texte))
		$texte = array_map('protege_champ',$texte);
	else {
		// ne pas corrompre une valeur serialize
		if (preg_match(",^[abis]:\d+[:;],", $texte) AND unserialize($texte)!=false)
			return $texte;
		$texte = entites_html($texte);
		$texte = str_replace("'","&#39;",$texte);
	}
	return $texte;
}

function existe_formulaire($form)
{
	if (substr($form,0,11)=="FORMULAIRE_")
		$form = strtolower(substr($form,11));
	else 
		$form = strtolower($form);

	if (!$form) return ''; // on ne sait pas, le nom du formulaire n'est pas fourni ici

	return find_in_path($form.'.' . _EXTENSION_SQUELETTES, 'formulaires/') ? $form : false;
}


/* prendre en charge par defaut les balises formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dist
function balise_FORMULAIRE__dist($p) {

	// Cas d'un #FORMULAIRE_TOTO inexistant : renvoyer la chaine vide.
	// mais si #FORMULAIRE_{toto} on ne peut pas savoir a la compilation, continuer
	if (existe_formulaire($p->nom_champ)===FALSE) {
		    $p->code = "''";
		    $p->interdire_scripts = false;
		    return $p;
	}

	// sinon renvoyer un code php dnamique
	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

/* prendre en charge par defaut les balises dynamiques formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dyn
function balise_FORMULAIRE__dyn($form)
{
	$form = existe_formulaire($form);
	if (!$form) return '';

	// deux moyen d'arriver ici : 
	// soit #FORMULAIRE_XX reroute avec 'FORMULAIRE_XX' ajoute en premier arg
	// soit #FORMULAIRE_{xx}
		
	// recuperer les arguments passes a la balise
	// on enleve le premier qui est le nom de la balise 
	// deja recupere ci-dessus

	$args = func_get_args();
	array_shift($args);

	// tester si ce formulaire vient d'etre poste (memes arguments)
	// pour ne pas confondre 2 #FORMULAIRES_XX identiques sur une meme page
	$je_suis_poste = false;
	if ($post_form = _request('formulaire_action')
	AND ($post_args = _request('formulaire_action_args'))
	AND is_array($post_args = decoder_contexte_ajax($post_args,$post_form))) {
		// enlever le faux attribut de langue masque
		array_shift($post_args);
		if ($args === $post_args){
			$je_suis_poste = true;
		}
	}

	// init
	$erreurs = $valeurs = array();
	$message_ok = $message_erreur = "";
	$editable = true;

	// si le formulaire vient d'etre poste, on recupere les erreurs
	if ($je_suis_poste){
		$post = traiter_formulaires_dynamiques(true);
		$erreurs = isset($post["erreurs_$form"])?$post["erreurs_$form"]:array();
		$message_ok = "";
		if (isset($post["message_ok_$form"]))
			$message_ok = $post["message_ok_$form"];
		elseif(isset($erreurs['message_ok']))
			$message_ok = $erreurs["message_ok"];
		$message_erreur = isset($erreurs['message_erreur'])?$erreurs['message_erreur']:"";
		$editable = (!isset($post["erreurs_$form"])) || count($erreurs) || 
			(isset($post["editable_$form"]) && $post["editable_$form"]);
	}

	if ($charger_valeurs = charger_fonction("charger","formulaires/$form/",true))
		$valeurs = call_user_func_array($charger_valeurs,$args);
	$valeurs = pipeline(
		'formulaire_charger',
		array(
			'args'=>array('form'=>$form,'args'=>$args,'je_suis_poste'=>$je_suis_poste),
			'data'=>$valeurs)
	);
	
	// si $valeurs n'est pas un tableau, le formulaire n'est pas applicable
	// C'est plus fort qu'editable qui est gere par le squelette 
	// Idealement $valeur doit etre alors un message explicatif.
	if (!is_array($valeurs)) return is_string($valeurs) ? $valeurs : '';
	
	// reperer les valeurs particulieres editable, message_ok et message_erreur
	if (isset($valeurs['editable'])){
		$editable = $valeurs['editable'];
		unset($valeurs['editable']);
	}
	foreach(array('message_ok','message_erreur') as $k){
		if (isset($valeurs[$k])){
			if (!$je_suis_poste) $$k = $valeurs[$k];
			unset($valeurs[$k]);
		}
	}
	
	// charger peut passer une action si le formulaire ne tourne pas sur self()
	// ou une action vide si elle ne sert pas
	$action = isset($valeurs['action'])?$valeurs['action']:self();
	// bug IEx : si action finit par / 
	// IE croit que le <form ... action=../ > est autoferme
	if (substr($action,-1)=='/')
		$action .= (_SPIP_SCRIPT?_SPIP_SCRIPT:"index.php");

	// recuperer la saisie en cours si erreurs
	// seulement si c'est ce formulaire qui est poste
	// ou si on le demande explicitement par le parametre _forcer_request = true
	foreach(array_keys($valeurs) as $champ){
		if (substr($champ,0,1)!=='_'){
			if ($je_suis_poste || (isset($valeurs['_forcer_request']) && $valeurs['_forcer_request'])) {
						if (($v = _request($champ))!==NULL)
							$valeurs[$champ] = $v;
			}
			if ($action)
				$action = parametre_url($action,$champ,''); // nettoyer l'url des champs qui vont etre saisis
			// proteger les ' et les " dans les champs que l'on va injecter
			$valeurs[$champ] = protege_champ($valeurs[$champ]);
		}
	}

	if ($action) {
		// nettoyer l'url
		$action = parametre_url($action,'formulaire_action','');
		$action = parametre_url($action,'formulaire_action_args','');
	}

	if (isset($valeurs['_action'])){
		$securiser_action = charger_fonction('securiser_action','inc');
		$secu = $securiser_action(reset($valeurs['_action']),end($valeurs['_action']),'',-1);
		$valeurs['_hidden'] = (isset($valeurs['_hidden'])?$valeurs['_hidden']:'') .
		"<input type='hidden' name='arg' value='".$secu['arg']."' />"
		. "<input type='hidden' name='hash' value='".$secu['hash']."' />";
	}
	
	// empiler la lang en tant que premier argument implicite du CVT
	// pour permettre de la restaurer au moment du Verifier et du Traiter
	array_unshift($args, $GLOBALS['spip_lang']);

	return array("formulaires/$form",
		3600,
		array_merge(
			$valeurs,
			array(
			'form' => $form,
			'action' => $action,
			'formulaire_args' => encoder_contexte_ajax($args,$form),
			'id' => isset($valeurs['id'])?$valeurs['id']:'new',
			'erreurs' => $erreurs,
			'message_ok' => $message_ok,
			'message_erreur' => $message_erreur,
			'editable' => $editable?' ':'',
			)
		)
	);
}

?>
