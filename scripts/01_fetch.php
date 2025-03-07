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

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/12818'), true);
foreach ($json['result']['distribution'] as $item) {
    if ($item['resourceFormat'] === 'CSV') {
        $client->request('GET', $item['resourceDownloadUrl']);
        $c = $client->getResponse()->getContent();
        if (false === strpos($c, '</html>')) {
            file_put_contents($path . '/a1.csv', $c);
        }
    }
}

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/13139'), true);
$zip = new ZipArchive;
$zipCounter = 0;
foreach ($json['result']['distribution'] as $item) {
    if ($item['resourceFormat'] === 'ZIP') {
        ++$zipCounter;
        $zipFile = $path . '/' . $zipCounter . '.zip';
        $client->request('GET', $item['resourceDownloadUrl']);
        file_put_contents($zipFile, $client->getResponse()->getContent());
        $zip->open($zipFile);
        $zip->extractTo($path);
        $zip->close();
        unlink($zipFile);
    }
}

$metaFiles = [
    'file.csv',
    'manifest.csv',
    'schema-file.csv',
];
foreach ($metaFiles as $metaFile) {
    if (file_exists($path . '/' . $metaFile)) {
        unlink($path . '/' . $metaFile);
    }
}
