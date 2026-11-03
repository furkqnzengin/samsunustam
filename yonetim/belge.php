<?php
// Ustalık belgelerini sadece yöneticiye gösterir (uploads/belgeler dışarıya kapalıdır).
require __DIR__ . '/../inc/init.php';
giris_gerekli('admin');

$dosya = val('SELECT belge_dosya FROM ustalar WHERE id = ?', [(int)get('id')]);
$yol = $dosya ? realpath(ROOT . '/uploads/' . $dosya) : false;
$kok = realpath(ROOT . '/uploads/belgeler');
if (!$yol || !$kok || !str_starts_with($yol, $kok . DIRECTORY_SEPARATOR) || !is_file($yol)) {
    http_response_code(404);
    exit('Belge bulunamadı.');
}
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][strtolower(pathinfo($yol, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($yol));
header('Content-Disposition: inline; filename="belge.' . pathinfo($yol, PATHINFO_EXTENSION) . '"');
header('Cache-Control: private, no-store');
readfile($yol);
