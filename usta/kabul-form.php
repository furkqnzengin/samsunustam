<?php
// Servis kabul 4. adım: servis kabul formu (yazdırılabilir, QR ve imzalı)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$id = (int)get('id');
$s = row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]);
if (!$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/serviste.php'); }

if (is_post()) {
    csrf_check();
    foreach (['imza_usta' => 'imza_kabul_usta', 'imza_musteri' => 'imza_kabul_musteri'] as $alan => $kolon) {
        $v = imza_oku($alan);
        if ($v !== null) q("UPDATE servisler SET $kolon = ? WHERE id = ?", [$v ?: null, $id]);
    }
    flash('İmzalar kaydedildi.');
    redirect(post('ileri') ? 'usta/servis-kayit.php?id=' . $id : 'usta/kabul-form.php?id=' . $id);
}

$kart = $s['kart_id'] ? row('SELECT * FROM kartlar WHERE id = ?', [$s['kart_id']]) : null;
$m = $s['musteri_id'] ? row('SELECT * FROM musteriler WHERE id = ?', [$s['musteri_id']]) : null;
$tanim = sektorler()[$s['sektor']] ?? sektorler()['yerinde'];
$kalemler = rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$id]);

panel_basla('usta', 'kabul', 'Servis Kabul Formu', kabul_baslik($s, $kart, ''));
?>
<?= kabul_sekmeleri($s, 'form', $tanim) ?>
<form method="post" class="adimgovde" id="sform">
  <?= csrf_field() ?>
  <div class="yazdir-gizle" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
    <button type="button" class="btn btn-ghost" onclick="window.print()">🖨️ Yazdır / PDF</button>
    <?php if ($s['musteri_tel']): ?><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e(wa_link($s['musteri_tel'], "Merhaba {$s['musteri_ad']}, #{$s['no']} numaralı servis kaydınız oluşturuldu. Takip için: " . rapor_url($s) . "\n" . ($u['firma_unvan'] ?: $u['ad_soyad']))) ?>">💬 Müşteriye WhatsApp'tan gönder</a><?php endif; ?>
  </div>
  <div class="belge">
    <?= belge_ust($s, $u, $kart, $m, 'SERVİS KABUL FORMU') ?>
    <?php if ($s['sikayet']): ?><div class="kutu"><h3>📝 Müşteri İstekleri</h3><div class="ic metin"><?= e($s['sikayet']) ?></div></div><?php endif; ?>
    <div class="kutu"><h3>🧾 Yapılacak İşler</h3><div class="ic"><?= belge_kalemler($s, $kalemler) ?></div></div>
    <?php if ($s['musteri_notu']): ?><div class="kutu"><h3>💬 Müşteriye İletilecek Notlar</h3><div class="ic metin"><?= e($s['musteri_notu']) ?></div></div><?php endif; ?>
    <div class="kutu"><div class="ic">
      <p class="metin" style="margin-bottom:16px"><?= e($u['kabul_metni'] ?: kabul_metni_varsayilan($tanim)) ?></p>
      <div class="imzalar"><?= imza_alani('imza_usta', 'Servis yetkilisi – İmza', $s['imza_kabul_usta']) ?><?= imza_alani('imza_musteri', 'Müşteri – İmza', $s['imza_kabul_musteri']) ?></div>
    </div></div>
  </div>
  <div class="alt yazdir-gizle" style="display:flex;justify-content:space-between;gap:10px;margin-top:6px">
    <a class="btn btn-ghost" href="kabul-talep.php?id=<?= $id ?>">← Geri</a>
    <span><button class="btn btn-soft">✍️ İmzayı kaydet</button> <button class="btn btn-main" name="ileri" value="1">İleri →</button></span>
  </div>
</form>
<?= imza_js() ?><?= qr_js() ?>
<?php panel_bitir();
