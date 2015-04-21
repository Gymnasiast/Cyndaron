<?php
function verwerkUrl($url)
{
	$url=geefUnfriendlyUrl($url);
	list($bestand,$rest)=explode('?',$url,2);
	$restarray=explode('&',$rest);
	$_GET=array('friendlyurls'=>true);
	foreach($restarray as $var)
	{
		list($key, $value)=explode('=', $var);
		$_GET[$key]=$value;
	}
	
	if (file_exists(__DIR__.'/'.$bestand))
	{
		include(__DIR__.'/'.$bestand);
	}
	else
	{
		include(__DIR__.'/404.php');
		die();
	}
}
function geefFriendlyUrl($url)
{
	if ($friendly=geefEen('SELECT naam FROM friendlyurls WHERE doel=?',array($url)))
		return $friendly;
	else
		return $url;
}
function geefUnfriendlyUrl($url)
{
	if ($unfriendly=geefEen('SELECT doel FROM friendlyurls WHERE naam=?',array($url)))
		return $unfriendly;
	else
		return $url;
}

function isDezelfdePagina($url1, $url2)
{
	$url1=geefUnfriendlyUrl($url1);
	$url2=geefUnfriendlyUrl($url2);

	if ($url1==$url2)
	{
		return true;
	}
	else
	{
		return false;
	}
}

function geefPostVeilig($var)
{
	return htmlentities($_POST[$var], null, 'UTF-8');
}

function geefGetVeilig($var)
{
	return htmlentities($_GET[$var], null, 'UTF-8');
}

function geefReferrerVeilig()
{
	return htmlentities($_SERVER['HTTP_REFERER'], null, 'UTF-8');
}

function maakFriendlyUrl($naam, $doel)
{
	maakEen('INSERT INTO friendlyurls(naam,doel) VALUES (?,?)', array($naam,$doel));
}

function verwijderFriendlyUrl($naam)
{
	maakEen('DELETE FROM friendlyurls WHERE naam=?',array($naam));
}

function geefUriPad()
{
	$dir=dirname($_SERVER[PHP_SELF]);
	if ($dir=='/')
		$dir='';
	return 'http://'.$_SERVER['HTTP_HOST'].$dir.'/';
}
