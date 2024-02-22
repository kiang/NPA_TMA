<?php

// A1 - https://data.gov.tw/dataset/12818
// A2 - https://data.gov.tw/dataset/13139
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

use Goutte\Client;

$y = date('Y');
$path = $basePath . '/data/' . $y;
if (!file_exists($path)) {
    mkdir($path, 0777, true);
}

foreach (glob($path . '/*.csv') as $csvFile) {
    unlink($csvFile);
}

$client = new Client();
$client->request('GET', 'https://data.moi.gov.tw/MoiOD/System/DownloadFile.aspx?DATA=402E554F-10E7-42C9-BAAF-DF7C431E3F18');
$c = $client->getResponse()->getContent();
if (false === strpos($c, '</html>')) {
    file_put_contents($path . '/a1.csv', $c);
}
$client->request('GET', 'https://data.moi.gov.tw/MoiOD/System/DownloadFile.aspx?DATA=99D093B4-2536-4891-9058-BE261D11F3AC');
$c = $client->getResponse()->getContent();
if (false === strpos($c, '</html>')) {
    file_put_contents($path . '/a2.csv', $c);
}

$metaFiles = [
    'file.csv',
    'manifest.csv',
    'schema-file.csv',
];
$fileType = finfo_file(finfo_open(FILEINFO_MIME), $path . '/a2.csv');
if (false !== strpos($fileType, 'application/zip')) {
    $zip = new ZipArchive;
    if ($zip->open($path . '/a2.csv') === TRUE) {
        $zip->extractTo($path);
        $zip->close();
        foreach ($metaFiles as $metaFile) {
            if (file_exists($path . '/' . $metaFile)) {
                unlink($path . '/' . $metaFile);
            }
        }
        unlink($path . '/a2.csv');
    }
}
