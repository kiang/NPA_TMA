<?php

$basePath = dirname(__DIR__);



$years = [2018, 2019, 2020, 2021, 2022];

$pool = [
    'total' => [],
];
$keys = [];
foreach ($years as $year) {
    foreach (glob($basePath . '/data/' . $year . '/*.csv') as $csvFile) {
        $fh = fopen($csvFile, 'r');
        $head = fgetcsv($fh, 4096);
        while ($line = fgetcsv($fh, 4096)) {
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
                    $k = $year . $k;
                    if (!isset($keys[$k])) {
                        $keys[$k] = true;
                    }
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
    }
}

$oFh = fopen($basePath . '/report/summary.csv', 'w');
$keys = array_keys($keys);
fputcsv($oFh, array_merge(['unit'], $keys));
foreach ($pool as $k => $v) {
    $line = [$k];
    foreach ($keys as $key) {
        if (isset($v[$key])) {
            $line[] = $v[$key];
        } else {
            $line[] = 0;
        }
    }
    fputcsv($oFh, $line);
}
