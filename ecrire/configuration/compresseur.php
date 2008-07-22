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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

function configuration_compresseur_dist()
{
	global $spip_lang_right;

	$res = '';

	// Compression du flux HTTP
	if (!function_exists('ob_gzhandler')) {
		$GLOBALS['meta']['auto_compress_http'] = 'non';
	} else {
		$res .= debut_cadre_relief("", true, "", _T('titre_compresser_flux_http'))
			.  "<p class='verdana2'>"
			. _T('texte_compresseur_page')
			. "</p>"
			. "<p class='verdana2'>"
			. _T('info_compresseur_gzip', array('testgzip' => propre('[->http://www.gidnetwork.com/tools/gzip-test.php]'))
			)
			. "</p>"

			. "<div class='verdana2'>"
			. "<p class='verdana2'>"
			. _T('info_question_activer_compresseur')
			. "</p>"
			. afficher_choix('auto_compress_http',
				($GLOBALS['meta']['auto_compress_http'] != 'non') ? 'oui' : 'non',
				array(
					'oui' => _T('item_compresseur'),
					'non' => _T('item_non_compresseur')
				)
			)
			. "</div>"
		. fin_cadre_relief(true);
	}


	// Compression des scripts et css
	$res .= debut_cadre_relief("", true, "", _T('titre_compacter_script_css'))
		.  "<p class='verdana2'>"
		. _T('texte_compacter_script_css')
		. " "
		. "</p>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _T('info_question_activer_compactage_js')
		. "</p>"
		. afficher_choix('auto_compress_js',
			($GLOBALS['meta']['auto_compress_js'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('item_compresseur'),
				'non' => _T('item_non_compresseur')
			)
		)
		. "</div>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _T('info_question_activer_compactage_css')
		. "</p>"
		. afficher_choix('auto_compress_css',
			($GLOBALS['meta']['auto_compress_css'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('item_compresseur'),
				'non' => _T('item_non_compresseur')
			)
		)
		. "</div>"

		. "<p><em>"._T('texte_compacter_avertissement')."</em></p>"


		. fin_cadre_relief(true);


/*
-- Compression du flux HTTP --

SPIP peut compresser automatiquement chaque page qu'il envoie aux
visiteurs du site. Ce r�glage permet d'optimiser la bande passante (le
site est plus rapide derri�re une liaison � faible d�bit), mais
demande plus de puissance au serveur. (Pour plus de d�tails, cf.
[->http://www.php.net/ob_gzhandler].)

Voulez-vous activer la compression du flux HTTP ?

() oui
(x) non




-- Traitement du HTML --

(TODO, avec tidy)

La commande "tidy" permet d'�liminer tous les espaces superflus des
pages HTML produites, de fa�on � limiter la taille en octets du
contenu envoy�. Elle offre aussi la possibilit� de nettoyer le code
HTML de mani�re � garantir qu'il est strictement conforme au standard
XHTML 1.0.

A noter : avec des squelettes conformes, SPIP produit du code conforme
sans qu'il soit n�cessaire de faire appel � tidy. De plus, ces deux
options demandent un peu de puissance au serveur.

Voulez-vous supprimer les espaces superflus des pages HTML ?

() oui
(x) non

Souhaitez-vous faire appel � tidy garantir la conformit� du code HTML ?

() oui
(x) non



-- Compactage des scripts et css --

SPIP peut compacter les scripts javascript et les feuilles de style
CSS, pour les enregistrer dans des fichiers statiques ; cela acc�l�re
grandement l'affichage, au d�triment toutefois de la lisibilit� du
code. De plus, certains scripts ou CSS peuvent s'av�rer incompatibles
avec ce traitement (le cas �ch�ant, vous pourrez les d�sactiver
individuellement).

Compacter les scripts ?
() oui
(x) non

Compacter les feuilles de style ?
() oui
(x) non


[si oui :]
SPIP a compact� les scripts et feuilles de style suivants. Si certains
ne doivent pas �tre compact�s, veuillez le signaler ci-dessous :

page=jquery.js []
plugins/thickbox/thickbox.js []
squelettes/toto.css [x]
...

(Note: seuls les scripts rencontr�s dans la journ�e qui pr�c�de sont
m�moris�s, ainsi que ceux qui sont interdits.)

../..

*/



	$res = debut_cadre_trait_couleur("", true, "",
		_T('info_compresseur_titre'))
	.  ajax_action_post('configurer', 'compresseur', 'config_fonctions', '', $res)
	.  fin_cadre_trait_couleur(true);

	return ajax_action_greffe("configurer-compresseur", '', $res);
}
?>
