<?php

$basePath = dirname(__DIR__);
$config = require __DIR__ . '/config.php';
$conn = new PDO('pgsql:host=localhost;dbname=' . $config['db'], $config['user'], $config['password']);

$oFh = fopen($basePath . '/data/cunli_lnglat.csv', 'w');
fputcsv($oFh, ['villcode', '經度', '緯度']);
for ($i = 2018; $i <= 2024; ++$i) {
    foreach (glob($basePath . '/data/' . $i . '/*.csv') as $csvFile) {
        if (strpos(strtolower($csvFile), 'a2')) {
            continue;
        }
        $fh = fopen($csvFile, 'r');
        $head = fgetcsv($fh, 4096);
        while ($line = fgetcsv($fh, 4096)) {
            $data = array_combine($head, $line);
            if (empty($data['經度'])) {
                continue;
            }
            $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$data['經度']} {$data['緯度']})'::geometry, cunli.geom)";
            $rs = $conn->query($sql);
            if ($rs) {
                $row = $rs->fetch(PDO::FETCH_ASSOC);
            }
            if (!empty($row['villcode'])) {
                fputcsv($oFh, [$row['villcode'], $data['經度'], $data['緯度']]);
            }
        }
    }
}
