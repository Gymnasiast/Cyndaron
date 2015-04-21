<?php
require('check.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/pagina.php');

$actie=$_GET['actie'];

if ($actie=='bewerken')
{
	$hash=$_GET['id'];
	$bijschrift=$_POST['artikel'];

	maakBijschrift($hash, $bijschrift);
	nieuweMelding('Bijschrift bewerkt.');
}
