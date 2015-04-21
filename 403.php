<?php
header('HTTP/1.0 403 Forbidden');

require_once(__DIR__.'/pagina.php');
$pagina=new Pagina('403: Forbidden');
$pagina->maakNietDelen(true);
$pagina->toonPrePagina();
echo 'U heeft geprobeerd een pagina op te vragen die niet mag worden opgevraagd.';
$pagina->toonPostPagina();
