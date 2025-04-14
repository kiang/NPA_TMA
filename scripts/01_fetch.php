<?php

// A1 - https://data.gov.tw/dataset/12818
// A2 - https://data.gov.tw/dataset/13139
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$y = date('Y');
$path = $basePath . '/data/' . $y;
if (!file_exists($path)) {
    mkdir($path, 0777, true);
}

foreach (glob($path . '/*.csv') as $csvFile) {
    unlink($csvFile);
}

$arrContextOptions = [
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ],
];

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/12818', false, stream_context_create($arrContextOptions)), true);
foreach ($json['result']['distribution'] as $item) {
    if ($item['resourceFormat'] === 'CSV') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $item['resourceDownloadUrl']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $c = curl_exec($ch);
        curl_close($ch);
        
        if (false === strpos($c, '</html>')) {
            file_put_contents($path . '/a1.csv', $c);
        }
    }
}

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/13139', false, stream_context_create($arrContextOptions)), true);
$zip = new ZipArchive;
$zipCounter = 0;
foreach ($json['result']['distribution'] as $item) {
    if ($item['resourceFormat'] === 'ZIP') {
        ++$zipCounter;
        $zipFile = $path . '/' . $zipCounter . '.zip';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $item['resourceDownloadUrl']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $content = curl_exec($ch);
        curl_close($ch);
        
        file_put_contents($zipFile, $content);
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
