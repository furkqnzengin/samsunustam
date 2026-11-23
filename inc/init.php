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
// Oturumlar bu siteye özel klasörde ve 8 saat tutulur (paylaşımlı hostingde
// başka sitelerin temizliği oturumu erken silmesin diye). Klasör açılamazsa varsayılan kullanılır.
$oturumDizin = dirname(ROOT) . '/.samsunustam_oturum';
if ((is_dir($oturumDizin) || @mkdir($oturumDizin, 0700)) && is_writable($oturumDizin)) {
    session_save_path($oturumDizin);
}
ini_set('session.gc_maxlifetime', '28800');
ini_set('session.gc_probability', '1'); // kendi klasörümüzü biz temizleriz
ini_set('session.gc_divisor', '100');
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
require __DIR__ . '/guncelle.php';

db_guncelle();
hatirla_dene();
