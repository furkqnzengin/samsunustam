<?php
declare(strict_types=1);

require_once __DIR__ . '/site.php';

function panel_basla(string $rol, string $aktif, string $baslik, string $alt = ''): void
{
    $k = kullanici();
    if ($rol === 'usta') {
        $usta = row('SELECT id, slug, ad_soyad, foto FROM ustalar WHERE kullanici_id = ?', [$k['id']]);
        $yeni = (int)val("SELECT COUNT(*) FROM talepler WHERE usta_id = ? AND durum = 'yeni'", [$usta['id']]);
        $acik = $basvuru = 0;
        try { $acik = (int)val("SELECT COUNT(*) FROM servisler WHERE usta_id = ? AND durum = 'hazir'", [$usta['id']]); } catch (Throwable) {}
        try { $basvuru = (int)val("SELECT COUNT(*) FROM ilan_basvurulari b JOIN ilanlar l ON l.id = b.ilan_id WHERE l.usta_id = ? AND b.durum = 'yeni'", [$usta['id']]); } catch (Throwable) {}
        $menu = [
            ['Atölyem', [
                ['servis.php', '🛠️', 'Servis Takip', $acik],
                ['servis-kayit.php', '➕', 'Yeni Kayıt', 0],
                ['kasa.php', '💰', 'Gelir - Gider', 0],
                ['ilanlar.php', '📢', 'Eleman Arıyorum', $basvuru],
            ]],
            ['Usta Paneli', [
                ['index.php', '🏠', 'Panelim', 0],
                ['profil.php', '👤', 'Profilim', 0],
                ['fotograflar.php', '🖼️', 'İş Fotoğraflarım', 0],
                ['talepler.php', '💬', 'İş Talepleri', $yeni],
                ['yorumlar.php', '⭐', 'Yorumlarım', 0],
            ]],
            ['Hesap', [
                ['ayarlar.php', '⚙️', 'Hesap Ayarları', 0],
            ]],
        ];
        $alt_link = ['../usta.php?u=' . $usta['slug'], '↗ Profilimi Görüntüle'];
        $tab = [['index.php', '🏠', 'Panel'], ['servis.php', '🛠️', 'Servis'], ['servis-kayit.php', '➕', 'Yeni'], ['kasa.php', '💰', 'Kasa']];
        $avatar = $usta['foto'] ? '<img class="av" src="' . e(upload_url($usta['foto'])) . '" alt="">' : '<div class="av">' . e(initials($usta['ad_soyad'])) . '</div>';
        $isim = $usta['ad_soyad'];
    } else {
        $bek = (int)val("SELECT COUNT(*) FROM ustalar WHERE durum = 'beklemede'");
        $yor = (int)val("SELECT COUNT(*) FROM yorumlar WHERE durum = 'beklemede'");
        $ara = 0;
        try { $ara = (int)val("SELECT COUNT(*) FROM is_arayanlar WHERE durum = 'beklemede'"); } catch (Throwable) {}
        $menu = [
            ['Yönetim', [
                ['index.php', '📊', 'Genel Bakış', 0],
                ['ustalar.php?durum=beklemede', '⏳', 'Onay Bekleyenler', $bek],
                ['ustalar.php', '👷', 'Ustalar', 0],
                ['yorumlar.php', '⭐', 'Yorumlar', $yor],
                ['talepler.php', '💬', 'İş Talepleri', 0],
                ['ilanlar.php', '📢', 'İş İlanları', $ara],
            ]],
            ['İçerik', [
                ['kategoriler.php', '🗂️', 'Kategoriler & Hizmetler', 0],
                ['paketler.php', '💎', 'Premium & Paketler', 0],
                ['ayarlar.php', '⚙️', 'Site Ayarları', 0],
            ]],
        ];
        $alt_link = ['../', '↗ Siteyi Görüntüle'];
        $tab = [['index.php', '📊', 'Özet'], ['ustalar.php?durum=beklemede', '⏳', 'Onay'], ['ustalar.php', '👷', 'Ustalar'], ['yorumlar.php', '⭐', 'Yorumlar']];
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
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="<?= url('assets/marka/favicon.svg') ?>" type="image/svg+xml">
<link rel="icon" href="<?= url('assets/marka/favicon-32.png') ?>" sizes="32x32">
<link rel="apple-touch-icon" href="<?= url('assets/marka/apple-touch-icon.png') ?>">
<meta name="theme-color" content="#852FFF">
<link rel="stylesheet" href="<?= asset('assets/panel.css') ?>">
</head>
<body>
<div class="mobilebar"><a class="logo" href="index.php"><img class="brandlogo" src="<?= url('assets/marka/logo-yatay-beyaz.svg') ?>" alt="Samsun Ustam"><?= $rol === 'admin' ? '<span class="rolbadge">Yönetim</span>' : '' ?></a><a href="<?= e($cikis) ?>" class="mlogout" title="Çıkış">🚪</a></div>
<div class="app">
  <aside class="side">
    <a class="logo" href="<?= url() ?>"><img class="brandlogo" src="<?= url('assets/marka/logo-yatay-beyaz.svg') ?>" alt="Samsun Ustam"></a>
    <span class="rolbadge side-rol"><?= $rol === 'admin' ? 'Yönetim Paneli' : 'Usta Paneli' ?></span>
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
    $GLOBALS['__tabbar'] = [$tab, $aktif, $menu, $alt_link, $cikis];
}

function panel_bitir(): void
{
    [$tab, $aktif, $menu, $alt_link, $cikis] = $GLOBALS['__tabbar'];
    ?>
  </main>
</div>
<nav class="tabbar"><?php foreach ($tab as [$href, $ic, $ad]): ?><a class="<?= $aktif === $href ? 'on' : '' ?>" href="<?= e($href) ?>"><span><?= $ic ?></span><?= e($ad) ?></a><?php endforeach; ?>
  <a href="#" onclick="document.body.classList.toggle('msheet-acik');return false"><span>☰</span>Menü</a></nav>
<div class="msheet" style="display:none" onclick="if(event.target===this)document.body.classList.remove('msheet-acik')"><div class="msheet-in">
  <?php foreach ($menu as [$grup, $items]): ?><div class="tag"><?= e($grup) ?></div><?php foreach ($items as [$href, $ic, $ad, $cnt]): ?><a class="<?= $aktif === $href ? 'on' : '' ?>" href="<?= e($href) ?>"><?= $ic ?> <?= e($ad) ?><?= $cnt ? '<span class="cnt">' . $cnt . '</span>' : '' ?></a><?php endforeach; ?><?php endforeach; ?>
  <div class="tag">Diğer</div><a href="<?= e($alt_link[0]) ?>" target="_blank"><?= e($alt_link[1]) ?></a><a href="<?= e($cikis) ?>">🚪 Çıkış Yap</a>
</div></div>
<script>
// Panel açık kaldıkça oturumu canlı tut (5 dakikada bir)
setInterval(() => { fetch('<?= url('canli.php') ?>', { credentials: 'same-origin', cache: 'no-store' }).catch(() => {}); }, 5 * 60 * 1000);
</script>
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
