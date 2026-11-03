<?php
declare(strict_types=1);

const LOGO_SVG = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>';

function site_basla(string $baslik = '', string $aciklama = ''): void
{
    $k = kullanici();
    $tamBaslik = $baslik ? "$baslik – Samsun Ustam" : "Samsun Ustam – Samsun'un Ustaları Tek Yerde";
    $aciklama = $aciklama ?: "Samsun'da elektrikçi, tesisatçı, kombici, boyacı ve daha fazlası. 17 ilçede güvenilir ustaları bul, yorumları oku, hemen ara.";
    ?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tamBaslik) ?></title>
<meta name="description" content="<?= e($aciklama) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/site.css') ?>?v=3">
</head>
<body>
<header><div class="wrap">
  <a class="logo" href="<?= url() ?>"><div class="mark"><?= LOGO_SVG ?></div><span>Samsun<b>Ustam</b></span></a>
  <nav><a href="<?= url('#hizmetler') ?>">Hizmetler</a><a href="<?= url('ara.php') ?>">Ustalar</a><a href="<?= url('#ilceler') ?>">İlçeler</a><a href="<?= url('#nasil') ?>">Nasıl Çalışır?</a></nav>
  <div class="hbtns">
  <?php if ($k): ?>
    <a class="btn btn-dark" href="<?= url($k['rol'] === 'admin' ? 'yonetim/' : 'usta/') ?>">Panelim →</a>
  <?php else: ?>
    <a class="btn btn-line" href="<?= url('giris.php') ?>">Giriş</a><a class="btn btn-dark" href="<?= url('kayit.php') ?>">Usta mısın?</a>
  <?php endif; ?>
  </div>
</div></header>
<?php
    $f = flash_html();
    if ($f) echo '<div class="wrap" style="margin-top:16px">' . $f . '</div>';
}

function site_bitir(): void
{
    $bulut = [];
    try {
        $bulut = rows("SELECT h.ad AS hizmet, h.slug AS hslug, i.ad AS ilce, i.slug AS islug, COUNT(*) AS n
            FROM usta_hizmet uh JOIN usta_ilce ui ON ui.usta_id = uh.usta_id JOIN ustalar u ON u.id = uh.usta_id AND u.durum = 'aktif'
            JOIN hizmetler h ON h.id = uh.hizmet_id JOIN ilceler i ON i.id = ui.ilce_id
            GROUP BY h.id, h.ad, h.slug, i.id, i.ad, i.slug ORDER BY n DESC LIMIT 16");
    } catch (Throwable) {}
    $tel = ayar('site_telefon');
    $mail = ayar('site_eposta');
    ?>
<footer><div class="wrap">
  <div class="ftop">
    <div><div class="logo"><div class="mark"><?= LOGO_SVG ?></div><span>Samsun<b>Ustam</b></span></div>
      <p>Samsun'un yerel usta rehberi. Mahallendeki ustayı bul, komşularının yorumlarını oku.</p>
      <?php if ($tel || $mail): ?><p><?= $tel ? '📞 ' . e(tel_goster($tel)) . '<br>' : '' ?><?= $mail ? '✉️ ' . e($mail) : '' ?></p><?php endif; ?>
    </div>
    <div><h4>Samsun'da sık arananlar</h4><div class="cloud">
      <?php if ($bulut): foreach ($bulut as $b): ?>
        <a href="<?= url('ara.php?h=' . $b['hslug'] . '&i=' . $b['islug']) ?>"><?= e($b['ilce'] . ' ' . tr_lower($b['hizmet'])) ?></a>
      <?php endforeach; else: foreach (['elektrikci' => 'Elektrikçi', 'su-tesisatcisi' => 'Su Tesisatçısı', 'kombi-tamiri' => 'Kombi Tamiri', 'boya-badana' => 'Boya Badana', 'evden-eve-nakliyat' => 'Evden Eve Nakliyat', 'cilingir' => 'Çilingir', 'cam-balkon' => 'Cam Balkon', 'hali-yikama' => 'Halı Yıkama'] as $s => $a): ?>
        <a href="<?= url('ara.php?h=' . $s) ?>">Samsun <?= e(tr_lower($a)) ?></a>
      <?php endforeach; endif; ?>
    </div></div>
  </div>
  <div class="fbot"><span>© <?= date('Y') ?> Samsun Ustam</span><div><a href="<?= url('kayit.php') ?>">Usta Kaydı</a><a href="<?= url('gizlilik.php') ?>">Gizlilik & KVKK</a><a href="<?= url('giris.php') ?>">Giriş</a></div></div>
</div></footer>
</body>
</html>
<?php
}

/** Ana sayfa ve arama sonuçlarında kullanılan usta kartı. */
function usta_karti(array $u): string
{
    $ilceler = $u['ilce_adlari'] ? implode(', ', array_slice(explode(', ', $u['ilce_adlari']), 0, 2)) : 'Samsun';
    $info = '<span>📍 ' . e($ilceler) . '</span>';
    if ($u['tecrube_yil']) $info .= '<span>🕒 ' . (int)$u['tecrube_yil'] . ' yıl tecrübe</span>';
    if ($u['belgeli']) $info .= '<span>✔ Belgeli</span>';
    if ($u['kesif_ucretsiz']) $info .= '<span>🎁 Keşif ücretsiz</span>';
    if ($u['acil_hizmet']) $info .= '<span>🚨 7/24 acil</span>';
    $quote = '';
    if (!empty($u['son_yorum'])) {
        [$m, $ad, $mah] = array_pad(explode('||', $u['son_yorum']), 3, '');
        $m = mb_strlen($m) > 90 ? mb_substr($m, 0, 88) . '…' : $m;
        $quote = '<blockquote>"' . e($m) . '"<small>— ' . e($ad) . ($mah ? ', ' . e($mah) : '') . '</small></blockquote>';
    }
    $foto = $u['foto'] ? '<img class="av" src="' . e(upload_url($u['foto'])) . '" alt="">' : '<div class="av">' . e(initials($u['ad_soyad'])) . '</div>';
    $score = $u['yorum_sayisi'] > 0
        ? '<div class="score"><b>' . puan_fmt($u['puan']) . '</b><small>' . (int)$u['yorum_sayisi'] . ' yorum</small></div>'
        : '<div class="score"><b>Yeni</b><small>usta</small></div>';
    $badge = premium_mu($u) ? '<span class="prem">★ Premium</span>' : '';
    $profil = url('usta.php?u=' . $u['slug']);
    $wa = $u['whatsapp'] ? '<a class="btn btn-wa" href="' . url('git.php?u=' . $u['id'] . '&t=whatsapp') . '" rel="nofollow">WhatsApp</a>' : '<a class="btn btn-wa" href="' . $profil . '">Profil</a>';
    return '<div class="uc' . (premium_mu($u) ? ' is-prem' : '') . '">' . $badge .
        '<a class="top" href="' . $profil . '">' . $foto . '<div><h3>' . e($u['ad_soyad']) . '</h3><div class="job">' . e($u['meslek']) . '</div></div>' . $score . '</a>' .
        '<div class="info">' . $info . '</div>' . $quote .
        '<div class="acts"><a class="btn btn-dark" href="' . url('git.php?u=' . $u['id'] . '&t=arama') . '" rel="nofollow">📞 Ara</a>' . $wa . '</div></div>';
}
