<?php
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/pagina.php');

$connectie=newPDO();
$leden=$connectie->query('SELECT * FROM leden ORDER BY achternaam,tussenvoegsel,voornaam;');
$pagina=new Pagina('Wie is wie');
$pagina->toonPrePagina();
echo '<table class="ledenlijst">';
foreach($leden as $lid)
{
	echo '<tr><td><img style="height: 150px;" alt="" src="afb/leden/'.$lid['foto'].'"/></td>';
	echo '<td><b><span style="text-decoration: underline;">'.$lid['voornaam'].' ';
	echo $lid['tussenvoegsel'];
	if (substr($lid['tussenvoegsel'],-1)!="'")
		echo ' ';
	echo $lid['achternaam'].'</span></b><br /><br />';
	echo $lid['functie'];
	if ($lid['opmerkingen'])
		echo '<br />'.$lid['opmerkingen'];
	echo '</td></tr>';
}
echo '</table>';
$pagina->toonPostPagina();
