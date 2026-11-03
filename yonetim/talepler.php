<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

$talepler = rows('SELECT t.*, u.ad_soyad, u.slug, i.ad AS ilce FROM talepler t JOIN ustalar u ON u.id = t.usta_id LEFT JOIN ilceler i ON i.id = t.ilce_id ORDER BY t.id DESC LIMIT 200');
$gunluk = gunluk('SELECT DATE(olusturma) tarih, COUNT(*) n FROM talepler WHERE olusturma > CURDATE() - INTERVAL 7 DAY GROUP BY DATE(olusturma)', []);

panel_basla('admin', 'talepler.php', 'İş Talepleri', 'Ziyaretçilerin ustalara gönderdiği talepler (son 200).');
?>
<div class="card" style="margin-bottom:20px"><h2>Son 7 gün</h2><?= grafik($gunluk) ?></div>
<div class="card">
  <?php if ($talepler): ?><div class="scroll"><table class="tbl">
    <tr><th>Müşteri</th><th>Usta</th><th class="hide-m">İlçe</th><th class="hide-m">Mesaj</th><th>Durum</th><th class="hide-m">Tarih</th></tr>
    <?php foreach ($talepler as $t): ?>
      <tr><td><b><?= e($t['ad']) ?></b><small class="muted" style="display:block"><?= e(tel_maskele($t['telefon'])) ?></small></td>
        <td><a class="link" href="../usta.php?u=<?= e($t['slug']) ?>" target="_blank"><?= e($t['ad_soyad']) ?></a></td>
        <td class="hide-m"><?= e($t['ilce'] ?? '—') ?></td>
        <td class="hide-m"><small><?= e(mb_strimwidth($t['mesaj'], 0, 70, '…')) ?></small></td>
        <td><?= durum_pill($t['durum']) ?></td>
        <td class="hide-m"><small><?= zaman_once($t['olusturma']) ?></small></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Henüz iş talebi yok.</p><?php endif; ?>
</div>
<?php panel_bitir();
