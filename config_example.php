<?php

// gdzie mają być pliki? (Podzielone na dwie zmienne, aby grepować crontab)
$scriptName='isplog';
$dir='/var/www/html/'.$scriptName.'/data';

// plain text [false], czy txt do ściągnięcia (Content-Disposition: attachment) [true]
$defaultdownload=true;

// hostarray definiujemy tylko aby na dole pokazać idealny crontab. Może być tu dowolna ilość hostów.
$hostarray=array(
	'8.8.8.8' => 'google',
	'213.180.141.140' => 'onet',
	'192.168.50.254' => 'linksys',
	'192.168.50.1' => 'upcmodem');

// ile razy pingować (1-9) - to wykorzystujemy przy sprawdzaniu błędu (sprzed użycia sed)
$pingcount=1;
