<?php
// Müşteriye gönderilen servis raporu linki: rapor.php?t=<gizli anahtar>
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/servis.php';
require __DIR__ . '/inc/rapor.php';

$t = get('t');
$s = preg_match('/^[a-f0-9]{32}$/', $t) ? row('SELECT * FROM servisler WHERE rapor_token = ?', [$t]) : null;
header('X-Robots-Tag: noindex, nofollow');
if (!$s) {
    http_response_code(404);
    exit('Rapor bulunamadı. Linki ustanızdan tekrar isteyin.');
}
rapor_sayfasi($s, false);
