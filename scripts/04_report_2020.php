<?php

$basePath = dirname(__DIR__);

$counter = $cityCounter = [];
$a1 = fopen($basePath . '/history/109年度A1類交通事故資料.csv', 'r');
fgetcsv($a1, 2048);
while ($line = fgetcsv($a1, 2048)) {
    preg_match('/(鄉|鎮|市|區)/', $line[1], $matches, PREG_OFFSET_CAPTURE, 9);
    if(isset($matches[0][1])) {
        $area = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
        $city = substr($area, 0, 9);
        $area = substr($area, 9);
        switch($area) {
            case '前鎮':
                $area = '前鎮區';
                break;
            case '左鎮':
                $area = '左鎮區';
                break;
            case '平鎮':
                $area = '平鎮區';
                break;
        }
        if (!isset($counter[$city])) {
            $counter[$city] = [
                'accidents' => 0,
                'dies' => 0,
                'hurts' => 0,
            ];
            $cityCounter[$city] = [];
        }
        if(!isset($cityCounter[$city][$area])) {
            $cityCounter[$city][$area] = [
                'accidents' => 0,
                'dies' => 0,
                'hurts' => 0,
            ];
        }
        $parts = explode(';', $line[2]);
        foreach ($parts as $part) {
            $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
            switch ($d[0]) {
                case '死亡':
                    $counter[$city]['dies'] += $d[1];
                    $cityCounter[$city][$area]['dies'] += $d[1];
                    break;
                case '受傷':
                    $counter[$city]['hurts'] += $d[1];
                    $cityCounter[$city][$area]['hurts'] += $d[1];
                    break;
            }
        }
        ++$counter[$city]['accidents'];
        ++$cityCounter[$city][$area]['accidents'];
    }
}

$a1 = fopen($basePath . '/history/109年度A2類交通事故資料.csv', 'r');
fgetcsv($a1, 2048);
while ($line = fgetcsv($a1, 2048)) {
    preg_match('/(鄉|鎮|市|區)/', $line[1], $matches, PREG_OFFSET_CAPTURE, 9);
    if(isset($matches[0][1])) {
        $area = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
        $city = substr($area, 0, 9);
        $area = substr($area, 9);
        switch($area) {
            case '前鎮':
                $area = '前鎮區';
                break;
            case '左鎮':
                $area = '左鎮區';
                break;
            case '平鎮':
                $area = '平鎮區';
                break;
        }
        if (!isset($counter[$city])) {
            $counter[$city] = [
                'accidents' => 0,
                'dies' => 0,
                'hurts' => 0,
            ];
            $cityCounter[$city] = [];
        }
        if(!isset($cityCounter[$city][$area])) {
            $cityCounter[$city][$area] = [
                'accidents' => 0,
                'dies' => 0,
                'hurts' => 0,
            ];
        }
        $parts = explode(';', $line[2]);
        foreach ($parts as $part) {
            $d = preg_split('/([0-9]+)/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
            switch ($d[0]) {
                case '死亡':
                    $counter[$city]['dies'] += $d[1];
                    $cityCounter[$city][$area]['dies'] += $d[1];
                    break;
                case '受傷':
                    $counter[$city]['hurts'] += $d[1];
                    $cityCounter[$city][$area]['hurts'] += $d[1];
                    break;
            }
        }
        ++$counter[$city]['accidents'];
        ++$cityCounter[$city][$area]['accidents'];
    }
}

function cmp($a, $b)
{
    if ($a['dies'] == $b['dies']) {
        return 0;
    }
    return ($a['dies'] > $b['dies']) ? -1 : 1;
}

$svgPath = $basePath . '/report/2020';
if(!file_exists($svgPath)) {
    mkdir($svgPath, 0777, true);
}
$population = [
    '新北市' => 4030954,
    '臺北市' => 2602418,
    '桃園市' => 2268807,
    '臺中市' => 2820787,
    '臺南市' => 1874917,
    '高雄市' => 2765932,
    '宜蘭縣' => 453087,
    '新竹縣' => 570775,
    '苗栗縣' => 542590,
    '彰化縣' => 1266670,
    '南投縣' => 490832,
    '雲林縣' => 676873,
    '嘉義縣' => 499481,
    '屏東縣' => 812658,
    '臺東縣' => 215261,
    '花蓮縣' => 324372,
    '澎湖縣' => 105952,
    '基隆市' => 367577,
    '新竹市' => 451412,
    '嘉義市' => 266005,
    '金門縣' => 140597,
    '連江縣' => 13279,
];

$fh = fopen(__DIR__ . '/tmp.csv', 'w');
foreach($cityCounter AS $city => $areaCounter) {
    $report = file_get_contents($basePath . '/art/2020.svg');
    // fputcsv($fh, [$city, round($counter[$city]['dies'] / ($population[$city] / 100000), 2),
    // round($counter[$city]['hurts'] / ($population[$city] / 100000), 0),
    // round($counter[$city]['accidents'] / ($population[$city] / 100000), 0)]);
    $report = strtr($report, [
        '{{report_date}}' => '2020 | ' . $city,
        '{{new_dies}}' => $counter[$city]['dies'],
        '{{new_hurts}}' => $counter[$city]['hurts'],
        '{{new_accidents}}' => $counter[$city]['accidents'],
        '{{sum_dies}}' => round($counter[$city]['dies'] / ($population[$city] / 100000), 2),
        '{{sum_hurts}}' => round($counter[$city]['hurts'] / ($population[$city] / 100000), 0),
        '{{sum_accidents}}' => round($counter[$city]['accidents'] / ($population[$city] / 100000), 0),
    ]);
    $pos = strpos($report, '{{loop_begin}}');
    $posEnd = strpos($report, '{{loop_end}}');
    $reportEnd = substr($report, $posEnd  + 14);
    $cityTemplate = substr($report, $pos + 14, $posEnd - $pos - 14);
    $loopY = 0;
    $report = substr($report, 0, $pos);
    uasort($areaCounter, 'cmp');

    $loopY = 0;
    foreach($areaCounter AS $area => $data) {
        $report .= strtr($cityTemplate, [
            '{{loop_y}}' => $loopY,
            '{{loop_city}}' => $area,
            '{{loop_text}}' => " {$data['accidents']} 事故， {$data['dies']} 死亡、 {$data['hurts']} 受傷",
        ]);
        $loopY += 70;
    }
    file_put_contents($svgPath . '/' . $city . '.svg', $report . $reportEnd);
    //exec('inkscape -z ' . $svgPath . '/' . $city . '.svg -e ~/Downloads/' . $city . '.png');
}