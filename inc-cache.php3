<?php
# Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");

include_local('inc-dir.php3');

# V�rif de p�remption d'une compil de squelette par rapport � son source
# et les fonctions utilisateurs agissant sur le compilateur.
# Ses fonctions internes sont suppos�es ne changer qu'� l'installation; 
# sinon vider explicitement par l'interface priv�e.

function squelette_obsolete($naissance, $source)
{
  $e = $GLOBALS['extension_squelette'];
  $x = (($GLOBALS['recalcul_squelettes'] == 'oui')
	  OR ((filemtime($source . ".$e") > $naissance)
	      OR (file_exists($source . '_fonctions.php3')
		  AND (filemtime($source . '_fonctions.php3')> $naissance))
	      OR (file_exists("ecrire/mes_options.php3")
		  AND (filemtime("ecrire/mes_options.php3") > $naissance))
	      OR (file_exists("mes_fonctions.php3")
		  AND (filemtime("mes_fonctions.php3") > $naissance) ) ) );
#  spip_log("squelette_obsolete $source " . ($x ? 'mauvais' : 'bon'));
  return $x;
}

# Retourne la fonction principale d'un squelette compil�.
# En lance la compilation s'il ne l'�tait pas.

function ramener_squelette($squelette)
{
  $e = $GLOBALS['extension_squelette'];
  $nom = $e . '_' . md5($squelette);
  $sourcefile = $squelette . ".$e";

  if (function_exists($nom))
    {
      spip_log("Squelette $squelette:\t($nom) d�j� en m�moire (INCLURE r�p�t�)");
      return $nom;
    }
# spip_log("demande verrou $squelette"); 
  clearstatcache();
  if (!$lock = fopen($sourcefile, 'rb'))
      $r = '';
  else
    {
#      spip_log("obtient verrou $squelette"); 
# empecher un meme calcul par 2 processus diff�rents en se r�servant le source
      while (!flock($lock, LOCK_EX));
# remplacer la ligne ci-dessus par les 3 suivantes pour d�monstration:
#  while (!flock($lock, LOCK_EX + LOCK_NB))
# {sleep(1);spip_log("Lock: $nom " . getmypid());}
#  sleep(3);
      $phpfile = subdir_skel() . $nom . '.php';
      if (file_exists($phpfile))
	{
	  if (!squelette_obsolete(filemtime($phpfile), $squelette))
	    {
	      include($phpfile);
	      if (function_exists($nom))
		{
		  spip_log("Squelette $squelette:\t($nom) charg�");
		  flock($lock, LOCK_UN);
		  return $nom;
		}
	    }
	  # Cache obsolete ou erron�.
	  @unlink($phpfile);
	}
      include_local("inc-calcul-squel.php3");
      $timer_a = explode(" ", microtime());
# si vous n'etes pas sous Windows, vous am�liorerez les perfs en 
# d�commentant les 2 lignes suivantes (quant � Windows, il fait: $r =""; !)
      $r = # function_exists('file_get_contents') ?
	# file_get_contents($spipfile) : 
	fread($lock, filesize($sourcefile));
    }
  if (!$r)
    {
      if ($lock) flock($lock, LOCK_UN);
      include_ecrire ("inc_presentation.php3");
      install_debut_html(_T('info_erreur_systeme'));
      echo $sourcefile, _L(' squelette illisible');
      install_fin_html();
      exit;
    }

  $r = calculer_squelette($r, $nom, $e);
  $timer_b = explode(" ", microtime());
  $timer = ceil(1000*($timer_b[0] + $timer_b[1]-$timer_a[0]-$timer_a[1]));
  $f=fopen($phpfile, "wb"); 
  fwrite($f,"<?php # $squelette pid: " .  getmypid() ."\n");
  fwrite($f,$r);
  fwrite($f,'?'.'>');
  fclose($f);
  flock($lock, LOCK_UN);
  spip_log("Squelette $squelette: ($nom)"  . strlen($r) . " octets, $timer ms");
  eval($r); # + rapide qu'un include puisqu'on l'a
  return $nom;
}

# Teste si le squelette PHP ayant produit un cache est obsolete

function generateur_obsolete($nom)
{
#  spip_log("Generateur de $nom");
  $d = subdir_skel() . $nom . '.php';
  if (file_exists($d))
    {
      $f = fopen($d, 'r');
      if ($f)
	{
	  $l = fgets($f,1024);
	  fclose($f);
	  if (preg_match('/<.php #\s(\S*)\s/', $l, $m))
	    return (squelette_obsolete(filemtime($d), $m[1]));
	}
    }
  return true;
}

# Controle la validit� d'un cache .
# retourne False ou un tableau de 3 �l�ments:
# - texte
# - date de naissance
# - pr�sence de php � r�executer
# Si pr�sent, on modifie $fraicheur (pass� en r�f�rence)
# pour qu'il indique la dur�e de vie restante

function page_perenne($lock, $file, &$fraicheur, $passer_outre)
{
  $naissance = filemtime($file);
  $t = time() - $naissance;
  if (($t > $fraicheur) && $passer_outre) return false; 
#  spip_log("Perenne: fraicheur ok");
# la ligne 1 contient un commentaire comportant successivement
# - la long�vit� du include le plus bref
# - le  type (html ou php)
# - le squelette ayant produit la page
# - d'autres info pour debug seulement
  $l = fgets($lock,1024);
  if ((!preg_match("/^<!--\s(\d+)\s(\w+)\s(\S+)\s/", $l, $m)) && $passer_outre)
# fichier non conforme, on ignore
    return false; 
#  spip_log("Perenne: contenu ok");
  $t =  $m[1] - $t;
  if (!$passer_outre) 
    {
      if ($t < 0) return false;
#  spip_log("Perenne: include ok");
      if (generateur_obsolete($m[3])) return false;
    }
#  spip_log("Perenne: generateur $m[3] ok");
  $fraicheur = $t;
  return array('texte' =>
# si vous n'etes pas sous Windows, vous am�liorerez les perfs en 
# d�commentant les 2 lignes suivantes (quant � Windows, il retourne "" !)
#        function_exists('file_get_contents') ?
#        substr(file_get_contents($file), strlen($l)) : 
	       fread($lock, filesize($file)),
	       'naissance' => $naissance,
	       'process_ins' => $m[2]);
}

# Retourne une page, d�crite par le tableau de 2 ou 3 �l�ments:
# 'texte' => la page
# 'process_ins' => 'html' ou 'php' si pr�sence d'un '<?php'
# 'naissance' => heure du calcul si d�j� calcul� (absent si nouveau)

# Si elle n'est pas dans le cache ou que celui-ci est inemployable,
# calcul de la page en appliquant la fonction $calcul sur $contexte
# (tableau de valeurs, hack standard pour langage comme PHP qui
# permettent toutes les horreurs mais pas les belles et utiles fermetures)
# et ecriture dans le cache sous le r�petoire $fraicheur.
# Celle-ci est pas�e par r�f�rence pour �tre chang�e
# $calcul est soit cherche_page_incluse soit cherche_page_incluante
# qui appelle toute deux cherche_page, qui construit le tableau a 2 �l�ments

# Les acc�s concurrents sont g�r�s par un verrou g�n�ral, 
# remplac� rapidement par un verrou sp�cifique

function ramener_cache($cle, $calcul, $contexte, &$fraicheur)
{
  # pas de mise en cache si:
  # - recherche (trop couteux de m�moriser une recherche pr�cise)
  # - valeurs hors URL (i.e. POST) sauf Forum qui les traite � part
  
  if ($GLOBALS['var_recherche']||
      ($HTTP_POST_VARS && !$GLOBALS['ajout_forum']))
      {
	include_local('inc-calcul.php3');
	return $calcul('', $contexte);
      }
# Bloquer/se faire bloquer par TOUS les cr�ateurs de cache
# Ce fichier sert de verrou (on est sur qu'il existe!).
  if (!$lock = fopen('inc-cache.php3', 'rb'))
    return(array('texte' => 'Cache en panne'));
  while (!flock($lock, LOCK_EX));
  $file = file_cache($cle, $fraicheur);
  if (!file_exists($file))
    {
      fclose(fopen($file,'w'));
      $obsolete = false;
      $usefile = false;
    }
  else
    {
      $obsolete = true;
      $usefile = ($GLOBALS['recalcul'] != 'oui');
    }
# Acqu�rir le verrou sp�cifique et lib�rer le pr�c�dent
# pour permettre d'autres calculs (notamment d'�ventuels include).
# Ouvrir par r+ verrouill� pour forcer un 2e processus de m�me intention
# � attendre le r�sulat du premier et s'en servir. 
# Pour voir, d�commenter le sleep ci-dessous,
# lancer 2 demandes d'une page (surtout � inclusion) et regarder spip_log
#  sleep(3);
#  spip_log("demande de verrou pour $cle"); 
  if (!$lock2 = fopen($file, 'r+b'))
    {
      flock($lock, LOCK_UN);
      return(array('texte' => 'Cache en panne'));
    }
  if (!flock($lock2, LOCK_EX + LOCK_NB))
    {
# un autre processus s'occupe du b�b�; 
# on se bloque dessus apr�s lib�ration du verrou g�n�ral
      flock($lock, LOCK_UN);
      $usefile = true;
      while(!flock($lock2, LOCK_EX));
    }
  else
    {
    flock($lock, LOCK_UN);
    }
#  spip_log("obtient verrou $cle et lib�re le g�n�ral"); 
  $passer_outre =  !(timeout(false,false));
  $r = ((!$usefile) && (!$passer_outre)) ? '' :
    page_perenne($lock2, $file, $fraicheur, $passer_outre);
  if ($r)
    {
#      spip_log("lib�re verrou $cle (page perenne)"); 
      flock($lock2, LOCK_UN);
      return $r;
    }
  if ($obsolete && (file_exists('inc-invalideur.php3')))
    {
      include_local('inc-invalideur.php3');
      supprime_invalideurs_inclus("hache='$file'");
    }
  include_local('inc-calcul.php3');
  if (!function_exists($calcul))
      {
	flock($lock2, LOCK_UN);
	return(array('texte' => 'Compilateur absent'));
      }
  $page = $calcul($file, $contexte);
  $texte = $page['texte'];
  $n = ($fraicheur ? strlen($texte) : 0);
  if (!$n)
    {
      flock($lock2, LOCK_UN);
      @unlink($file);
    }
  else
    {
      spip_log("Ecriture ($cle): $n octets (validit�: $fraicheur sec.)");
      fseek($lock2,0);
      fwrite($lock2, "<!-- $fraicheur\t" . 
	     $page['process_ins'] .
	     "\t" .
	     $page['invalideurs']['squelette'] .
	     "\t$cle  pid: " .  
	     getmypid() .
	     " -->\n");
      fwrite($lock2,$texte);
      flock($lock2, LOCK_UN);
      fclose($lock2);
      if (file_exists('inc-invalideur.php3'))
	{
	  include_local('inc-invalideur.php3');
	  maj_invalideurs($file, $page['invalideurs']);
	  if ($f = $contexte['cache_incluant'])
	    insere_invalideur(array($file => true), 'inclure', $f);
	}
    }
  return $page;
}

# retourne la date de naissance ou 0 si inexistant ou obsolete
# attention: ne controle pas l'obsolescence des includes et du squelette.
# Pas 100% fiable, donc, mais suffisant en pratique

function cv_du_cache($cle, $fraicheur)
{
  $file = file_cache($cle, $fraicheur);
  if (!file_exists($file))
    return 0;
  else
    {
      $naissance = filemtime($file);
      $t = time() - $naissance;
      return (($t > $fraicheur) ? 0 : $naissance);
    }
}

# d�truit tous les squelettes

function retire_caches_squelette()
{
  $i= 0 ;
  $dir = subdir_skel();
  if ($handle = @opendir($dir))
    {
      while (($fichier = readdir($handle)) != '') {
	if ($fichier[0] != '.') { @unlink("$dir$fichier"); $i++ ;}
      }
    }
  spip_log("Destruction des $i squelette(s)");
}

# d�truit toutes les pages cach�es et leurs invalideurs
function retire_caches_pages()
{
  $j = 0;
  foreach (alldir_cache() as $dir)
    { 
      if ($handle = opendir($dir))
	{
	  while (($subdir = readdir($handle)) != '') {
	    if (($subdir[0] != '.') && ($handle2 = opendir("$dir$subdir")))
	      {
		while (($fichier = readdir($handle2)) != '') {
		  if ($fichier[0] != '.')
		    { @unlink("$dir$subdir/$fichier"); $j++;}
		}
		@rmdir("$dir$subdir");
	      }
	  }
	}
    }
  spip_log("Destruction des $j cache(s)");
  if (file_exists('inc-invalideur.php3'))
    {
      include_local('inc-invalideur.php3');
      supprime_invalideurs();
    }
}

# elimine les caches obsoletes figurant dans le m�me rep que la page indiqu�e

function retire_vieux_caches($cle, $delais)
{
  $dir = dir_of_file_cache($cle, $delais);
  $tous = trouve_caches('retire_cond_cache', $delais, $dir);
  spip_log("nettoyage de $dir (" . count($tous) . " obsol�te(s)");
  if ($tous)
    {
      if (!file_exists('inc-invalideur.php3'))
	retire_caches($tous);
      else
	{
	  include_local('inc-invalideur.php3');
	  applique_invalideur($tous);
	}
    }
}

# trouve dans un r�pertoire les caches
# v�rifiant un pr�dicat binaire (donn� avec son premier argument)

function trouve_caches($cond, $arg, $rep)
{
  if ($handle = opendir($dir))
     {
       while (($fichier = readdir($handle)) != '') {
	 $path = "$dir/$fichier";
	 if ($cond($arg, $path)) $tous[] = $path;
       }
     }
   return $tous;
}

# Teste l'obsolescence d'un cache. 
# Celle de son include le + bref (indiqu�e ligne 1) serait + juste
# mais lors d'un balayage de r�pertoire, 
# ouvrir chaque fichier serait couteux, et de gain faible

function retire_cond_cache($arg,$path)
{
    return (filemtime($path) <  $arg);
}

# d�truit les caches donn�s en arguments.
# En fait il faudrait poser un verrou sur chaque fichier
# pour que ramener_cache ne puisse s'ex�cuter � ce moment-l�
# Trop cher pour une situation peu probable, mais � �tudier.

function retire_caches($caches)
{
  if ($caches)
    {
      foreach ($caches as $path)
	{ @unlink($GLOBALS['flag_ecrire'] ? ('../' . $path) : $path);}
    }
}
?>
