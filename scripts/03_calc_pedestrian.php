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
            if ($data['當事者區分-類別-子類別名稱-車種'] === '行人') {
                if (!isset($pool[$year])) {
                    $pool[$year] = [];
                }
                if (!isset($pool[$year][$data['肇因研判大類別名稱-主要']])) {
                    $pool[$year][$data['肇因研判大類別名稱-主要']] = 0;
                }
                if (!isset($pool['total'][$data['肇因研判大類別名稱-主要']])) {
                    $pool['total'][$data['肇因研判大類別名稱-主要']] = 0;
                }
                if (!isset($pool[$year]['count'])) {
                    $pool[$year]['count'] = 0;
                }
                if (!isset($pool['total']['count'])) {
                    $pool['total']['count'] = 0;
                }
                $pool[$year][$data['肇因研判大類別名稱-主要']] += 1;
                $pool['total'][$data['肇因研判大類別名稱-主要']] += 1;
                $pool[$year]['count'] += 1;
                $pool['total']['count'] += 1;
            }
        }
    }
}

print_r($pool);