<?php
//
//
// internet provider log
// (c) Kamil Barański http://kamilbaranski.com/
// (nie byłoby tego skryptu, gdyby UPC dobrze wykonywało swoją robotę)
// disclaimer: AS IS. bez gwarancji.
//
// NAJPIERW USTAW PARAMETRY (copy config_example.php to config.php!)
// 
//
$wersja = '0.7a';
//
//
//
$opiswywolywania =
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



include 'config.php';



header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
error_reporting(E_ALL);
ini_set('display_errors', 1);


$download = isset($_GET['download']) ? $_GET['download'] : $defaultDownload;
if ($download != $defaultDownload) {
  $downloadstr = '&download=' . $download;
} else {
  $downloadstr = '';
};

if (isset($_GET['plik'])) {
  $plik = $logPath . '/' . $_GET['plik'];
  if (strpos($plik, '..') !== false) {
    die('Sorry, no.');
  };
  if (!file_exists($plik)) {
    die('File doesnt exist.');
  };
  header('Content-Type: text/plain');
  if ($download) {
    header('Content-Disposition: attachment; filename=' . basename($plik) . '.txt');
  };
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($plik));
  readfile($plik);
  exit;
};
$lf = chr(13) . chr(10);      // ;)
if (isset($_GET['dzien'])) {
  $dzien = $_GET['dzien'];
  if (strpos($dzien, '..') !== false) {
    die('Sorry, no.');
  };
  $raport = '';

  $dzienarr = explode(',', $dzien);

  foreach ($dzienarr as $dzienjeden) {
    $raport .= str_repeat('-', 60) . $lf . $lf;
    $raport .= '*** ' . $dzienjeden . ':' . $lf . $lf;
    foreach (glob($logPath . '/' . $dzienjeden . '*') as $plik) {
      $raport .= basename($plik) . ':' . $lf;
      $raport .= file_get_contents($plik) . $lf . $lf;
    };
  };
  if ($download) {
    header('Content-Disposition: attachment; filename=raport_' . $dzien . '.txt');
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
      background: #f0f0f0;
      padding: 10px;
    }

    .datalink {
      color: black;
      font-weight: bold;
    }

    .niedziala,
    .niedziala A {
      color: red;
      font-weight: bold;
    }

    .trocheniedziala,
    .trocheniedziala A {
      color: #D00000;
    }

    .raportzawszystkie {
      font-size: 120%;
    }
  </style>
</head>

<body>
  <pre><?php


        if (isset($_GET['grep'])) {
          $grep = $_GET['grep'];
          if (strpos($grep, '..') !== false) {
            die('Sorry, no.');
          };
        } else {
          $grep = '';
        };
        $files = glob($logPath . '/' . $grep . '*.log', GLOB_MARK); // scandir($logPath);
        for ($i = 0; $i < count($files); $i++) {
          if (substr($files[$i], -1) == '/') {
            //    unset($files[$i]);
            //	$i--;
            $files[$i] = basename($files[$i]) . '/';
          } else {
            $files[$i] = basename($files[$i]);
          }
        }
        $files = array_values($files);

        echo '<h1>Internet Provider Log</h1>';
        echo $opiswywolywania;
        echo '<hr />';
        if ($grep == '') {
          echo '<h2>Wszystkie pliki</h2>';
        } else {
          echo '<h2>Tylko pliki zaczynające się od ' . $grep . '</h2>';
        };

        print_r($files);
        echo '<hr />';

        $maxlen = 0;
        foreach ($files as $dzien) {
          $maxlen = max($maxlen, strlen($dzien));
        }


        echo '<div id="lista">';
        $data = '';
        $niedzialaarray = array();
        foreach ($files as $dzien) {
          if (($dzien != '..') and ($dzien != '.') and (substr($dzien, -1) != '/')) {    // na razie nic tu nie robimy z folderami
            if (substr($dzien, 0, 8) != $data) {
              if ($data != '') {
                echo '<br />';
              };
              $data = substr($dzien, 0, 8);
              echo '<a href="?dzien=' . $data . $downloadstr . '" class="datalink">' . $data . '</a><br />';
            };
            $zaw = file_get_contents($logPath . '/' . $dzien);
            $zaw = explode(chr(10), $zaw);
            for ($i = 0; $i < count($zaw); $i++) {
              $zaw[$i] = substr($zaw[$i], strpos($zaw[$i], ' '));
            };
            $zaw = implode(chr(10), $zaw);
            $dziala = substr_count($zaw, $pingCount) + substr_count($zaw, '.');    // kompatybilność z plikami sprzed replace $pingCount na kropkę
            $niedziala = substr_count($zaw, '0');
            if ($niedziala > 3) {
              echo '<span class="niedziala">';
              array_push($niedzialaarray, $data);
            } else if ($niedziala > 0) {
              echo '<span class="trocheniedziala">';
              array_push($niedzialaarray, $data);
            } else {
              echo '<span>';
            }
            echo '<a href="?plik=' . $dzien . $downloadstr . '">';
            echo $dzien . '</a>' . str_repeat(' ', ($maxlen - strlen($dzien))) . ' - ';
            echo $dziala . '/' . ($dziala + $niedziala) . ' poprawnie (' . $niedziala . ' nie wróciło).';
            echo '</span><br />';
          }
        }
        echo '</div>';


        ?>
<hr /><button onclick="window.getSelection().selectAllChildren(document.getElementById('lista'));">zaznacz całość</button> | 
<?php

$niedzialaarray = array_unique($niedzialaarray);
if (count($niedzialaarray) > 0) {
  echo '<span class="niedziala raportzawszystkie"><a href="?dzien=' . join(',', $niedzialaarray) . $downloadstr . '">Raport za wszystkie ' . count($niedzialaarray) . ' dni z awariami</a></span>';
};

echo '<hr />';

/*
$firstpart='';
$secondpart='';
foreach ($hosts as $ip => $hostname) {
	$firstpart.="* * * * * echo -n `ping -c ".$pingCount." -q ".$ip." | grep transmitted | cut -d ' ' -f4 | sed -e 's/".$pingCount."/./g'` >> ".$logPath."/`date +\%Y\%m\%d`".$hostname.'.log'.chr(13).chr(10);
	$secondpart.="59 * * * * sleep 40 ; echo >> ".$logPath."/`date +\%Y\%m\%d`".$hostname.".log ; echo -ne `date +\%H --date='+1 minute'`: \\\\c >> ".$logPath."/`date +\%Y\%m\%d --date='+1 minute'`".$hostname.'.log'.chr(13).chr(10);
	// (uwaga, \\ jest escape'owany)
}
$defaultcron=$firstpart.$secondpart;
*/

$defaultCron = '* * * * * ' . __DIR__ . '/pingHosts.php';

$currentCron = shell_exec('sudo -g crontab -l | grep "' . $scriptName . '"');

//ech.
//$wymiany=array(chr(13).chr(10)=>chr(10),chr(13)=>chr(10));
//$defaultcron=rtrim(strtr($defaultcron,$wymiany));
//$currentcron=rtrim(strtr($currentcron,$wymiany));

if ($currentCron == $defaultCron) {
  echo 'Current cron=default cron:' . $lf . $lf . $currentCron;
} else {
  echo 'Current cron:' . $lf . $lf . $currentCron . '<hr />' . 'Default cron:' . $lf . $lf . $defaultCron;
};

?><hr />v<?= $wersja; ?> &copy; Kamil Barański http://kamilbaranski.com/ 2018-2024 [current time=<?= date('r'); ?>] // (disclaimer: nie byłoby tego skryptu, gdyby UPC dobrze wykonywało swoją robotę)
</pre>
</body>

</html>