<?php
declare(strict_types=1);

require_once __DIR__ . '/site.php'; // LOGO_SVG

function panel_basla(string $rol, string $aktif, string $baslik, string $alt = ''): void
{
    $k = kullanici();
    if ($rol === 'usta') {
        $usta = row('SELECT id, slug, ad_soyad, foto FROM ustalar WHERE kullanici_id = ?', [$k['id']]);
        $yeni = (int)val("SELECT COUNT(*) FROM talepler WHERE usta_id = ? AND durum = 'yeni'", [$usta['id']]);
        $menu = [
            ['Usta Paneli', [
                ['index.php', '🏠', 'Panelim', 0],
                ['profil.php', '👤', 'Profilim', 0],
                ['fotograflar.php', '🖼️', 'İş Fotoğraflarım', 0],
                ['talepler.php', '💬', 'İş Talepleri', $yeni],
                ['yorumlar.php', '⭐', 'Yorumlarım', 0],
            ]],
            ['Hesap', [
                ['paket.php', '💎', 'Üyelik Paketim', 0],
                ['ayarlar.php', '⚙️', 'Hesap Ayarları', 0],
            ]],
        ];
        $alt_link = ['../usta.php?u=' . $usta['slug'], '↗ Profilimi Görüntüle'];
        $tab = [['index.php', '🏠', 'Panel'], ['talepler.php', '💬', 'Talepler'], ['yorumlar.php', '⭐', 'Yorumlar'], ['profil.php', '👤', 'Profil'], ['ayarlar.php', '⚙️', 'Hesap']];
        $avatar = $usta['foto'] ? '<img class="av" src="' . e(upload_url($usta['foto'])) . '" alt="">' : '<div class="av">' . e(initials($usta['ad_soyad'])) . '</div>';
        $isim = $usta['ad_soyad'];
    } else {
        $bek = (int)val("SELECT COUNT(*) FROM ustalar WHERE durum = 'beklemede'");
        $yor = (int)val("SELECT COUNT(*) FROM yorumlar WHERE durum = 'beklemede'");
        $pak = (int)val("SELECT COUNT(*) FROM paket_talepleri WHERE durum = 'yeni'");
        $menu = [
            ['Yönetim', [
                ['index.php', '📊', 'Genel Bakış', 0],
                ['ustalar.php?durum=beklemede', '⏳', 'Onay Bekleyenler', $bek],
                ['ustalar.php', '👷', 'Ustalar', 0],
                ['yorumlar.php', '⭐', 'Yorumlar', $yor],
                ['talepler.php', '💬', 'İş Talepleri', 0],
            ]],
            ['İçerik', [
                ['kategoriler.php', '🗂️', 'Kategoriler & Hizmetler', 0],
                ['paketler.php', '💎', 'Üyelik Paketleri', $pak],
                ['ayarlar.php', '⚙️', 'Site Ayarları', 0],
            ]],
        ];
        $alt_link = ['../', '↗ Siteyi Görüntüle'];
        $tab = [['index.php', '📊', 'Özet'], ['ustalar.php?durum=beklemede', '⏳', 'Onay'], ['ustalar.php', '👷', 'Ustalar'], ['yorumlar.php', '⭐', 'Yorumlar'], ['ayarlar.php', '⚙️', 'Ayarlar']];
        $avatar = '<div class="av">' . e(initials($k['ad'])) . '</div>';
        $isim = $k['ad'];
    }
    $cikis = url('cikis.php?csrf=' . csrf_token());
    ?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($baslik) ?> – <?= $rol === 'usta' ? 'Usta Paneli' : 'Yönetim' ?> – Samsun Ustam</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/panel.css') ?>?v=3">
</head>
<body>
<div class="mobilebar"><a class="logo" href="index.php"><div class="mark"><?= LOGO_SVG ?></div><span>Samsun<b>Ustam</b><?= $rol === 'admin' ? ' · Yönetim' : '' ?></span></a><a href="<?= e($cikis) ?>" class="mlogout" title="Çıkış">🚪</a></div>
<div class="app">
  <aside class="side">
    <a class="logo" href="<?= url() ?>"><div class="mark"><?= LOGO_SVG ?></div><span>Samsun<b>Ustam</b></span></a>
    <?php foreach ($menu as [$grup, $items]): ?>
      <div class="tag"><?= e($grup) ?></div>
      <?php foreach ($items as [$href, $ic, $ad, $cnt]): ?>
        <a class="<?= $aktif === $href ? 'on' : '' ?>" href="<?= e($href) ?>"><?= $ic ?> <?= e($ad) ?><?= $cnt ? '<span class="cnt">' . $cnt . '</span>' : '' ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="bottom"><a href="<?= e($alt_link[0]) ?>" target="_blank"><?= e($alt_link[1]) ?></a><a href="<?= e($cikis) ?>">🚪 Çıkış Yap</a></div>
  </aside>
  <main>
    <div class="topbar">
      <div><h1><?= e($baslik) ?></h1><?php if ($alt): ?><p><?= $alt ?></p><?php endif; ?></div>
      <div class="tright"><div class="me"><?= $avatar ?><span><?= e($isim) ?></span></div></div>
    </div>
    <?= flash_html() ?>
<?php
    $GLOBALS['__tabbar'] = [$tab, $aktif];
}

function panel_bitir(): void
{
    [$tab, $aktif] = $GLOBALS['__tabbar'];
    ?>
  </main>
</div>
<nav class="tabbar"><?php foreach ($tab as [$href, $ic, $ad]): ?><a class="<?= $aktif === $href ? 'on' : '' ?>" href="<?= e($href) ?>"><span><?= $ic ?></span><?= e($ad) ?></a><?php endforeach; ?></nav>
</body>
</html>
<?php
}

/** Son N günün günlük sayılarını [tarih => adet] olarak döndürür. */
function gunluk(string $sql, array $params, int $gun = 7): array
{
    $out = [];
    for ($i = $gun - 1; $i >= 0; $i--) $out[date('Y-m-d', strtotime("-$i day"))] = 0;
    foreach (rows($sql, $params) as $r) if (isset($out[$r['tarih']])) $out[$r['tarih']] = (int)$r['n'];
    return $out;
}

function grafik(array $gunluk): string
{
    $gunler = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];
    $max = max(1, ...array_values($gunluk));
    $enYuksek = array_search(max($gunluk), $gunluk, true);
    $bars = $labels = '';
    foreach ($gunluk as $t => $n) {
        $h = $n ? max(6, round($n / $max * 100)) : 3;
        $bars .= '<div class="' . ($t === $enYuksek && $n ? 'hi' : '') . '" style="height:' . $h . '%" title="' . $n . '"><span>' . $n . '</span></div>';
        $labels .= '<span>' . $gunler[(int)date('w', strtotime($t))] . '</span>';
    }
    return '<div class="chart">' . $bars . '</div><div class="xlab">' . $labels . '</div>';
}

function degisim(int $simdi, int $once): string
{
    if ($once === 0) return $simdi ? '<span class="up">yeni</span>' : '<span class="muted">—</span>';
    $d = (int)round(($simdi - $once) / $once * 100);
    return $d >= 0 ? '<span class="up">▲ %' . $d . '</span>' : '<span class="down">▼ %' . abs($d) . '</span>';
}

function durum_pill(string $d): string
{
    $map = [
        'beklemede' => ['p-amber', 'Onay bekliyor'], 'aktif' => ['p-green', 'Aktif'], 'askida' => ['p-red', 'Askıda'], 'reddedildi' => ['p-red', 'Reddedildi'],
        'yayinda' => ['p-green', 'Yayında'], 'gizli' => ['p-red', 'Gizli'],
        'yeni' => ['p-amber', 'Yeni'], 'goruldu' => ['p-blue', 'Görüldü'], 'yanitlandi' => ['p-green', 'Yanıtlandı'], 'gorusuldu' => ['p-green', 'Görüşüldü'],
    ];
    [$c, $t] = $map[$d] ?? ['p-blue', $d];
    return '<span class="pill ' . $c . '">' . $t . '</span>';
}
