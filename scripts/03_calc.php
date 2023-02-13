<?php

$basePath = dirname(__DIR__);
$count = $vehicles = [];
$fh = fopen($basePath . '/data/2023/a1.csv', 'r');
$head = fgetcsv($fh, 2048);
$pool = [
    'total' => [],
];
while ($line = fgetcsv($fh, 2048)) {
    $data = array_combine($head, $line);
    if ($data['當事者順位'] == 1) {
        $parts = explode(';', $data['死亡受傷人數']);
        $count = [];
        foreach ($parts as $v) {
            $count[mb_substr($v, 0, 2, 'utf-8')] = intval(mb_substr($v, 2, null, 'utf-8'));
        }
        if (!isset($pool[$data['處理單位名稱警局層']])) {
            $pool[$data['處理單位名稱警局層']] = [];
        }
        foreach ($count as $k => $v) {
            if (!isset($pool[$data['處理單位名稱警局層']][$k])) {
                $pool[$data['處理單位名稱警局層']][$k] = 0;
            }
            if (!isset($pool['total'][$k])) {
                $pool['total'][$k] = 0;
            }
            $pool[$data['處理單位名稱警局層']][$k] += $v;
            $pool['total'][$k] += $v;
        }
    }
}

$fh = fopen($basePath . '/data/2023/a2.csv', 'r');
$head = fgetcsv($fh, 2048);
while ($line = fgetcsv($fh, 2048)) {
    $data = array_combine($head, $line);
    if ($data['當事者順位'] == 1) {
        $parts = explode(';', $data['死亡受傷人數']);
        $count = [];
        foreach ($parts as $v) {
            $count[mb_substr($v, 0, 2, 'utf-8')] = intval(mb_substr($v, 2, null, 'utf-8'));
        }
        if (!isset($pool[$data['處理單位名稱警局層']])) {
            $pool[$data['處理單位名稱警局層']] = [];
        }
        foreach ($count as $k => $v) {
            if (!isset($pool[$data['處理單位名稱警局層']][$k])) {
                $pool[$data['處理單位名稱警局層']][$k] = 0;
            }
            $pool[$data['處理單位名稱警局層']][$k] += $v;
        }
    }
}
print_r($pool);
