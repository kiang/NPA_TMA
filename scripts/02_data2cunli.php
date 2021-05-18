<?php

$basePath = dirname(__DIR__);
$config = require __DIR__ . '/config.php';
$conn = new PDO('pgsql:host=localhost;dbname=' . $config['db'], $config['user'], $config['password']);
$a1 = json_decode(file_get_contents($basePath . '/raw/a1.json'), true);
$cunli = [];
$cunliWeekly = [];
$oFh = [];
foreach ($a1['result']['records'] as $record) {
    $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$record['經度']} {$record['緯度']})'::geometry, cunli.geom)";
    $rs = $conn->query($sql);
    $row = $rs->fetch(PDO::FETCH_ASSOC);
    if (isset($row['villcode'])) {
        if (!isset($cunli[$row['villcode']])) {
            $cunli[$row['villcode']] = [
                'a1' => 0,
                'a2' => 0,
                'total' => 0,
            ];
            $cunliWeekly[$row['villcode']] = [];
        }
        $time = preg_split('/[^0-9]+/i', $record['發生時間']);
        $time = mktime($time[3], $time[4], $time[5], $time[1], $time[2], $time[0] += 1911);
        $week = date('oW', $time);
        if (!isset($cunliWeekly[$row['villcode']][$week])) {
            $cunliWeekly[$row['villcode']][$week] = [
                'a1' => 0,
                'a2' => 0,
            ];
        }

        ++$cunli[$row['villcode']]['a1'];
        ++$cunli[$row['villcode']]['total'];
        ++$cunliWeekly[$row['villcode']][$week]['a1'];
    }
}

$a2 = json_decode(file_get_contents($basePath . '/raw/a2.json'), true);
foreach ($a2['result']['records'] as $record) {
    $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$record['經度']} {$record['緯度']})'::geometry, cunli.geom)";
    $rs = $conn->query($sql);
    $row = $rs->fetch(PDO::FETCH_ASSOC);
    if (isset($row['villcode'])) {
        if (!isset($cunli[$row['villcode']])) {
            $cunli[$row['villcode']] = [
                'a1' => 0,
                'a2' => 0,
                'total' => 0,
            ];
            $cunliWeekly[$row['villcode']] = [];
        }
        $time = preg_split('/[^0-9]+/i', $record['發生時間']);
        $time = mktime($time[3], $time[4], $time[5], $time[1], $time[2], $time[0] += 1911);
        $week = date('oW', $time);
        if (!isset($cunliWeekly[$row['villcode']][$week])) {
            $cunliWeekly[$row['villcode']][$week] = [
                'a1' => 0,
                'a2' => 0,
            ];
        }

        ++$cunli[$row['villcode']]['a2'];
        ++$cunli[$row['villcode']]['total'];
        ++$cunliWeekly[$row['villcode']][$week]['a2'];
    }
}

file_put_contents($basePath . '/data/cunli.json', json_encode($cunli, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

foreach ($cunliWeekly as $villcode => $lv1) {
    $cunliPath = $basePath . '/data/cunli/' . substr($villcode, 0, 5);
    if (!file_exists($cunliPath)) {
        mkdir($cunliPath, 0777, true);
    }
    $oFh = fopen($cunliPath . '/' . $villcode . '.csv', 'w');
    fputcsv($oFh, ['week', 'a1', 'a2']);
    ksort($lv1);
    foreach ($lv1 as $week => $lv2) {
        fputcsv($oFh, [$week, $lv2['a1'], $lv2['a2']]);
    }
}