<?php
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/pagina.php');
$isadmin=isAdmin();
$connectie=newPDO();
$subid=$_GET['id'];
if (!is_numeric($subid) || $subid<=0)
{
	header("Location: 404.php");
	die('Incorrecte parameter ontvangen.');
}
$subnaam=geefEen('SELECT naam FROM subs WHERE id=?', array($subid));
$reactiesaan=geefEen('SELECT reacties_aan FROM subs WHERE id=?',array($subid));
$referrer = htmlentities($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8');
if ($reactiesaan && !empty($_POST))
{
	$auteur=$_POST['auteur'];
	$reactie=$_POST['reactie'];
	$antispam=strtolower($_POST['antispam']);
	if ($auteur && $reactie && ($antispam=='acht' || $antispam=='8'))
	{
		$datum=date('Y-m-d H:i:s');
		$prep=$connectie->prepare('INSERT INTO reacties(subid, auteur, tekst, datum) VALUES (?, ?, ?, ?)');
		$prep->execute(array($subid, $auteur, $reactie, $datum));
	}
}
$controls=knopcode('bewerken', "editor.php?type=sub&amp;id=$subid", "Bewerk deze sub");
$controls.=knopcode('verwijderen', "overzicht.php?type=sub&amp;actie=verwijderen&amp;id=$subid", "Verwijder deze sub");
if (geefEen('SELECT * FROM vorigesubs WHERE id= ?', array($subid)))
{
	$controls.=knopcode('vorigeversie', "editor.php?type=sub&amp;vorigeversie=1&amp;id=$subid", "Vorige versie");
}
$pagina=new Pagina($subnaam, $controls);
$pagina->toonPrePagina();

echo geefEen('SELECT tekst FROM subs WHERE id=?', array($subid));

$reacties=$connectie->prepare("SELECT *,DATE_FORMAT(datum, '%d-%m-%Y') AS rdatum,DATE_FORMAT(datum, '%H:%i') AS rtijd FROM reacties WHERE subid=? ORDER BY datum ASC");
$reacties->execute(array($subid));
foreach($reacties->fetchAll() as $reactie)
{
	echo '<div class="reactiecontainer">';
	echo "\nReactie van <strong>".$reactie['auteur']."</strong> op ".$reactie['rdatum']." om ".$reactie['rtijd'].":<br />\n";
	echo '<div class="reactie">'.$reactie['tekst']."</div></div>\n";
	$reactiesaanwezig=true;
}
if ($reactiesaanwezig || $reactiesaan)
	echo '<div class="reactiecontainer"><br />';

if ($reactiesaanwezig)
{
	if (!$reactiesaan)
	{
		echo 'Op dit bericht kan niet (meer) worden gereageerd.<br />';
	}
}
if ($reactiesaan)
{
	echo '<h3>Reageren:</h3><form name="reactie" method="post" action="toonsub.php?id='.$subid.'">
		<table>
		<tr><td>Naam:</td><td><input style="width: 300px;" name="auteur" maxlength="100" /></td></tr>
		<tr><td>Reactie:</td><td><textarea style="width: 300px; height: 80px;" name="reactie"></textarea></td></tr>
		<tr><td>Hoeveel is de<br />wortel uit 64?</td><td><input style="width: 300px;" name="antispam" /></td></tr>
		<tr><td colspan="100%"><input type="submit" value="Versturen" /></td></tr>
		</table></form>';
}
if ($reactiesaanwezig || $reactiesaan)
	echo '</div>';
#echo "<a href=\"$referrer\">Terug</a><br />";
$pagina->toonPostPagina();
?>
