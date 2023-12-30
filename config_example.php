<?php

// gdzie mają być pliki? (Podzielone na dwie zmienne, aby grepować crontab)
$scriptName = 'isplog';
$dir = '/var/www/html/' . $scriptName . '/data';

// plain text [false], czy txt do ściągnięcia (Content-Disposition: attachment) [true]
$defaultDownload = true;

// hostArray definiujemy tylko dla pingHosts.php. Może być tu dowolna ilość hostów.
$hosts = array(
	'8.8.8.8' => 'google',
	'75.2.92.173' => 'onet',
	'192.168.50.254' => 'linksys',
	'192.168.50.1' => 'upcmodem'
);

// ile razy pingować (1-9) - to wykorzystujemy przy sprawdzaniu błędu (sprzed użycia sed)
$pingCount = 1;
