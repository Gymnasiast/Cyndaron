<?php
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/pagina.php');
$pagina=new Pagina('Idee&euml;nbus');
$pagina->toonPrePagina();
$actie=$_GET['actie'];
$connectie=newPDO();
if ($actie!='verwijderen')
{

	if (!empty($_POST))
	{
		$naam=htmlentities($_POST['naam'], ENT_QUOTES, 'utf-8');
		$tekst=htmlentities($_POST['tekst'], ENT_QUOTES, 'utf-8');
		$input=$connectie->prepare('INSERT INTO ideeen VALUES (NULL, ?, ?)');
		$input->execute(array($naam, $tekst));
		echo '<p>Commentaar is achtergelaten.</p><br />';
	}
	else
	{
		echo '<form method="post" action="ideeenbus.php"><p>Uw naam:<br /><input type="text" name="naam" /><br />Uw idee:<br />
			<textarea name="tekst" rows="6" cols="60"></textarea><br /><input type="submit" value="Versturen" /></p></form>';
	}
	$inhoud=$connectie->prepare("SELECT id, naam, tekst FROM ideeen ORDER BY id DESC ;");
	$inhoud->execute();
	foreach ($inhoud->fetchAll() as $idee)
	{
		echo '<p>';
		if (isAdmin())
		{
			knop('verwijderen', 'ideeenbus.php?actie=verwijderen&amp;id='.$idee['id'], 'Verwijder dit idee');
		}
		echo '<b>Achtergelaten door: '.$idee['naam'].'</b><br />'.$idee['tekst'].'</p>';
	}

}
else
{
	if (isAdmin())
	{
		$id=$_GET['id'];
		$deletion=$connectie->prepare('DELETE FROM ideeen WHERE id=?');
		$deletion->execute(array($id));
		echo 'Idee verwijderd.';
	}
}	
$pagina->toonPostPagina();