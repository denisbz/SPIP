<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

$GLOBALS['my_sites']=array();

// http://doc.spip.org/@icone_table
function icone_table($type){
	$derog = array('document'=> 'doc-24.gif', 'mot'=>'mot-cle-24.gif','syndic_article'=>'site-24.gif');
	if (isset($derog[$type]))
		return $derog[$type];
	return "$type-24.gif";
}

// http://doc.spip.org/@lien_editer_objet
function lien_editer_objet($type,$key,$id){
	return $type == 'document' ? '' : generer_url_ecrire($type . "s_edit","$key=$id");
}

// http://doc.spip.org/@lien_voir_objet
function lien_voir_objet($type,$key,$id){
	if ($type == 'document') return generer_url_document($id);
	$exec = array('article'=>'articles','breve'=>'breves_voir','rubrique'=>'naviguer','mot'=>'mots_edit', 'signature'=>'controle_petition');
	$exec = isset($exec[$type])?$exec[$type]:$type . "s";
	return generer_url_ecrire($exec,"$key=$id");
}

// http://doc.spip.org/@afficher_numero_edit
function afficher_numero_edit($id, $key, $type,$row=NULL) {
	global $spip_lang_right, $spip_lang_left,$my_sites;
	static $numero , $style='' ;
	if ($type=='syndic_article') {
		$redirect = _request('id_syndic') ? 'id_syndic='._request('id_syndic') : '';
		if (autoriser('modifier',$type,$id)) {
			if ($row['statut'] == "publie"){
			  $s =  "[<a href='". redirige_action_auteur('instituer_syndic',"$id-refuse", _request('exec'), $redirect) . "'><span style='color: black'>"._T('info_bloquer_lien')."</span></a>]";
			
			}
			else if ($row['statut'] == "refuse"){
			  $s =  "[<a href='". redirige_action_auteur('instituer_syndic',"$id-publie", _request('exec'), $redirect) . "'>"._T('info_retablir_lien')."</a>]";
			}
			else if ($row['statut'] == "off"
			AND isset($my_sites[$id_syndic]['miroir']) AND $my_sites[$id_syndic]['miroir'] == 'oui') {
				$s = '('._T('syndic_lien_obsolete').')';
			}
			else /* 'dispo' ou 'off' (dans le cas ancien site 'miroir') */
			{
			  $s = "[<a href='". redirige_action_auteur('instituer_syndic',"$id-publie", _request('exec'), $redirect) . "'>"._T('info_valider_lien')."</a>]";
			}
			return $s;
		}		
	}
	
	if (!$style) {
		$style = " class='spip_xx-small' style='float: $spip_lang_right; padding-$spip_lang_left: 4px; color: black; '"; 

		$numero = _T('info_numero_abbreviation');
	}

	if (!autoriser('modifier',$type,$id) OR
	    !$href = lien_editer_objet($type,$key,$id)) {
		$bal ='span';
	} else {
		$bal = 'a';
		$href = "\nhref='"
		. $href
		. "' title='"
		. _T('bouton_modifier')
		. "'";
	}
	return "<$bal$style$href><b>"
	. $numero
	. $id
	. "</b></$bal>";
}

// libelle du titre de l'objet :
// la partie du titre a afficher dans un lien
// puis la partie hors lien
// http://doc.spip.org/@afficher_titre_objet
function afficher_titre_objet($type,$row){
	if (function_exists($f = "afficher_titre_$type"))
		return $f($row);

	$titre = isset($row['titre'])?sinon($row['titre'], "("._T('info_sans_titre_2').")"):
	(isset($row['nom'])?sinon($row['nom'], "("._T('info_sans_titre_2').")"):
	 (isset($row['nom_email'])?sinon($row['nom_email'], "("._T('info_sans_titre_2').")"):
	  ""));
	return array(typo(supprime_img($titre,'')),'');
}
// http://doc.spip.org/@afficher_titre_site
function afficher_titre_site($row){
	$syndication = $row['syndication'];
	$s = "";
	$s .= $row['nom_site']?typo($row['nom_site']):"("._T('info_sans_titre_2').")";
	$s2 = "&nbsp;&nbsp; <span class='spip_xx-small'>[<a href='"
	.$row['url_site']."'>"._T('lien_visite_site')."</a>]</span>";
	
	return array($s,$s2);
}
// http://doc.spip.org/@afficher_titre_auteur
function afficher_titre_auteur($row){
	return array($row['nom'],
		((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : ''));
}

// http://doc.spip.org/@afficher_titre_syndic_article
function afficher_titre_syndic_article($row){
	$titre=safehtml($row["titre"]);
	$url=$row["url"];
	$date=$row["date"];
	$lesauteurs=typo($row["lesauteurs"]);
	$statut=$row["statut"];
	$descriptif=safehtml($row["descriptif"]);

	if ($url)
		$s = "<a href='$url'>$titre</a>";
	else
		$s = $titre;

	$date = affdate_court($date);
	if (strlen($lesauteurs) > 0) $date = $lesauteurs.', '.$date;
	$s.= " ($date)";

	// Tags : d'un cote les enclosures, de l'autre les liens
	if($e = afficher_enclosures($row['tags']))
		$s .= ' '.$e;

	// descriptif
	if (strlen($descriptif) > 0) {
		// couper un texte vraiment tres long
		if (strlen($descriptif) > 10000)
			$descriptif = safehtml(spip_substr($descriptif, 0, 6000)).' (...)';
		else
			$descriptif = safehtml($descriptif);
		$s .= '<div class="arial1">'
			# 385px = largeur de la colonne ou s'affiche le texte
			. filtrer('image_graver',filtrer('image_reduire',$descriptif, 385, 550))
			. '</div>';
	}

	// tags
	if ($tags = afficher_tags($row['tags']))
		$s .= "<div style='float:$spip_lang_right;'>&nbsp;<em>"
			. $tags . '</em></div>';

	// source
	if (strlen($row['url_source']))
		$s .= "<div style='float:$spip_lang_right;'>"
		. propre("[".$row['source']."->".$row['url_source']."]")
		. "</div>";
	else if (strlen($row['source']))
		$s .= "<div style='float:$spip_lang_right;'>"
		. typo($row['source'])
		. "</div>";

	return array('',$s);
}

// http://doc.spip.org/@afficher_complement_objet
function afficher_complement_objet($type,$row){
	if (function_exists($f = "afficher_complement_$type"))
		return $f($row);
	 return "";
}

// http://doc.spip.org/@afficher_complement_site
function afficher_complement_site($row){
	$syndication = $row['syndication'];
	$s = "";
	if ($syndication == 'off' OR $syndication == 'sus') {
		$s .= "<div style='color: red;'>"
			. http_img_pack('puce-orange-anim.gif', $syndication, "class='puce'",_T('info_panne_site_syndique'))
			. " "._T('info_probleme_grave')." </div>";
	}
	if ($syndication == "oui" or $syndication == "off" OR $syndication == 'sus'){
		$s .= "<div style='color: red;'>"._T('info_syndication')."</div>";
	}
	if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
		$id_syndic = $row['id_syndic'];
		$total_art = sql_fetsel("COUNT(*) AS n", "spip_syndic_articles", "id_syndic=$id_syndic");
		$s .= " " . $total_art['n'] . " " . _T('info_syndication_articles');
	} else {
			$s .= "&nbsp;";
	}
	return $s;
}
// http://doc.spip.org/@afficher_complement_syndic_article
function afficher_complement_syndic_article($row){
	global $my_sites;
	if ($GLOBALS['exec'] != 'sites') {
		$id_syndic = $row['id_syndic'];
		// $my_sites cache les resultats des requetes sur les sites
		if (!isset($my_sites[$id_syndic]))
			$my_sites[$id_syndic] = sql_fetsel("nom_site, moderation, miroir", "spip_syndic", "id_syndic=$id_syndic");

		$aff = $my_sites[$id_syndic]['nom_site'];
		if ($my_sites[$id_syndic]['moderation'] == 'oui')
			$aff = "<i>$aff</i>";
			
		$s = "<a href='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "'>$aff</a>";

		return $s;
	}
	return "";	
}

// affichage des liste d'objets
// Cas generique, utilise pour tout sauf article
// http://doc.spip.org/@inc_afficher_objets_dist
function inc_afficher_objets_dist($type, $titre_table,$requete,$formater='', $force=false){
	if ($afficher = charger_fonction("afficher_{$type}s",'inc',true)){
		return $afficher($titre_table,$requete,$formater);
	}

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui'
	     AND (!isset($GLOBALS['id_rubrique'])))
	OR $GLOBALS['meta']['multi_articles'] == 'oui') {
		$afficher_langue = true;

		if (isset($GLOBALS['langue_rubrique'])) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = $GLOBALS['meta']['langue_site'];
	} else $afficher_langue = $langue_defaut = '';

	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);

	$largeurs = array('7','', '', '', '100', '38');
	$styles = array('arial11', 'arial11', 'arial1', 'arial1', 'arial1 centered', 'arial1');

	$arg = array( $afficher_langue, false, $langue_defaut);
	if (!function_exists($fonction_ligne = "afficher_{$type}s_boucle")){
		$fonction_ligne = "afficher_objet_boucle";
		$arg = array($type,id_table_objet($type),$afficher_langue, false, $langue_defaut);
	}

	return affiche_tranche_bandeau($requete, icone_table($type), NULL, NULL, $tmp_var, $titre_table, $force, $largeurs, $styles, $fonction_ligne, $arg);
}

// http://doc.spip.org/@afficher_objet_boucle
function afficher_objet_boucle($row, &$tous_id,  $voir_logo, $own)
{
	global $connect_statut, $spip_lang_right;
	list($type,$primary,$afficher_langue, $affrub, $langue_defaut) = $own;
	$vals = '';
	$id_objet = $row[$primary];
	if (autoriser('voir',$type,$id_objet)){
		$tous_id[] = $id_objet;
		
		$date_heure = isset($row['date'])?$row['date']:(isset($row['date_heure'])?$row['date_heure']:"");

		$statut = isset($row['statut'])?$row['statut']:"";
		if (isset($row['lang']))
		  changer_typo($lang = $row['lang']);
		else $lang = $langue_defaut;
		$lang_dir = lang_dir($lang);
		$id_rubrique = isset($row['id_rubrique'])?$row['id_rubrique']:0;
		
		$puce_statut = charger_fonction('puce_statut', 'inc');
		$vals[] = $puce_statut($id_objet, $statut, $id_rubrique, $type);
	
		list($titre,$suite) = afficher_titre_objet($type,$row);
		$s = "\n<div>";
		if ($voir_logo) {
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if ($logo = $chercher_logo($id_objet, $primary, 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				include_spip('inc/filtres_images');
				$logo = image_reduire("<img src='$fid' alt='' />", 26, 20);

				if ($logo)
					$s .= "\n<span style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</span>";
			}
		}
		if (strlen($titre)){
			$s .= "<a href='"
			.  lien_voir_objet($type,$primary,$id_objet)
			.  "' "
			. "title='" . _T('info_numero_abbreviation'). $id_objet
			. "'>"
			. $titre 
			. "</a>";
		}
		$s .= $suite;
		$s .= "</div>";
		$vals[] = $s;

		$s = "";
		if ($afficher_langue){
			if (isset($row['langue_choisie'])){
				$s .= " <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>";
				if ($row['langue_choisie'] == "oui") $s .= "<b>".traduire_nom_langue($lang)."</b>";
				else $s .= "(".traduire_nom_langue($lang).")";
				$s .= "</span>";
			}
			elseif ($lang != $langue_defaut)
				$s .= " <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>".
					($lang
						? "(".traduire_nom_langue($lang).")"
						: ''
					)
				."</span>";
		}
		$vals[] = $s;
		
		$s = afficher_complement_objet($type,$row);
		$vals[] = $s;
		
		$s = "";
		if ($affrub && $id_rubrique) {
			$rub = sql_fetsel("id_rubrique, titre", "spip_rubriques", "id_rubrique=$id_rubrique");
			$id_rubrique = $rub['id_rubrique'];
			$s .= "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style=\"display:block;\">".typo($rub['titre'])."</a>";
		} else 
		if ($statut){
			if ($statut != "prop")
					$s = affdate_jourcourt($date_heure);
				else
					$s .= _T('info_a_valider');
		}
		$vals[] = $s;
				
		$vals[] = afficher_numero_edit($id_objet, $primary, $type, $row);
	}
	return $vals;
}

// Cas particuliers -----------------------------------------------------------------

//
// Afficher tableau d'articles
//
// http://doc.spip.org/@inc_afficher_articles_dist
function inc_afficher_articles_dist($titre, $requete, $formater='') {

	if (!isset($requete['FROM'])) $requete['FROM'] = 'spip_articles AS articles';

	if (!isset($requete['SELECT'])) {
		$requete['SELECT'] = "articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date, articles.lang, articles.id_trad, articles.descriptif";
	}
	
	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = sql_countsel($requete['FROM'], $requete['WHERE'], $requete['GROUP BY']);

	if (!$cpt) return '' ;

	$requete['FROM'] = preg_replace("/(spip_articles( AS \w*)?)/", "\\1 LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete['FROM']);

	$requete['SELECT'] .= ", petitions.id_article AS petition ";

	// memorisation des arguments pour gerer l'affichage par tranche
	// et/ou par langues.

	$hash = substr(md5(serialize($requete) . $GLOBALS['meta']['gerer_trad'] . $titre), 0, 31);
	$tmp_var = 't' . substr($hash, 0, 7);
	$nb_aff = floor(1.5 * _TRANCHES);
	$deb_aff = intval(_request($tmp_var));

	//
	// Stocke la fonction ajax dans le fichier temp pour exec=memoriser
	//

	// on lit l'existant
	lire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt', $ajax_fonctions);
	$ajax_fonctions = @unserialize($ajax_fonctions);

	// on ajoute notre fonction
	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);
	$v = array(time(), $titre, $requete, $tmp_var, $formater);
	$ajax_fonctions[$hash] = $v;

	// supprime les fonctions trop vieilles
	foreach ($ajax_fonctions as $h => $fonc)
		if (time() - $fonc[0] > 48*3600)
			unset($ajax_fonctions[$h]);

	// enregistre
	ecrire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt',
		serialize($ajax_fonctions));


	return afficher_articles_trad($titre, $requete, $formater, $tmp_var, $hash, $cpt);
}

// http://doc.spip.org/@afficher_articles_trad
function afficher_articles_trad($titre_table, $requete, $formater, $tmp_var, $hash, $cpt, $trad=0) {

	global $spip_lang_right;

	if ($trad) {
		$formater = 'afficher_articles_trad_boucle';
		$icone = "langues-off-12.gif";
		$alt = _T('masquer_trad');
	} else {
		if (!$formater) {
			$formater_article =  charger_fonction('formater_article', 'inc');
			$formater = $formater_article;
		}
		$icone = 'langues-12.gif';
		$alt = _T('afficher_trad');
	}

	$nb_aff = ($cpt  > floor(1.5 * _TRANCHES)) ? _TRANCHES : floor(1.5 * _TRANCHES) ;
	$deb_aff = intval(_request($tmp_var));

	$q = sql_select($requete['SELECT'], $requete['FROM'], $requete['WHERE'], $requete['GROUP BY'], $requete['ORDER BY'], ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$id_liste = 't'.substr(md5(join(',',$requete)),0,8);

	$t = '';
	while ($r = sql_fetch($q))
		if (autoriser('voir','article',$r['id_article']))
			$t .= $formater($r);
	sql_free($q);

	if ($t)
	  $t = afficher_liste_debut_tableau()
	    . $t
	    . afficher_liste_fin_tableau();

	$style = "style='visibility: hidden; float: $spip_lang_right'";

	$texte = http_img_pack("searching.gif", "", $style . " id='img_$tmp_var'");

	if (($GLOBALS['meta']['gerer_trad'] == "oui")) {
		$url = generer_url_ecrire('memoriser',"hash=$hash&trad=" . (1-$trad));
		$texte .= 
		 "\n<span style='float: $spip_lang_right;'><a href=\"#\"\nonclick=\"return charger_id_url('$url','$tmp_var');\">"
		. "<img\nsrc='". _DIR_IMG_PACK . $icone ."' alt='$alt' /></a></span>";
	}
	$texte .=  '<b>' . $titre_table  . '</b>';
	
	$res = debut_cadre('liste',"article-24.gif",'',$bouton = bouton_block_depliable($texte,true,$id_liste))
	. debut_block_depliable(true,$id_liste)
	. (($cpt <= $nb_aff) ? ''
	   : afficher_tranches_requete($cpt, $tmp_var, generer_url_ecrire('memoriser', "hash=$hash&trad=$trad"), $nb_aff))
	. $t
	. fin_block()
	. fin_cadre();

	return ajax_action_greffe($tmp_var, '', $res);
}

// http://doc.spip.org/@afficher_articles_trad_boucle
function afficher_articles_trad_boucle($row)
{
  	global $lang_objet,  $spip_lang_right, $spip_display;

	$lang_dir = lang_dir($lang_objet);
	$vals = '';
	$id_article = $row['id_article'];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$id_trad = $row['id_trad'];
	$lang = $row['lang'];

	// La petite puce de changement de statut
	$puce_statut = charger_fonction('puce_statut', 'inc');
	$vals[] = $puce_statut($id_article, $statut, $id_rubrique,'article');

	// Le titre (et la langue)
	
	$langues_art = "";
	$dates_art = "";
	$l = "";

	$res_trad = sql_select("id_article, lang, date_modif", "spip_articles", "id_trad = $id_trad AND id_trad > 0");

	while ($row_trad = sql_fetch($res_trad)) {

		$id_article_trad = $row_trad["id_article"];
		$lang_trad = $row_trad["lang"];
		$date_trad = $row_trad["date_modif"];
		$dates_art[$lang_trad] = $date_trad;
		$langues_art[$lang_trad] = $id_article_trad;
		if ($id_article_trad == $id_trad) $date_ref = $date;
	}


	// faudrait sortir ces invariants de boucle

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND (!isset($GLOBALS['id_rubrique']))) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$afficher_langue = true;
			$langue_defaut = isset($GLOBALS['langue_rubrique'])
			  ? $GLOBALS['meta']['langue_site']
			  : $GLOBALS['langue_rubrique'];
	}

	$span_lang = false;

	foreach(explode(',', $GLOBALS['meta']['langues_multilingue']) as $k){
		if ($langues_art[$k]) {
			if ($langues_art[$k] == $id_trad) {
				$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "'><span class='lang_base'>$k</span></a>";
				$l .= $span_lang;
			} else {
				$date = $dates_art[$k];
				if ($date < $date_ref) 
					$l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='claire'>$k</a>";
				else $l .= "<a href='" . generer_url_ecrire("articles","id_article=".$langues_art[$k]) . "' class='foncee'>$k</a>";
			}			
		}
#				else $l.= "<span class='creer'>$k</span>";
	}
			
	if (!$span_lang)
		$span_lang = "<a href='" . generer_url_ecrire("articles","id_article=$id_article") . "'><span class='lang_base'>$lang</span></a>";

	$vals[] = "\n<div style='text-align: center;'>$span_lang</div>";
			

	$s.= "\n<div style='float: $spip_lang_right; margin-right: -10px;'>$l</div>";
	
	if (acces_restreint_rubrique($id_rubrique))
		$s .= http_img_pack("admin-12.gif", _T('titre_image_administrateur'), "width='12' height='12'", _T('titre_image_admin_article'));

	if ($id_article == $id_trad) $titre = "<b>$titre</b>";
			
	$titre = typo(supprime_img($titre,''));

	if ($afficher_langue AND $lang != $langue_defaut)
		$titre .= " <span class='spip_xx-small' style='color: #666666'  dir='$lang_dir'>(".traduire_nom_langue($lang).")</span>";

	$s .= "<a href='" 
	  . generer_url_ecrire("articles","id_article=$id_article") 
	  . "' title='" . _T('info_numero_abbreviation'). "$id_article'"
	  . " dir='$lang_dir' style=\"display:block;\">"
	  . $titre
	  . "</a>";

	$vals[] = "\n<div>$s</div>";
	
	$vals[] = "";
	
	$largeurs = array(11, 24, '', '1');
	$styles = array('', 'arial1', 'arial1', '');

	return ($spip_display != 4)
	? afficher_liste_display_neq4($largeurs, $vals, $styles)
	: afficher_liste_display_eq4($largeurs, $vals, $styles);
}

// http://doc.spip.org/@afficher_auteurs_boucle
function afficher_auteurs_boucle($row, &$tous_id,  $voir_logo, $own){
	$vals = array();
	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	if ($row['statut'] == '0minirezo')
		$row['restreint'] = sql_countsel('spip_auteurs_rubriques', "id_auteur=".intval($row['id_auteur']));

	list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur'],$row);
	if ($w) {
	  if (preg_match(',^([^>]*>)[^<]*(.*)$,', $w,$r)) {
	    $w = $r[1] . substr($row['site'],0,20) . $r[2];
	  }
	}
	$vals[] = $s;
	$vals[] = $mail;
	$vals[] = $nom
		. ((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : '');
	$vals[] = $w;
	$vals[] = $p;
	return $vals;	
}
?>
