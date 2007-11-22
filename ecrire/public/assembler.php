<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// On fixe $GLOBALS['var_mode']
$GLOBALS['var_mode'] = false;
$GLOBALS['var_preview'] = false;
$GLOBALS['var_images'] = false;
if (isset($_GET['var_mode'])) {
	// tout le monde peut calcul/recalcul
	if ($_GET['var_mode'] == 'calcul'
	OR $_GET['var_mode'] == 'recalcul')
		$GLOBALS['var_mode'] = $_GET['var_mode'];

	// preview et debug necessitent une autorisation
	else if ($_GET['var_mode'] == 'preview'
	OR $_GET['var_mode'] == 'debug') {
		include_spip('inc/autoriser');
		if (autoriser(
			($_GET['var_mode'] == 'preview')
				? 'previsualiser'
				: 'debug'
		)) {
			// preview ?
			if ($_GET['var_mode'] == 'preview') {
				// forcer le compilo et ignorer les caches existants
				$GLOBALS['var_mode'] = 'recalcul';
				// truquer les boucles et ne pas enregistrer de cache
				$GLOBALS['var_preview'] = true;
			}
			// seul cas ici: 'debug'
			else { 
				$GLOBALS['var_mode'] = $_GET['var_mode'];
			}
			spip_log($GLOBALS['auteur_session']['nom']
				. " ".$GLOBALS['var_mode']);
		}
		// pas autorise ?
		else {
			// si on n'est pas connecte on se redirige
			if (!$GLOBALS['auteur_session']) {
				include_spip('inc/headers');
				redirige_par_entete(generer_url_public('login',
				'url='.rawurlencode(
				parametre_url(self(), 'var_mode', $_GET['var_mode'], '&')
				), true));
			}
			// sinon tant pis
		}
	}
	else if ($_GET['var_mode'] == 'images'){
		// forcer le compilo et ignorer les caches existants
		$GLOBALS['var_mode'] = 'calcul';
		// indiquer qu'on doit recalculer les images
		$GLOBALS['var_images'] = true;
	}
}

// fonction principale declenchant tout le service
// elle-meme ne fait que traiter les cas particuliers, puis passe la main.
// http://doc.spip.org/@public_assembler_dist
function public_assembler_dist($fond, $connect='') {
	  global $forcer_lang, $ignore_auth_http;

	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non')) {
		include_spip('inc/lang');
		verifier_lang_url();
	}
	if ($l = isset($_GET['lang'])) {
		$l = lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger
	if (isset($_POST['confirmer_forum'])
	OR (isset($_POST['ajouter_mot']) AND $GLOBALS['afficher_texte']=='non')) {
		include_spip('inc/headers');
		$forum_insert = charger_fonction('forum_insert', 'inc');
		redirige_par_entete($forum_insert());
	}

	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature
	if (isset($_GET['var_confirm'])) {
		include_spip('balise/formulaire_signature');
		reponse_confirmation($_GET['var_confirm']);
	}

	if ($l) lang_select();
	return assembler_page ($fond, $connect);
}


//
// calcule la page et les entetes
//
// http://doc.spip.org/@assembler_page
function assembler_page ($fond, $connect='') {
	global $flag_preserver,$lastmodified,
		$use_cache;

	// Cette fonction est utilisee deux fois
	$cacher = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache
	// Si un resultat est retourne, c'est un message d'impossibilite
	$res = $cacher(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
	if ($res) {return array('texte' => $res);}

	if (!$chemin_cache || !$lastmodified) $lastmodified = time();

	$headers_only = ($_SERVER['REQUEST_METHOD'] == 'HEAD');

	// Pour les pages non-dynamiques (indiquees par #CACHE{duree,cache-client})
	// une perennite valide a meme reponse qu'une requete HEAD (par defaut les
	// pages sont dynamiques)
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !$GLOBALS['var_mode']
	AND $chemin_cache
	AND isset($page['entetes'])
	AND isset($page['entetes']['Cache-Control'])
	AND strstr($page['entetes']['Cache-Control'],'max-age=')
	AND !strstr($_SERVER['SERVER_SOFTWARE'],'IIS/')
	) {
		$since = preg_replace('/;.*/', '',
			$_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$since = str_replace('GMT', '', $since);
		if (trim($since) == gmdate("D, d M Y H:i:s", $lastmodified)) {
			$page['status'] = 304;
			$headers_only = true;
		}
	}

	// Si requete HEAD ou Last-modified compatible, ignorer le texte
	// et pas de content-type (pour contrer le bouton admin de inc-public)

	if ($headers_only) {
		$page['entetes']["Connection"] = "close";
		$page['texte'] = "";
	} else {
		if (!$use_cache)  {
			if (isset($page['contexte'])){
				// Remplir les globals pour les boutons d'admin
				foreach ($page['contexte'] as $var=>$val)
					$GLOBALS[$var] = $val;
			}
		} else {
			$parametrer = charger_fonction('parametrer', 'public');
			$page = $parametrer($fond, '', $chemin_cache, $connect);

			//ajouter les scripts poue le mettre en cache
			$page['insert_js_fichier'] = pipeline("insert_js",array("type" => "fichier","data" => array()));
			$page['insert_js_inline'] = pipeline("insert_js",array("type" => "inline","data" => array()));

			if ($chemin_cache)
				$cacher(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
		}

		if ($chemin_cache) $page['cache'] = $chemin_cache;

		auto_content_type($page);

		$flag_preserver |=  headers_sent();

		// Definir les entetes si ce n'est fait 
		if (!$flag_preserver) {
			if ($GLOBALS['flag_ob']) {
				// Si la page est vide, produire l'erreur 404 ou message d'erreur pour les inclusions
				if (trim($page['texte']) === ''
				AND $GLOBALS['var_mode'] != 'debug'
				AND !isset($page['entetes']['Location']) // cette page realise une redirection, donc pas d'erreur
				) {
					$page = message_erreur_404();
				}
				// pas de cache client en mode 'observation'
				if ($GLOBALS['var_mode']) {
					$page['entetes']["Cache-Control"]= "no-cache,must-revalidate";
					$page['entetes']["Pragma"] = "no-cache";
				}
			}
		}
	}

	// Entete Last-Modified:
	// eviter d'etre incoherent en envoyant un lastmodified identique
	// a celui qu'on a refuse d'honorer plus haut (cf. #655)
	if ($lastmodified
	AND !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		$page['entetes']["Last-Modified"]=gmdate("D, d M Y H:i:s", $lastmodified)." GMT";

	return $page;
}

//
// 2 fonctions pour compatibilite arriere. Sont probablement superflues
//

// http://doc.spip.org/@auto_content_type
function auto_content_type($page)
{
	global $flag_preserver;
	if (!isset($flag_preserver))
	  {
		$flag_preserver = preg_match("/header\s*\(\s*.content\-type:/isx",$page['texte']) || (isset($page['entetes']['Content-Type']));
	  }
}

// http://doc.spip.org/@stop_inclure
function stop_inclure($fragment) {
	if ($fragment == _request('var_fragment')) {
		define('_STOP_INCLURE', 1);
		#spip_log("fin du fragment $fragment, on arrete d'inclure");
	}
}
// http://doc.spip.org/@inclure_page
function inclure_page($fond, $contexte_inclus, $connect='') {
	global $lastmodified;
	if (!defined('_PAS_DE_PAGE_404'))
		define('_PAS_DE_PAGE_404',1);

	// Si un fragment est demande et deja obtenu, inutile de continuer a inclure
	if (defined('_STOP_INCLURE')) {
		return array(
		'texte' => '',
		'process_ins' => 'html'
		);
	}
	$contexte_inclus['fond'] = $fond; // securite, necessaire pour calculer correctement le cache

	// Si on a inclus sans fixer le critere de lang, on prend la langue courante
	if (!isset($contexte_inclus['lang']))
		$contexte_inclus['lang'] = $GLOBALS['spip_lang'];

	if ($contexte_inclus['lang'] != $GLOBALS['meta']['langue_site']) {
		$lang_select = lang_select($contexte_inclus['lang']);
	} else $lang_select ='';

	$cacher = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache :
	// emplacement, validite, et, s'il est valide, contenu & age
	$cacher($contexte_inclus, $use_cache, $chemin_cache, $page, $lastinclude);

	// Une fois le chemin-cache decide, on ajoute la date (et date_redac)
	// dans le contexte inclus, pour que les criteres {age} etc fonctionnent
	if (!isset($contexte_inclus['date']))
		$contexte_inclus['date'] = date('Y-m-d H:i:s');
	if (!isset($contexte_inclus['date_redac']))
		$contexte_inclus['date_redac'] = $contexte_inclus['date'];
	// il faut enlever le fond de contexte inclus car sinon il prend la main
	// dans les sous inclusions -> boucle infinie d'inclusion identique
	unset($contexte_inclus['fond']);

	// Si use_cache vaut 0, la page a ete tiree du cache et se trouve dans $page
	if (!$use_cache) {
		$lastmodified = max($lastmodified, $lastinclude);
	}
	// sinon on la calcule
	else {
		$parametrer = charger_fonction('parametrer', 'public');
		$page = $parametrer($fond, $contexte_inclus, $chemin_cache, $connect);
		$lastmodified = time();
		// et on l'enregistre sur le disque
		if ($chemin_cache
		AND $page['entetes']['X-Spip-Cache'] > 0)
			$cacher($contexte_inclus, $use_cache, $chemin_cache, $page,
				$lastmodified);
	}
	if ($lang_select) lang_select();

	return $page;
}


# Attention, un appel explicite a cette fonction suppose certains include
# (voir l'exemple de spip_inscription et spip_pass)
# $echo = faut-il faire echo ou return

// http://doc.spip.org/@inclure_balise_dynamique
function inclure_balise_dynamique($texte, $echo=true, $ligne=0) {
	global $contexte_inclus; # provisoire : c'est pour le debuggueur

	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : NULL;
		$GLOBALS['delais'] = $delainc;
		$page = inclure_page($fond, $contexte_inclus);
		$GLOBALS['delais'] = $d;

		// Faire remonter les entetes
		if (is_array($page['entetes'])) {
			// mais pas toutes
			unset($page['entetes']['X-Spip-Cache']);
			unset($page['entetes']['Content-Type']);
			if (is_array($GLOBALS['page'])) {
				if (!is_array($GLOBALS['page']['entetes']))
					$GLOBALS['page']['entetes'] = array();
				$GLOBALS['page']['entetes'] = 
					array_merge($GLOBALS['page']['entetes'],$page['entetes']);
			}
		}

		if ($page['process_ins'] == 'html') {
				$texte = $page['texte'];
		} else {
				ob_start();
				eval('?' . '>' . $page['texte']);
				$texte = ob_get_contents();
				ob_end_clean();
		}
	}

	if ($GLOBALS['var_mode'] == 'debug')
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;

	if ($echo)
		echo $texte;
	else
		return $texte;

}

// Traiter var_recherche ou le referrer pour surligner les mots
// http://doc.spip.org/@f_surligne
function f_surligne ($texte) {
	if (isset($_SERVER['HTTP_REFERER']) || isset($_GET['var_recherche'])) {
		include_spip('inc/surligne');
		$texte = surligner_mots($texte);
	}
	return $texte;
}

// Valider/indenter a la demande.
// http://doc.spip.org/@f_tidy
function f_tidy ($texte) {
	global $xhtml;

	if ($xhtml # tidy demande
	AND $GLOBALS['html'] # verifie que la page avait l'entete text/html
	AND strlen($texte)
	AND (_request('var_fragment') === NULL)
	AND !headers_sent()) {
		# Compatibilite ascendante
		if (!is_string($xhtml)) $xhtml ='tidy';

		if (!$f = charger_fonction($xhtml, 'inc', true)) {
			spip_log("tidy absent, l'indenteur SPIP le remplace");
			$f = charger_fonction('sax', 'inc');
		}
		return $f($texte);
	}

	return $texte;
}

// Offre #INSERT_HEAD sur tous les squelettes (bourrin)
// a activer dans mes_options via :
// $spip_pipeline['affichage_final'] .= '|f_insert_head';
// http://doc.spip.org/@f_insert_head
function f_insert_head($texte) {
	if (!$GLOBALS['html']) return $texte;
	include_spip('public/admin'); // pour strripos

	($pos = stripos($texte, '</head>'))
	    || ($pos = stripos($texte, '<body>'))
	    || ($pos = 0);

	if (false === strpos(substr($texte, 0,$pos), '<!-- insert_head -->')) {
		$insert = "\n".pipeline('insert_head','<!-- f_insert_head -->')."\n";
		$texte = substr_replace($texte, $insert, $pos, 0);
	}

	return $texte;
}

// Inserer au besoin les boutons admins
// http://doc.spip.org/@f_admin
function f_admin ($texte) {
	if ($GLOBALS['affiche_boutons_admin']) {
		include_spip('public/admin');
		$texte = affiche_boutons_admin($texte);
	}

	return $texte;
}

// Ajoute ce qu'il faut pour les clients MSIE et leurs debilites notoires
// * gestion du PNG transparent
// * images background (TODO)
// Cf. aussi inc/presentation, fonction fin_page();
// http://doc.spip.org/@f_msie
function f_msie ($texte) {
	if (!$GLOBALS['html']) return $texte;

	// test si MSIE et sinon quitte
	if (
		strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie')
		AND preg_match('/MSIE /i', $_SERVER['HTTP_USER_AGENT'])
		AND $msiefix = charger_fonction('msiefix', 'inc')
	)
		return $msiefix($texte);
	else
		return $texte;
}


// Ajoute a la volee scripts a le squelette jquery.js.html
// http://doc.spip.org/@ajouter_js_affichage_final
function ajouter_js_affichage_final($page,$scripts,$inline = false) {
	if(!$scripts || (!$inline && !preg_match(",\w+\|?,",$scripts)) || ($inline && !preg_match(",^\s*<script.*</script>\s*$,Us",$scripts))) {
		spip_log("ajouter_js_afficaghe_final interdite $scripts");
		return $page;
	}
	if($inline) {
		$page = substr_replace($page,$scripts."\n",strpos($page,"</head>"),0);
	}
	//verifie c'est un script HTML et que jquery.js.html est la
	else if($res = jquery_chargee($page)) {
		list($pos_script,$appelle) = $res;
		$params = $appelle.(strpos($appelle,"&")?"|":"&amp;script=").$scripts; 
		$page = substr_replace($page,$params,$pos_script,strlen($appelle));
	}
	return $page;
}

//verifie si le squelette jquery.js.html est appelle dans un flux de page et donnee 
//false ou un tableau avec la position et la chaine de l'appelle
// http://doc.spip.org/@jquery_chargee
function jquery_chargee($page) {
	$pos_debut_head=strpos($page,"<head>");
	$pos_fin_head=strpos($page,"</head>",$pos_debut_head);
	if($pos_debut_head!==false && $pos_fin_head!==false) { 
		$head = substr($page,$pos_debut_head,$pos_fin_head-$pos_debut_head);
		// verifie on a l'appelle a le squelette jquery.js
		if ($pos_script = strpos($head, generer_url_public('jquery.js'))){
			$pos_script += $pos_debut_head;
			$appelle = substr($page,$pos_script,strpos($page,'"',$pos_script)-$pos_script);
			return array($pos_script,$appelle);
		}
	}
	return false;
}

// http://doc.spip.org/@analyse_js_ajoutee
function analyse_js_ajoutee($page) {
	//verifie si jquery.js.html est chargee
	$corps = $page['texte'];
	if(!($jquery_chargee = jquery_chargee($corps))) return $page;
	//verifie js necessaire
	$js_necessaire = pipeline("verifie_js_necessaire",array("page" => $page, "data" => ""));
	$scripts_fichier = $page['insert_js_fichier'];
	$scripts_inline = $page['insert_js_inline'];
	$scripts_a_ajouter = array();
	if (is_array($scripts_fichier))
		foreach($scripts_fichier as $nom => $script)
		if (!isset($js_necessaire[$nom]) || $js_necessaire[$nom]) {
			//ajoute script fichier
			if(is_array($script)) 
				foreach($script as $code)
					push_script($scripts_a_ajouter,$code);
			else
				push_script($scripts_a_ajouter,$script);
		}
	// ajoute le scripts trouvee
	if(count($scripts_a_ajouter)) {
		$scripts_a_ajouter = join("|",$scripts_a_ajouter);
		list($pos_script,$appelle) = $jquery_chargee;
		$params = $appelle.(strpos($appelle,"&")?"|":"&amp;script=").$scripts_a_ajouter; 
		$corps = substr_replace($corps,$params,$pos_script,strlen($appelle));
	}
	$scripts_a_ajouter = array();
	if (is_array($scripts_inline))
	foreach($scripts_inline as $nom => $script)
	if (!isset($js_necessaire[$nom]) || $js_necessaire[$nom]) {
		//ajoute script inline
		if (is_array($script)) {
			foreach($script as $code)
				push_script($scripts_a_ajouter,$code,true);
		} else
			push_script($scripts_a_ajouter,$script,true);
	}
	// ajoute le scripts trouvee
	if (count($scripts_a_ajouter)) {
		list($pos_script,$appelle) = $jquery_chargee;
		$pos_fin_script = strpos($corps,"</script>",$pos_script)+strlen("</script>");
		$corps = substr_replace($corps,join("\n",$scripts_a_ajouter),$pos_fin_script,0);
	}
	$page['texte'] = $corps;
	return $page;
}

// http://doc.spip.org/@push_script
function push_script(&$scripts,$script,$inline = false) {
  if(($inline && preg_match(",^\s*<script.*</script>\s*$,Us",$script)) || (!$inline && preg_match(",^\w+$,",$script)))
    $scripts[]= $script;
  else
    spip_log("insert_js ".($inline?"inline":"")." interdite $script");
}

// http://doc.spip.org/@message_erreur_404
function message_erreur_404 ($erreur= "") {
	if (defined('_PAS_DE_PAGE_404'))
		return "erreur";
	if (!$erreur) {
		if (isset($GLOBALS['id_article']))
		$erreur = 'public:aucun_article';
		else if (isset($GLOBALS['id_rubrique']))
		$erreur = 'public:aucune_rubrique';
		else if (isset($GLOBALS['id_breve']))
		$erreur = 'public:aucune_breve';
		else if (isset($GLOBALS['id_auteur']))
		$erreur = 'public:aucun_auteur';
		else if (isset($GLOBALS['id_syndic']))
		$erreur = 'public:aucun_site';
	}
	$contexte_inclus = array(
		'erreur' => _T($erreur),
		'lang' => $GLOBALS['spip_lang']
	);
	$page = inclure_page('404', $contexte_inclus);
	$page['status'] = 404;
	return $page;
}

// fonction permettant de recuperer le resultat du calcul d'un squelette
// pour une inclusion dans un flux
// http://doc.spip.org/@recuperer_fond
function recuperer_fond($fond, $contexte=array(), $protect_xml=false, $trim=true, $connect='') {
	$options = array(
		'protect_xml' => $protect_xml,
		'trim' => $trim
	);

	$texte = "";
	foreach(is_array($fond) ? $fond : array($fond) as $f){
		$page = assembler($f, $contexte, $options, $connect);
		$texte .= $trim ? rtrim($page['texte']) : $page['texte'];
	}

	return $trim ? ltrim($texte) : $texte;
}

// temporairement ici : a mettre dans le futur inc/modeles
// creer_contexte_de_modele('left', 'autostart=true', ...) renvoie un array()
// http://doc.spip.org/@creer_contexte_de_modele
function creer_contexte_de_modele($args) {
	$contexte = array();
	$params = array();
	foreach ($args as $var=>$val) {
		if (is_int($var)){ // argument pas formate
			if (in_array($val, array('left', 'right', 'center'))) {
				$var = 'align';
				$contexte[$var] = $val;
			} else {
				$args = explode('=', $val);
				if (count($args)>=2) // Flashvars=arg1=machin&arg2=truc genere plus de deux args
					$contexte[trim($args[0])] = substr($val,strlen($args[0])+1);
			}
		}
		else
			$contexte[$var] = $val;
	}

	return $contexte;
}

// Calcule le modele et retourne la mini-page ainsi calculee
// http://doc.spip.org/@inclure_modele
function inclure_modele($type, $id, $params, $lien, $connect='') {
	static $compteur;
	if (++$compteur>10) return ''; # ne pas boucler indefiniment

	$type = strtolower($type);

	$fond = 'modeles/'.$type;

	$params = array_filter(explode('|', $params));
	if ($params) {
		list(,$soustype) = each($params);
		$soustype = strtolower($soustype);
		if (in_array($soustype,
		array('left', 'right', 'center'))) {
			list(,$soustype) = each($params);
			$soustype = strtolower($soustype);
		}

		if (preg_match(',^[a-z0-9_]+$,', $soustype)) {
			$fond = 'modeles/'.$type.'_'.$soustype;
			if (!find_in_path($fond.'.html')) {
				$fond = 'modeles/'.$type;
				$class = $soustype;
			}
		}
	}

	// en cas d'echec : si l'objet demande a une url, on cree un petit encadre
	// avec un lien vers l'objet ; sinon on passe la main au suivant
	if (!find_in_path($fond.'.html')) {
		if (!$lien)
			$lien = calculer_url("$type$id", '', 'tout', $connect);
		if (strpos($lien[1],'spip_url') !== false)
			return false;
		else
			return '<a href="'.$lien[0].'" class="spip_modele'
				. ($class ? " $class" : '')
				. '">'.sinon($lien[2], _T('ecrire:info_sans_titre'))."</a>";
	}


	// Creer le contexte
	$contexte = array( 
		'lang' => $GLOBALS['spip_lang'], 
		'fond' => $fond, 
		'dir_racine' => _DIR_RACINE # eviter de mixer un cache racine et un cache ecrire (meme si pour l'instant les modeles ne sont pas caches, le resultat etant different il faut que le contexte en tienne compte 
	); 
	// Le numero du modele est mis dans l'environnement
	// d'une part sous l'identifiant "id"
	// et d'autre part sous l'identifiant de la cle primaire supposee
	// par la fonction table_objet, 
	// qui ne marche vraiment que pour les tables std de SPIP
	// (<site1> =>> site =>> id_syndic =>> id_syndic=1)
	$_id = 'id_' . table_objet($type);
	if (preg_match('/s$/',$_id)) $_id = substr($_id,0,-1);
	$contexte['id'] = $contexte[$_id] = $id;

	if (isset($class))
		$contexte['class'] = $class;

	// Si un lien a ete passe en parametre, ex: [<modele1>->url]
	if ($lien) {
		# un eventuel guillemet (") sera reechappe par #ENV
		$contexte['lien'] = str_replace("&quot;",'"', $lien[0]);
		$contexte['lien_class'] = $lien[1];
	}

	// Traiter les parametres
	// par exemple : <img1|center>, <emb12|autostart=true> ou <doc1|lang=en>
	$contexte = array_merge($contexte,
		creer_contexte_de_modele($params));

	// On cree un marqueur de notes unique lie a ce modele
	// et on enregistre l'etat courant des globales de notes...
	$enregistre_marqueur_notes = $GLOBALS['marqueur_notes'];
	$enregistre_les_notes = $GLOBALS['les_notes'];
	$enregistre_compt_note = $GLOBALS['compt_note'];
	$GLOBALS['marqueur_notes'] = substr(md5(serialize($contexte)),0,8);
	$GLOBALS['les_notes'] = '';
	$GLOBALS['compt_note'] = 0;

	// Appliquer le modele avec le contexte
	$page = assembler($fond, $contexte, array(), $connect);
	$retour = trim($page['texte']);

	// Lever un drapeau (global) si le modele utilise #SESSION
	// a destination de public/cacher
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session']))
		$GLOBALS['cache_utilise_session'] = $page['invalideurs']['session'];

	// On restitue les globales de notes telles qu'elles etaient avant l'appel
	// du modele. Si le modele n'a pas affiche ses notes, tant pis (elles *doivent*
	// etre dans le cache du modele, autrement elles ne seraient pas prises en
	// compte a chaque calcul d'un texte contenant un modele, mais seulement
	// quand le modele serait calcule, et on aurait des resultats incoherents)
	$GLOBALS['les_notes'] = $enregistre_les_notes;
	$GLOBALS['marqueur_notes'] = $enregistre_marqueur_notes;
	$GLOBALS['compt_note'] = $enregistre_compt_note;

	// Regarder si le modele tient compte des liens (il *doit* alors indiquer
	// spip_lien_ok dans les classes de son conteneur de premier niveau ;
	// sinon, s'il y a un lien, on l'ajoute classiquement
	if (strstr(' ' . ($classes = extraire_attribut($retour, 'class')).' ',
	'spip_lien_ok')) {
		$retour = inserer_attribut($retour, 'class',
			trim(str_replace(' spip_lien_ok ', ' ', " $classes ")));
	} else if ($lien)
		$retour = "<a href='".$lien[0]."' class='".$lien[1]."'>".$retour."</a>";

	$compteur--;
	return $retour;
}

?>
