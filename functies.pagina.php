<?php
/**
  * Functies voor het beheren van pagina's en elementen erop.
  */
function nieuweCategorie($naam, $alleentitel=false)
{
	return maakEen('INSERT INTO categorieen(`naam`,`alleentitel`) VALUES (?,?);',array($naam, (int)$alleentitel));
}
function nieuwFotoalbum($naam, $notities="")
{
	return maakEen('INSERT INTO fotoboeken(`naam`,`notities`) VALUES (?,?);',array($naam, $notities));
}

function wijzigCategorie($id, $naam=null, $alleentitel=null, $beschrijving=null)
{
	if ($naam!==null)
		geefEen('UPDATE categorieen SET `naam`=? WHERE id=?',array($naam,$id));
	if ($alleentitel!==null)
		geefEen('UPDATE categorieen SET `alleentitel`=? WHERE id=?',array(parseCheckboxAlsInt($alleentitel),$id));
	if($beschrijving!==null)
		geefEen('UPDATE categorieen SET `beschrijving`=? WHERE id=?',array($beschrijving,$id));
}
function wijzigFotoalbum($id, $naam=null, $notities=null)
{
	if ($naam!==null)
		geefEen('UPDATE fotoboeken SET `naam`=? WHERE id=?',array($naam,$id));
	if ($notities!==null)
		geefEen('UPDATE fotoboeken SET `notities`=? WHERE id=?',array($notities,$id));
}

function verwijderCategorie($id)
{
	geefEen('DELETE FROM categorieen WHERE id=?;',array($id));
}
function verwijderFotoalbum($id)
{
	geefEen('DELETE FROM fotoboeken WHERE id=?;',array($id));
}
function nieuweSub($titel,$tekst,$reacties_aan,$categorieid)
{
	if (!$reacties_aan)
		$reacties_aan='0';
	else
		$reacties_aan='1';

	$connectie=newPDO();
	$prep=$connectie->prepare('INSERT INTO subs(naam, tekst, reacties_aan, categorieid) VALUES ( ?, ?, ?, ?)');
	$prep->execute(array($titel, $tekst, $reacties_aan, $categorieid));
	return $connectie->lastInsertId();
}
function wijzigSub($id,$titel,$tekst,$reacties_aan,$categorieid)
{
	$reacties_aan=parseCheckboxAlsInt($reacties_aan);
	$connectie=newPDO();
	if(!geefEen('SELECT * FROM vorigesubs WHERE id=?',array($id)))
	{
		$prep=$connectie->prepare('INSERT INTO vorigesubs VALUES (?, \'\', \'\')');
		$prep->execute(array($id));
	}
	$prep=$connectie->prepare('UPDATE vorigesubs SET tekst=( SELECT tekst FROM subs WHERE id=? ) WHERE id=?');
	$prep->execute(array($id, $id));
	$prep=$connectie->prepare('UPDATE vorigesubs SET naam=( SELECT naam FROM subs WHERE id=? ) WHERE id=?');
	$prep->execute(array($id, $id));

	$prep=$connectie->prepare('UPDATE subs SET tekst= ?, naam= ?, reacties_aan=?, categorieid= ? WHERE id= ?');
	$prep->execute(array($tekst, $titel, $reacties_aan, $categorieid, $id));
}
function verwijderSub($id)
{
	geefEen('DELETE FROM subs WHERE id=?;',array($id));
	geefEen('DELETE FROM vorigesubs WHERE id=?;',array($id));
}


function maakBijschrift($hash, $bijschrift)
{
	geefEen('DELETE FROM bijschriften WHERE hash = ?',array($hash));
	geefEen('INSERT INTO bijschriften(hash,bijschrift) VALUES (?,?)',array($hash,$bijschrift));
}

function parseCheckboxAlsInt($waarde)
{
	if (!$waarde)
		return 0;
	else
		return 1;
}

function parseCheckBoxAlsBool($waarde)
{
	if (!$waarde)
		return false;
	else
		return true;
}

function geefPaginanaam($link)
{
	$link=geefUnfriendlyUrl($link);
	$pos=strrpos($link, '/', -1);
	$laatstedeel=substr($link, $pos);
	$split=explode('?',$laatstedeel);
	$vars=explode('&',$split[1]);
	$values=null;
	foreach ($vars as $var)
	{
		$temp=explode('=',$var);
		$values[$temp[0]]=$temp[1];
	}
	switch ($split[0])
	{
		case 'toonhoofdstuk.php':
			$sql='SELECT naam FROM hoofdstukken WHERE id=?';
			break;
		case 'toonsub.php':
			$sql='SELECT naam FROM subs WHERE id=?';
			break;
		case 'tooncategorie.php':
			if ($values['id']=='fotoboeken')
				return 'Fotoboeken';
			else
				$sql='SELECT naam FROM categorieen WHERE id=?';
			break;
		case 'toonfotoboek.php':
			$sql='SELECT naam FROM fotoboeken WHERE id=?';
			break;
	}
	if ($naam=geefEen($sql,array($values['id'])))
		return $naam;
	elseif ($naam=geefEen('SELECT naam FROM friendlyurls WHERE link=?',array($link)))
		return $naam;
	else
		return $link;
}
function knopcode($soort, $link, $beschrijving=null, $tekst=null, $formaat=20)
{
	return knopcode_js($soort, 'window.location=\''.$link.'\'', $beschrijving, $tekst, $formaat);
}
function knopcode_js($soort, $js, $beschrijving=null, $tekst=null, $formaat=20)
{
	$code='<button class="sys" onClick="'.$js.'"';
	if ($beschrijving)
		$code.=' title="'.$beschrijving.'"';
	$code.='><img class="sys" style="vertical-align: middle; width: '.$formaat.'px; height: '.$formaat.'px;"  src="';
	$code.=geefPictogram($soort);
	$code.='" alt="'.$beschrijving.'"';

	if ($beschrijving)
		$code.=' title="'.$beschrijving.'"';

	$code.='/>';
	if ($tekst)
		$code.='<span style="vertical-align: middle; ">&nbsp;'.$tekst.'</span>';
	$code.="</button>";
	return $code;
}
function knop_js($soort, $js, $beschrijving=null, $tekst=null, $formaat=20)
{
	echo knopcode_js($soort, $js, $beschrijving, $tekst, $formaat);
}
function knop($soort, $link, $beschrijving=null, $tekst=null, $formaat=20)
{
	echo knopcode($soort, $link, $beschrijving, $tekst, $formaat);
}
function geefPictogram($naam)
{
	$thema=geefInstelling('pictogrammenthema');
	if (!$thema)
		$thema='mono';
	$prefix=geefInstelling('cyndir').'sys/pictogrammen/'.$thema.'/';
	if (file_exists($prefix.$naam.'.png'))
		return $prefix.$naam.'.png';
	else
		return $prefix.$naam;
}

function woordlimiet($string, $lengte = 50, $ellips = "...")
{
	$string=strip_tags($string);
	$words = explode(' ', $string);
	if (count($words) > $lengte)
		return implode(' ', array_slice($words, 0, $lengte)) . $ellips;
	else
	return $string;
}
function toonIndienAanwezig($string, $voor=null, $na=null)
{
	if($string)
	{
		echo $voor;
		echo $string;
		echo $na;
	}
}

function toonDeelknoppen($sites=null)
{
	if (geefInstelling('facebook_share')==1)
        {
		echo '<br /><div class="fb-like" data-href="http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true" data-font="trebuchet ms"></div>';
        }
}

function geefMenu()
{
	$connectie=newPDO();
	$menu=$connectie->prepare('SELECT * FROM menu ORDER BY volgorde ASC;');
	$menu->execute();
	$menuitems=null;
	$eersteitem=true;
	foreach ($menu->fetchAll() as $menuitem)
	{
		$menuitem['naam'] = $menuitem['alias']?strtr($menuitem['alias'], array(' '=> '&nbsp;')):geefPaginanaam($menuitem['link']);
		if ($eersteitem)
		{
			// De . is nodig omdat het menu anders niet goed werkt in subdirectories.
			$menuitem['link']='./';
		}
		elseif($url=geefEen('SELECT naam FROM friendlyurls WHERE doel=?', array($menuitem['link'])))
		{
				$menuitem['link']=$url;
		}
		$menuitems[]=$menuitem;
		$eersteitem=false;
	}
	return $menuitems;
}

function vervangMenu($nieuwmenu)
{
	geefEen('DELETE FROM menu;',array());
	$teller=1;
	foreach ($nieuwmenu as $menuitem)
	{
		geefEen('INSERT INTO menu(volgorde,link,alias) VALUES(?,?,?);',array($teller,$menuitem['link'],$menuitem['alias']));
		$teller++;		
	}
}

function voegToeAanMenu($link,$alias="")
{
	$teller=geefEen('SELECT MAX(volgorde) FROM menu;',array())+1;
	geefEen('INSERT INTO menu(volgorde,link,alias) VALUES(?,?,?);',array($teller,$link,$alias));
}
