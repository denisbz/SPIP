<?php
	include ("inc_version.php3");	// pour le register_globals
	@Header ("Content-Type: text/css");
	@Header ("Expires: ".gmdate("D, d M Y H:i:s", time() + 7 * 24 * 3600)." GMT");
	@Header ("Last-Modified: ".gmdate("D, d M Y H:i:s", @filemtime("spip_style.php3"))." GMT");
	if (!isset($couleur_claire))
		$couleur_claire = "#EDF3FE";
	if (!isset($couleur_foncee))
		$couleur_foncee = "#3874B0";
?>

/*
 * Police par defaut (bof...)
 */
body { font-family: Verdana,Arial,Helvetica,sans-serif; }

/*
 * Formulaires
 */
.forml { width: 100%; padding: 2px; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000 }
.formo { width: 100%; padding: 2px; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; }
.fondl { background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000 }
.fondo { background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF }
.fondf { background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519 }
.sanscadre { padding: 4px; margin: 0px; }
.aveccadre { cursor: pointer; padding: 3px; margin: 0px; border-left: solid 1px <?php echo $couleur_claire; ?>; border-top: solid 1px <?php echo $couleur_claire; ?>; border-right: solid 1px #000000; border-bottom: solid 1px #000000; }

/*
 * Style des icones
 */
.iconeimpoff { padding: 3px; margin: 1px; border: 1px dashed <? echo $couleur_foncee; ?>; background-color: #e4e4e4 }
.fondgris { cursor: pointer; padding: 4px; margin: 1px; }
.fondgrison { cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4; }
.fondgrison2 { cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white; }
.bouton48gris {
	display: block;
	background:url(img_pack/pave-gris-48.png);
	cursor: pointer;
	padding: 3px;
	margin: 4px;
	width: 48px;
	height: 48px;
}
.bouton48blanc {
	display: block;
	background:url(img_pack/pave-blanc-48.png);
	cursor: pointer;
	padding: 3px;
	margin: 4px;
	width: 48px;
	height: 48px;
}
.bouton48off {
	cursor: pointer;
	padding: 3px;
	margin: 4px;
	width: 48px;
	height: 48px;
}
.bouton24gris {
	background:url(img_pack/pave-gris-24.png);
	padding: 3px;
	width: 24px;
	height: 24px;
}
.bouton24blanc {
	background:url(img_pack/pave-blanc-24.png);
	padding: 3px;
	width: 24px;
	height: 24px;
}
.bouton24rouge {
	background:url(img_pack/pave-rouge-24.png);
	padding: 3px;
	width: 24px;
	height: 24px;
}
.bouton24off {
	padding: 3px;
	width: 24px;
	height: 24px;
}
.bouton36gris {
	background:url(img_pack/pave-gris-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36blanc {
	background:url(img_pack/pave-blanc-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36rouge {
	background:url(img_pack/pave-rouge-36.png);
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}
.bouton36off {
	padding: 6px;
	margin-top: 2px;
	width: 24px;
	height: 24px;
}


.reliefblanc { background-image: url(img_pack/barre-blanc.gif) }
.reliefgris { background-image: url(img_pack/barre-noir.gif) }
.iconeoff { padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0 }
.iconeon { cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee; }
.iconedanger { padding: 3px; margin: 1px; border: 1px dashed black; background: url(img_pack/rayures-sup.gif)}

.profondeur { border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid }
.hauteur { border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid }
label { cursor: pointer; }
.pointeur { cursor: pointer; }

/* Raccourcis pour les polices (utile pour les tableaux) */
.arial1 { font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
.arial2 { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
.verdana1 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; }
.verdana2 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; }

/* Liens hypertexte */
a { text-decoration: none; }
a:hover { text-decoration: underline; }
a.icone { text-decoration: none; }
a.icone:hover { text-decoration: none; }

/*
 * Barre de raccourcis
 */

a.spip_barre {
	border: 0px solid #666666;
	padding: 4px;
	margin-right: 3px;
	background: url(img_pack/pave-gris-16.png);
}
a.spip_barre:hover {
	border: 0px solid #666666;
	padding: 4px;
	margin-right: 3px;
	background: url(img_pack/pave-blanc-16.png);
}

td.icone table {
}
td.icone a {
	color: black;
	text-decoration: none;
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size: 10px;
	font-weight: bold;
}
td.icone a:hover {
	text-decoration: none;
}
td.icone a img {
	border: 0px;
}

/*
 * Cadre blanc arrondi
 */

.cadre {
	padding: 0px;
	margin: 0px;
	border: 0px;
	width: 100%;
}
/* Haut-gauche, etc. */
.r-hg 	{
	width: 5px; height: 24px; background: url('img_pack/rond-hg-24.gif') no-repeat right bottom;
}
.r-h {
	height: 24px; background: url('img_pack/rond-h-24.gif') repeat-x bottom;
}
.r-hd {
	width: 5px; height: 24px; background: url('img_pack/rond-hd-24.gif') no-repeat left bottom;
}
.r-g {
	width: 5px; background: url('img_pack/rond-g.gif') repeat-y right;
}
.r-d {
	width: 5px; background: url('img_pack/rond-d.gif') repeat-y left;
}
.r-bg {
	width: 5px; height: 5px; background: url('img_pack/rond-bg.gif') no-repeat right top;
}
.r-b {
	height: 5px; background: url('img_pack/rond-b.gif') repeat-x top;
}
.r-bd {
	width: 5px; height: 5px; background: url('img_pack/rond-bd.gif') no-repeat left top;
}
.r-c {
	background: white; padding: 2px;
}


/*
 * Cadre gris enfonce
 */

/* Haut-gauche, etc. */
.e-hg {
	width: 5px; height: 24px; background: url('img_pack/cadre-hg.gif') no-repeat right bottom;
}
.e-h {
	height: 24px; background: url('img_pack/cadre-h.gif') repeat-x bottom;
}
.e-hd {
	width: 5px; height: 24px; background: url('img_pack/cadre-hd.gif') no-repeat left bottom;
}
.e-g {
	width: 5px; background: url('img_pack/cadre-g.gif') repeat-y right;
}
.e-d {
	width: 5px; background: url('img_pack/cadre-d.gif') repeat-y left;
}
.e-bg {
	width: 5px; height: 5px; background: url('img_pack/cadre-bg.gif') no-repeat right top;
}
.e-b {
	height: 5px; background: url('img_pack/cadre-b.gif') repeat-x top;
}
.e-bd {
	width: 5px; height: 5px; background: url('img_pack/cadre-bd.gif') no-repeat left top;
}
.e-c {
	background: #e0e0e0; padding: 2px;
}


/*
 * Icones horizontales
 * (on utilise deux styles distincts car IE ne gere pas le :hover)
 */

.icone-h {
	padding: 3px;
	margin: 2px;
	border: 1px dashed #aaaaaa;
	background-color: #f0f0f0;
	width: 100%;
	color: #666666;
}
.icone-h-danger {
	padding: 3px;
	margin: 2px;
	border: 1px dashed black;
	background: url(img_pack/rayures-sup.gif);
	width: 100%;
}
.icone-h-on {
	cursor: pointer;
	padding: 3px;
	margin: 2px;
	border-right: solid 1px white;
	border-bottom: solid 1px white;
	border-left: solid 1px #666666;
	border-top: solid 1px #666666;
	background-color: #eeeeee;
	width: 100%;
}



/*
 * Styles generes par les raccourcis de mis en page
 */

a.spip_in  {background-color:#eeeeee;}
a.spip_note {background-color:#eeeeee;}
a.spip_out {}
a.spip_url {}
a.spip_glossaire:hover {text-decoration: underline overline;}

.spip_recherche {padding: 2px; width : 100px; font-size: 10px;}
.spip_cadre {
	width : 100%;
	background-color: #FFFFFF;
	padding: 5px;
}
.spip_quote {
	margin-left : 40px;
	margin-top : 10px;
	margin-bottom : 10px;
	border : solid 1px #aaaaaa;
	background-color: #ffffff;
	padding: 5px;
}

.boutonlien {
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-weight: bold;
	font-size: 9px;
}
a.boutonlien:hover {color:#454545; text-decoration: none;}
a.boutonlien {color:#808080; text-decoration: none;}

h3.spip {
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-weight: bold;
	font-size: 115%;
	text-align: center;
}
.spip_documents{
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size : 70%;
}
table.spip {
}
table.spip tr.row_first {
	background-color: #FCF4D0;
}
table.spip tr.row_odd {
	background-color: #C0C0C0;
}
table.spip tr.row_even {
	background-color: #F0F0F0;
}
table.spip td {
	padding: 1px;
	text-align: left;
	vertical-align: center;
}


