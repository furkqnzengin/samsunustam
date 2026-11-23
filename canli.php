<?php
// Panel açıkken birkaç dakikada bir çağrılır; oturumun zaman aşımına uğramasını önler.
require __DIR__ . '/inc/init.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['giris' => (bool)kullanici()]);
