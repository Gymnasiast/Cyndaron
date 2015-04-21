<?php
require_once(__DIR__.'/check.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/pagina.php');

$actie=$_GET['actie'];

if ($actie=='bewerken')
{
	$id=$_GET['id'];
	$titel=$_POST['titel'];
	$tekst=$_POST['artikel'];
	$reacties_aan=$_POST['reacties_aan'];
	$categorieid=$_POST['categorieid'];

	if (!$categorieid)
		$categorieid='0';

	if ($id>0) // Als het id is meegegeven bestond de sub al. In dat geval moet er geüpdatet worden. Anders moet het toegevoegd worden onder vermelding van een naam/titel.
	{
		wijzigSub($id,$titel,$tekst,$reacties_aan,$categorieid);
	}
	else
	{
		$id=nieuweSub($titel,$tekst,$reacties_aan,$categorieid);
	}
	
	nieuweMelding('Pagina bewerkt.');
	$returnUrl='toonsub.php?id='.$id;
}
