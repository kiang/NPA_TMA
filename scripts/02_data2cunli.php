<?php

$basePath = dirname(__DIR__);
$config = require __DIR__ . '/config.php';
$conn = new PDO('pgsql:host=localhost;dbname=' . $config['db'], $config['user'], $config['password']);
$currentYear = date('Y');
$cunli = [];
$cunliWeekly = [];

$a2File = $basePath . '/data/' . $currentYear . '/a2.csv';
$zip = new ZipArchive;
$zip->open($a2File);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $filename = $zip->getNameIndex($i);
    if (false !== strpos($filename, 'NPA_TMA2')) {
        $fh = fopen('zip://' . $a2File . '#' . $filename, 'r');
        $head = fgetcsv($fh, 4096);
        while ($line = fgetcsv($fh, 4096)) {
            $data = array_combine($head, $line);
            if (empty($data['經度']) || $data['當事者順位'] != 1) {
                continue;
            }
            $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$data['經度']} {$data['緯度']})'::geometry, cunli.geom)";
            $rs = $conn->query($sql);
            if ($rs) {
                $row = $rs->fetch(PDO::FETCH_ASSOC);
            }

            if (isset($row['villcode'])) {
                if (!isset($cunli[$row['villcode']])) {
                    $cunli[$row['villcode']] = [
                        'a1' => 0,
                        'a2' => 0,
                        'total' => 0,
                    ];
                    $cunliWeekly[$row['villcode']] = [];
                }
                $time = mktime(substr($data['發生時間'], 0, 2), substr($data['發生時間'], 2, 2), substr($data['發生時間'], 4, 2), substr($data['發生日期'], 4, 2), substr($data['發生日期'], 6, 2), substr($data['發生日期'], 0, 4));
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
    }
}

$fh = fopen($basePath . '/data/' . $currentYear . '/a1.csv', 'r');
$head = fgetcsv($fh, 4096);
while ($line = fgetcsv($fh, 4096)) {
    $data = array_combine($head, $line);
    if (empty($data['經度']) || $data['當事者順位'] != 1) {
        continue;
    }
    $sql = "SELECT villcode FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$data['經度']} {$data['緯度']})'::geometry, cunli.geom)";
    $rs = $conn->query($sql);
    if ($rs) {
        $row = $rs->fetch(PDO::FETCH_ASSOC);
    }

    if (isset($row['villcode'])) {
        if (!isset($cunli[$row['villcode']])) {
            $cunli[$row['villcode']] = [
                'a1' => 0,
                'a2' => 0,
                'total' => 0,
            ];
            $cunliWeekly[$row['villcode']] = [];
        }
        $time = mktime(substr($data['發生時間'], 0, 2), substr($data['發生時間'], 2, 2), substr($data['發生時間'], 4, 2), substr($data['發生日期'], 4, 2), substr($data['發生日期'], 6, 2), substr($data['發生日期'], 0, 4));
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

file_put_contents($basePath . '/data/cunli.json', json_encode($cunli, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$oFh = [];
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
