<?php
require_once __DIR__ . '/../include/config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $temp_link_id = (int)($_GET['temp_link_id'] ?? 0);

    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$wechat_appid&secret=$wechat_secret&code=$code&grant_type=authorization_code";
    $response = file_get_contents($url);
    $result = json_decode($response, true);

    if (isset($result['openid'])) {
        $openid = $result['openid'];
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/user/register.php?id=' . $temp_link_id . '&openid=' . urlencode($openid));
        exit;
    }
    echo '微信授权失败！';
} else {
    echo '无效的回调！';
}
