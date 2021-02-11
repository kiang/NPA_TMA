<?php

// A1 - https://data.moi.gov.tw/MoiOD/Data/DataDetail.aspx?oid=F4077949-50CC-4640-8114-79958CC8BBEA
// A2 - https://data.moi.gov.tw/MoiOD/Data/DataDetail.aspx?oid=F713DBFE-7432-4401-B5C0-1C07A8F5B1FB
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

use Goutte\Client;

$client = new Client();
$client->request('GET', 'https://data.moi.gov.tw/MoiOD/System/DownloadFile.aspx?DATA=01987403-8634-4E3F-B626-E7777014AE43');
file_put_contents($basePath . '/raw/a1.json', $client->getResponse()->getContent());
$client->request('GET', 'https://data.moi.gov.tw/MoiOD/System/DownloadFile.aspx?DATA=35011243-38FD-42BF-9073-A3DE6A696532');
file_put_contents($basePath . '/raw/a2.json', $client->getResponse()->getContent());