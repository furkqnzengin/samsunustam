<?php
declare(strict_types=1);

/**
 * Müşteriye verilecek servis raporunun tam HTML sayfası.
 * Hem ustanın panelinden (yazdır) hem de müşteriye gönderilen linkten (rapor.php) kullanılır.
 */
function rapor_sayfasi(array $s, bool $ustaGoruyor): void
{
    $u = row('SELECT * FROM ustalar WHERE id = ?', [$s['usta_id']]);
    $tanim = sektorler()[$s['sektor']] ?? sektorler()['yerinde'];
    $ozel = json_decode((string)$s['ozel'], true) ?: [];
    $kontrol = json_decode((string)$s['kontrol'], true) ?: [];
    $kalemler = rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$s['id']]);
    $odemeler = rows('SELECT * FROM servis_odemeleri WHERE servis_id = ? ORDER BY tarih, id', [$s['id']]);
    $fotolar = rows('SELECT * FROM servis_fotolari WHERE servis_id = ? ORDER BY etiket, id', [$s['id']]);
    $h = servis_hesap($s, $kalemler, $odemeler);
    $adet = fn($n) => rtrim(rtrim(number_format((float)$n, 2, ',', '.'), '0'), ',');
    $etiketler = ['once' => 'İşlem öncesi', 'sonra' => 'İşlem sonrası', 'diger' => 'Fotoğraf'];
    ?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Servis Raporu #<?= (int)$s['no'] ?> – <?= e($s['baslik']) ?></title>
<link rel="icon" href="<?= url('assets/marka/favicon.svg') ?>" type="image/svg+xml">
<style>
:root{--g:#4609A8;--g2:#852FFF;--l:#E9DFF8;--m:#6E5A90;--bg:#F8F4FF}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1F0C42;background:#F3EEFC;font-size:13.5px;line-height:1.5}
.bar{position:sticky;top:0;background:#22074F;color:#fff;padding:10px 16px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;z-index:2}
.bar button,.bar a{background:#fff;color:#22074F;border:0;border-radius:999px;padding:9px 18px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}
.bar a.alt{background:#852FFF;color:#fff}
.page{max-width:820px;margin:20px auto;background:#fff;padding:36px 40px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.head{display:flex;justify-content:space-between;gap:20px;border-bottom:3px solid var(--g);padding-bottom:16px;margin-bottom:18px}
.head h1{font-size:22px;color:var(--g)}
.head .usta b{font-size:16px;display:block}
.head small,.muted{color:var(--m)}
.doc{text-align:right}
.doc .t{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--g2);font-weight:800}
.doc .n{font-size:24px;font-weight:800}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.box{border:1px solid var(--l);border-radius:12px;padding:12px 14px}
.box h3,.sec h3{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--g2);margin-bottom:8px}
dl{display:grid;grid-template-columns:auto 1fr;gap:3px 12px}
dt{color:var(--m)}
dd{font-weight:600}
.sec{margin-bottom:16px}
.txt{white-space:pre-line;background:var(--bg);border-radius:10px;padding:10px 12px}
table{width:100%;border-collapse:collapse}
th{background:var(--g);color:#fff;text-align:left;padding:8px 10px;font-size:12px}
td{padding:8px 10px;border-bottom:1px solid var(--l)}
td.r,th.r{text-align:right;white-space:nowrap}
.tot{margin-left:auto;width:300px;margin-top:10px}
.tot div{display:flex;justify-content:space-between;padding:4px 10px}
.tot .g{background:var(--g);color:#fff;border-radius:8px;font-size:16px;font-weight:800;padding:8px 10px;margin-top:4px}
.tot .k{font-weight:800;color:#b33}
.tot .ok{font-weight:800;color:var(--g2)}
.checks{display:flex;flex-wrap:wrap;gap:6px}
.checks span{background:var(--bg);border:1px solid var(--l);border-radius:999px;padding:3px 10px;font-size:12px}
.photos{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.photos figure img{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;display:block;border:1px solid var(--l)}
.photos figcaption{font-size:11px;color:var(--m);margin-top:3px}
.sign{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:34px}
.sign div{border-top:1px solid #999;padding-top:6px;text-align:center;color:var(--m);font-size:12px}
.note{font-size:12px;color:var(--m);border-top:1px dashed var(--l);padding-top:10px;margin-top:18px;white-space:pre-line}
.foot{text-align:center;font-size:11px;color:var(--m);margin-top:18px}
@media (max-width:640px){.page{padding:20px 16px;margin:0;border-radius:0}.grid{grid-template-columns:1fr}.head{flex-direction:column}.doc{text-align:left}.photos{grid-template-columns:repeat(2,1fr)}.tot{width:100%}table{font-size:12px}}
@media print{body{background:#fff}.bar{display:none}.page{box-shadow:none;margin:0;max-width:none;padding:0;border-radius:0}.photos figure{break-inside:avoid}@page{margin:14mm}}
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">🖨️ Yazdır / PDF kaydet</button><?php if ($ustaGoruyor): ?><a class="alt" href="servis-kayit.php?id=<?= (int)$s['id'] ?>">← Kayda dön</a><?php endif; ?></div>
<div class="page">
  <div class="head">
    <div class="usta">
      <?php if (!empty($u['firma_logo'])): ?><img src="<?= e(upload_url($u['firma_logo'])) ?>" alt="" style="max-height:56px;max-width:200px;margin-bottom:6px;display:block"><?php endif; ?>
      <h1><?= e(($u['firma_unvan'] ?? '') ?: $u['ad_soyad']) ?></h1>
      <b><?= e($u['meslek']) ?></b>
      <small>📞 <?= e(tel_goster($u['telefon'])) ?><?= $u['adres'] ? '<br>📍 ' . e($u['adres']) : '' ?></small>
    </div>
    <div class="doc"><div class="t"><?= e($tanim['kayit'] === 'İş emri' ? 'İş emri / servis raporu' : 'Servis raporu') ?></div><div class="n">#<?= (int)$s['no'] ?></div>
      <small>Giriş: <?= date('d.m.Y H:i', strtotime($s['giris_tarihi'])) ?><?= $s['teslim_tarihi'] ? '<br>Teslim: ' . date('d.m.Y H:i', strtotime($s['teslim_tarihi'])) : '' ?></small><br>
      <small>Durum: <b><?= e(servis_durumlari()[$s['durum']][0]) ?></b></small></div>
  </div>

  <div class="grid">
    <div class="box"><h3>Müşteri</h3><dl>
      <dt>Ad soyad</dt><dd><?= e($s['musteri_ad']) ?></dd>
      <?php if ($s['musteri_firma']): ?><dt>Firma</dt><dd><?= e($s['musteri_firma']) ?></dd><?php endif; ?>
      <?php if ($s['musteri_tel']): ?><dt>Telefon</dt><dd><?= e($ustaGoruyor ? tel_goster($s['musteri_tel']) : tel_maskele($s['musteri_tel'])) ?></dd><?php endif; ?>
    </dl></div>
    <div class="box"><h3><?= e($tanim['kayit']) ?> bilgileri</h3><dl>
      <?php foreach ($tanim['alanlar'] as $a): $v = trim((string)($ozel[$a[0]] ?? '')); if ($v === '' || $a[2] === 'textarea') continue; ?>
        <dt><?= e($a[1]) ?></dt><dd><?= e($a[0] === 'km' && is_numeric($v) ? number_format((float)$v, 0, ',', '.') : ($a[2] === 'date' ? date('d.m.Y', strtotime($v)) : $v)) ?></dd>
      <?php endforeach; ?>
    </dl></div>
  </div>

  <?php foreach ($tanim['alanlar'] as $a): $v = trim((string)($ozel[$a[0]] ?? '')); if ($a[2] !== 'textarea' || $v === '') continue; ?>
    <div class="sec"><h3><?= e($a[1]) ?></h3><div class="txt"><?= e($v) ?></div></div>
  <?php endforeach; ?>
  <?php if ($s['sikayet']): ?><div class="sec"><h3>Müşteri şikâyeti / talebi</h3><div class="txt"><?= e($s['sikayet']) ?></div></div><?php endif; ?>
  <?php if ($s['yapilan']): ?><div class="sec"><h3>Yapılan işlemler / tespitler</h3><div class="txt"><?= e($s['yapilan']) ?></div></div><?php endif; ?>
  <?php if ($kontrol): ?><div class="sec"><h3>Kontrol edilenler</h3><div class="checks"><?php foreach ($kontrol as $c): ?><span>✓ <?= e($c) ?></span><?php endforeach; ?></div></div><?php endif; ?>

  <?php if ($kalemler): ?>
  <div class="sec"><h3>İşçilik ve parçalar</h3>
    <table><tr><th>Açıklama</th><th>Tür</th><th class="r">Miktar</th><th class="r">Birim fiyat</th><th class="r">KDV</th><th class="r">Tutar</th></tr>
      <?php foreach ($kalemler as $k): ?><tr><td><?= e($k['aciklama']) ?></td><td><?= e(KALEM_TURLERI[$k['tur']] ?? '') ?></td><td class="r"><?= $adet($k['adet']) ?> <?= e($k['birim'] ?? '') ?></td><td class="r"><?= para($k['birim_fiyat']) ?></td><td class="r">%<?= (int)($k['kdv'] ?? $s['kdv']) ?></td><td class="r"><?= para((float)$k['adet'] * (float)$k['birim_fiyat']) ?></td></tr><?php endforeach; ?>
    </table>
    <div class="tot">
      <div><span>Ara toplam (KDV hariç)</span><span><?= para($h['ara']) ?></span></div>
      <?php if ($h['iskonto'] > 0): ?><div><span>İndirim</span><span>-<?= para($h['iskonto']) ?></span></div><?php endif; ?>
      <?php if ($h['kdv'] > 0.009): ?><div><span>KDV</span><span><?= para($h['kdv']) ?></span></div><?php endif; ?>
      <div class="g"><span>Genel toplam</span><span><?= para($h['toplam']) ?></span></div>
      <?php if ($odemeler): ?><div><span>Ödenen</span><span><?= para($h['odenen']) ?></span></div>
        <div class="<?= $h['kalan'] > 0.009 ? 'k' : 'ok' ?>"><span><?= $h['kalan'] > 0.009 ? 'Kalan' : 'Durum' ?></span><span><?= $h['kalan'] > 0.009 ? para($h['kalan']) : 'Ödendi ✓' ?></span></div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($odemeler): ?><div class="sec"><h3>Ödemeler</h3><table><tr><th>Tarih</th><th>Şekil</th><th>Açıklama</th><th class="r">Tutar</th></tr>
    <?php foreach ($odemeler as $o): ?><tr><td><?= date('d.m.Y', strtotime($o['tarih'])) ?></td><td><?= ODEME_YONTEMLERI[$o['yontem']] ?? '' ?></td><td><?= e($o['aciklama']) ?></td><td class="r"><?= para($o['tutar']) ?></td></tr><?php endforeach; ?>
  </table></div><?php endif; ?>

  <?php if ($s['garanti'] || $s['sonraki_bakim'] || $s['tahmini_teslim']): ?>
  <div class="grid"><div class="box"><dl>
    <?php if ($s['garanti']): ?><dt>Garanti</dt><dd><?= e($s['garanti']) ?></dd><?php endif; ?>
    <?php if ($s['sonraki_bakim']): ?><dt>Sonraki bakım</dt><dd><?= e($s['sonraki_bakim']) ?></dd><?php endif; ?>
    <?php if ($s['tahmini_teslim'] && !$s['teslim_tarihi']): ?><dt>Tahmini teslim</dt><dd><?= date('d.m.Y', strtotime($s['tahmini_teslim'])) ?></dd><?php endif; ?>
  </dl></div></div>
  <?php endif; ?>

  <?php if ($fotolar): ?><div class="sec"><h3>Fotoğraflar</h3><div class="photos">
    <?php foreach ($fotolar as $f): ?><figure><img src="<?= e(upload_url($f['dosya'])) ?>" alt=""><figcaption><?= $etiketler[$f['etiket']] ?></figcaption></figure><?php endforeach; ?>
  </div></div><?php endif; ?>

  <div class="sign"><div>Teslim eden<br><b><?= e($u['ad_soyad']) ?></b></div><div>Teslim alan<br><b><?= e($s['musteri_ad']) ?></b></div></div>
  <?php if ($u['rapor_notu']): ?><div class="note"><?= e($u['rapor_notu']) ?></div><?php endif; ?>
  <div class="foot"><img src="<?= url('assets/marka/logo-yatay.svg') ?>" alt="Samsun Ustam" height="26" style="vertical-align:middle;margin-right:8px">servis takip sistemiyle oluşturuldu · <?= date('d.m.Y H:i') ?></div>
</div>
</body>
</html>
<?php
}
