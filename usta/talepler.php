<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];

if (is_post()) {
    csrf_check();
    $durum = post('durum');
    if (in_array($durum, ['yeni', 'goruldu', 'yanitlandi'], true)) {
        q('UPDATE talepler SET durum = ? WHERE id = ? AND usta_id = ?', [$durum, (int)post('id'), $uid]);
    }
    redirect('usta/talepler.php' . (get('f') ? '?f=' . urlencode(get('f')) : ''));
}

$f = get('f');
$where = 'usta_id = ?';
$params = [$uid];
if (in_array($f, ['yeni', 'goruldu', 'yanitlandi'], true)) { $where .= ' AND t.durum = ?'; $params[] = $f; }
$talepler = rows("SELECT t.*, i.ad AS ilce FROM talepler t LEFT JOIN ilceler i ON i.id = t.ilce_id WHERE $where ORDER BY t.id DESC LIMIT 200", $params);
$sayac = [];
foreach (rows('SELECT durum, COUNT(*) n FROM talepler WHERE usta_id = ? GROUP BY durum', [$uid]) as $r) $sayac[$r['durum']] = (int)$r['n'];

panel_basla('usta', 'talepler.php', 'İş Talepleri', 'Profilindeki formdan sana gönderilen talepler. Müşteriyi arayıp durumunu güncelle.');
?>
<div class="filterbar">
  <a class="<?= $f === '' ? 'on' : '' ?>" href="talepler.php">Tümü <b><?= array_sum($sayac) ?></b></a>
  <a class="<?= $f === 'yeni' ? 'on' : '' ?>" href="?f=yeni">Yeni <b><?= $sayac['yeni'] ?? 0 ?></b></a>
  <a class="<?= $f === 'goruldu' ? 'on' : '' ?>" href="?f=goruldu">Görüldü <b><?= $sayac['goruldu'] ?? 0 ?></b></a>
  <a class="<?= $f === 'yanitlandi' ? 'on' : '' ?>" href="?f=yanitlandi">Yanıtlandı <b><?= $sayac['yanitlandi'] ?? 0 ?></b></a>
</div>
<div class="card">
  <?php if ($talepler): ?><div class="list">
    <?php foreach ($talepler as $t): ?>
      <div class="li" id="t<?= $t['id'] ?>"><div class="av sm"><?= e(initials($t['ad'])) ?></div>
        <div class="grow"><b><?= e($t['ad']) ?></b> <?= durum_pill($t['durum']) ?><br>
          <small>📍 <?= e($t['ilce'] ?? 'İlçe belirtilmedi') ?> · <?= zaman_once($t['olusturma']) ?> · 📞 <?= e(tel_goster($t['telefon'])) ?></small>
          <p><?= nl2br(e($t['mesaj'])) ?></p>
          <div class="acts wrapacts">
            <a class="btn btn-main" href="tel:<?= e($t['telefon']) ?>">📞 Ara</a>
            <a class="btn btn-soft" href="<?= e(wa_link($t['telefon'], 'Merhaba ' . $t['ad'] . ', Samsun Ustam üzerinden gönderdiğiniz talep için yazıyorum.')) ?>" target="_blank" rel="noopener">💬 WhatsApp</a>
            <?php foreach (['goruldu' => 'Görüldü', 'yanitlandi' => 'Yanıtlandı'] as $d => $ad) if ($t['durum'] !== $d): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="durum" value="<?= $d ?>"><button class="btn btn-ghost">✓ <?= $ad ?></button></form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div><?php else: ?><p class="muted">Bu listede talep yok.</p><?php endif; ?>
</div>
<?php panel_bitir();
