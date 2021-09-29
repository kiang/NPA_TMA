<?php

$basePath = dirname(__DIR__);
$rawPath = '/home/kiang/public_html/roadsafety.tw/raw/GetCitiesAreaAccDataStatistics';
$rawCounter = [];
foreach (glob($rawPath . '/*/110_07.json') as $jsonFile) {
    $json = json_decode(file_get_contents($jsonFile), true);
    foreach ($json as $line) {
        if ($line['row'][0] === 'ALL') {
            $key = $line['col'][0];
        } else {
            $key = $line['row'][0] . $line['col'][0];
        }
        if (!isset($rawCounter[$key])) {
            $rawCounter[$key] = 0;
        }
        $rawCounter[$key] += $line['value'];
    }
}

$today = date('m-d');

$counter = $cityCounter = [];
$a1 = fopen($basePath . '/data/a1.csv', 'r');
fgetcsv($a1, 2048);
while ($line = fgetcsv($a1, 2048)) {
    preg_match('/(鄉|鎮|市|區)/', $line[1], $matches, PREG_OFFSET_CAPTURE, 9);
    if (isset($matches[0][1])) {
        $area = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
        $city = substr($area, 0, 9);
        $area = substr($area, 9);
        switch ($area) {
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
        if (!isset($cityCounter[$city][$area])) {
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
                    if (date('n', strtotime($line[0])) > 7) {
                        $rawCounter[$city] += $d[1];
                        if ($area !== '新市') {
                            $rawCounter[$city . $area] += $d[1];
                        } else {
                            $rawCounter[$city . $area . '區'] += $d[1];
                        }
                    }
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

$a1 = fopen($basePath . '/data/a2.csv', 'r');
fgetcsv($a1, 2048);
while ($line = fgetcsv($a1, 2048)) {
    preg_match('/(鄉|鎮|市|區)/', $line[1], $matches, PREG_OFFSET_CAPTURE, 9);
    if (isset($matches[0][1])) {
        $area = substr($line[1], 0, $matches[0][1]) . $matches[0][0];
        $city = substr($area, 0, 9);
        $area = substr($area, 9);
        switch ($area) {
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
        if (!isset($cityCounter[$city][$area])) {
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
                    if (date('n', strtotime($line[0])) > 7) {
                        $rawCounter[$city] += $d[1];
                        if ($area !== '新市') {
                            $rawCounter[$city . $area] += $d[1];
                        } else {
                            $rawCounter[$city . $area . '區'] += $d[1];
                        }
                    }
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
$cityCounter['臺南市']['新市區'] = $cityCounter['臺南市']['新市'];
unset($cityCounter['臺南市']['新市']);

foreach ($counter as $city => $v1) {
    $counter[$city]['dies'] = $rawCounter[$city];
}
foreach ($cityCounter as $city => $v1) {
    foreach ($v1 as $area => $v2) {
        $cityCounter[$city][$area]['dies'] = $rawCounter[$city . $area];
    }
}

function cmp($a, $b)
{
    if ($a['dies'] == $b['dies']) {
        return 0;
    }
    return ($a['dies'] > $b['dies']) ? -1 : 1;
}

$svgPath = $basePath . '/report/2021_30days';
if (!file_exists($svgPath)) {
    mkdir($svgPath, 0777, true);
}
// 2021/08
$population = [
    '新北市' => 4019898,
    '臺北市' => 2553798,
    '桃園市' => 2272976,
    '臺中市' => 2818139,
    '臺南市' => 1867554,
    '高雄市' => 2753530,
    '臺灣省' => 7012518,
    '宜蘭縣' => 451635,
    '新竹縣' => 573858,
    '苗栗縣' => 539879,
    '彰化縣' => 1259246,
    '南投縣' => 487185,
    '雲林縣' => 672557,
    '嘉義縣' => 495662,
    '屏東縣' => 807159,
    '臺東縣' => 213956,
    '花蓮縣' => 322506,
    '澎湖縣' => 105645,
    '基隆市' => 365591,
    '新竹市' => 452781,
    '嘉義市' => 264858,
    '金門縣' => 140004,
    '連江縣' => 13420,
];

$all = [
    'population' => 0,
];

$fh = fopen($svgPath . '/summary.csv', 'w');
fputcsv($fh, ['縣市', '死亡', '事故', '受傷', '10萬人發生率 - 死亡', '10萬人發生率 - 事故', '10萬人發生率 - 受傷', '202108人口']);
foreach ($cityCounter as $city => $areaCounter) {
    foreach ($counter[$city] as $k => $v) {
        if (!isset($all[$k])) {
            $all[$k] = 0;
        }
        $all[$k] += $v;
    }
    $all['population'] += $population[$city];
    $report = file_get_contents($basePath . '/art/2020.svg');
    fputcsv($fh, [
        $city, $counter[$city]['dies'], $counter[$city]['accidents'], $counter[$city]['hurts'],
        round($counter[$city]['dies'] / ($population[$city] / 100000), 2),
        round($counter[$city]['accidents'] / ($population[$city] / 100000), 0),
        round($counter[$city]['hurts'] / ($population[$city] / 100000), 0),
        $population[$city]
    ]);
    $report = strtr($report, [
        '{{report_date}}' => '2021 | ' . $city . '@' . $today,
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
    $reportFh = fopen($svgPath . '/' . $city . '.csv', 'w');
    fputcsv($reportFh, ['行政區', '事故', '死亡', '受傷']);
    foreach ($areaCounter as $area => $data) {
        $report .= strtr($cityTemplate, [
            '{{loop_y}}' => $loopY,
            '{{loop_city}}' => $area,
            '{{loop_text}}' => " {$data['accidents']} 事故， {$data['dies']} 死亡、 {$data['hurts']} 受傷",
        ]);
        fputcsv($reportFh, [$area, $data['accidents'], $data['dies'], $data['hurts']]);
        $loopY += 70;
    }
    file_put_contents($svgPath . '/' . $city . '.svg', $report . $reportEnd);
}
$all['dies'] += $rawCounter['國道'];
$all['dies'] += $rawCounter['其他'];

$report = file_get_contents($basePath . '/art/2020.svg');
fputcsv($fh, [
    '全國', $all['dies'], $all['accidents'], $all['hurts'],
    round($all['dies'] / ($all['population'] / 100000), 2),
    round($all['accidents'] / ($all['population'] / 100000), 0),
    round($all['hurts'] / ($all['population'] / 100000), 0),
    $all['population']
]);
$report = strtr($report, [
    '{{report_date}}' => '2021 | 全國 @' . $today,
    '{{new_dies}}' => $all['dies'],
    '{{new_hurts}}' => $all['hurts'],
    '{{new_accidents}}' => $all['accidents'],
    '{{sum_dies}}' => round($all['dies'] / ($all['population'] / 100000), 2),
    '{{sum_hurts}}' => round($all['hurts'] / ($all['population'] / 100000), 0),
    '{{sum_accidents}}' => round($all['accidents'] / ($all['population'] / 100000), 0),
]);
$pos = strpos($report, '{{loop_begin}}');
$posEnd = strpos($report, '{{loop_end}}');
$reportEnd = substr($report, $posEnd  + 14);
$cityTemplate = substr($report, $pos + 14, $posEnd - $pos - 14);
$loopY = 0;
$report = substr($report, 0, $pos);
uasort($counter, 'cmp');

$loopY = 0;
foreach ($counter as $city => $data) {
    $report .= strtr($cityTemplate, [
        '{{loop_y}}' => $loopY,
        '{{loop_city}}' => $city,
        '{{loop_text}}' => " {$data['accidents']} 事故， {$data['dies']} 死亡、 {$data['hurts']} 受傷",
    ]);
    $loopY += 70;
}
$report = strtr($report, [
    '<rect x="650" y="385.6" class="st7" width="800" height="336.8" />' => '<rect x="850" y="385.6" class="st7" width="750" height="336.8" />',
    '<text transform="matrix(1 0 0 1 700 519.593)" class="st4 st8 st9">' => '<text transform="matrix(1 0 0 1 900 519.593)" class="st4 st8 st9">',
    '<text transform="matrix(1 0 0 1 1000 521.5084)" class="st4 st10 st11">' => '<text transform="matrix(1 0 0 1 1200 521.5084)" class="st4 st10 st11">',
    '<text transform="matrix(1 0 0 1 1000 651.7274)" class="st4 st10 st11">' => '<text transform="matrix(1 0 0 1 1200 651.7274)" class="st4 st10 st11">',
    '<text transform="matrix(1 0 0 1 700 649.4965)" class="st4 st8 st9">' => '<text transform="matrix(1 0 0 1 900 649.4965)" class="st4 st8 st9">',
]);
file_put_contents($svgPath . '/全國.svg', $report . $reportEnd);
