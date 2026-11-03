<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

$toplam = (int)val("SELECT COUNT(*) FROM ustalar WHERE durum = 'aktif'");
$buAy = (int)val("SELECT COUNT(*) FROM ustalar WHERE olusturma >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$bekleyen = (int)val("SELECT COUNT(*) FROM ustalar WHERE durum = 'beklemede'");
$bugunGor = (int)val("SELECT COALESCE(SUM(adet),0) FROM olaylar WHERE tur = 'goruntuleme' AND tarih = CURDATE()");
$dunGor = (int)val("SELECT COALESCE(SUM(adet),0) FROM olaylar WHERE tur = 'goruntuleme' AND tarih = CURDATE() - INTERVAL 1 DAY");
$ulasim = (int)val("SELECT COALESCE(SUM(adet),0) FROM olaylar WHERE tur IN ('arama','whatsapp') AND tarih > CURDATE() - INTERVAL 7 DAY");

$basvurular = rows("SELECT u.*, (SELECT GROUP_CONCAT(i.ad SEPARATOR ', ') FROM usta_ilce ui JOIN ilceler i ON i.id = ui.ilce_id WHERE ui.usta_id = u.id) AS ilceler FROM ustalar u WHERE u.durum = 'beklemede' ORDER BY u.id LIMIT 5");
$gunlukGor = gunluk("SELECT tarih, SUM(adet) n FROM olaylar WHERE tur = 'goruntuleme' AND tarih > CURDATE() - INTERVAL 7 DAY GROUP BY tarih", []);
$aranan = rows("SELECT h.ad, SUM(a.adet) n FROM aramalar a JOIN hizmetler h ON h.id = a.hizmet_id WHERE a.tarih > CURDATE() - INTERVAL 30 DAY GROUP BY h.id, h.ad ORDER BY n DESC LIMIT 6");
$aranMax = max(1, ...array_map(fn($r) => (int)$r['n'], $aranan ?: [['n' => 1]]));
$sonUstalar = rows('SELECT u.*, p.ad AS paket_ad, p.one_cikar FROM ustalar u LEFT JOIN paketler p ON p.id = u.paket_id ORDER BY u.id DESC LIMIT 5');
$yorumlar = rows("SELECT y.*, u.ad_soyad FROM yorumlar y JOIN ustalar u ON u.id = y.usta_id WHERE y.durum = 'beklemede' ORDER BY y.id LIMIT 4");
$paketTalep = rows("SELECT pt.*, u.ad_soyad, u.telefon, p.ad AS paket FROM paket_talepleri pt JOIN ustalar u ON u.id = pt.usta_id JOIN paketler p ON p.id = pt.paket_id WHERE pt.durum = 'yeni' ORDER BY pt.id DESC LIMIT 5");

panel_basla('admin', 'index.php', 'Genel Bakış', tarih_tr() . ' · Sitenin bugünkü durumu');
?>
<?php if (is_dir(ROOT . '/kurulum')): ?><div class="notice n-red"><b>Güvenlik:</b> Kurulum tamamlandı. Sunucudaki <b>kurulum</b> klasörünü silmeyi unutma.</div><?php endif; ?>

<div class="stats">
  <div class="stat"><div class="ic">👷</div><div><small>Aktif Usta</small><b><?= sayi($toplam) ?></b><span class="up">+<?= $buAy ?> bu ay kayıt</span></div></div>
  <a class="stat" href="ustalar.php?durum=beklemede"><div class="ic" style="background:var(--amber100)">⏳</div><div><small>Onay Bekleyen</small><b><?= $bekleyen ?></b><?= $bekleyen ? '<span class="down">İnceleme gerekli</span>' : '<span class="up">Hepsi tamam</span>' ?></div></a>
  <div class="stat"><div class="ic" style="background:var(--blue100)">👁️</div><div><small>Bugün Profil Görüntülenme</small><b><?= sayi($bugunGor) ?></b><?= degisim($bugunGor, $dunGor) ?> <small class="inline">düne göre</small></div></div>
  <div class="stat"><div class="ic">📞</div><div><small>Ustalara Ulaşım</small><b><?= sayi($ulasim) ?></b><span class="up">Arama + WhatsApp, 7 gün</span></div></div>
</div>

<div class="card" style="margin-bottom:20px">
  <h2>Onay Bekleyen Usta Başvuruları <a href="ustalar.php?durum=beklemede">Tümü (<?= $bekleyen ?>) →</a></h2>
  <?php if ($basvurular): ?><div class="scroll"><table class="tbl">
    <tr><th>Usta</th><th>Meslek</th><th class="hide-m">İlçe</th><th class="hide-m">Başvuru</th><th class="hide-m">Belge</th><th>İşlem</th></tr>
    <?php foreach ($basvurular as $b): ?>
      <tr><td><a class="who" href="usta.php?id=<?= $b['id'] ?>"><div class="av sm"><?= e(initials($b['ad_soyad'])) ?></div><div><b><?= e($b['ad_soyad']) ?></b><small><?= e(tel_goster($b['telefon'])) ?></small></div></a></td>
        <td><?= e($b['meslek']) ?></td><td class="hide-m"><?= e(mb_strimwidth((string)$b['ilceler'], 0, 30, '…')) ?></td><td class="hide-m"><?= zaman_once($b['olusturma']) ?></td>
        <td class="hide-m"><?= $b['belge_dosya'] ? '<span class="pill p-green">Yüklendi</span>' : '<span class="pill p-amber">Yok</span>' ?></td>
        <td><div class="acts">
          <form method="post" action="usta.php?id=<?= $b['id'] ?>"><?= csrf_field() ?><input type="hidden" name="islem" value="onayla"><input type="hidden" name="geri" value="index"><button class="btn btn-main">Onayla</button></form>
          <a class="btn btn-soft" href="usta.php?id=<?= $b['id'] ?>">İncele</a>
        </div></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Onay bekleyen başvuru yok. 🎉</p><?php endif; ?>
</div>

<div class="row">
  <div class="card"><h2>Son 7 Gün Profil Görüntülenmeleri</h2><?= grafik($gunlukGor) ?></div>
  <div class="card"><h2>En Çok Aranan Hizmetler <small class="muted small">30 gün</small></h2>
    <?php if ($aranan): ?><div class="bars"><?php foreach ($aranan as $a): ?><div class="bar"><span><?= e($a['ad']) ?></span><div class="track"><div class="fill" style="width:<?= round($a['n'] / $aranMax * 100) ?>%"></div></div><span><?= sayi($a['n']) ?></span></div><?php endforeach; ?></div>
    <?php else: ?><p class="muted">Henüz arama verisi yok.</p><?php endif; ?>
  </div>
</div>

<div class="row">
  <div class="card"><h2>Son Kayıt Olan Ustalar <a href="ustalar.php">Tüm ustalar →</a></h2>
    <?php if ($sonUstalar): ?><div class="scroll"><table class="tbl">
      <tr><th>Usta</th><th>Meslek</th><th class="hide-m">Paket</th><th>Durum</th><th></th></tr>
      <?php foreach ($sonUstalar as $s): ?>
        <tr><td><div class="who"><div class="av sm"><?= e(initials($s['ad_soyad'])) ?></div><b><?= e($s['ad_soyad']) ?></b></div></td><td><?= e($s['meslek']) ?></td>
          <td class="hide-m"><span class="pill <?= premium_mu($s) ? 'p-blue' : 'p-green' ?>"><?= e($s['paket_ad'] ?? '—') ?></span></td><td><?= durum_pill($s['durum']) ?></td><td><a class="btn btn-soft" href="usta.php?id=<?= $s['id'] ?>">Düzenle</a></td></tr>
      <?php endforeach; ?>
    </table></div><?php else: ?><p class="muted">Henüz kayıtlı usta yok. Ustalar <a class="link" href="../kayit.php" target="_blank">kayıt sayfasından</a> başvurabilir.</p><?php endif; ?>
  </div>
  <div class="card"><h2>Onay Bekleyen Yorumlar <a href="yorumlar.php">Tümü →</a></h2>
    <?php if ($yorumlar): ?><div class="list"><?php foreach ($yorumlar as $y): ?>
      <div class="li"><div class="grow"><b>"<?= e(mb_strimwidth($y['metin'], 0, 80, '…')) ?>"</b><br><small><?= e($y['ad']) ?> · <span class="stars"><?= yildiz($y['puan']) ?></span> · <?= e($y['ad_soyad']) ?> hakkında</small>
        <div class="acts" style="margin-top:8px">
          <form method="post" action="yorumlar.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $y['id'] ?>"><input type="hidden" name="islem" value="yayinda"><input type="hidden" name="geri" value="index"><button class="btn btn-main">Yayınla</button></form>
          <form method="post" action="yorumlar.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $y['id'] ?>"><input type="hidden" name="islem" value="gizli"><input type="hidden" name="geri" value="index"><button class="btn btn-red">Gizle</button></form>
        </div></div></div>
    <?php endforeach; ?></div><?php else: ?><p class="muted">Bekleyen yorum yok.</p><?php endif; ?>
  </div>
</div>

<?php if ($paketTalep): ?>
<div class="card"><h2>Paket Bilgi Talepleri <a href="paketler.php">Tümü →</a></h2>
  <div class="list"><?php foreach ($paketTalep as $p): ?>
    <div class="li"><div class="grow"><b><?= e($p['ad_soyad']) ?></b> → <?= e($p['paket']) ?> paketi<br><small><?= zaman_once($p['olusturma']) ?> · <?= e(tel_goster($p['telefon'])) ?></small></div><a class="btn btn-main" href="tel:<?= e($p['telefon']) ?>">📞 Ara</a></div>
  <?php endforeach; ?></div>
</div>
<?php endif; ?>
<?php panel_bitir();
