<?php
// Müşteri arama önerileri (en az 3 karakter): ad, ünvan, telefon, TC/vergi no
require __DIR__ . '/../inc/init.php';
$u = aktif_usta();
header('Content-Type: application/json; charset=utf-8');
$q = mb_substr(trim(get('q')), 0, 50);
if (mb_strlen($q) < 3) { echo '[]'; exit; }
$rakam = preg_replace('/\D/', '', $q);
$like = '%' . $q . '%';
$sonuc = rows('SELECT id, ad_soyad, unvan, telefon, tc_vkn FROM musteriler WHERE usta_id = ? AND (ad_soyad LIKE ? OR unvan LIKE ?'
    . (strlen($rakam) >= 3 ? ' OR telefon LIKE ? OR tc_vkn LIKE ?' : '') . ') ORDER BY ad_soyad LIMIT 10',
    strlen($rakam) >= 3 ? [$u['id'], $like, $like, "%$rakam%", "%$rakam%"] : [$u['id'], $like, $like]);
echo json_encode(array_map(fn($m) => ['id' => (int)$m['id'], 'ad' => $m['unvan'] ?: $m['ad_soyad'], 'alt' => trim(($m['unvan'] ? $m['ad_soyad'] . ' · ' : '') . ($m['telefon'] ? tel_goster($m['telefon']) : '') . ($m['tc_vkn'] ? ' · ' . $m['tc_vkn'] : ''), ' ·')], $sonuc), JSON_UNESCAPED_UNICODE);
