<?php

$basePath = dirname(__DIR__);
$len = [];
$count = [];
foreach(glob($basePath . '/history/109年度*.csv') AS $csvFile) {
    $p = pathinfo($csvFile);
    $type = substr($p['filename'], 9, 2);
    $fh = fopen($csvFile, 'r');
    fgetcsv($fh, 2048);
    while($line = fgetcsv($fh, 2048)) {
        preg_match('/(縣|市)/', $line[1], $matches, PREG_OFFSET_CAPTURE);
        if(!isset($matches[0][1])) {
            continue;
        }
        $city = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
        if(!isset($len[$city])) {
            $len[$city] = strlen($city);
        }
        if(!isset($count[$city])) {
            $count[$city] = [
                'total' => 0,
                'A1' => 0,
                'A2' => 0,
                'A3' => 0,
            ];
        }
        $line[1] = substr($line[1], $len[$city]);
        preg_match('/(鄉|鎮|市|區)/', $line[1], $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][1])) {
            $area = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
            switch($area) {
                case '前鎮':
                    $area = '前鎮區';
                    break;
                case '左鎮':
                    $area = '左鎮區';
                    break;
                case '平鎮':
                    $area = '平鎮區';
                    break;
            }
            if(!isset($count[$city][$area])) {
                $count[$city][$area] = [
                    'total' => 0,
                    'A1' => 0,
                    'A2' => 0,
                    'A3' => 0,
                ];
            }
            ++$count[$city]['total'];
            ++$count[$city][$type];
            ++$count[$city][$area]['total'];
            ++$count[$city][$area][$type];
        }
    }
}
print_r($count);