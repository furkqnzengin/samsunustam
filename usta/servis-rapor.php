<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/rapor.php';
$u = aktif_usta();
$s = row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [(int)get('id'), $u['id']]);
if (!$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/servis.php'); }
rapor_sayfasi($s, true);
