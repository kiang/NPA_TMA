<?php
$basePath = dirname(__DIR__);

$pool = [];
$count = [
    'count' => 0,
    '死亡' => 0,
    '受傷' => 0,
];
foreach (glob($basePath . '/data/*/*.csv') as $csvFile) {
    $p = pathinfo($csvFile);
    $parts = explode('/', $p['dirname']);
    $y = array_pop($parts);
    if (!isset($pool[$y])) {
        $pool[$y] = $count;
    }

    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        $data = array_combine($head, $line);
        if (false === strpos($data['發生地點'], '龍崎') || false === strpos($data['發生地點'], '182')) {
            continue;
        }

        $parts = explode(';', $data['死亡受傷人數']);
        foreach ($parts as $v) {
            $pool[$y][mb_substr($v, 0, 2, 'utf-8')] += intval(mb_substr($v, 2, null, 'utf-8'));
        }
        $pool[$y]['count'] += 1;
    }
}

$oFh = fopen($basePath . '/report/182.csv', 'w');
fputcsv($oFh, ['year', 'count', 'death', 'hurt']);
foreach ($pool as $y => $l1) {
    fputcsv($oFh, array_merge([$y], $l1));
}
