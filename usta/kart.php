<?php
// Araç / cihaz kartı: bilgiler (düzenlenebilir), belge, müşteri, servis geçmişi
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$id = (int)get('id');
$k = row('SELECT * FROM kartlar WHERE id = ? AND usta_id = ?', [$id, $uid]);
if (!$k) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/kartlar.php'); }
$tanim = sektorler()[$k['sektor']] ?? sektorler()['yerinde'];
$hata = '';

if (is_post()) {
    csrf_check();
    if (post('islem') === 'sil') {
        if ((int)val('SELECT COUNT(*) FROM servisler WHERE kart_id = ?', [$id])) flash('Servis kaydı olan kart silinemez.', 'err');
        else { dosya_sil($k['belge']); q('DELETE FROM kartlar WHERE id = ?', [$id]); flash('Kart silindi.', 'info'); redirect('usta/kartlar.php'); }
        redirect('usta/kart.php?id=' . $id);
    }
    [$ozel, $anahtar, $hata] = kart_oku($tanim);
    if (!$hata) {
        q('UPDATE kartlar SET anahtar = ?, baslik = ?, ozel = ?, notlar = ?, guncelleme = NOW() WHERE id = ?',
            [$anahtar, mb_substr(servis_baslik($k['sektor'], $ozel), 0, 200), json_encode($ozel, JSON_UNESCAPED_UNICODE), mb_substr(post('notlar'), 0, 2000) ?: null, $id]);
        flash('Bilgiler kaydedildi.');
        redirect('usta/kart.php?id=' . $id);
    }
}
if (get('servis') === '1') {
    $_SESSION['kabul'] = ['kart_id' => $id, 'musteri_id' => $k['musteri_id'] ? (int)$k['musteri_id'] : null];
    redirect($k['musteri_id'] ? 'usta/kabul-talep.php' : 'usta/kabul-musteri.php');
}
$m = $k['musteri_id'] ? row('SELECT * FROM musteriler WHERE id = ?', [$k['musteri_id']]) : null;
$ozel = $hata ? (array)($_POST['ozel'] ?? []) : (json_decode((string)$k['ozel'], true) ?: []);
$servisler = rows(servis_liste_sql("s.usta_id = $uid AND s.kart_id = " . $id) . ' ORDER BY giris_tarihi DESC');
$hat = kart_hatirlatmalar($k);

panel_basla('usta', 'kartlar.php', $tanim['kayit'] . ' Kartı', kart_rozet($k) . ' ' . e($k['baslik']) . ($hat ? ' ' . hatirlatma_rozet($hat) : ''));
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
<div class="card actionbar">
  <a class="btn btn-main" href="?id=<?= $id ?>&servis=1">+ Servise al</a>
  <?php if ($m): ?><a class="btn btn-soft" href="musteri.php?id=<?= $m['id'] ?>">👤 <?= e($m['unvan'] ?: $m['ad_soyad']) ?></a><?php if ($m['telefon']): ?><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e(wa_link($m['telefon'], 'Merhaba ' . $m['ad_soyad'] . ', ')) ?>">💬 WhatsApp</a><?php endif; ?><?php endif; ?>
  <?php if ($k['belge']): ?><a class="btn btn-ghost" href="kart-belge.php?id=<?= $id ?>" target="_blank">📄 Belge</a><?php endif; ?>
</div>
<form method="post" class="kutu" id="sform">
  <?= csrf_field() ?>
  <h3><?= $tanim['ikon'] ?> <?= e($tanim['kayit']) ?> Bilgileri</h3>
  <div class="ic"><div class="fgrid">
    <?php foreach ($tanim['alanlar'] as $a) echo kart_alani($a, (string)($ozel[$a[0]] ?? '')); ?>
    <label class="frow wide"><span>Notlar</span><textarea class="inp" name="notlar" rows="2"><?= e($hata ? post('notlar') : (string)$k['notlar']) ?></textarea></label>
  </div></div>
  <div class="alt"><a class="btn btn-ghost" href="kartlar.php">← Liste</a><button class="btn btn-main">Kaydet</button></div>
</form>
<div class="card"><h2>🕘 Servis geçmişi <span class="muted small"><?= count($servisler) ?> kayıt</span></h2>
  <?php if ($servisler): ?><div class="scroll"><table class="tbl">
    <tr><th>Kayıt no</th><th>Giriş</th><?php if (!empty($tanim['servis']['km'])): ?><th><?= e($tanim['servis']['km']) ?></th><?php endif; ?><th>Müşteri talebi</th><th>Toplam</th><th>Durum</th></tr>
    <?php foreach ($servisler as $r): ?>
      <tr onclick="location='servis-kayit.php?id=<?= $r['id'] ?>'" style="cursor:pointer"><td><b>#<?= (int)$r['no'] ?></b></td><td><small><?= date('d.m.Y', strtotime($r['giris_tarihi'])) ?></small></td>
        <?php if (!empty($tanim['servis']['km'])): ?><td><?= $r['km'] !== null ? sayi($r['km']) : '—' ?></td><?php endif; ?>
        <td><small><?= e(mb_strimwidth((string)$r['sikayet'], 0, 80, '…')) ?></small></td><td><?= para($r['toplam']) ?></td><td><?= servis_durum_pill($r['durum']) ?></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Henüz servis kaydı yok.</p><?php endif; ?>
</div>
<?php if (!$servisler): ?><form method="post" class="card danger inline-form" onsubmit="return confirm('Kart silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><span class="muted grow">Bu kartın servis kaydı yok.</span><button class="btn btn-red">Kartı sil</button></form><?php endif; ?>
<script>document.querySelectorAll('input.upper').forEach(i => i.addEventListener('input', () => { const p = i.selectionStart; i.value = i.value.toLocaleUpperCase('tr-TR'); i.setSelectionRange(p, p); }));</script>
<?php panel_bitir();
