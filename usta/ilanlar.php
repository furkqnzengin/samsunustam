<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/is.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$ilanlar = rows("SELECT l.*, i.ad AS ilce_ad,
    (SELECT COUNT(*) FROM ilan_basvurulari b WHERE b.ilan_id = l.id) AS basvuru,
    (SELECT COUNT(*) FROM ilan_basvurulari b WHERE b.ilan_id = l.id AND b.durum = 'yeni') AS yeni
    FROM ilanlar l LEFT JOIN ilceler i ON i.id = l.ilce_id WHERE l.usta_id = ? ORDER BY l.durum = 'aktif' DESC, l.olusturma DESC", [$uid]);
$arayanSay = (int)val("SELECT COUNT(*) FROM is_arayanlar WHERE durum = 'yayinda' AND bitis >= CURDATE()");

panel_basla('usta', 'ilanlar.php', 'Eleman Arıyorum', 'Ücretsiz eleman ilanı aç; başvurular buraya gelsin.');
?>
<?php if ($u['durum'] !== 'aktif'): ?><div class="notice n-amber"><b>Profilin onaylanınca ilanların yayına çıkar.</b> İlanını şimdiden hazırlayabilirsin.</div><?php endif; ?>

<div class="stats three">
  <a class="stat" href="ilan.php"><div class="ic">➕</div><div><small>Yeni ilan</small><b style="font-size:20px">İlan aç</b><span class="up">Eleman ara →</span></div></a>
  <div class="stat"><div class="ic">📥</div><div><small>Yeni başvuru</small><b><?= array_sum(array_column($ilanlar, 'yeni')) ?></b><span class="up">Toplam <?= array_sum(array_column($ilanlar, 'basvuru')) ?> başvuru</span></div></div>
  <a class="stat" href="../is-arayanlar.php" target="_blank"><div class="ic">🙋</div><div><small>İş arayanlar</small><b><?= $arayanSay ?></b><span class="up">Adaylara göz at ↗</span></div></a>
</div>

<div class="card">
  <h2>İlanlarım <a class="btn btn-main" style="color:#fff" href="ilan.php">+ Yeni ilan</a></h2>
  <?php if ($ilanlar): ?><div class="list">
    <?php foreach ($ilanlar as $l): $suresi = $l['bitis'] < date('Y-m-d'); ?>
      <a class="li" href="ilan.php?id=<?= $l['id'] ?>">
        <div class="kico gelir">💼</div>
        <div class="grow"><b><?= e($l['baslik']) ?></b> <?= $suresi && $l['durum'] === 'aktif' ? '<span class="pill p-blue">Süresi doldu</span>' : ilan_durum_pill($l['durum']) ?><?= $l['yeni'] ? ' <span class="pill p-amber">' . (int)$l['yeni'] . ' yeni başvuru</span>' : '' ?><br>
          <small>📍 <?= e($l['ilce_ad'] ?? 'Samsun') ?> · <?= e($l['calisma']) ?> · <?= (int)$l['basvuru'] ?> başvuru · <?= (int)$l['goruntulenme'] ?> görüntülenme · Bitiş <?= date('d.m.Y', strtotime($l['bitis'])) ?></small></div>
        <span class="btn btn-soft">Aç</span>
      </a>
    <?php endforeach; ?>
  </div><?php else: ?>
    <div class="emptybox"><div style="font-size:40px">💼</div><p>Henüz ilanın yok. Kalfa, çırak ya da usta mı arıyorsun?</p><a class="btn btn-main" href="ilan.php">+ İlk ilanını aç</a></div>
  <?php endif; ?>
</div>
<?php panel_bitir();
