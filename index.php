<?php
//
//
// internet provider log
// (c) Kamil Barański http://kamilbaranski.com/
// (nie byłoby tego skryptu, gdyby UPC dobrze wykonywało swoją robotę)
// disclaimer: AS IS. bez gwarancji.
// 
//
$wersja='0.7';
//
//
//
$opiswywolywania=
'// Skrypt służy do analizy stabilności połączenia internetowego.
// Należy jednorazowo dopisać do crona komendy, które skrypt wygeneruje
// (na końcu), a następnie przy użyciu skryptu można monitorować ilość
// oraz częstotliwość pakietów, które się zagubiły.
//
//
// możliwe wywołania skryptu:
//
// bez parametrów (wypisuje listę plików zaznaczając, gdzie były awarie)
// ?grep=201806 (ogranicza listę plików do np. danego miesiąca)
// ?plik=20180621google (podaje plik)
// ?dzien=20180621 (podaje raport dniowy)
// ?dzien=201806 (podaje raport miesięczny, etc)
// ?dzien=20180621,20180623 (podaje raport na konkretne daty)
// ?download=0 (plik lub raport podaje w plain text, a nie w txt do ściągnięcia)';




// --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//
// NAJPIERW USTAW PARAMETRY
//
// --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
$scriptName='isplog';
$dir='/var/www/html/'.$scriptName.'/data';				// gdzie mają być pliki?
$defaultdownload=true;								// plain text [false], czy txt do ściągnięcia (Content-Disposition: attachment) [true]
$hostarray=array(									// hostarray definiujemy tylko aby na dole pokazać idealny crontab. Może być tu dowolna ilość hostów.
	'8.8.8.8' => 'google',
	'213.180.141.140' => 'onet',
	'192.168.50.254' => 'linksys',
	'192.168.50.1' => 'upcmodem');
$pingcount=1;										// ile razy pingować (1-9) - to wykorzystujemy przy sprawdzaniu błędu (sprzed użycia sed)
// --------------------------------------------------------------------------------------------------------------------------------------------------------------------------






header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
error_reporting(E_ALL);
ini_set('display_errors', 1);


$download=isset($_GET['download']) ? $_GET['download'] : $defaultdownload;
if ($download!=$defaultdownload) {
  $downloadstr='&download='.$download;
} else {
  $downloadstr='';
};

if(isset($_GET['plik'])) {
  $plik=$dir.'/'.$_GET['plik'];
  if (strpos($plik,'..')!==false) { die('Sorry, no.'); };
  if (!file_exists($plik)) { die('File doesnt exist.'); };
  header('Content-Type: text/plain');
  if ($download) { header('Content-Disposition: attachment; filename='.basename($plik).'.txt'); };
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($plik));
  readfile($plik);
  exit;
};
$lf=chr(13).chr(10);			// ;)
if(isset($_GET['dzien'])) {
  $dzien=$_GET['dzien'];
  if (strpos($dzien,'..')!==false) { die('Sorry, no.'); };
  $raport='';
  
  $dzienarr=explode(',',$dzien);

  foreach($dzienarr as $dzienjeden) {
    $raport.=str_repeat('-',60).$lf.$lf;
	$raport.='*** '.$dzienjeden.':'.$lf.$lf;
    foreach(glob($dir.'/'.$dzienjeden.'*') as $plik) {
      $raport.=basename($plik).':'.$lf;
      $raport.=file_get_contents($plik).$lf.$lf;
    };
  };
  if ($download) {
    header('Content-Disposition: attachment; filename=raport_'.$dzien.'.txt');
  };
  header('Content-Type: text/plain');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . strlen($raport));
  echo $raport;
  exit;
};


?><html>
<head>
<title>Internet Provider Log</title>
<meta http-equiv="contents" content="text/html; charset=utf-8" />
<link rel="icon" href="internetproviderlog.png" sizes="225x225" type="image/png" />
<style>
#lista { 
	background:#f0f0f0;
	padding:10px;
}
.datalink {
	color:black;
	font-weight:bold;
}
.niedziala, .niedziala A {
	color:red;
	font-weight:bold;
}
.trocheniedziala, .trocheniedziala A {
	color:#D00000;
}
.raportzawszystkie {
	font-size:120%;
}
</style>
</head>
<body>
<pre><?php


if(isset($_GET['grep'])) {
  $grep=$_GET['grep'];
  if (strpos($grep,'..')!==false) { die('Sorry, no.'); };
} else {
  $grep='';  
};
$files=glob($dir.'/'.$grep.'*', GLOB_MARK); // scandir($dir);
for($i=0;$i<count($files);$i++) {
  if (substr($files[$i], -1)=='/') {
//    unset($files[$i]);
//	$i--;
    $files[$i]=basename($files[$i]).'/';
  } else { 
    $files[$i]=basename($files[$i]);
  }
}
$files=array_values($files);

echo '<h1>Internet Provider Log</h1>';
echo $opiswywolywania;
echo '<hr />';
if($grep=='') {
  echo '<h2>Wszystkie pliki</h2>';
} else {
  echo '<h2>Tylko pliki zaczynające się od '.$grep.'</h2>';
};

print_r($files);
echo '<hr />';
 
$maxlen=0;
foreach ($files as $dzien) {
  $maxlen=max($maxlen,strlen($dzien));
}


echo '<div id="lista">';
$data='';
$niedzialaarray=array();
foreach ($files as $dzien) {
  if (($dzien!='..') and ($dzien!='.') and (substr($dzien,-1)!='/')) {		// na razie nic tu nie robimy z folderami
	if(substr($dzien,0,8)!=$data) {
	  if ($data!='') { echo '<br />'; };
	  $data=substr($dzien,0,8);
	  echo '<a href="?dzien='.$data.$downloadstr.'" class="datalink">'.$data.'</a><br />';
	};
    $zaw=file_get_contents($dir.'/'.$dzien);
	$zaw=explode(chr(10),$zaw);
	for ($i=0;$i<count($zaw);$i++) {
		$zaw[$i]=substr($zaw[$i],strpos($zaw[$i],' '));
	};
	$zaw=implode(chr(10),$zaw);
    $dziala=substr_count($zaw,$pingcount)+substr_count($zaw,'.');		// kompatybilność z plikami sprzed replace $pingcount na kropkę
    $niedziala=substr_count($zaw,'0');
	if ($niedziala>3) {
		echo '<span class="niedziala">';
		array_push($niedzialaarray,$data);
	} else if ($niedziala>0) {
		echo '<span class="trocheniedziala">';
		array_push($niedzialaarray,$data);
	} else {
		echo '<span>';
	}
    echo '<a href="?plik='.$dzien.$downloadstr.'">';
	echo $dzien.'</a>'.str_repeat(' ',($maxlen-strlen($dzien))).' - ';
    echo $dziala.'/'.($dziala+$niedziala).' poprawnie ('.$niedziala.' nie wróciło).';
	echo '</span><br />';
  }
}
echo '</div>';


?>
<hr /><button onclick="window.getSelection().selectAllChildren(document.getElementById('lista'));">zaznacz całość</button> | <?php

$niedzialaarray=array_unique($niedzialaarray);
if (count($niedzialaarray)>0) {
  echo '<span class="niedziala raportzawszystkie"><a href="?dzien='.join(',',$niedzialaarray).$downloadstr.'">Raport za wszystkie '.count($niedzialaarray).' dni z awariami</a></span>';
};

echo '<hr />';


$firstpart='';
$secondpart='';
foreach ($hostarray as $ip => $hostname) {
	$firstpart.="* * * * * echo -n `ping -c ".$pingcount." -q ".$ip." | grep transmitted | cut -d ' ' -f4 | sed -e 's/".$pingcount."/./g'` >> ".$dir."/`date +\%Y\%m\%d`".$hostname.'.log'.chr(13).chr(10);
	$secondpart.="59 * * * * sleep 40 ; echo >> ".$dir."/`date +\%Y\%m\%d`".$hostname.".log ; echo -ne `date +\%H --date='+1 minute'`: \\\\c >> ".$dir."/`date +\%Y\%m\%d --date='+1 minute'`".$hostname.'.log'.chr(13).chr(10);
	// (uwaga, \\ jest escape'owany)
}
$defaultcron=$firstpart.$secondpart;

$currentcron=`sudo crontab -l | grep {$scriptName}`;

//ech.
$wymiany=array(chr(13).chr(10)=>chr(10),chr(13)=>chr(10));
$defaultcron=rtrim(strtr($defaultcron,$wymiany));
$currentcron=rtrim(strtr($currentcron,$wymiany));

if ($currentcron==$defaultcron) {
  echo 'Current cron=default cron:'.$lf.$lf.$currentcron;
} else {
  echo 'Current cron:'.$lf.$lf.$currentcron.'<hr />'.'Default cron:'.$lf.$lf.$defaultcron;
};

?><hr />v<?=$wersja;?> &copy; Kamil Barański http://kamilbaranski.com/ 2018-2021 [current time=<?=date('r');?>] // (disclaimer: nie byłoby tego skryptu, gdyby UPC dobrze wykonywało swoją robotę)
</pre>
</body>
</html>