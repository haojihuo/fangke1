<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'fangke';
$DB_USER = 'root';
$DB_PASS = '';

// 部署在子目录时请修改为实际路径，例如 /fangke1
$app_path = '/fangke1';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $scheme . '://' . $host . rtrim($app_path, '/');

function app_url(string $path = ''): string
{
    global $base_url;
    return $base_url . '/' . ltrim($path, '/');
}

// 微信公众号配置
$wechat_appid = 'YOUR_WECHAT_APPID';
$wechat_secret = 'YOUR_WECHAT_SECRET';
