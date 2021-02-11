<?php

$basePath = dirname(__DIR__);
$a1 = json_decode(file_get_contents($basePath . '/raw/a1.json'), true);
$oFh = false;
foreach($a1['result']['records'] AS $record) {
    $time = preg_split('/[^0-9]+/i', $record['發生時間']);
    $time = mktime($time[3], $time[4], $time[5], $time[1], $time[2], $time[0] += 1911);
    $record['發生時間'] = date('Y-m-d H:i:s', $time);
    $record['unixtime'] = $time;
    if(false === $oFh) {
        $oFh = fopen($basePath . '/data/a1.csv', 'w');
        fputcsv($oFh, array_keys($record));
    }
    fputcsv($oFh, $record);
}
$a2 = json_decode(file_get_contents($basePath . '/raw/a2.json'), true);
$oFh = false;
foreach($a2['result']['records'] AS $record) {
    $time = preg_split('/[^0-9]+/i', $record['發生時間']);
    $time = mktime($time[3], $time[4], $time[5], $time[1], $time[2], $time[0] += 1911);
    $record['發生時間'] = date('Y-m-d H:i:s', $time);
    $record['unixtime'] = $time;
    if(false === $oFh) {
        $oFh = fopen($basePath . '/data/a2.csv', 'w');
        fputcsv($oFh, array_keys($record));
    }
    fputcsv($oFh, $record);
}