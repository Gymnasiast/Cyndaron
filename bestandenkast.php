<?php
require_once(__DIR__.'/pagina.php');

$pagina=new Pagina('Bestandenkast');
$pagina->toonPrePagina();
$includefile = './bestandenkast/include.html';
if ($handle = @fopen($includefile, 'r'))
{
	$contents = fread($handle, filesize($includefile));
	preg_match("/<body(.*?)>(.*?)<\\/body>/si", $contents, $match);
	echo $match[2]; 
	fclose($handle);
}

if($bestandendir = @opendir("./bestandenkast"))
{
	// Bestanden inlezen
	while($entryName = readdir($bestandendir))
	{
		$dirArray[] = $entryName;
	}
	closedir($bestandendir);

	$aantalBestanden = count($dirArray);
	sort($dirArray);

	// Einde begeleidende tekst, begin bestandenlijst
	echo '<hr />';
	echo '<ul>';

	for($index=0; $index < $aantalBestanden; $index++)
	{	
        	if ((substr("$dirArray[$index]", 0, 1) != ".") && (substr("$dirArray[$index]", -4) != "html") && (substr("$dirArray[$index]", -3) != "php")) // verberg eventuele verborgen bestanden plus html- en php-bestanden
		{
			echo '<li><a href="./bestandenkast/'.$dirArray[$index].'">'.pathinfo($dirArray[$index], PATHINFO_FILENAME).'</a></li>';
		}
	}
	echo '</ul>';
}
$pagina->toonPostPagina();

?>
