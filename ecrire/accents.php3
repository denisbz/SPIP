<?

include ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_admin.php3");


debut_admin("accents");

function changer_accents($dir) {

	$handle = opendir($dir);
	while ($nomfich = readdir($handle)) {
		if (ereg('\.', $nomfich) && !ereg('(\.php3|\.html)$', $nomfich)) continue;
		if ($nomfich == 'accents.php3') continue;
		if ($nomfich == 'inc_texte.php3') continue;
		if ($nomfich == 'inc_index.php3') continue;
		if ($nomfich == 'inc_mail.php3') continue;
		$nomfich = "$dir/$nomfich";
		$fichier = @file("$nomfich");

		if ($fichier) {
			echo "$nomfich<br>";
			$fichier = join('', $fichier);
			$fichier = ereg_replace('�', '&eacute;', $fichier);
			$fichier = ereg_replace('�', '&Eacute;', $fichier);

			$fichier = ereg_replace('�', '&agrave;', $fichier);
			$fichier = ereg_replace('�', '&egrave;', $fichier);
			$fichier = ereg_replace('�', '&ugrave;', $fichier);
			$fichier = ereg_replace('�', '&Agrave;', $fichier);
			$fichier = ereg_replace('�', '&Egrave;', $fichier);
			$fichier = ereg_replace('�', '&Ugrave;', $fichier);

			$fichier = ereg_replace('�', '&acirc;', $fichier);
			$fichier = ereg_replace('�', '&ecirc;', $fichier);
			$fichier = ereg_replace('�', '&icirc;', $fichier);
			$fichier = ereg_replace('�', '&ocirc;', $fichier);
			$fichier = ereg_replace('�', '&ucirc;', $fichier);
			$fichier = ereg_replace('�', '&Acirc;', $fichier);
			$fichier = ereg_replace('�', '&Ecirc;', $fichier);
			$fichier = ereg_replace('�', '&Icirc;', $fichier);
			$fichier = ereg_replace('�', '&Ocirc;', $fichier);
			$fichier = ereg_replace('�', '&Ucirc;', $fichier);

			$fichier = ereg_replace('�', '&ccedil;', $fichier);
			$fichier = ereg_replace('�', '&Ccedil;', $fichier);

			$fichier = ereg_replace('�', '&laquo;', $fichier);
			$fichier = ereg_replace('�', '&raquo;', $fichier);

	
			$fichier = eregi_replace("(face *= *['\"\\]+)[^'\"\\]*georgia[^'\"\\]*(['\"\\]+)", "\\1Georgia,Garamond,Times,serif\\2", $fichier);
			$fichier = eregi_replace("(face *= *['\"\\]+)[^'\"\\]*verdana[^'\"\\]*(['\"\\]+)", "\\1Verdana,Arial,Helvetica,sans-serif\\2", $fichier);

			$f = fopen($nomfich, 'wb');
			fputs($f, $fichier);
			fclose($f);
		}
	}

	closedir($handle); 
}


changer_accents(".");
changer_accents("AIDE");


fin_admin("accents");

?>