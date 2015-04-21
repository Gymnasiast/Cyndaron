<?php
header('HTTP/1.0 404 Not Found');

require_once(__DIR__.'/pagina.php');
$pagina=new Pagina('404: Not Found');
$pagina->maakNietDelen(true);
$pagina->toonPrePagina();
echo 'U heeft geprobeerd een pagina op te vragen die niet kon worden gevonden.';
$pagina->toonPostPagina();
