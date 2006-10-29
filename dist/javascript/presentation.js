var init_gauche = true;

function changestyle(id_couche, element, style) {

	if (admin) {
		hide_obj("bandeauaccueil");
		hide_obj("bandeaunaviguer");
		hide_obj("bandeauforum");
		hide_obj("bandeauauteurs");
		if (stat) {  hide_obj("bandeaustatistiques_visites"); } 
		hide_obj("bandeauconfiguration"); 
	}
	hide_obj("bandeaudeconnecter");
	hide_obj("bandeautoutsite");
	hide_obj("bandeaunavrapide");
	hide_obj("bandeauagenda");
	hide_obj("bandeaumessagerie");
	hide_obj("bandeausynchro");
	//hide_obj("nav-recherche");
	hide_obj("bandeaurecherche");
	hide_obj("bandeauinfoperso");
	hide_obj("bandeaudisplay");
	hide_obj("bandeauecran");
	hide_obj("bandeauinterface");

	if (init_gauche) {
		if (admin) {
			decalerCouche('bandeauaccueil');
			decalerCouche('bandeaunaviguer');
			decalerCouche('bandeauforum');
			decalerCouche('bandeauauteurs');
			if (stat) decalerCouche('bandeaustatistiques_visites');
			decalerCouche('bandeauconfiguration');
		}
		init_gauche = false;
	}

	if (!(layer = findObj(id_couche))) return;

	layer.style[element] = style;
}

function decalerCouche(id_couche) {
	if (!(layer = findObj(id_couche))) return;
	if (bug_offsetwidth && ( parseInt(layer.style.left) > 0)) {
		demilargeur = Math.floor( layer.offsetWidth / 2 );
		if (demilargeur == 0) demilargeur = 100; // bug offsetwidth MSIE, on fixe une valeur arbitraire
		gauche = parseInt(layer.style.left)
		  - demilargeur
		  + Math.floor(largeur_icone / 2);

		if (gauche < 0) gauche = 0;

		layer.style.left = gauche+"px";
	}

}

var accepter_change_statut = false;

function selec_statut(id, type, decal, puce, script) {

	if (!accepter_change_statut)
		accepter_change_statut = confirm(confirm_changer_statut);

	if (accepter_change_statut) {
		changestyle ('statutdecal'+type+id, 'marginLeft', decal+'px');
		cacher ('statutdecal'+type+id);

		$.get(script,
			function (c) {
				if (!c)
					findObj('imgstatut'+type+id).src = puce;
				else
					alert(c); // eventuel message d'erreur (TODO)
			}
		);
	}
}

function changeclass(objet, myClass) {
	objet.className = myClass;
}
function changesurvol(iddiv, myClass) {
	document.getElementById(iddiv).className = myClass;
}

function setvisibility (objet, statut) {
	element = findObj(objet);
	if (element.style.visibility != statut) element.style.visibility = statut;
}

function montrer(objet) {
	setvisibility(objet, 'visible');
}
function cacher(objet) {
	setvisibility(objet, 'hidden');
}



function getHeight(obj) {
	if (obj == "window") {
		return hauteur_fenetre();
	}
	else
	{
		obj = document.getElementById(obj);
		if (obj.offsetHeight) return obj.offsetHeight;
	}
}
function hauteur_fenetre() {
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myHeight = window.innerHeight;
	} else {
		if( document.documentElement &&
			( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			//IE 6+ in 'standards compliant mode'
			myHeight = document.documentElement.clientHeight;
		} else {
			if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
				//IE 4 compatible
				myHeight = document.body.clientHeight;
			}
		}
	}
	return myHeight;
}


function hauteurFrame(nbCol) {
	hauteur = hauteur_fenetre() - 40;
	hauteur = hauteur - getHeight('haut-page');
	
	if (findObj('brouteur_hierarchie')) hauteur = hauteur - getHeight('brouteur_hierarchie');
		
	for (i=0; i<nbCol; i++) {
		source = document.getElementById("iframe" + i);
		source.style.height = hauteur + 'px';
	}
}

function hauteurTextarea() {
	hauteur = hauteur_fenetre() - 80;
	
	source = document.getElementById("text_area");
	source.style.height = hauteur + 'px';
}

function changeVisible(input, id, select, nonselect) {
	if (input) {
		element = findObj(id);
		if (element.style.display != select)  element.style.display = select;
	} else {
		element = findObj(id);
		if (element.style.display != nonselect)  element.style.display = nonselect;
	}
}



// livesearchlike...



// effacement titre quand new=oui
var antifocus=false;
// effacement titre des groupes de mots-cles de plus de 50 mots
var antifocus_mots = new Array();

function puce_statut(selection){
	if (selection=="publie"){
		return "puce-verte.gif";
	}
	if (selection=="prepa"){
		return "puce-blanche.gif";
	}
	if (selection=="prop"){
		return "puce-orange.gif";
	}
	if (selection=="refuse"){
		return "puce-rouge.gif";
	}
	if (selection=="poubelle"){
		return "puce-poubelle.gif";
	}
}

// Pour ne pas fermer le formulaire de recherche pendant qu'on l'edite
function recherche_desesperement()
{
	if (findObj('bandeaurecherche') && findObj('bandeaurecherche').style.visibility == 'visible') 
		{ ouvrir_recherche = true; } 
	else { ouvrir_recherche = false; } 
	changestyle('bandeauvide', 'visibility', 'hidden'); 
	if (ouvrir_recherche == true) 
		{ changestyle('bandeaurecherche','visibility','visible'); }
}
