<?php
require_once(__DIR__.'/check.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/pagina.php');

$actie=$_GET['actie'];

if ($actie=='bewerken')
{
	$id=$_GET['id'];
	$naam=$_POST['titel'];
	$notities=$_POST['artikel'];

	if ($id>0) // Als het id is meegegeven bestond de categorie al.
	{
		wijzigFotoalbum($id,$naam,$notities);
	}
	else
	{
		$id=nieuwFotoalbum($naam,$notities);
	}
	
	nieuweMelding('Fotoboek bewerkt.');
	$returnUrl='toonfotoboek.php?id='.$id;
}
