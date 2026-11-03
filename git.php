<?php
// "Ara" ve "WhatsApp" tıklamalarını sayıp ilgili bağlantıya yönlendirir.
require __DIR__ . '/inc/init.php';

$u = row("SELECT id, telefon, whatsapp, ad_soyad FROM ustalar WHERE id = ? AND durum = 'aktif'", [(int)get('u')]);
if (!$u) redirect('');

$tur = get('t') === 'whatsapp' ? 'whatsapp' : 'arama';
$anahtar = $tur . '_' . $u['id'];
if (empty($_SESSION['tik'][$anahtar]) || $_SESSION['tik'][$anahtar] < time() - 3600) {
    olay_kaydet((int)$u['id'], $tur);
    $_SESSION['tik'][$anahtar] = time();
}

if ($tur === 'whatsapp' && $u['whatsapp']) {
    redirect(wa_link($u['whatsapp'], 'Merhaba ' . $u['ad_soyad'] . ', Samsun Ustam üzerinden ulaşıyorum.'));
}
header('Location: tel:' . tel_temizle($u['telefon']));
exit;
