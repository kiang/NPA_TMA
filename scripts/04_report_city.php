<?php

$basePath = dirname(__DIR__);

$counter = $cityCounter = [];
$a1 = fopen($basePath . '/data/a1.csv', 'r');
fgetcsv($a1, 2048);
while ($line = fgetcsv($a1, 2048)) {
    $yw = date('oW', $line[6]);
    if (!isset($counter[$yw])) {
        $counter[$yw] = [
            'accidents' => 0,
            'dies' => 0,
            'hurts' => 0,
            'sum_accidents' => 0,
            'sum_dies' => 0,
            'sum_hurts' => 0,
            'time' => 0,
        ];
    }
    if ($counter[$yw]['time'] < $line[6]) {
        $counter[$yw]['time'] = $line[6];
    }
    if (!isset($cityCounter[$yw])) {
        $cityCounter[$yw] = [];
    }
    preg_match('/(縣|市)/', $line[1], $matches, PREG_OFFSET_CAPTURE);
    if (!isset($matches[0][1])) {
        continue;
    }
    $city = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
    if (!isset($cityCounter[$yw][$city])) {
        $cityCounter[$yw][$city] = [
            'accidents' => 0,
            'dies' => 0,
            'hurts' => 0,
        ];
    }

    $parts = explode(';', $line[2]);
    foreach ($parts as $part) {
        $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        switch ($d[0]) {
            case '死亡':
                $counter[$yw]['dies'] += $d[1];
                $cityCounter[$yw][$city]['dies'] += $d[1];
                break;
            case '受傷':
                $counter[$yw]['hurts'] += $d[1];
                $cityCounter[$yw][$city]['hurts'] += $d[1];
                break;
        }
    }
    ++$counter[$yw]['accidents'];
    ++$cityCounter[$yw][$city]['accidents'];
}

$a2 = fopen($basePath . '/data/a2.csv', 'r');
fgetcsv($a2, 2048);
while ($line = fgetcsv($a2, 2048)) {
    $yw = date('oW', $line[6]);
    if (!isset($counter[$yw])) {
        $counter[$yw] = [
            'accidents' => 0,
            'dies' => 0,
            'hurts' => 0,
            'sum_accidents' => 0,
            'sum_dies' => 0,
            'sum_hurts' => 0,
            'time' => 0,
        ];
    }
    if ($counter[$yw]['time'] < $line[6]) {
        $counter[$yw]['time'] = $line[6];
    }

    if (!isset($cityCounter[$yw])) {
        $cityCounter[$yw] = [];
    }
    preg_match('/(縣|市)/', $line[1], $matches, PREG_OFFSET_CAPTURE);
    if (!isset($matches[0][1])) {
        continue;
    }
    $city = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
    if (!isset($cityCounter[$yw][$city])) {
        $cityCounter[$yw][$city] = [
            'accidents' => 0,
            'dies' => 0,
            'hurts' => 0,
        ];
    }

    $parts = explode(';', $line[2]);
    foreach ($parts as $part) {
        $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        switch ($d[0]) {
            case '死亡':
                $counter[$yw]['dies'] += $d[1];
                $cityCounter[$yw][$city]['dies'] += $d[1];
                break;
            case '受傷':
                $counter[$yw]['hurts'] += $d[1];
                $cityCounter[$yw][$city]['hurts'] += $d[1];
                break;
        }
    }
    ++$counter[$yw]['accidents'];
    ++$cityCounter[$yw][$city]['accidents'];
}

$ywPool = array_keys($counter);
$numKeys = ['hurts', 'accidents', 'sum_dies', 'sum_hurts', 'sum_accidents'];
function cmp($a, $b)
{
    if ($a['dies'] == $b['dies']) {
        return 0;
    }
    return ($a['dies'] > $b['dies']) ? -1 : 1;
}

foreach ($ywPool as $yw) {
    $nextMonday = strtotime('next monday', $counter[$yw]['time']);
    foreach ($counter as $subYw => $data) {
        if ($subYw < $yw) {
            $counter[$yw]['sum_accidents'] += $data['accidents'];
            $counter[$yw]['sum_dies'] += $data['dies'];
            $counter[$yw]['sum_hurts'] += $data['hurts'];
        }
    }
    $counter[$yw]['sum_accidents'] += $counter[$yw]['accidents'];
    $counter[$yw]['sum_dies'] += $counter[$yw]['dies'];
    $counter[$yw]['sum_hurts'] += $counter[$yw]['hurts'];

    $report = file_get_contents($basePath . '/art/base.svg');
    $thisMonday = strtotime('last monday', $counter[$yw]['time']);
    $nextMonday = strtotime('next monday', $counter[$yw]['time']) - 1;
    $report = strtr($report, [
        '{{report_date}}' => date('Y', $thisMonday) . ' | ' . date('m-d', $thisMonday) . ' ~ ' . date('m-d', $nextMonday),
        '{{new_dies}}' => $counter[$yw]['dies'],
        '{{new_hurts}}' => $counter[$yw]['hurts'],
        '{{new_accidents}}' => $counter[$yw]['accidents'],
        '{{sum_dies}}' => $counter[$yw]['sum_dies'],
        '{{sum_hurts}}' => $counter[$yw]['sum_hurts'],
        '{{sum_accidents}}' => $counter[$yw]['sum_accidents'],
    ]);
    $pos = strpos($report, '{{loop_begin}}');
    $posEnd = strpos($report, '{{loop_end}}');
    $reportEnd = substr($report, $posEnd  + 14);
    $cityTemplate = substr($report, $pos + 14, $posEnd - $pos - 14);
    $loopY = 0;
    $report = substr($report, 0, $pos);
    uasort($cityCounter[$yw], 'cmp');
    foreach($cityCounter[$yw] AS $city => $data) {
        $report .= strtr($cityTemplate, [
            '{{loop_y}}' => $loopY,
            '{{loop_city}}' => $city,
            '{{loop_text}}' => " {$data['accidents']} 事故， {$data['dies']} 死亡、 {$data['hurts']} 受傷",
        ]);
        $loopY += 70;
    }
    $svgPath = $basePath . '/report/city/' . date('o', $counter[$yw]['time']);
    if(!file_exists($svgPath)) {
        mkdir($svgPath, 0777, true);
    }
    file_put_contents($svgPath . '/' . $yw . '.svg', $report . $reportEnd);
    file_put_contents($svgPath . '/' . $yw . '.json', json_encode([
        'timeBegin' => $thisMonday,
        'timeEnd' => $nextMonday,
        'new_dies' => $counter[$yw]['dies'],
        'new_hurts' => $counter[$yw]['hurts'],
        'new_accidents' => $counter[$yw]['accidents'],
        'sum_dies' => $counter[$yw]['sum_dies'],
        'sum_hurts' => $counter[$yw]['sum_hurts'],
        'sum_accidents' => $counter[$yw]['sum_accidents'],
        'cityies' => $cityCounter,
    ]));
}