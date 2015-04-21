<?php
require_once(__DIR__.'/check.php');
require_once(__DIR__.'/functies.url.php');

$id = geefGetVeilig('id');
$type = geefGetVeilig('type');
$returnUrl = geefGetVeilig('returnUrl');


if (@file_exists('bewerk.'.$type.'.php'))
{
	require('bewerk.'.$type.'.php');
}
else
{
	die ('Ongeldig paginatype!');
}

if ($friendlyUrl=geefPostVeilig('friendlyUrl'))
{
	$unfriendlyUrl='toon'.$type.'.php?id='.$id;
	$oudeFriendlyUrl=geefFriendlyUrl($unfriendlyUrl);
	verwijderFriendlyUrl($oudeFriendlyUrl);
	maakFriendlyUrl($friendlyUrl, $unfriendlyUrl);
	// Als de friendly URL gebruikt is in het menu moet deze daar ook worden aangepast
	geefEen('UPDATE menu SET link = ? WHERE link = ?',array($friendlyUrl, $oudeFriendlyUrl));
}
if (!$returnUrl)
{
	$returnUrl=$_SESSION['referrer'];
	$returnUrl=strtr($returnUrl,array('&amp;'=>'&'));
}
header('Location: '.$returnUrl);
?>
