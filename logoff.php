<?php
require_once(__DIR__.'/functies.gebruikers.php');

session_start(); 
session_destroy(); 

session_start();
nieuweMelding('U bent afgemeld.');
header('Location: ./');
