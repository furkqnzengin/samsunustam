<?php
// Servis kabul 6. adım: servis teslim formu (yazdırılabilir, imzalı)
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
    foreach (['imza_usta' => 'imza_teslim_usta', 'imza_musteri' => 'imza_teslim_musteri'] as $alan => $kolon) {
        $v = imza_oku($alan);
        if ($v !== null) q("UPDATE servisler SET $kolon = ? WHERE id = ?", [$v ?: null, $id]);
    }
    if (post('teslim')) {
        q("UPDATE servisler SET durum = 'teslim', teslim_tarihi = COALESCE(teslim_tarihi, NOW()), guncelleme = NOW() WHERE id = ?", [$id]);
        flash('Teslim edildi olarak işaretlendi.');
    } else {
        flash('İmzalar kaydedildi.');
    }
    redirect('usta/kabul-teslim.php?id=' . $id);
}

$kart = $s['kart_id'] ? row('SELECT * FROM kartlar WHERE id = ?', [$s['kart_id']]) : null;
$m = $s['musteri_id'] ? row('SELECT * FROM musteriler WHERE id = ?', [$s['musteri_id']]) : null;
$tanim = sektorler()[$s['sektor']] ?? sektorler()['yerinde'];
$kalemler = rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$id]);
$odemeler = rows('SELECT * FROM servis_odemeleri WHERE servis_id = ? ORDER BY tarih, id', [$id]);
$kontrol = json_decode((string)$s['kontrol'], true) ?: [];
$sonra = rows("SELECT * FROM servis_fotolari WHERE servis_id = ? AND etiket = 'sonra' ORDER BY id", [$id]);
$h = servis_hesap($s, $kalemler, $odemeler);

panel_basla('usta', 'kabul', 'Servis Teslim Formu', kabul_baslik($s, $kart, '') . ' · ' . servis_durum_pill($s['durum']));
?>
<?= kabul_sekmeleri($s, 'teslim', $tanim) ?>
<form method="post" class="adimgovde" id="sform">
  <?= csrf_field() ?>
  <div class="yazdir-gizle" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px;flex-wrap:wrap">
    <button type="button" class="btn btn-ghost" onclick="window.print()">🖨️ Yazdır / PDF</button>
    <?php if ($s['musteri_tel']): ?><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e(wa_link($s['musteri_tel'], "Merhaba {$s['musteri_ad']}, #{$s['no']} numaralı servis kaydınız " . ($s['durum'] === 'teslim' ? 'teslim edildi' : 'teslime hazır') . ". Rapor: " . rapor_url($s) . ($h['kalan'] > 0.009 ? "\nKalan tutar: " . para($h['kalan']) : '') . "\n" . ($u['firma_unvan'] ?: $u['ad_soyad']))) ?>">💬 Müşteriye bildir</a><?php endif; ?>
  </div>
  <?php if ($h['kalan'] > 0.009): ?><div class="notice n-amber yazdir-gizle">Bu kaydın <b><?= para($h['kalan']) ?></b> ödemesi kalmış. Tahsilatı <a href="servis-kayit.php?id=<?= $id ?>#tahsilat">İşlemler</a> adımından ekleyebilirsin.</div><?php endif; ?>
  <div class="belge">
    <?= belge_ust($s, $u, $kart, $m, 'SERVİS TESLİM FORMU') ?>
    <?php if ($s['yapilan']): ?><div class="kutu"><h3>🛠️ Yapılan İşlemler</h3><div class="ic metin"><?= e($s['yapilan']) ?></div></div><?php endif; ?>
    <?php if ($kontrol): ?><div class="kutu"><h3>✅ Kontrol Edilenler</h3><div class="ic"><div class="hasarliste"><?php foreach ($kontrol as $c): ?><span style="border-color:var(--g300)">✓ <?= e($c) ?></span><?php endforeach; ?></div></div></div><?php endif; ?>
    <div class="kutu"><h3>🧾 İşçilik ve Parçalar</h3><div class="ic"><?= belge_kalemler($s, $kalemler, $odemeler, true) ?></div></div>
    <?php if ($s['garanti'] || $s['sonraki_bakim']): ?><div class="kutu"><h3>🛡️ Garanti ve Bakım</h3><div class="ic"><div class="dl2">
      <?= $s['garanti'] ? '<div><span>Garanti</span><b>' . e($s['garanti']) . '</b></div>' : '' ?><?= $s['sonraki_bakim'] ? '<div><span>Sonraki bakım</span><b>' . e($s['sonraki_bakim']) . '</b></div>' : '' ?>
      <?= $s['teslim_tarihi'] ? '<div><span>Teslim tarihi</span><b>' . date('d.m.Y H:i', strtotime($s['teslim_tarihi'])) . '</b></div>' : '' ?></div></div></div><?php endif; ?>
    <?php if ($sonra): ?><div class="kutu"><h3>📷 İşlem Sonrası</h3><div class="ic photos"><?php foreach ($sonra as $f): ?><div class="ph"><img src="<?= e(upload_url($f['dosya'])) ?>" alt=""></div><?php endforeach; ?></div></div><?php endif; ?>
    <?php if ($s['musteri_notu']): ?><div class="kutu"><h3>💬 Müşteriye Notlar</h3><div class="ic metin"><?= e($s['musteri_notu']) ?></div></div><?php endif; ?>
    <div class="kutu"><div class="ic">
      <p class="metin" style="margin-bottom:16px"><?= e($u['teslim_metni'] ?: teslim_metni_varsayilan($tanim)) ?></p>
      <div class="imzalar"><?= imza_alani('imza_usta', 'Teslim eden – İmza', $s['imza_teslim_usta']) ?><?= imza_alani('imza_musteri', 'Teslim alan (müşteri) – İmza', $s['imza_teslim_musteri']) ?></div>
    </div></div>
  </div>
  <div class="alt yazdir-gizle" style="display:flex;justify-content:space-between;gap:10px;margin-top:6px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="servis-kayit.php?id=<?= $id ?>">← İşlemler</a>
    <span><button class="btn btn-soft">✍️ İmzayı kaydet</button>
      <?php if ($s['durum'] !== 'teslim'): ?><button class="btn btn-main" name="teslim" value="1" onclick="return confirm('Kayıt teslim edildi olarak işaretlensin mi?')">✓ Teslim edildi</button><?php endif; ?></span>
  </div>
</form>
<?= imza_js() ?><?= qr_js() ?>
<?php panel_bitir();
