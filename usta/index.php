<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$say = fn(string $tur, int $basGun, int $bitGun) => (int)val(
    "SELECT COALESCE(SUM(adet),0) FROM olaylar WHERE usta_id = ? AND tur = ? AND tarih > CURDATE() - INTERVAL ? DAY AND tarih <= CURDATE() - INTERVAL ? DAY",
    [$uid, $tur, $basGun, $bitGun]
);
$stat = [];
foreach (['goruntuleme', 'arama', 'whatsapp'] as $t) $stat[$t] = [$say($t, 30, 0), $say($t, 60, 30)];
$hafta = $say('goruntuleme', 7, 0);
$gunluk = gunluk("SELECT tarih, adet AS n FROM olaylar WHERE usta_id = ? AND tur = 'goruntuleme' AND tarih > CURDATE() - INTERVAL 7 DAY", [$uid]);

$fotoSay = (int)val('SELECT COUNT(*) FROM fotograflar WHERE usta_id = ?', [$uid]);
$hizSay = (int)val('SELECT COUNT(*) FROM usta_hizmet WHERE usta_id = ?', [$uid]);
$adimlar = [
    ['Profil fotoğrafı', (bool)$u['foto'], 'profil.php'],
    ['Hakkında yazısı', mb_strlen((string)$u['hakkinda']) >= 40, 'profil.php'],
    ['WhatsApp numarası', (bool)$u['whatsapp'], 'profil.php'],
    ['En az 3 hizmet seçimi', $hizSay >= 3, 'profil.php#hizmetler'],
    ["En az 4 iş fotoğrafı ($fotoSay/4)", $fotoSay >= 4, 'fotograflar.php'],
    ['Ustalık belgesi', (bool)$u['belge_dosya'], 'profil.php#belge'],
];
$tamam = count(array_filter($adimlar, fn($a) => $a[1]));
$yuzde = (int)round($tamam / count($adimlar) * 100);

$talepler = rows('SELECT t.*, i.ad AS ilce FROM talepler t LEFT JOIN ilceler i ON i.id = t.ilce_id WHERE t.usta_id = ? ORDER BY t.id DESC LIMIT 4', [$uid]);
$yorumlar = rows("SELECT * FROM yorumlar WHERE usta_id = ? AND durum = 'yayinda' ORDER BY id DESC LIMIT 3", [$uid]);

$ilkAd = explode(' ', $u['ad_soyad'])[0];
panel_basla('usta', 'index.php', "Hoş geldin, $ilkAd Usta 👋", $u['durum'] === 'aktif' ? 'Bu hafta profilin <b>' . sayi($hafta) . '</b> kez görüntülendi.' : '');
?>
<?php if ($u['durum'] === 'beklemede'): ?>
  <div class="notice n-amber"><b>⏳ Profilin onay bekliyor.</b> Yönetici onayladığında sitede yayına çıkacak. Bu sürede profilini eksiksiz doldurman onayı hızlandırır. <a href="../usta.php?u=<?= e($u['slug']) ?>" target="_blank">Önizle →</a></div>
<?php elseif ($u['durum'] === 'askida'): ?>
  <div class="notice n-red"><b>Profilin askıya alındı</b> ve şu an sitede görünmüyor. Bilgi için site yöneticisiyle iletişime geç.</div>
<?php elseif ($u['durum'] === 'reddedildi'): ?>
  <div class="notice n-red"><b>Başvurun onaylanmadı.</b> <?= $u['red_nedeni'] ? 'Neden: ' . e($u['red_nedeni']) . '. ' : '' ?>Profilini düzenleyip tekrar başvurabilirsin. <a href="profil.php">Profili düzenle →</a></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><div class="ic">👁️</div><div><small>Profil Görüntülenme</small><b><?= sayi($stat['goruntuleme'][0]) ?></b><?= degisim(...$stat['goruntuleme']) ?> <small class="inline">son 30 gün</small></div></div>
  <div class="stat"><div class="ic">📞</div><div><small>Arama Tıklaması</small><b><?= sayi($stat['arama'][0]) ?></b><?= degisim(...$stat['arama']) ?> <small class="inline">son 30 gün</small></div></div>
  <div class="stat"><div class="ic">💬</div><div><small>WhatsApp Tıklaması</small><b><?= sayi($stat['whatsapp'][0]) ?></b><?= degisim(...$stat['whatsapp']) ?> <small class="inline">son 30 gün</small></div></div>
  <div class="stat"><div class="ic">⭐</div><div><small>Ortalama Puan</small><b><?= $u['yorum_sayisi'] ? puan_fmt($u['puan']) : '—' ?></b><span class="up"><?= (int)$u['yorum_sayisi'] ?> yorum</span></div></div>
</div>

<div class="row">
  <div class="card">
    <h2>Son 7 Gün Profil Ziyaretleri</h2>
    <?= grafik($gunluk) ?>
  </div>
  <div class="card">
    <h2>Profil Doluluk</h2>
    <b class="bigpct">%<?= $yuzde ?></b>
    <div class="prog"><div style="width:<?= $yuzde ?>%"></div></div>
    <small class="muted">Profili eksiksiz ustalar müşterilerin daha çok dikkatini çeker.</small>
    <div class="check">
      <?php foreach ($adimlar as [$ad, $ok, $link]): ?>
        <?= $ok ? '<span>✅ ' . e($ad) . '</span>' : '<a class="no" href="' . e($link) . '">⬜ ' . e($ad) . ' →</a>' ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row">
  <div class="card">
    <h2>Gelen İş Talepleri <a href="talepler.php">Tümü →</a></h2>
    <?php if ($talepler): ?><div class="list">
      <?php foreach ($talepler as $t): ?>
        <div class="li"><div class="av sm"><?= e(initials($t['ad'])) ?></div><div class="grow"><b><?= e($t['ad']) ?></b> <?= durum_pill($t['durum']) ?><br><small>📍 <?= e($t['ilce'] ?? 'İlçe belirtilmedi') ?> · <?= zaman_once($t['olusturma']) ?></small><p><?= e(mb_strimwidth($t['mesaj'], 0, 140, '…')) ?></p></div><a class="btn btn-main" href="talepler.php#t<?= $t['id'] ?>">Aç</a></div>
      <?php endforeach; ?>
    </div><?php else: ?><p class="muted">Henüz iş talebi yok. Profilin yayına çıkınca müşteriler buradan sana ulaşabilecek.</p><?php endif; ?>
  </div>
  <div class="card">
    <h2>Son Yorumlar <a href="yorumlar.php">Tümü →</a></h2>
    <?php if ($yorumlar): ?><div class="list">
      <?php foreach ($yorumlar as $y): ?>
        <div class="li"><div class="grow"><b><?= e($y['ad']) ?></b> <span class="stars"><?= yildiz($y['puan']) ?></span><br><small><?= zaman_once($y['olusturma']) ?></small><p><?= e(mb_strimwidth($y['metin'], 0, 120, '…')) ?></p>
          <?php if (!$y['usta_yaniti']): ?><a class="btn btn-soft" style="margin-top:6px" href="yorumlar.php#y<?= $y['id'] ?>">Yanıt yaz</a><?php endif; ?></div></div>
      <?php endforeach; ?>
    </div><?php else: ?><p class="muted">Henüz yorum yok. Memnun müşterilerinden profiline yorum yazmalarını isteyebilirsin.</p><?php endif; ?>
  </div>
</div>
<?php panel_bitir();
