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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_config_contenu_dist
function exec_config_contenu_dist()
{
	if (!autoriser('configurer', 'contenu')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$config = charger_fonction('config', 'inc');
	$config();

	pipeline('exec_init',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_contenu'), "configuration", "configuration");

	echo "<br /><br /><br />\n";
	echo gros_titre(_T('titre_page_config_contenu'),'', false);
	echo barre_onglets("configuration", "interactivite");

	echo debut_gauche('', true);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	echo debut_droite('', true);

	$participants = charger_fonction('participants', 'configuration');
	$redacteurs = charger_fonction('redacteurs', 'configuration');
	$visiteurs = charger_fonction('visiteurs', 'configuration');
	$contenu_forums = charger_fonction('contenu_forums', 'configuration');
	$annonces = charger_fonction('annonces', 'configuration');
	$notifications_forum = charger_fonction('notifications_forum', 'configuration');
	$administrateurs = charger_fonction('administrateurs', 'configuration');

// Mode de participation aux forums

	echo $participants(), "<br />";

//
// Inscriptions de redacteurs et visiteurs depuis le site public
// (la balise FORMULAIRE_INSCRIPTION sert au deux)
//
	echo  $redacteurs(),  $visiteurs(), "<br />";

// Champs actives sur les forums
	echo $contenu_forums();

//
// Activer/desactiver mails automatiques
//
	echo  $annonces(), "<br />\n";

	echo  $notifications_forum(), "<br />\n";

// Activer forum admins

	echo $administrateurs();

//
// Choix supplementaires proposees par les plugins
//
	$res = pipeline('affiche_milieu',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	if ($res)
		echo ajax_action_post('config_contenu', '', 'config_contenu', '', $res);

	echo fin_gauche(), fin_page();
}

?>
