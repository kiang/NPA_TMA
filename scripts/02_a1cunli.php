<?php

$basePath = dirname(__DIR__);
$config = require __DIR__ . '/config.php';
$conn = new PDO('pgsql:host=localhost;dbname=' . $config['db'], $config['user'], $config['password']);

$header = ['villcode', '經度', '緯度'];
$base = [];
for ($i = 2018; $i <= 2024; ++$i) {
    $csvFile = $basePath . "/data/cunli_lnglat/{$i}.csv";
    if (file_exists($csvFile)) {
        $fh = fopen($csvFile, 'r');
        fgetcsv($fh, 2048);
        while ($line = fgetcsv($fh, 2048)) {
            $data = array_combine($header, $line);
            $base["{$data['經度']} {$data['緯度']}"] = $data['villcode'];
        }
    }
    $oFh = fopen($csvFile, 'w');
    fputcsv($oFh, $header);
    $pool = [];
    foreach (glob($basePath . '/data/' . $i . '/*.csv') as $csvFile) {
        $fh = fopen($csvFile, 'r');
        $head = fgetcsv($fh, 4096);
        while ($line = fgetcsv($fh, 4096)) {
            $data = array_combine($head, $line);
            $lnglat = "{$data['經度']} {$data['緯度']}";
            if (empty($data['經度']) || isset($pool[$lnglat])) {
                continue;
            }
            if (isset($base[$lnglat])) {
                $pool[$lnglat] = true;
                fputcsv($oFh, [$base[$lnglat], $data['經度'], $data['緯度']]);
            } else {
                $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$lnglat})'::geometry, cunli.geom)";
                $rs = $conn->query($sql);
                if ($rs) {
                    $row = $rs->fetch(PDO::FETCH_ASSOC);
                }
                if (!empty($row['villcode'])) {
                    $pool[$lnglat] = true;
                    fputcsv($oFh, [$row['villcode'], $data['經度'], $data['緯度']]);
                }
            }
        }
    }
}
