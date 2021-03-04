<?php

$basePath = dirname(__DIR__);

function cmp($a, $b)
{
    if ($a['accidents'] == $b['accidents']) {
        return 0;
    }
    return ($a['accidents'] > $b['accidents']) ? -1 : 1;
}

$counter = $perpetrator = [];
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
    if (!isset($perpetrator[$yw])) {
        $perpetrator[$yw] = [];
    }
    $parts = explode(';', $line[3]);
    $item = $parts[0];
    if (!isset($perpetrator[$yw][$item])) {
        $perpetrator[$yw][$item] = [
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
                $perpetrator[$yw][$item]['dies'] += $d[1];
                break;
            case '受傷':
                $counter[$yw]['hurts'] += $d[1];
                $perpetrator[$yw][$item]['hurts'] += $d[1];
                break;
        }
    }
    ++$counter[$yw]['accidents'];
    ++$perpetrator[$yw][$item]['accidents'];
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

    if (!isset($perpetrator[$yw])) {
        $perpetrator[$yw] = [];
    }
    $parts = explode(';', $line[3]);
    $item = $parts[0];
    if (!isset($perpetrator[$yw][$item])) {
        $perpetrator[$yw][$item] = [
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
                $perpetrator[$yw][$item]['dies'] += $d[1];
                break;
            case '受傷':
                $counter[$yw]['hurts'] += $d[1];
                $perpetrator[$yw][$item]['hurts'] += $d[1];
                break;
        }
    }
    ++$counter[$yw]['accidents'];
    ++$perpetrator[$yw][$item]['accidents'];
}

$ywPool = array_keys($counter);

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

    $report = file_get_contents($basePath . '/art/perpetrator.svg');
    //str_repeat('&#160;', 2);
    $report = strtr($report, [
        '{{report_date}}' => date('Y-m-d', strtotime('next monday', $counter[$yw]['time'])),
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
    
    uasort($perpetrator[$yw], 'cmp');
    foreach ($perpetrator[$yw] as $item => $data) {
        switch ($item) {
            case '自用-小貨車(含客、貨兩用)':
                $item = '自用-小貨車';
                break;
            case '大型重型1(550C.C.以上)-機車':
                $item = '550C.C.以上-機車';
                break;
            case '大型重型2(250-550C.C.)-機車':
                $item = '250-550C.C.-機車';
                break;
            case '農耕用車(或機械)-其他車':
                $item = '農耕用車-其他車';
                break;
        }
        $report .= strtr($cityTemplate, [
            '{{loop_y}}' => $loopY,
            '{{loop_city}}' => $item,
            '{{loop_text}}' => " {$data['accidents']} 事故， {$data['dies']} 死亡、 {$data['hurts']} 受傷",
        ]);
        $loopY += 70;
    }
    $svgPath = $basePath . '/report/perpetrator/' . date('o', $counter[$yw]['time']);
    if (!file_exists($svgPath)) {
        mkdir($svgPath, 0777, true);
    }
    file_put_contents($svgPath . '/' . $yw . '.svg', $report . $reportEnd);
}
