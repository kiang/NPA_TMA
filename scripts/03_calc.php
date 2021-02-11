<?php

$basePath = dirname(__DIR__);
$count = $vehicles = [];
$fh = fopen($basePath . '/data/a1.csv', 'r');
$head = fgetcsv($fh, 2048);
while($line = fgetcsv($fh, 2048)) {
    $parts = explode(';', $line[2]);
    foreach($parts AS $part) {
        $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        if(!isset($count[$d[0]])) {
            $count[$d[0]] = 0;
        }
        $count[$d[0]] += $d[1];
    }
    $parts = explode(';', $line[3]);
    foreach($parts AS $part) {
        if(!isset($vehicles[$part])) {
            $vehicles[$part] = 0;
        }
        ++$vehicles[$part];
    }
}
$fh = fopen($basePath . '/data/a2.csv', 'r');
$head = fgetcsv($fh, 2048);
while($line = fgetcsv($fh, 2048)) {
    $parts = explode(';', $line[2]);
    foreach($parts AS $part) {
        $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        if(!isset($count[$d[0]])) {
            $count[$d[0]] = 0;
        }
        $count[$d[0]] += $d[1];
    }
    $parts = explode(';', $line[3]);
    foreach($parts AS $part) {
        if(!isset($vehicles[$part])) {
            $vehicles[$part] = 0;
        }
        ++$vehicles[$part];
    }
}
arsort($vehicles);
print_r($vehicles);