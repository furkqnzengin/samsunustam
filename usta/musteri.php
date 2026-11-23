<?php
// Müşteri kartı: bilgiler, bakiye, kartlar (araç/cihaz), servis geçmişi
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$id = (int)get('id');
$m = $id ? row('SELECT * FROM musteriler WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$m) { flash('Müşteri bulunamadı.', 'err'); redirect('usta/musteriler.php'); }
$hata = '';

if (is_post()) {
    csrf_check();
    if ($m && post('islem') === 'sil') {
        if ((int)val('SELECT COUNT(*) FROM servisler WHERE musteri_id = ?', [$id])) flash('Servis kaydı olan müşteri silinemez.', 'err');
        else { q('DELETE FROM musteriler WHERE id = ?', [$id]); flash('Müşteri silindi.', 'info'); redirect('usta/musteriler.php'); }
        redirect('usta/musteri.php?id=' . $id);
    }
    [$v, $hata] = musteri_oku();
    if (!$hata) {
        $id = musteri_kaydet($uid, $v, $m ? $id : null);
        if ($m) q('UPDATE servisler SET musteri_ad = ?, musteri_tel = ?, musteri_firma = ? WHERE musteri_id = ? AND durum NOT IN (\'teslim\',\'iptal\')',
            [$v['ad_soyad'], $v['telefon'], $v['tip'] === 'kurumsal' ? $v['unvan'] : null, $id]);
        flash($m ? 'Müşteri bilgileri kaydedildi.' : 'Müşteri eklendi.');
        redirect('usta/musteri.php?id=' . $id);
    }
}
// Bu müşteri için yeni servis kabul: araç/cihaz seçilerek talepler adımına geçilir
if ($m && get('servis') !== '') {
    $k = row('SELECT * FROM kartlar WHERE id = ? AND usta_id = ?', [(int)get('servis'), $uid]);
    $_SESSION['kabul'] = ['kart_id' => $k ? (int)$k['id'] : null, 'musteri_id' => $id];
    redirect($k ? 'usta/kabul-talep.php' : 'usta/kabul-kart.php');
}

$kartlar = $m ? rows('SELECT k.*, (SELECT MAX(giris_tarihi) FROM servisler s WHERE s.kart_id = k.id) AS son FROM kartlar k WHERE k.musteri_id = ? ORDER BY son DESC', [$id]) : [];
$servisler = $m ? rows(servis_liste_sql("s.usta_id = $uid AND s.musteri_id = " . (int)$id) . ' ORDER BY giris_tarihi DESC LIMIT 200') : [];
$bakiye = $m ? musteri_bakiye($id) : 0;
$tanim = sektorler()[usta_sektor($u)];

panel_basla('usta', 'musteriler.php', $m ? ($m['unvan'] ?: $m['ad_soyad']) : 'Yeni müşteri', $m ? 'Müşteri kodu ' . (int)$m['id'] . ' · Bakiye: <b class="' . ($bakiye > 0.009 ? 'down' : '') . '">' . para($bakiye) . '</b>' : '');
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
<?php if ($m): ?>
<div class="card actionbar">
  <a class="btn btn-main" href="?id=<?= $id ?>&servis=0">+ Yeni servis kabul</a>
  <?php if ($m['telefon']): ?><a class="btn btn-soft" href="tel:<?= e($m['telefon']) ?>">📞 Ara</a><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e(wa_link($m['telefon'], 'Merhaba ' . $m['ad_soyad'] . ', ')) ?>">💬 WhatsApp</a><?php endif; ?>
  <?php if ($bakiye > 0.009 && $m['telefon']): ?><a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e(wa_link($m['telefon'], 'Merhaba ' . $m['ad_soyad'] . ', servis kayıtlarınızdan ' . para($bakiye) . ' ödemeniz bulunmaktadır. Bilginize. ' . ($u['firma_unvan'] ?: $u['ad_soyad']))) ?>">⏰ Ödeme hatırlat</a><?php endif; ?>
</div>
<?php endif; ?>
<div class="row">
  <form method="post" class="kutu" id="sform">
    <?= csrf_field() ?>
    <h3>👤 Müşteri Bilgileri</h3>
    <div class="ic"><?= musteri_formu($hata ? array_merge($_POST, ['tip' => post('tip')]) : $m) ?></div>
    <div class="alt"><a class="btn btn-ghost" href="musteriler.php">← Liste</a><button class="btn btn-main"><?= $m ? 'Kaydet' : 'Müşteriyi ekle' ?></button></div>
  </form>
  <?php if ($m): ?>
  <div>
    <div class="kutu"><h3><?= $tanim['ikon'] ?> <?= e($tanim['kartlar']) ?></h3><div class="ic">
      <?php if ($kartlar): ?><div class="list"><?php foreach ($kartlar as $k): ?>
        <div class="li"><div class="grow"><a href="kart.php?id=<?= $k['id'] ?>"><?= kart_rozet($k, 'k') ?> <b><?= e($k['baslik']) ?></b></a><br><small><?= $k['son'] ? 'Son servis: ' . date('d.m.Y', strtotime($k['son'])) : 'Servis kaydı yok' ?></small></div>
          <a class="btn btn-soft" href="?id=<?= $id ?>&servis=<?= $k['id'] ?>">Servise al</a></div>
      <?php endforeach; ?></div><?php else: ?><p class="muted">Kayıtlı <?= e(tr_lower($tanim['kayit'])) ?> yok. "Yeni servis kabul" ile ekleyebilirsin.</p><?php endif; ?>
    </div></div>
  </div>
  <?php endif; ?>
</div>
<?php if ($m): ?>
<div class="card"><h2>🕘 Servis geçmişi <span class="muted small"><?= count($servisler) ?> kayıt</span></h2>
  <?php if ($servisler): ?><div class="scroll"><table class="tbl">
    <tr><th>Kayıt no</th><th>Giriş</th><th><?= e($tanim['kayit']) ?></th><th>Toplam</th><th>Kalan</th><th>Durum</th></tr>
    <?php foreach ($servisler as $r): $kl = (float)$r['toplam'] - (float)$r['odenen']; ?>
      <tr onclick="location='servis-kayit.php?id=<?= $r['id'] ?>'" style="cursor:pointer"><td><b>#<?= (int)$r['no'] ?></b></td><td><small><?= date('d.m.Y', strtotime($r['giris_tarihi'])) ?></small></td><td><?= satir_rozet($r) ?></td>
        <td><?= para($r['toplam']) ?></td><td><?= $kl > 0.009 && $r['durum'] !== 'iptal' ? '<b class="down">' . para($kl) . '</b>' : '<span class="up">✓</span>' ?></td><td><?= servis_durum_pill($r['durum']) ?></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Henüz servis kaydı yok.</p><?php endif; ?>
</div>
<?php if (!$servisler): ?><form method="post" class="card danger inline-form" onsubmit="return confirm('Müşteri silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><span class="muted grow">Bu müşterinin servis kaydı yok.</span><button class="btn btn-red">Müşteriyi sil</button></form><?php endif; ?>
<?php endif; ?>
<?= etiket_datalist($uid) ?>
<?php panel_bitir();
