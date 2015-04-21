<?php
require_once(__DIR__.'/functies.lingo.php');
require_once(__DIR__.'/functies.db.php');
require_once(__DIR__.'/functies.url.php');
require_once(__DIR__.'/pagina.php');
$connectie=newPDO();
$id=($_GET['id']);
if ($id!='fotoboeken' && (!is_numeric($id) || $id<0))
{
	header("Location: 404.php");
	die(Lingo::geefTekst(6, 'Incorrecte parameter ontvangen.'));
}
if ($id!='fotoboeken')
{
	$naam=geefEen("SELECT naam FROM categorieen WHERE id= ?;", array($id));
	$alleentitel=geefEen("SELECT alleentitel FROM categorieen WHERE id=?", array($id));
	$controls=knopcode('bewerken', "editor.php?type=categorie&amp;id=$id", "Bewerk deze categorie");
	$hpagina=new Pagina($naam,$controls);
	$hpagina->toonPrePagina();
	$beschrijving=geefEen('SELECT beschrijving FROM categorieen WHERE id= ?', array($id));
	echo $beschrijving;
	$paginas=$connectie->prepare('SELECT * FROM subs WHERE categorieid= ? ORDER BY id DESC');
	$paginas->execute(array($id));
	if ($alleentitel)
		echo '<ul class="zonderbullets">';
	foreach($paginas->fetchAll() as $pagina)
	{
		$link=geefFriendlyUrl('toonsub.php?id='.$pagina['id']);
		if($alleentitel)
		{
			echo '<li><h3><a href="'.$link.'">'.$pagina['naam'].'</a></h3></li>';
		}
		else
		{
			echo "\n<p><h3><a href=\"".$link.'">'.$pagina['naam']."</a></h3>\n";	
			echo woordlimiet($pagina['tekst'], 30, "..."). '<a href="'.$link.'"><br /><i>'.Lingo::geefTekst(201, 'Meer lezen...').'</i></a></p>';
		}
	}
	if ($alleentitel)
		 echo '</ul>';
}
else
{
	$hpagina=new Pagina('Fotoboeken');
	$hpagina->toonPrePagina();
	$fotoboeken=$connectie->prepare('SELECT * FROM fotoboeken ORDER BY id DESC');
	$fotoboeken->execute(array());
	echo '<ul class="zonderbullets">';
	foreach($fotoboeken->fetchAll() as $fotoboek)
	{
		$link=geefFriendlyUrl('toonfotoboek.php?id='.$fotoboek['id']);
		echo '<li><h3><a href="'.$link.'">'.$fotoboek['naam'].'</a></h3></li>';
	}
	echo '</ul>';
}
$hpagina->toonPostPagina();
