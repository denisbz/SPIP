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

// http://doc.spip.org/@echo_log
function echo_log($f, $ret) {
	spip_log("Page " . self() . " function $f: echo ".substr($ret,0,50)."...",'echo');
	echo
	(_SIGNALER_ECHOS?"#Echo par $f#" :"")
		. $ret;
}

// Retourne les parametres de personnalisation css de l'espace prive
// (ltr et couleurs) ce qui permet une ecriture comme :
// generer_url_public('style_prive', parametres_css_prive())
// qu'il est alors possible de recuperer dans le squelette style_prive.html avec
// #SET{claire,##ENV{couleur_claire,edf3fe}}
// #SET{foncee,##ENV{couleur_foncee,3874b0}}
// #SET{left,#ENV{ltr}|choixsiegal{left,left,right}}
// #SET{right,#ENV{ltr}|choixsiegal{left,right,left}}
// http://doc.spip.org/@parametres_css_prive
function parametres_css_prive(){
	global $visiteur_session;
	global $browser_name, $browser_version;

	$ie = "";
	include_spip('inc/layer');
	if ($browser_name=='MSIE')
		$ie = "&ie=$browser_version";
	
	$v = "&v=".$GLOBALS['spip_version_code'];

	$p = "&p=".substr(md5($GLOBALS['meta']['plugin']),0,4);

	$theme = "&themes=".implode(',',lister_themes_prives());
	
	$c = (is_array($visiteur_session)
	AND is_array($visiteur_session['prefs']))
		? $visiteur_session['prefs']['couleur']
		: 1;

	$couleurs = charger_fonction('couleurs', 'inc');
	$recalcul = _request('var_mode')=='recalcul' ? '&var_mode=recalcul':'';
	return 'ltr=' . $GLOBALS['spip_lang_left'] . '&'. $couleurs($c) . $theme . $v . $p . $ie . $recalcul ;
}


// http://doc.spip.org/@envoi_link
function envoi_link($nom_site_spip, $minipres=false) {
	global $spip_display, $spip_lang;

	$paramcss = parametres_css_prive();

	// CSS de secours en cas de non fonct de la suivante
	$res = '<link rel="stylesheet" type="text/css" href="'
	  . url_absolue(find_in_path('style_prive_defaut.css'))
	. '" id="cssprivee" />'  . "\n"

	// SPIP-style...
	. '<link rel="stylesheet" type="text/css" href="'
	  . url_absolue(find_in_path('spip_style.css'))
	. '" media="all" />' . "\n"

	// CSS imprimante (masque des trucs, a completer)
	. '<link rel="stylesheet" type="text/css" href="'
	  . url_absolue(find_in_path('spip_style_print.css'))
	. '" media="print" />' . "\n"

	// CSS "visible au chargement" differente selon js actif ou non

	. '<link rel="stylesheet" type="text/css" href="'
	  . url_absolue(find_in_path('spip_style_'
				     . (_SPIP_AJAX ? 'invisible' : 'visible')
				     . '.css'))
	.'" />' . "\n"

	// CSS espace prive : la vraie
	. '<link rel="stylesheet" type="text/css" href="'
	. generer_url_public('style_prive', $paramcss) .'" />' . "\n"

	// CSS optionelle minipres
	. ($minipres?'<link rel="stylesheet" type="text/css" href="'
	   . url_absolue(find_in_path('minipres.css')).'" />' . "\n":"");

	$favicon = find_in_path('spip.ico');

	// favicon.ico
	$res .= '<link rel="shortcut icon" href="'
	. url_absolue($favicon)
	. "\" type='image/x-icon' />\n";
	
	list($inlinejs,$js) = debut_javascript();

	$nom = entites_html($nom_site_spip);

	$res .= "<link rel='alternate' type='application/rss+xml' title=\"$nom\" href='"
			. generer_url_public('backend') . "' />\n";
	$res .= "<link rel='help' type='text/html' title=\""._T('icone_aide_ligne') .
			"\" href='"
			. generer_url_ecrire('aide_index',"var_lang=$spip_lang")
			."' />\n";
	if ($GLOBALS['meta']["activer_breves"] != "non")
		$res .= "<link rel='alternate' type='application/rss+xml' title=\""
			. $nom
			. " ("._T("info_breves_03")
			. ")\" href='" . generer_url_public('backend-breves') . "' />\n";

	return $inlinejs . $res . $js;
}

// http://doc.spip.org/@debut_javascript
function debut_javascript()
{
	global $spip_lang_left, $browser_name, $browser_version;
	include_spip('inc/charsets');

	// tester les capacites JS :

	// On envoie un script ajah ; si le script reussit le cookie passera a +1
	// on installe egalement un <noscript></noscript> qui charge une image qui
	// pose un cookie valant -1

	$testeur = str_replace('&amp;', '\\x26', generer_url_ecrire('test_ajax', 'js=1'));

	if (_SPIP_AJAX AND !defined('_TESTER_NOSCRIPT')) {
	  // pour le pied de page (deja defini si on est validation XML)
		define('_TESTER_NOSCRIPT',
			"<noscript>\n<div style='display:none;'><img src='"
		        . generer_url_ecrire('test_ajax', 'js=-1')
		        . "' width='1' height='1' alt='' /></div></noscript>\n");
	}

	if (!defined('_LARGEUR_ICONES_BANDEAU'))
		include_spip('inc/bandeau');
	return array(
	 	"<script type='text/javascript'>/*<![CDATA[*/"
			.((isset($_COOKIE['spip_accepte_ajax']) && $_COOKIE['spip_accepte_ajax'] >= 1)
			? ''
			: "\nfunction test_accepte_ajax(){jQuery.ajax({'url':'$testeur'});}") .
			(_OUTILS_DEVELOPPEURS ?"var _OUTILS_DEVELOPPEURS=true;":"") .
			"\nvar ajax_image_searching = \n'<img src=\"".url_absolue(chemin_image("searching.gif"))."\" alt=\"\" />';" .
			"\nvar stat = " . (($GLOBALS['meta']["activer_statistiques"] != 'non') ? 1 : 0) .
			"\nvar largeur_icone = " .
			intval(_LARGEUR_ICONES_BANDEAU) .
			"\nvar  bug_offsetwidth = " .
// uniquement affichage ltr: bug Mozilla dans offsetWidth quand ecran inverse!
			((($spip_lang_left == "left") &&
			  (($browser_name != "MSIE") ||
			   ($browser_version >= 6))) ? 1 : 0) .
			"\nvar confirm_changer_statut = '" .
			unicode_to_javascript(addslashes(html2unicode(_T("confirm_changer_statut")))) .
			"';\n/*]]>*/</script>\n",
	// envoi le fichier JS de config si browser ok.
		$GLOBALS['browser_layer'] .
    http_script('', 'presentation.js') .
    http_script('', 'gadgets.js')
		);
}

//
// Cadre centre (haut de page)
//

// http://doc.spip.org/@debut_grand_cadre
function debut_grand_cadre($return=false){
	$res =  "\n<div class='table_page'>\n";
	if ($return) return $res; else echo_log('debut_grand_cadre',$res);
}

// http://doc.spip.org/@fin_grand_cadre
function fin_grand_cadre($return=false){
	$res = "\n</div>";
	if ($return) return $res; else echo_log('fin_grand_cadre',$res);
}

//
// Debut de la colonne de gauche
//

// http://doc.spip.org/@debut_gauche
function debut_gauche($rubrique = "accueil", $return=false) {
	global $spip_display;
	global $spip_ecran, $spip_lang_rtl, $spip_lang_left;

	// div navigation fermee par creer_colonne_droite qui ouvre
	// div extra lui-meme ferme par debut_droite qui ouvre
	// div contenu lui-meme ferme par fin_gauche() ainsi que
	// div conteneur

	$res = "<div id='conteneur' class='".(_INTERFACE_ONGLETS ? "onglets" : "no_onglets")  ."'>
		\n<div id='navigation'>\n";

	if ($spip_display == 4) $res .= "<!-- ";

	if ($return) return $res; else echo_log('debut_gauche',$res);
}

// http://doc.spip.org/@fin_gauche
function fin_gauche()
{
	return "</div></div><br class='nettoyeur' />";
}

//
// Presentation de l''interface privee, marge de droite
//

// http://doc.spip.org/@creer_colonne_droite
function creer_colonne_droite($rubrique="", $return= false){
	static $deja_colonne_droite;
	global $spip_ecran, $spip_lang_rtl, $spip_lang_left;

	if ((!($spip_ecran == "large")) OR $deja_colonne_droite) return '';
	$deja_colonne_droite = true;

	$res = "\n</div><div id='extra'>";

	if ($return) return $res; else echo_log('creer_colonne_droite',$res);
}

// http://doc.spip.org/@debut_droite
function debut_droite($rubrique="", $return= false) {
	global $spip_ecran, $spip_display, $spip_lang_left;

	$res = '';

	if ($spip_display == 4) $res .= " -->";

	$res .= liste_articles_bloques();

	$res .= creer_colonne_droite($rubrique, true)
	. "</div>";

	$res .= "\n<div id='contenu'>";

	// touche d'acces rapide au debut du contenu : z
	// Attention avant c'etait 's' mais c'est incompatible avec
	// le ctrl-s qui fait "enregistrer"
	$res .= "\n<a id='saut' href='#saut' accesskey='z'></a>\n";

	if ($return) return $res; else echo_log('debut_droite',$res);
}

// http://doc.spip.org/@liste_articles_bloques
function liste_articles_bloques()
{
	global $connect_id_auteur;

	$res = '';
	if ($GLOBALS['meta']["articles_modif"] != "non") {
		include_spip('inc/drapeau_edition');
		$articles_ouverts = liste_drapeau_edition ($connect_id_auteur, 'article');
		if (count($articles_ouverts)) {
			$res .=
				debut_cadre('bandeau-rubriques',"article-24.png",'',_T('info_cours_edition'))
				. "\n<div class='plan-articles-bloques'>";
			foreach ($articles_ouverts as $row) {
				$ze_article = $row['id_article'];
				$ze_titre = $row['titre'];
				$statut = $row["statut"];

				$res .= "\n<div class='$statut'>"
				. "\n<div style='float:right; '>"
				. debloquer_article($ze_article,_T('lien_liberer'))
				. "</div>"
				. "<a  href='"
				. generer_url_ecrire("articles","id_article=$ze_article")
				. "'>$ze_titre</a>"
				. "</div>";
			}

			if (count($articles_ouverts) >= 4) {
				$res .= "\n<div style='text-align:right; '>"
				. debloquer_article('tous', _T('lien_liberer_tous'))
				. "</div>";
			}
			$res .= fin_cadre('bandeau-rubriques') . "</div>";
		}
	}
	return $res;
}

//
// Fin de page de l'interface privee.
// Elle comporte une image invisible declenchant une tache de fond

// http://doc.spip.org/@fin_page
function fin_page()
{
	global $spip_display, $tableau_des_temps;

	$debug = ((_request('exec') !== 'valider_xml')  AND ((_request('var_mode') == 'debug') OR $tableau_des_temps AND isset($_COOKIE['spip_admin'])));

	// avec &var_profile=1 on a le tableau de mesures SQL
	if ($debug) {
		$chrono = erreur_squelette();
	} else $chrono = '';

	return debut_grand_cadre(true)
	. (($spip_display == 4)
		? ("<div><a href='"
		   . generer_action_auteur('preferer','display:2', self('&'))
			. "'>"
			.  _T("access_interface_graphique")
			. "</a></div>")
		: ("<div id='copyright'>"

			. info_maj_spip()
			. info_copyright()
			. "<br />"
		 	. _T('info_copyright_doc',
				array('spipnet' => $GLOBALS['home_server']
				      . '/' .    $GLOBALS['spip_lang']))
			     . '</div>'))

	. fin_grand_cadre(true)
	. "</div>\n" // cf. div centered ouverte dans conmmencer_page()
	. $GLOBALS['rejoue_session']
	. '<div style="background-image: url(\''
	. generer_url_action('cron')
	. '\');"></div>'
	. (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '')
	   . $chrono
	. "</body></html>\n";
}

function info_maj_spip(){

	$maj = $GLOBALS['meta']['info_maj_spip'];
	if (!$maj)
		return "";

	$maj = explode('|',$maj);
	// c'est une ancienne notif, on a fait la maj depuis !
	if ($GLOBALS['spip_version_branche']!==reset($maj))
		return "";

	if (!autoriser('webmestre'))
		return "";

	array_shift($maj);
	$maj = implode('|',$maj);

	return "$maj<br />";
}

// http://doc.spip.org/@info_copyright
function info_copyright() {
	global $spip_version_affichee, $spip_lang;

	$version = $spip_version_affichee;

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($svn_revision = version_svn_courante(_DIR_RACINE)) {
		$version .= ' ' . (($svn_revision < 0) ? 'SVN ':'')
		. "[<a href='http://core.spip.org/trac/spip/changeset/"
		. abs($svn_revision) . "' onclick=\"window.open(this.href); return false;\">"
		. abs($svn_revision) . "</a>]";
	}

	// et la version de l'ecran de securite
	$secu = defined('_ECRAN_SECURITE')
		? "<br />"._L(' + ecran_securite v&nbsp;')._ECRAN_SECURITE
		: '';

	return _T('info_copyright',
		   array('spip' => "<b>SPIP $version</b> ",
			 'lien_gpl' =>
			 "<a href='". generer_url_ecrire("aide_index", "aide=licence&var_lang=$spip_lang") . "' onclick=\"window.open(this.href, 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"))
		. $secu;

}

// http://doc.spip.org/@debloquer_article
function debloquer_article($arg, $texte) {

	// cas d'un article pas liberable : on est sur sa page d'edition
	if (_request('exec') == 'articles_edit'
	AND $arg == _request('id_article'))
		return '';

	$lien = parametre_url(self(), 'debloquer_article', '', '&');
	return "<a href='" .
	  generer_action_auteur('instituer_collaboration', $arg, $lien) .
	  "' title=\"" .
	  attribut_html($texte) .
	  "\">"
	  . ($arg == 'tous' ? "$texte&nbsp;" : '')
	  . http_img_pack(chemin_image('supprimer-8.png'), ($arg=='tous' ? "" : "X"),
			"") .
	  "</a>";
}


// Voir en ligne, ou apercu, ou rien (renvoie tout le bloc)
// http://doc.spip.org/@voir_en_ligne
function voir_en_ligne ($type, $id, $statut=false, $image='racine-24.png', $af = true, $inline=true) {

	$en_ligne = $message = '';
	switch ($type) {
	case 'article':
			if ($statut == "publie" AND $GLOBALS['meta']["post_dates"] == 'non') {
				$n = sql_fetsel("id_article", "spip_articles", "id_article=$id AND date<=NOW()");
				if (!$n) $statut = 'prop';
			}
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
	case 'rubrique':
			if ($id > 0)
				if ($statut == 'publie')
					$en_ligne = 'calcul';
				else
					$en_ligne = 'preview';
			break;
	case 'breve':
	case 'site':
			if ($statut == 'publie')
				$en_ligne = 'calcul';
			else if ($statut == 'prop')
				$en_ligne = 'preview';
			break;
	case 'mot':
			$en_ligne = 'calcul';
			break;
	case 'auteur':
			$n = sql_fetsel('A.id_article', 'spip_auteurs_liens AS L LEFT JOIN spip_articles AS A ON (L.objet=\'article\' AND L.id_objet=A.id_article)', "A.statut='publie' AND L.id_auteur=".sql_quote($id));
			if ($n) $en_ligne = 'calcul';
			else $en_ligne = 'preview';
			break;
	default: return '';
	}

	if ($en_ligne == 'calcul')
		$message = _T('icone_voir_en_ligne');
	else if ($en_ligne == 'preview'
	AND autoriser('previsualiser'))
		$message = _T('previsualiser');
	else
		return '';

	$h = generer_url_action('redirect', "type=$type&id=$id&var_mode=$en_ligne");

	return $inline  
	  ? icone_inline($message, $h, $image, "", $GLOBALS['spip_lang_left'])
	: icone_horizontale($message, $h, $image, "",$af);

}


// http://doc.spip.org/@formulaire_recherche
function formulaire_recherche($page, $complement=""){
	$recherche = _request('recherche');
	$recherche_aff = entites_html($recherche);
	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	} else $onfocus = '';

	$form = '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="recherche" accesskey="r"' . $onfocus . ' />';
	$form .= "<input type='image' src='" . chemin_image('loupe.png') . "' name='submit' class='submit' alt='"._T('info_rechercher')."' />";
	return "<div class='spip_recherche'>".generer_form_ecrire($page, $form . $complement, " method='get'")."</div>";
}

?>
