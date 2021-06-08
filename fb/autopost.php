<?php
/**
 * ref
 * 1. https://adamboother.com/blog/automatically-posting-to-a-facebook-page-using-the-facebook-sdk-v5-for-php-facebook-api/
 * 2. https://phppot.com/php/publishing-multi-photo-stories-to-facebook-using-php-sdk/
 * 
 * generate the token
 * https://developers.facebook.com/tools/explorer/
 * 
 * permission: pages_manage_posts, pages_read_engagement, pages_show_list
 * 
 * remember to extend token expire date every 2 months
 * https://developers.facebook.com/tools/debug/accesstoken/?access_token=
 */
$basePath = dirname(__DIR__);
require_once $basePath . '/fb/vendor/autoload.php';
$config = require $basePath . '/fb/config.php';

$reportFiles = $reports = [];
foreach(glob($basePath . '/report/city/*/*.json') AS $jsonFile) {
    $reportFiles[] = $jsonFile;
}
rsort($reportFiles);
for($i = 0; $i < 5; $i++) {
    $reports[$i] = json_decode(file_get_contents($reportFiles[$i]), true);
    $reports[$i]['svg'] = substr($reportFiles[$i], 0, -4) . 'svg';
}
$yearDays = date('z', $reports[0]['timeEnd']) + 1;
$yearDies = round($reports[0]['sum_dies'] / $yearDays, 2);
$yearAccidents = round($reports[0]['sum_accidents'] / $yearDays, 0);

$message = '國內交通事故通報 ' . date('Y', $reports[0]['timeBegin']) . ' | ' . date('m-d', $reports[0]['timeBegin']) . ' ~ ' . date('m-d', $reports[0]['timeEnd']);
$message .= "\n\n統計今年累計至本週共發生 {$reports[0]['sum_accidents']} 起事故，造成 {$reports[0]['sum_dies']} 例死亡、 {$reports[0]['sum_hurts']} 例受傷";
$message .= "\n" . date('m-d', $reports[0]['timeEnd']) . " 為今年第 {$yearDays} 天，平均每天有 {$yearAccidents} 起事故、 {$yearDies} 人死亡";
$message .= "\n\n❗本週新增 {$reports[0]['new_dies']} 例死亡，";
$key = key($reports[0]['city']);
$message .= "{$key} {$reports[0]['city'][$key]['dies']} 例、";
next($reports[0]['city']);
$key = key($reports[0]['city']);
if(!empty($key)) {
    $message .= "{$key} {$reports[0]['city'][$key]['dies']} 例、";
}
next($reports[0]['city']);
$key = key($reports[0]['city']);
if(!empty($key)) {
    $message .= "{$key} {$reports[0]['city'][$key]['dies']} 例❗";
}

$message .= "\n⭐全部報表 - https://github.com/kiang/NPA_TMA/tree/master/report/";
$message .= "\n⭐地圖 - https://kiang.github.io/NPA_TMA/";

$message .= "\n\n✏" . date('m-d', $reports[1]['timeBegin']) . ' ~ ' . date('m-d', $reports[1]['timeEnd']);
$message .= " 新增 {$reports[1]['new_dies']} 例死亡、 {$reports[1]['new_hurts']} 例受傷與 {$reports[1]['new_accidents']} 起事故";
$message .= "\n✏" . date('m-d', $reports[2]['timeBegin']) . ' ~ ' . date('m-d', $reports[2]['timeEnd']);
$message .= " 新增 {$reports[2]['new_dies']} 例死亡、 {$reports[2]['new_hurts']} 例受傷與 {$reports[2]['new_accidents']} 起事故";
$message .= "\n✏" . date('m-d', $reports[3]['timeBegin']) . ' ~ ' . date('m-d', $reports[3]['timeEnd']);
$message .= " 新增 {$reports[3]['new_dies']} 例死亡、 {$reports[3]['new_hurts']} 例受傷與 {$reports[3]['new_accidents']} 起事故";
$message .= "\n✏" . date('m-d', $reports[4]['timeBegin']) . ' ~ ' . date('m-d', $reports[4]['timeEnd']);
$message .= " 新增 {$reports[4]['new_dies']} 例死亡、 {$reports[4]['new_hurts']} 例受傷與 {$reports[4]['new_accidents']} 起事故";
$message .= "\n⭐結算日期 - " . date('Y-m-d');
$message .= "\n\n#交通安全最前線 #謝謝辛苦的警察人員";

$imgPath = $basePath . '/fb/tmp';
if(!file_exists($imgPath)) {
    mkdir($imgPath, 0777);
}
$fb = new Facebook\Facebook([
    'app_id' => $config['app_id'],
    'app_secret' => $config['app_secret'],
    'default_graph_version' => 'v2.2',
]);
$media = [];

foreach($reports AS $k => $report) {
    $photoMessage = '國內交通事故通報 ' . date('Y', $report['timeBegin']) . ' | ' . date('m-d', $report['timeBegin']) . ' ~ ' . date('m-d', $report['timeEnd']);
    $photoMessage .= "\n\n❗本週新增 {$report['new_accidents']} 起事故，有 {$report['new_dies']} 例死亡、{$report['new_hurts']} 例受傷❗\n";
    foreach($report['city'] AS $city => $cityReport) {
        $photoMessage .= "\n✏{$city} 有 {$cityReport['accidents']} 起事故，有 {$cityReport['dies']} 例死亡、{$cityReport['hurts']} 例受傷";
    }
    $photoMessage .= "\n⭐結算日期 - " . date('Y-m-d');
    $imgFile = $imgPath . '/' . $k . '.png';
    if(file_exists($imgFile)) {
        unlink($imgFile);
    }
    exec('inkscape -w 1080 -h 1350 -z ' . $report['svg'] . ' -e ' . $imgFile);

    try {
        $response = $fb->post('/' . $config['page_id'] . '/photos', [
            'message' => $photoMessage,
            'source' => $fb->fileToUpload($imgFile),
            'published' => false,
        ], $config['token']);    
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit();
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit();
    }
    $media[] = ['media_fbid' => $response->getDecodedBody()['id']];
}

//Post property to Facebook
$linkData = [
    'message' => $message,
    'attached_media' => $media,
];

try {
    $response = $fb->post('/' . $config['page_id'] . '/feed', $linkData, $config['token']);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}
$graphNode = $response->getGraphNode();