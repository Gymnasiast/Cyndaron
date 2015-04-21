<?php
require_once(__DIR__.'/pagina.php');
require_once(__DIR__.'/functies.lingo.php');
require_once(__DIR__.'/functies.db.php');

if (!$_SESSION)
	session_start();

if(!empty($_POST))
{
	if(!empty($_POST['login_naam']) && !empty($_POST['login_wach']))
	{
	        $login['naam'] = htmlentities($_POST['login_naam'], ENT_QUOTES, 'UTF-8');
	        $login['wach'] = hash('sha512',$_POST['login_wach']);

		$connectie=newPDO();

		$prep=$connectie->prepare('SELECT * FROM gebruikers WHERE gebruikersnaam=?');
		$prep->execute(array($login['naam']));
		$userdata=$prep->fetch();

		if (!$userdata)
		{
			$pagina=new Pagina(Lingo::geefTekst(7, 'Fout'));
			$pagina->maakNietDelen(true);
			$pagina->toonPrePagina();
			echo Lingo::geefTekst(107, 'Verkeerde gebruikersnaam.');
			$pagina->toonPostPagina();
		}
		elseif($userdata['wachtwoord']!==$login['wach'])
		{
			$pagina=new Pagina(Lingo::geefTekst(7, 'Fout'));
			$pagina->maakNietDelen(true);
			$pagina->toonPrePagina();
			echo Lingo::geefTekst(108, 'Verkeerd wachtwoord.');
			$pagina->toonPostPagina();
           	}
  		elseif($userdata['wachtwoord']==$login['wach'] && $userdata['gebruikersnaam']==$login['naam'])
		{
			$_SESSION['naam'] = $login['naam'];
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['niveau'] = $userdata['niveau'];
			nieuweMelding(Lingo::geefTekst(105, 'U bent ingelogd.'));
			if ($_SESSION['redirect'])
			{
				$_SESSION['request']=$_SESSION['redirect'];
				$_SESSION['redirect']=null;
			}
			else
				$_SESSION['request']='/';
			header('Location: '.$_SESSION['request']);

		}
		else
		{
			$pagina=new Pagina(Lingo::geefTekst(7, 'Fout'));
			$pagina->maakNietDelen(true);
			$pagina->toonPrePagina();
			echo 'Er is een fout opgetreden.';
			$pagina->toonPostPagina();
           	}
    	}
	else
	{
		$pagina=new Pagina(Lingo::geefTekst(7, 'Fout'));
		$pagina->maakNietDelen(true);
		$pagina->toonPrePagina();
		echo Lingo::geefTekst(107, 'Verkeerde gebruikersnaam.');
		$pagina->toonPostPagina();
	}
}
else
{
	if (!$_SESSION['redirect'])
		$_SESSION['redirect']=geefReferrerVeilig();
	$pagina=new Pagina(Lingo::geefTekst(101, 'Inloggen'));
	$pagina->maakNietDelen(true);
	$pagina->toonPrePagina();
	echo '
<form method="post" action="#">
<p>'.Lingo::geefTekst(104, 'Dit is bedoeld voor beheerders om wijzigingen aan de pagina aan te brengen. Als u hier toevallig terecht bent gekomen kunt u hier niets doen. U kunt dan klikken op &eacute;&eacute;n van de onderdelen in het menu.').'</p>
<p>'.Lingo::geefTekst(102, 'Gebruikersnaam').':<br /><input type="text" name="login_naam" maxlength="20" /></p>
<p>'.Lingo::geefTekst(103, 'Wachtwoord').':<br /><input type="password" name="login_wach" maxlength="20" /></p>
<p><input type="submit" name="submit" value="'.Lingo::geefTekst(101, 'Inloggen').'" /></p>
</form>
';
	$pagina->toonPostPagina();
}
?>
