<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';

// Kategoriler ve hizmetler (aktif usta sayılarıyla)
$hizmetSay = [];
foreach (rows("SELECT uh.hizmet_id, COUNT(*) n FROM usta_hizmet uh JOIN ustalar u ON u.id = uh.usta_id AND u.durum = 'aktif' GROUP BY uh.hizmet_id") as $r) {
    $hizmetSay[$r['hizmet_id']] = (int)$r['n'];
}
$kategoriler = rows('SELECT * FROM kategoriler ORDER BY sira, id');
$hizmetler = rows('SELECT * FROM hizmetler ORDER BY sira, id');
$katSay = [];
foreach (rows("SELECT h.kategori_id, COUNT(DISTINCT uh.usta_id) n FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id JOIN ustalar u ON u.id = uh.usta_id AND u.durum = 'aktif' GROUP BY h.kategori_id") as $r) {
    $katSay[$r['kategori_id']] = (int)$r['n'];
}
$hizmetByKat = [];
$hizmetBySlug = [];
foreach ($hizmetler as $h) {
    $hizmetByKat[$h['kategori_id']][] = $h;
    $hizmetBySlug[$h['slug']] = $h;
}

$ilceler = rows("SELECT i.*, (SELECT COUNT(*) FROM usta_ilce ui JOIN ustalar u ON u.id = ui.usta_id AND u.durum = 'aktif' WHERE ui.ilce_id = i.id) AS n FROM ilceler i ORDER BY i.sira");
$ilceSirali = $ilceler;
usort($ilceSirali, fn($a, $b) => [$b['n'], $a['sira']] <=> [$a['n'], $b['sira']]);

$toplamUsta = (int)val("SELECT COUNT(*) FROM ustalar WHERE durum = 'aktif'");
$toplamYorum = (int)val("SELECT COUNT(*) FROM yorumlar y JOIN ustalar u ON u.id = y.usta_id AND u.durum = 'aktif' WHERE y.durum = 'yayinda'");

// Bu hafta en çok aranan hizmetler
$trend = rows("SELECT h.ad, h.slug, k.ad AS kat,
        SUM(CASE WHEN a.tarih > CURDATE() - INTERVAL 7 DAY THEN a.adet ELSE 0 END) AS bu,
        SUM(CASE WHEN a.tarih <= CURDATE() - INTERVAL 7 DAY THEN a.adet ELSE 0 END) AS onceki
    FROM aramalar a JOIN hizmetler h ON h.id = a.hizmet_id JOIN kategoriler k ON k.id = h.kategori_id
    WHERE a.tarih > CURDATE() - INTERVAL 14 DAY
    GROUP BY h.id, h.ad, h.slug, k.ad HAVING bu > 0 ORDER BY bu DESC LIMIT 10");
$trendGercek = count($trend) >= 5;
if (!$trendGercek) {
    // Yeterli arama verisi yoksa en çok ustası olan hizmetleri göster
    $trend = rows("SELECT h.ad, h.slug, k.ad AS kat, COUNT(uh.usta_id) AS bu, 0 AS onceki
        FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id
        LEFT JOIN usta_hizmet uh ON uh.hizmet_id = h.id LEFT JOIN ustalar u ON u.id = uh.usta_id AND u.durum = 'aktif'
        GROUP BY h.id, h.ad, h.slug, k.ad, k.sira, h.sira ORDER BY bu DESC, k.sira, h.sira LIMIT 10");
}
$trendMax = max(1, ...array_map(fn($t) => (int)$t['bu'], $trend ?: [['bu' => 1]]));

$oneCikan = rows(usta_kart_sql() . ' LIMIT 3');

// Arama cümlesindeki hizmet listesi: önce popüler olanlar
$populer = array_filter(array_map('trim', explode(',', ayar('populer_hizmetler', 'Elektrikçi,Su Tesisatçısı,Kombi Tamiri,Boya Badana'))));
$populerH = array_values(array_filter(array_map(fn($ad) => $hizmetBySlug[slugify($ad)] ?? null, $populer)));

// Mevsimsel öneri: Ekim-Mart kış, Nisan-Eylül yaz
$ay = (int)date('n');
$kis = $ay >= 10 || $ay <= 3;
$mevsim = $kis
    ? ['🌧️', 'Karadeniz kışı kapıda.', 'Yağmur ve soğuk gelmeden evini hazırla. Bu mevsim en çok bunlar yaptırılıyor.', [['🔥', 'kombi-bakimi'], ['🌡️', 'petek-temizleme'], ['🏠', 'cati-tamiri'], ['🧱', 'mantolama']]]
    : ['☀️', 'Samsun yazı nemli geçer.', 'Sıcaklar bastırmadan klimanı ve evini yaza hazırla.', [['❄️', 'klima-bakimi'], ['🌬️', 'klima-montaji'], ['🦟', 'sineklik'], ['🐜', 'ilaclama']]];

site_basla();
?>
<div class="hero"><div class="wrap">
  <div>
    <span class="eyebrow"><i></i> <?= $toplamUsta ? sayi($toplamUsta) . ' usta · ' : '' ?>17 ilçe · <?= count($hizmetler) ?> hizmet</span>
    <h1>Evde bir şey mi bozuldu? <span class="hl">Samsun'da ustası var.</span></h1>
    <p class="lead">Atakum'dan Bafra'ya, mahallendeki güvenilir ustayı bul. Yorumları oku, tek dokunuşla ara.</p>
    <form class="ask" method="get" action="<?= url('ara.php') ?>">
      <div class="sent">Bana bir
        <select name="h" aria-label="Hizmet">
          <?php foreach ($populerH as $h): ?><option value="<?= e($h['slug']) ?>"><?= e(tr_lower($h['ad'])) ?></option><?php endforeach; ?>
          <?php foreach ($kategoriler as $k): ?><optgroup label="<?= e($k['ad']) ?>">
            <?php foreach ($hizmetByKat[$k['id']] ?? [] as $h): ?><option value="<?= e($h['slug']) ?>"><?= e(tr_lower($h['ad'])) ?></option><?php endforeach; ?>
          </optgroup><?php endforeach; ?>
        </select>
        lazım,
        <select name="i" id="ilce" aria-label="İlçe">
          <?php foreach ($ilceler as $i): ?><option value="<?= e($i['slug']) ?>"><?= e($i['ad']) ?></option><?php endforeach; ?>
        </select><span id="ek">'da</span>.
      </div>
      <div class="row">
        <div class="quick">
          <?php foreach (['kombi-bakimi', 'su-kacagi-tespiti', 'boya-badana'] as $s): if (isset($hizmetBySlug[$s])): ?>
            <a href="<?= url('ara.php?h=' . $s) ?>"><?= e($hizmetBySlug[$s]['ad']) ?></a>
          <?php endif; endforeach; ?>
        </div>
        <button class="btn btn-dark">Ustaları göster →</button>
      </div>
    </form>
  </div>
  <div class="stage">
    <div class="blob"></div>
    <?php if ($oneCikan): $u = $oneCikan[0]; ?>
    <a class="float f1" href="<?= url('usta.php?u=' . $u['slug']) ?>">
      <div class="top"><?= $u['foto'] ? '<img class="av" src="' . e(upload_url($u['foto'])) . '" alt="">' : '<div class="av">' . e(initials($u['ad_soyad'])) . '</div>' ?><div><b><?= e($u['ad_soyad']) ?></b><small><?= e($u['meslek']) ?> · <?= e(explode(', ', $u['ilce_adlari'] ?? 'Samsun')[0]) ?></small></div></div>
      <div class="tags"><?= $u['belgeli'] ? '<span class="tag">✔ Belgeli</span>' : '' ?><?= $u['yorum_sayisi'] ? '<span class="tag y">★ ' . puan_fmt($u['puan']) . '</span>' : '' ?><?= $u['kesif_ucretsiz'] ? '<span class="tag">Keşif ücretsiz</span>' : '' ?></div>
    </a>
    <?php else: ?>
    <div class="float f1"><div class="top"><div class="av">🔧</div><div><b>Usta profili</b><small>Puan, yorum, iletişim</small></div></div><div class="tags"><span class="tag">✔ Onaylı ustalar</span></div></div>
    <?php endif; ?>
    <div class="float f2"><div class="dot">✓</div><div><b>Aracı yok</b><small>Ustayla doğrudan konuş, komisyon ödeme</small></div></div>
    <div class="float f3"><?php if ($toplamYorum >= 10): ?><b><?= sayi($toplamYorum) ?></b><small>Samsunlu komşunun<br>onaylı yorumu</small><?php else: ?><b>17</b><small>ilçenin hepsinde<br>yerel ustalar</small><?php endif; ?></div>
  </div>
</div>
<div class="wave"><svg viewBox="0 0 1440 80" preserveAspectRatio="none" width="100%" height="70"><path d="M0 40 C240 80 480 0 720 30 C960 60 1200 10 1440 40 L1440 80 L0 80 Z" fill="#06291a"/></svg></div>
</div>

<div class="sos"><div class="wrap">
  <div><h2>Acil mi? <span>7/24</span></h2><p>Gece yarısı da olsa nöbetçi usta bul.</p></div>
  <div class="list">
    <?php foreach ([['🔑', 'cilingir', 'Çilingir'], ['💧', 'su-kacagi-tespiti', 'Su Kaçağı'], ['⚡', 'elektrikci', 'Elektrik Arızası'], ['🔥', 'kombi-tamiri', 'Kombi Arızası'], ['🛻', 'oto-cekici', 'Oto Çekici']] as [$ic, $s, $ad]): ?>
      <a href="<?= url('ara.php?h=' . $s . '&acil=1') ?>"><?= $ic ?> <?= e($ad) ?></a>
    <?php endforeach; ?>
  </div>
</div></div>

<section id="hizmetler"><div class="wrap">
  <div class="shead"><div><span class="num">01 — HİZMETLER</span><h2>Ne lazımsa, kategorisini seç.</h2></div><a class="link" href="<?= url('ara.php') ?>">Tüm ustalar →</a></div>
  <div class="explorer">
    <div class="tabs" id="tabs">
      <?php foreach ($kategoriler as $n => $k): ?>
        <button type="button" class="<?= $n ? '' : 'on' ?>" data-t="<?= $n ?>"><span class="e"><?= e($k['ikon']) ?></span><?= e($k['ad']) ?><small><?= $katSay[$k['id']] ?? 0 ?></small></button>
      <?php endforeach; ?>
    </div>
    <div>
      <?php foreach ($kategoriler as $n => $k): ?>
      <div class="panel<?= $n ? '' : ' on' ?>">
        <h3><?= e($k['ad']) ?></h3><p><?= e($k['aciklama']) ?></p>
        <div class="subs">
          <?php foreach ($hizmetByKat[$k['id']] ?? [] as $h): $c = $hizmetSay[$h['id']] ?? 0; ?>
            <a href="<?= url('ara.php?h=' . $h['slug']) ?>"><?= e($h['ad']) ?><small><?= $c ? $c . ' usta' : '' ?></small></a>
          <?php endforeach; ?>
        </div>
        <div class="foot"><span>Aradığını bulamadın mı? Kategorideki tüm ustalara göz at.</span><a class="btn btn-dark" href="<?= url('ara.php?k=' . $k['slug']) ?>"><?= e($k['ad']) ?> ustaları →</a></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div></section>

<section style="padding-top:0"><div class="wrap">
  <div class="shead"><div><span class="num">02 — <?= $trendGercek ? 'BU HAFTA' : 'POPÜLER' ?></span><h2><?= $trendGercek ? 'Samsun bu hafta ne arıyor?' : 'Samsun\'da en çok ustası olan hizmetler' ?></h2><p><?= $trendGercek ? 'Son 7 günde en çok aranan hizmetler' : 'Hizmet seç, ustaları gör' ?></p></div></div>
  <div class="trend-wrap">
    <?php foreach ($trend as $n => $t):
        $ch = '';
        if ($trendGercek) $ch = $t['onceki'] > 0 ? (($d = round(($t['bu'] - $t['onceki']) / $t['onceki'] * 100)) >= 0 ? '+%' . $d : '-%' . abs($d)) : 'yeni';
    ?>
      <a class="rank" href="<?= url('ara.php?h=' . $t['slug']) ?>"><span class="n"><?= str_pad((string)($n + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <div><b><?= e($t['ad']) ?></b><small class="muted"><?= e($t['kat']) ?></small><div class="meter"><i style="width:<?= max(4, round($t['bu'] / $trendMax * 100)) ?>%"></i></div></div>
        <div class="ch"><?= $trendGercek ? e($ch) . '<small>bu hafta</small>' : ((int)$t['bu'] ? (int)$t['bu'] . '<small>usta</small>' : '→') ?></div></a>
    <?php endforeach; ?>
  </div>
</div></section>

<section style="padding-top:0"><div class="wrap">
  <div class="season">
    <div class="l"><div class="big"><?= $mevsim[0] ?></div><h2><?= e($mevsim[1]) ?></h2><p><?= e($mevsim[2]) ?></p></div>
    <div class="r">
      <?php foreach ($mevsim[3] as [$ic, $s]): if (!isset($hizmetBySlug[$s])) continue; $h = $hizmetBySlug[$s]; $c = $hizmetSay[$h['id']] ?? 0; ?>
        <a class="sc" href="<?= url('ara.php?h=' . $s) ?>"><div class="e"><?= $ic ?></div><b><?= e($h['ad']) ?></b><small><?= $c ? $c . ' usta hazır' : 'Ustaları gör' ?></small><div class="p">Ustaları gör →</div></a>
      <?php endforeach; ?>
    </div>
  </div>
</div></section>

<section id="ustalar" style="padding-top:0"><div class="wrap">
  <div class="shead"><div><span class="num">03 — USTALAR</span><h2>Komşularının tavsiye ettiği ustalar</h2></div><a class="link" href="<?= url('ara.php') ?>">Tüm ustalar →</a></div>
  <?php if ($oneCikan): ?>
    <div class="ustalar"><?php foreach ($oneCikan as $u) echo usta_karti($u); ?></div>
  <?php else: ?>
    <div class="empty"><div class="big">👷</div><h3>İlk ustalarımız çok yakında burada.</h3><p>Samsun'da usta mısın? Ücretsiz profilini oluştur, ilk listelenenlerden ol.</p><a class="btn btn-dark" href="<?= url('kayit.php') ?>">Usta olarak kaydol →</a></div>
  <?php endif; ?>
</div></section>

<section id="ilceler" style="padding-top:0"><div class="wrap">
  <div class="shead"><div><span class="num">04 — İLÇELER</span><h2>17 ilçenin hepsinde.</h2></div></div>
  <div class="bento">
    <?php foreach (array_slice($ilceSirali, 0, 10) as $n => $i): $cls = $n === 0 ? 'd xl' : ($n === 1 ? 'd lg' : 'd'); ?>
      <a class="<?= $cls ?>" href="<?= url('ara.php?i=' . $i['slug']) ?>"><?php if ($n === 0): ?><small><?= $i['n'] ? 'En çok usta' : 'Samsun' ?></small><?php endif; ?><b><?= e($i['ad']) ?></b><small><?= $i['n'] ? $i['n'] . ' usta' : 'Ustaları gör' ?></small></a>
    <?php endforeach; ?>
    <a class="d more" href="<?= url('ara.php') ?>">+<?= max(0, count($ilceler) - 10) ?> ilçe daha →</a>
  </div>
</div></section>

<section id="nasil" style="padding-top:0"><div class="wrap">
  <div class="shead"><div><span class="num">05 — NASIL ÇALIŞIR?</span><h2>Üç adım, bir telefon.</h2></div></div>
  <div class="steps">
    <div class="step"><div class="c">1</div><h3>Ne lazım, söyle</h3><p>Hizmeti ve ilçeni seç, sana yakın ustalar listelensin.</p></div>
    <div class="step"><div class="c">2</div><h3>Komşuna sor</h3><p>Samsunlu müşterilerin yorumlarını ve puanlarını oku.</p></div>
    <div class="step"><div class="c">3</div><h3>Ara, anlaş</h3><p>Aracı yok, komisyon yok. Ustayla doğrudan konuş.</p></div>
  </div>
</div></section>

<div class="wrap"><div class="join">
  <div><h2>Usta mısın? Müşteri seni bulsun.</h2><p>Samsun'da her gün insanlar usta arıyor. Profilini oluştur, telefonun çalsın.</p><a class="btn btn-dark" href="<?= url('kayit.php') ?>">Ücretsiz profil oluştur →</a></div>
  <ul><li>📱 5 dakikada profil</li><li>📊 Kaç kişinin baktığını gör</li><li>⭐ Yorumlarla güven kazan</li></ul>
</div></div>

<script>
document.getElementById('tabs').addEventListener('click', e => {
  const b = e.target.closest('button'); if (!b) return;
  document.querySelectorAll('.tabs button').forEach(x => x.classList.toggle('on', x === b));
  document.querySelectorAll('.explorer .panel').forEach((p, i) => p.classList.toggle('on', i == b.dataset.t));
});
const ilce = document.getElementById('ilce'), ek = document.getElementById('ek');
function sfx() {
  const ad = ilce.options[ilce.selectedIndex].text, w = ad.replace(/I/g, 'ı').replace(/İ/g, 'i').toLowerCase();
  const v = [...w].reverse().find(c => 'aeıioöuü'.includes(c)) || 'a';
  ek.textContent = "'" + ('fstkçşhp'.includes(w.slice(-1)) ? 't' : 'd') + ('eiöü'.includes(v) ? 'e' : 'a');
}
ilce.addEventListener('change', sfx); sfx();
</script>
<?php site_bitir();
