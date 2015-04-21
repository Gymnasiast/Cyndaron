<?php
require_once(__DIR__.'/functies.gebruikers.php');
/*
* Require deze pagina bovenaan iedere pagina die alleen voor members toegankelijk mag zijn.
* Dit moet helemaal bovenaan, omdat anders de session_start() & header() functie niet werken.
*/
session_start();

if(!isset($_SESSION['naam']) OR $_SESSION['ip'] != $_SERVER['REMOTE_ADDR'])
{
	session_destroy(); 
	session_start();
	nieuweMelding('U moet inloggen om deze pagina te bekijken');
	$_SESSION['redirect']=$_SERVER['REQUEST_URI'];
    	header('Location: login.php');
	die();
}
