<?php
$heeftTitel=true;

if ($id)
{
	$content=geefEen('SELECT tekst FROM '.$vvstring.'subs WHERE id=?', array($id));
	$titel=geefEen('SELECT naam FROM '.$vvstring.'subs WHERE id=?',array($id));
}

function toonSpecifiekeKnoppen()
{
	global $id;
	echo '<input name="reacties_aan" type="checkbox" ';
	if (geefEen('SELECT reacties_aan FROM subs WHERE id=?', array($id)))
		echo 'checked="checked"';
	echo '/> Reacties aan<br />';
	echo 'Plaats dit artikel in de categorie ';
	echo '<select name="categorieid"><option value="0">&lt;Geen categorie&gt;</option>';

	$connectie=newPDO();

	if($id)	
		$categorieid=geefEen('SELECT categorieid FROM subs WHERE id= ?',array($id));
	else
		$categorieid=geefInstelling('standaardcategorie');

	$categorieen=$connectie->query("SELECT * FROM categorieen ORDER BY naam;");
	foreach ($categorieen->fetchAll() as $categorie)	
	{
		echo '<option value="'.$categorie['id'].'" ';
		if ($categorieid==$categorie['id'])
		{
			echo 'selected="selected"';
		}
		echo '>'.$categorie['naam'].'</option>';
	}
	echo '</select></td></tr><tr><td class="tablesys">';
}
