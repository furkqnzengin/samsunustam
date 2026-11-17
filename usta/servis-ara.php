<?php
// Plaka / seri no ile ustanın önceki kaydını bulur (yeni kayıt formunu otomatik doldurmak için).
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/servis.php';
$u = aktif_usta();
header('Content-Type: application/json; charset=utf-8');

$a = anahtar_norm(get('a'));
if (mb_strlen($a) < 3) { echo json_encode(['bulundu' => false]); exit; }

$son = row('SELECT * FROM servisler WHERE usta_id = ? AND anahtar = ? ORDER BY giris_tarihi DESC, id DESC LIMIT 1', [$u['id'], $a]);
if (!$son) { echo json_encode(['bulundu' => false]); exit; }

echo json_encode([
    'bulundu' => true,
    'adet' => (int)val('SELECT COUNT(*) FROM servisler WHERE usta_id = ? AND anahtar = ?', [$u['id'], $a]),
    'son_id' => (int)$son['id'],
    'son_no' => (int)$son['no'],
    'son_tarih' => date('d.m.Y', strtotime($son['giris_tarihi'])),
    'musteri_ad' => $son['musteri_ad'],
    'musteri_tel' => $son['musteri_tel'] ? tel_goster($son['musteri_tel']) : '',
    'musteri_firma' => $son['musteri_firma'] ?? '',
    'ozel' => json_decode((string)$son['ozel'], true) ?: new stdClass(),
], JSON_UNESCAPED_UNICODE);
