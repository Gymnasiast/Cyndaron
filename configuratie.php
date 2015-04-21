<?php
require_once(__DIR__.'/check.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.cyndaron.php');
require_once(__DIR__.'/pagina.php');
if ($_POST)
{
	maakInstelling('websitenaam', $_POST['websitenaam']);
	maakInstelling('ondertitel', $_POST['ondertitel']);
	maakInstelling('paneel', $_POST['paneel']);
	maakInstelling('favicon', $_POST['favicon']);
	maakInstelling('achtergrondkleur', $_POST['achtergrondkleur']);
	maakInstelling('menukleur', $_POST['menukleur']);
	maakInstelling('menuachtergrond', $_POST['menuachtergrond']);
	maakInstelling('artikelkleur', $_POST['artikelkleur']);
	maakInstelling('pictogrammenthema', $_POST['pictogrammenthema']);
	maakInstelling('standaardcategorie', $_POST['standaardcategorie']);
	maakInstelling('facebook_share', $_POST['facebook_share']);
	maakInstelling('extra_bodycode', $_POST['extra_bodycode']);
	$menu=$_POST['menu'];
	$split1=explode(';',$menu);
	$nieuwmenu=null;

	foreach ($split1 as $split2)
	{
		$menuitem=explode('|',$split2);

		if ($menuitem[0])
		{
			$nieuwmenu[]=array('link'=>$menuitem[0],'alias'=>$menuitem[1]);
		}
	}
	vervangMenu($nieuwmenu);
}
$pagina=new Pagina('Configuratie');
$pagina->maakNietDelen(true);
$pagina->toonPrePagina();
?>
<script type="text/javascript">
/* <![CDATA[ */
function testkleuren()
{
	var body = document.getElementsByTagName('body');
	var bodyset = document.getElementsByName('achtergrondkleur');
	body[0].style.backgroundColor = bodyset[0].value;
	var menu = document.getElementsByClassName('menu');
	var menuset = document.getElementsByName('menukleur');
	var menuag = document.getElementsByName('menuachtergrond');
	menu[0].style.backgroundColor = menuset[0].value;
	menu[0].style.backgroundImage = "url('" + menuag[0].value + "')";
	var artikel = document.getElementsByClassName('inhoud');
	var artikelset = document.getElementsByName('artikelkleur');
	artikel[0].style.backgroundColor = artikelset[0].value;
}
/* ]]> */
</script>
<form method="post" action="configuratie.php">
<table>
<?php
echo '<tr><td>Naam website:</td><td><input type="text" name="websitenaam" value="'.geefInstelling('websitenaam').'" /></td></tr>';
echo '<tr><td>Ondertitel:</td><td><input type="text" name="ondertitel" value="'.geefInstelling('ondertitel').'" /></td></tr>';
echo '<tr><td>Paneel:</td><td><input type="text" name="paneel" value="'.geefInstelling('paneel').'" /></td></tr>';
echo '<tr><td>Websitepictogram:</td><td><input type="text" name="favicon" value="'.geefInstelling('favicon').'" /></td></tr>';
echo '<tr><td>Achtergrondkleur hele pagina:</td><td><input type="text" name="achtergrondkleur" value="'.geefInstelling('achtergrondkleur').'" /></td></tr>';
echo '<tr><td>Achtergrondkleur menu:</td><td><input type="text" name="menukleur" value="'.geefInstelling('menukleur').'" /></td></tr>';
echo '<tr><td>Achtergrondafbeelding menu:</td><td><input type="text" name="menuachtergrond" value="'.geefInstelling('menuachtergrond').'" /></td></tr>';
echo '<tr><td>Achtergrondkleur artikel:</td><td><input type="text" name="artikelkleur" value="'.geefInstelling('artikelkleur').'" /></td></tr>';
if (geefInstelling('facebook_share')==1)
	$fbselected=' checked="checked"';
echo '<tr><td>Facebookintegratie:</td><td><input type="checkbox" name="facebook_share" value="1"'.$fbselected.' /></td></tr>';
echo '<tr><td>Pictogrammenthema:</td><td><select name="pictogrammenthema">';

if($themata = @opendir("./sys/pictogrammen"))
{
	while($entryName = readdir($themata))
	{
		$dirArray[] = $entryName;
	}
	closedir($themata);
	$indexCount = count($dirArray);
	sort($dirArray);
	foreach ($dirArray as $thema)
	{
	        if (substr($thema, 0, 1) != ".")
		{
			echo '<option value="'.$thema.'"';
			if ($thema==geefInstelling('pictogrammenthema'))
			{
				echo ' selected="selected"';
			}
			echo '>'.$thema.'</option>';
		}
	}
}
echo '</select></td></tr>';
echo '<tr><td>Standaardcategorie:</td><td><select name="standaardcategorie">';
echo '<option value="0"';
$standaardcategorie=geefInstelling('standaardcategorie');
if ($standaardcategorie==0)
{
	echo ' selected="selected"';
}
echo '>Geen</option>';
$connectie=newPDO();
$categorieen=$connectie->prepare('SELECT id,naam FROM categorieen ORDER BY id ASC');
$categorieen->execute();
foreach ($categorieen as $categorie)
{
	if ($categorie['id']==$standaardcategorie)
		$selected=' selected="selected"';
	echo '<option value="'.$categorie['id'].'"'.$selected.'>'.$categorie['naam'].'</option>';
}
echo '</select></td></tr>';
echo '<tr><td>Menu</td><td><input type="text" name="menu" value="';

$menu=$connectie->prepare('SELECT link,alias FROM menu ORDER BY volgorde ASC;');
$menu->execute();
foreach ($menu as $menuitem)
{
	$link=htmlentities($menuitem['link'],null,'UTF-8');
	$alias=htmlentities($menuitem['alias'],null,'UTF-8');

	echo $link.'|'.$alias.';';
}
echo '"/></td></tr>';
echo '<tr><td>Extra bodycode (o.a. Google Analytics)</td><td><textarea style="width: 225px; height: 75px;" name="extra_bodycode">'.geefInstelling('extra_bodycode').'</textarea></td></tr>';
echo '</table>';
echo '<input type="button" onclick="testkleuren();" value="Test kleuren" />';
echo '<input type="submit" value="Opslaan" /></form>';
echo '<h2>Informatie</h2>';
echo geefProductNaam().' '.geefProductVersie().' ('.geefProductCodenaam().')<br />';
echo '© Michael Steenbeek, 2009-2014<br />';
echo 'Beschikbaar onder de ISC-licentie (zie het bestand LICENSE), m.u.v. CKeditor.<br />';
echo 'CKeditor gebruikt onder LGPL-licentie.<br />';
echo 'Engineversie: '.geefCyndaronVersie();
$pagina->toonPostPagina();
