<?php
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/pagina.php');
$boekid=$_GET['id'];
if (!is_numeric($boekid) || $boekid<1)
{
	header("Location: 404.php");
	die('Incorrecte parameter ontvangen.');
}
$boeknaam=geefEen('SELECT naam FROM fotoboeken WHERE id=?',array($boekid));
$notities=geefEen('SELECT notities FROM fotoboeken WHERE id=?',array($boekid));
$_SESSION['referrer'] = htmlentities($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8');

$controls=knopcode('bewerken','editor.php?type=fotoboek&amp;id='.$boekid,'Dit fotoboek bewerken');
$pagina=new Pagina($boeknaam,$controls);
$pagina->toonPrepagina();
// het album openen
if($fotodir = @opendir("./fotoalbums/$boekid"))
{
	// in de juiste vorm gieten
	while($entryName = readdir($fotodir))
	{
		$dirArray[] = $entryName;
	}
	closedir($fotodir);
	// aantal bestanden tellen
	$indexCount = count($dirArray);

	// sorteren
	sort($dirArray);
	$waregrootte=true;
	$aantal=0;

	$uitvoer="";

	for($index=0; $index < $indexCount; $index++)
	{
		if (substr($dirArray[$index], 0, 1) != ".")
		{
			$aantal++;
			$size=getimagesize('fotoalbums/' . $boekid . '/' .$dirArray[$index]);
			$width=$size[0];
			if ($width>='270')
			{
				$waregrootte=false;
			
				$uitvoer.="<a href=\"toonfoto.php?boekid=$boekid&amp;bestandsnr=$index\"><img class=\"thumb\" src=\"fotoalbums/$boekid";
				$thumbnail='fotoalbums/'.$boekid.'thumbnails/'.$dirArray[$index];

				if (file_exists($thumbnail))
				{
					$uitvoer.='thumbnails/'.$dirArray[$index].'"';
				}
				else
				{
					$uitvoer.='/'.$dirArray[$index].'" style="width:270px; height:200px"';
				}
				$uitvoer.=" alt=\"".$dirArray[$index]."\" /></a>";
			}
			else
			{
				$uitvoer.="<img class=\"thumb\" src=\"fotoalbums/$boekid/$dirArray[$index]\">";
			}
		}
	}

	toonIndienAanwezig($notities, '', '');
	if ($aantal==1)
		echo "Dit album bevat 1 foto.";
	else
		echo "Dit album bevat $aantal foto's.";

	if (!$waregrootte && $aantal==1)
		echo " Klik op de verkleinde foto om een vergroting te zien.";
	if (!$waregrootte && $aantal!=1)
		echo " Klik op de verkleinde foto's om een vergroting te zien.";

	echo '<br /><br />';
	echo $uitvoer;

}
else
{
	echo 'Dit album bestaat niet.<br />';
}
echo '<br /><a href="'.$_SESSION['referrer']."\">Terug</a>\n";
$pagina->toonPostPagina();
?>
