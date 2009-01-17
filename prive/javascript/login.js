function affiche_login_secure() {
	if (alea_actuel)
		jQuery('#pass_securise').show();
	else
		jQuery('#pass_securise').hide();
}

function informe_auteur(c){
	informe_auteur_en_cours = false;
	eval('c = '+c); // JSON envoye par informer_auteur.html
	if (c) {
		alea_actuel = c.alea_actuel;
		alea_futur = c.alea_futur;
		// indiquer le cnx si on n'y a pas touche
		jQuery('input#session_remember:not(.modifie)')
		.attr('checked',(c.cnx=='1')?'checked':'');
	} else {
		alea_actuel = '';
	}
	if (c.logo)
		jQuery('#spip_logo_auteur').html(c.logo);
	else
		jQuery('#spip_logo_auteur').html('');
	affiche_login_secure();
}

function calcule_md5_pass(pass){
	if (alea_actuel) {
		jQuery('input[name=password]').attr('value','');
		jQuery('input[name=session_password_md5]').attr('value',calcMD5(alea_actuel + pass));
		jQuery('input[name=next_session_password_md5]').attr('value',calcMD5(alea_futur + pass));
	}
}

function actualise_auteur(){
	if (login != jQuery('#var_login').attr('value')) {
		informe_auteur_en_cours = true;
		login = jQuery('#var_login').attr('value');
		var currentTime = new Date();// on passe la date en var pour empecher la mise en cache de cette requete (bug avec FF3 & IE7)
		jQuery.get(page_auteur, {var_login:login,var_compteur:currentTime.getTime()},informe_auteur);
	}
}

function login_submit(){
	actualise_auteur();
	pass = jQuery('input[name=password]').attr('value');
	// ne pas laisser le pass d'un auteur "auth=spip" circuler en clair
	if (pass) {
		// si l'information est en cours, retenter sa chance
		// pas plus de 5 fois (si profondeur_url fausse, la requete d'information echoue et ne repond jamais)
		if (informe_auteur_en_cours && (attente_informe<5)) { 
			attente_informe++;
			jQuery('form#formulaire_login').animeajax().find('p.boutons input').before(attente_informe); // montrer qu'il se passe quelque chose
			setTimeout(function(){
				jQuery('form#formulaire_login').submit();
			}, 1000);
			return false;
		}

		// Si on a l'alea, on peut lancer le submit apres avoir hashe le pass
		if (alea_actuel) {
			calcule_md5_pass(pass);
		}
		// sinon c'est que l'auteur n'existe pas
		// OU qu'il sera accepte par LDAP ou autre auth
	}
}
