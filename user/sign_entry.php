<?php
require_once __DIR__ . '/../include/config.php';
$taskId = (int)($_GET['task_id'] ?? 0);
if (!$taskId) {
    die('无效签到任务');
}
$redirectUri = urlencode('http://' . $_SERVER['HTTP_HOST'] . '/user/sign_callback.php?task_id=' . $taskId);
$authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $wechat_appid . '&redirect_uri=' . $redirectUri . '&response_type=code&scope=snsapi_base&state=SIGN#wechat_redirect';
header('Location: ' . $authUrl);
exit;
