<?php
$fond = "backend";
$delais = 3600;

// cette ligne empeche l'affichage des boutons d'administration
// Et les headers !!!!

$flag_preserver = true;

@header("Content-type: text/xml");

include ("inc-public.php3");

?>
