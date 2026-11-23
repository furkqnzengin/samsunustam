<?php
// Araç/cihaz kartına yüklenen belgeyi sadece kartın sahibi ustaya gösterir.
require __DIR__ . '/../inc/init.php';
$u = aktif_usta();
$dosya = val('SELECT belge FROM kartlar WHERE id = ? AND usta_id = ?', [(int)get('id'), $u['id']]);
$yol = $dosya ? realpath(ROOT . '/uploads/' . $dosya) : false;
$kok = realpath(ROOT . '/uploads/belgeler');
if (!$yol || !$kok || !str_starts_with($yol, $kok . DIRECTORY_SEPARATOR) || !is_file($yol)) { http_response_code(404); exit('Belge bulunamadı.'); }
$uz = strtolower(pathinfo($yol, PATHINFO_EXTENSION));
header('Content-Type: ' . (['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$uz] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="belge.' . $uz . '"');
header('Cache-Control: private, no-store');
readfile($yol);
