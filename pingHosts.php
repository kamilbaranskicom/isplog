#!/usr/bin/env php
<?php

include 'config.php';

// header('Content-Type: text/plain');
// $logPath = '/var/www/html/isplog/data';

foreach ($hosts as $hostIP => $hostName) {
    // echo 'pingujemy ' . $hostName . ' czyli ' . $hostIP . "\n";
    $logFile = $logPath . '/' . date('Ymd') . $hostName . '.log';
    if ((date('i') == '00') and (date('H') !='00')) {
        shell_exec("/usr/bin/echo -ne '\n" . date('H') . ": ' >> " . $logFile);
    };
    shell_exec("/usr/bin/echo -n `ping -c ".$pingCount." -q " . $hostIP . " | grep transmitted | cut -d ' ' -f4 | sed -e 's/1/./g'` >> " . $logFile);
};
