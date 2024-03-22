<?php

$basePath = dirname(__DIR__);
$config = require __DIR__ . '/config.php';
$conn = new PDO('pgsql:host=localhost;dbname=' . $config['db'], $config['user'], $config['password']);

$counter = $cityCounter = [];

foreach (glob('/home/kiang/public_html/roadsafety.tw/raw/GetCitiesAreaAccDataStatistics/*/112_12.json') as $jsonFile) {
    $items = json_decode(file_get_contents($jsonFile), true);
    foreach ($items as $item) {
        if ($item['row'][0] !== 'ALL') {
            if (!isset($counter[$item['row'][0]])) {
                $counter[$item['row'][0]] = [
                    'accidents' => 0,
                    'dies' => 0,
                    'hurts' => 0,
                ];
                $cityCounter[$item['row'][0]] = [];
            }
            $counter[$item['row'][0]]['dies'] += $item['value'];

            $cityCounter[$item['row'][0]][$item['col'][0]] = [
                'accidents' => 0,
                'dies' => $item['value'],
                'hurts' => 0,
            ];
        }
    }
}

foreach (glob($basePath . '/data/2023/*.csv') as $csvFile) {
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    $lastLine = '';
    while ($line = fgetcsv($fh, 2048)) {
        $data = array_combine($head, $line);
        if (empty($data['經度'])) {
            continue;
        }
        $sql = "SELECT countyname, townname FROM {$config['table']} AS cunli WHERE ST_Intersects('SRID=4326;POINT({$data['經度']} {$data['緯度']})'::geometry, cunli.geom)";
        $rs = $conn->query($sql);
        if (!$rs) {
            continue;
        }
        $row = $rs->fetch(PDO::FETCH_ASSOC);
        if (empty($row['countyname'])) {
            continue;
        }
        $city = $row['countyname'];
        $area = $row['townname'];
        $counter[$city]['hurts'] += 1;
        $cityCounter[$city][$area]['hurts'] += 1;
        $currentLine = $data['發生日期'] . $data['發生時間'] . $data['發生地點'];
        if ($currentLine !== $lastLine) {
            $lastLine = $currentLine;
            ++$counter[$city]['accidents'];
            ++$cityCounter[$city][$area]['accidents'];
        }
    }
}

function cmp($a, $b)
{
    if ($a['dies'] == $b['dies']) {
        return 0;
    }
    return ($a['dies'] > $b['dies']) ? -1 : 1;
}

$svgPath = $basePath . '/report/2023';
if (!file_exists($svgPath)) {
    mkdir($svgPath, 0777, true);
}
$population = [
    '新北市' => 4025405,
    '臺北市' => 2504687,
    '桃園市' => 2302465,
    '臺中市' => 2834046,
    '臺南市' => 1858444,
    '高雄市' => 2736019,
    '宜蘭縣' => 449705,
    '新竹縣' => 586299,
    '苗栗縣' => 534677,
    '彰化縣' => 1242521,
    '南投縣' => 478462,
    '雲林縣' => 661767,
    '嘉義縣' => 486229,
    '屏東縣' => 797291,
    '臺東縣' => 212008,
    '花蓮縣' => 318306,
    '澎湖縣' => 107312,
    '基隆市' => 362396,
    '新竹市' => 454603,
    '嘉義市' => 263707,
    '金門縣' => 142942,
    '連江縣' => 13992
];

$fh = fopen($svgPath . '/summary.csv', 'w');
fputcsv($fh, ['縣市', '死亡', '事故', '受傷', '10萬人發生率 - 死亡', '10萬人發生率 - 事故', '10萬人發生率 - 受傷']);
foreach ($cityCounter as $city => $areaCounter) {
    $report = file_get_contents($basePath . '/art/2020.svg');
    fputcsv($fh, [
        $city, $counter[$city]['dies'], $counter[$city]['accidents'], $counter[$city]['hurts'],
        round($counter[$city]['dies'] / ($population[$city] / 100000), 2),
        round($counter[$city]['accidents'] / ($population[$city] / 100000), 0),
        round($counter[$city]['hurts'] / ($population[$city] / 100000), 0)
    ]);
    $report = strtr($report, [
        '{{report_date}}' => '2023 | ' . $city,
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
    //exec('inkscape -z ' . $svgPath . '/' . $city . '.svg -e ~/Downloads/' . $city . '.png');
}
