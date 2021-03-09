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
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$fb = new Facebook\Facebook([
    'app_id' => $config['app_id'],
    'app_secret' => $config['app_secret'],
    'default_graph_version' => 'v2.2',
]);

$photos = [
    __DIR__ . '/tmp/img_to_post1.png',
    __DIR__ . '/tmp/img_to_post2.png',
    __DIR__ . '/tmp/img_to_post3.png',
    __DIR__ . '/tmp/img_to_post4.png',
];
$media = [];
foreach($photos AS $photo) {
    try {
        $response = $fb->post('/me/photos', [
            'message' => '',
            'source' => $fb->fileToUpload($photo),
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

$message = '國內交通事故通報 2021 | 02-22 ~ 02-28';
$message .= "\n\n❗新增 31 例死亡，桃園市 6 例、新竹縣 5 例、高雄市 5 例❗";
$message .= "\n⭐全部報表 - https://github.com/kiang/NPA_TMA/tree/master/report/";
$message .= "\n⭐地圖 - https://kiang.github.io/NPA_TMA/";
$message .= "\n\n✏警政署週一(3/8)更新 A1 即時交通事故資料，共新增 75 筆事故資料，其中 02-22 ~ 02-28 新增 31 例死亡。";
$message .= "\n\n✏02-22 ~ 02-28 新增 31 例死亡、 22 例受傷與 29 起事故";
$message .= "\n✏02-15 ~ 02-21 新增 41 例死亡、 676 例受傷與 496 起事故";
$message .= "\n✏02-08 ~ 02-14 新增 43 例死亡、 2872 例受傷與 2166 起事故";
$message .= "\n✏02-01 ~ 02-07 新增 48 例死亡、 4844 例受傷與 3625 起事故";
$message .= "\n\n#交通安全最前線 #謝謝辛苦的警察人員";

//Post property to Facebook
$linkData = [
    'message' => $message,
    'attached_media' => $media,
];

try {
    $response = $fb->post('/me/feed', $linkData, $config['token']);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}
$graphNode = $response->getGraphNode();