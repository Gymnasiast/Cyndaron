<?php
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.pagina.php');
require_once(__DIR__.'/functies.gebruikers.php');
require_once(__DIR__.'/pagina.php');

$bestandsnr=$_GET['bestandsnr'];
$boekid=htmlentities($_GET['boekid'], null, 'UTF-8');
if (!is_numeric($boekid) || $boekid<1 || !is_numeric($bestandsnr) || $bestandsnr<0)
{
	header("Location: 404.php");
	die('Incorrecte parameter ontvangen.');
}
$boeknaam=geefEen('SELECT naam FROM fotoboeken WHERE id=?', array($boekid));
$pagina=new Pagina($boeknaam);
$pagina->toonPrePagina();
echo '<div class="artikel" style="text-align: center;">';
// het album openen
if ($fotodir = @opendir("./fotoalbums/$boekid"))
{
	// in de juiste vorm gieten
	while($entryName = readdir($fotodir))
	{
		$dirArray[] = $entryName;
	}
	// en de map sluiten
	closedir($fotodir);
	// aantal bestanden tellen
	$indexCount = count($dirArray);

	// sorteren
	sort($dirArray);

	$bestandsnaam=$dirArray[$bestandsnr];
	$pad='fotoalbums/'.$boekid.'/';

	if (substr("$bestandsnaam", 0, 1) == "." || !file_exists($pad.$dirArray[$bestandsnr]))
	{
		header("Location: 404.php");
		die('Dit bestand is geen foto.');
	}

	echo '<table style="margin-left: auto; margin-right: auto; "><tr><td class="tablesys" style="padding: 0 0 0 0; width: 36px;">';

	$vorige=$bestandsnr-1;
	while ($vorige>=0){
		if(@file_exists($pad.$dirArray[$vorige]) && substr("$dirArray[$vorige]", 0, 1) != ".")
		{
			$vorigefotolink='toonfoto.php?boekid='.$boekid.'&amp;bestandsnr='.$vorige;
			knop('terug.png',$vorigefotolink,'Vorige foto');
			$vorigefotojs=strtr("\ncase 37:\nwindow.location = \"".$vorigefotolink."\";\nbreak;\n",array('&amp;'=>'&'));
			break;
		}
		else
		{
			$vorige--;
		}
	}

	echo '</td><td class="tablesys" style="padding: 0 0 0 0; width: 36px;">';
	knop('album.png', 'toonfotoboek.php?id='.$boekid, 'Terug naar album');
	echo '</td><td class="tablesys" style="padding: 0 0 0 0; width: 36px;">';
	
	$volgende=$bestandsnr+1;
	while($volgende<$indexCount)
	{
		if(@file_exists($pad.$dirArray[$volgende]) && substr("$dirArray[$volgende]", 0, 1) != ".")
		{
			$volgendefotolink='toonfoto.php?boekid='.$boekid.'&amp;bestandsnr='.$volgende;
			knop('vooruit.png',$volgendefotolink,'Volgende foto');
			$volgendefotojs=strtr("\ncase 39:\nwindow.location = \"".$volgendefotolink."\";\nbreak;\n",array('&amp;'=>'&'));
			break;
		}
		else
		{
			$volgende++;
		}
	}

	echo '</td></tr></table>';

	$size=getimagesize($pad.$bestandsnaam);
	if($size[0]>1024)
	{
		$stijl='style="width: 1024px;"';		
	}
	echo "<br />\n<img src=\"./".$pad.$bestandsnaam."\" alt=\"$bestandsnaam\" $stijl />";
        $hash=md5_file($pad.$bestandsnaam);
	if (isAdmin())
	{
		echo "<br />\n";
		knop('bewerken','editor.php?type=foto&amp;id='.$hash, 'Bijschrift bewerken', 'Bijschrift bewerken', 16);
	}
	//echo '<!-- Hash van de foto: '.$hash.'-->';
        if ($bijschrift=geefEen('SELECT bijschrift FROM bijschriften WHERE hash=?',array($hash)))
        {
            echo '<div class="bijschrift">'.$bijschrift.'</div>';
        }
	echo '
	<script type="text/javascript">
	document.onkeyup = KeyCheck;      
     
    	function KeyCheck(e)
        {
           var KeyID = (window.event) ? event.keyCode : e.keyCode;
         
           switch(KeyID)
           {'.$vorigefotojs.'
		case 38:
		window.location= "toonfotoboek.php?id='.$boekid.'";
		break;
		'.$volgendefotojs.'
           }
        }
	</script>';
}
else
{
	echo 'Deze foto of dit album bestaat niet.';
}
echo '</div>';
$pagina->toonPostPagina();
?>
