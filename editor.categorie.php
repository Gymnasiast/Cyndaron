<?php
$heeftTitel=true;

if ($id)
{
	$content=geefEen('SELECT beschrijving FROM categorieen WHERE id=?', array($id));
	$titel=geefEen('SELECT naam FROM categorieen WHERE id=?',array($id));
}

function toonSpecifiekeKnoppen()
{
	global $id;
	echo '<input name="alleentitel" type="checkbox" ';
	if (geefEen('SELECT alleentitel FROM categorieen WHERE id=?', array($id)))
		echo 'checked="checked"';
	echo '/> Toon alleen titels<br />';

	echo '</td></tr><tr><td class="tablesys">';
}
