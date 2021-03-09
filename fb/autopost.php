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
    __DIR__ . '/img_to_post.png',
    __DIR__ . '/img_to_post2.png',
];
$media = [];
foreach($photos AS $photo) {
    $response = $fb->post('/me/photos', [
        'source' => $fb->fileToUpload($photo),
        'published' => false,
    ], $config['token']);
    $media[] = ['media_fbid' => $response->getDecodedBody()['id']];
}

//Post property to Facebook
$linkData = [
    'message' => 'NPA TMA testing',
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