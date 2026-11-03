<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Site henüz yapılandırılmadı: config.sample.php dosyasını config.php olarak kopyalayıp doldurun, ardından /kurulum adresini açın.');
}
$CFG = require $configFile;
$CFG['base'] = rtrim($CFG['base'] ?? '', '/');

if (!empty($CFG['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
date_default_timezone_set('Europe/Istanbul');
mb_internal_encoding('UTF-8');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('su_oturum');
session_set_cookie_params([
    'path' => ($CFG['base'] ?: '') . '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $https,
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
