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
		$test_zlib = @ini_get('zlib.output_compression');

		$res .= debut_cadre_relief("", true, "", _L('Compression du flux HTTP'))
			.  "<p class='verdana2'>"
			. _L("SPIP peut compresser automatiquement chaque page qu'il envoie aux
visiteurs du site. Ce r&#233;glage permet d'optimiser la bande passante (le
site est plus rapide derri&#232;re une liaison &#224; faible d&#233;bit), mais
demande plus de puissance au serveur. (Pour plus de d&#233;tails, cf.
[->http://www.php.net/ob_gzhandler].)")
			. "</p>"

			. "<div class='verdana2'>"
			. ($test_zlib
				? _L('Votre serveur web &#233;tablit lui-m&#234;me la compression du flux HTTP.')
				:
				"<p class='verdana2'>"
				. _L('Voulez-vous activer la compression du flux HTTP ?')
				. "</p>"
				. afficher_choix('auto_compress_http',
					($GLOBALS['meta']['auto_compress_http'] != 'non') ? 'oui' : 'non',
					array(
						'oui' => _T('info_compresseur_activer'),
						'non' => _T('info_compresseur_desactiver')
					)
				)
			)
			. "</div>"
		. fin_cadre_relief(true);
	}


	// Compression des scripts et css
	$res .= debut_cadre_relief("", true, "", _L('Compactage des scripts et CSS'))
		.  "<p class='verdana2'>"
		. _L("SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela acc&#233;l&#232;re l'affichage du site.")
		. " "
		. "</p>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _L('Souhaitez-vous activer le compactage des scripts (javascript) ?')
		. "</p>"
		. afficher_choix('auto_compress_js',
			($GLOBALS['meta']['auto_compress_js'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('info_compresseur_activer'),
				'non' => _T('info_compresseur_desactiver')
			)
		)
		. "</div>"

		. "<div class='verdana2'>"
		. "<p class='verdana2'>"
		. _L('Souhaitez-vous activer le compactage des feuilles de style (CSS) ?')
		. "</p>"
		. afficher_choix('auto_compress_css',
			($GLOBALS['meta']['auto_compress_css'] != 'non') ? 'oui' : 'non',
			array(
				'oui' => _T('info_compresseur_activer'),
				'non' => _T('info_compresseur_desactiver')
			)
		)
		. "</div>"

		. "<p><em>"._L("Attention &#224; ne pas activer ces options durant le d&#233;veloppement de votre site : les &#233;l&#233;ments compact&#233;s perdent toute lisibilit&#233;.")."</em></p>"


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
