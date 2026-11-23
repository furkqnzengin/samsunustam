<?php
// TV modu: atölyedeki ekranda gösterilecek servis panosu (30 saniyede bir yenilenir)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$durumlar = servis_durumlari();
$PANO = ['kabul', 'islemde', 'parca', 'hazir'];
$liste = rows(servis_liste_sql("s.usta_id = $uid AND s.durum IN ('kabul','islemde','parca','hazir')") . ' ORDER BY giris_tarihi');
$renk = ['kabul' => '#A066FF', 'islemde' => '#FFDA00', 'parca' => '#FF8431', 'hazir' => '#3DDC84'];
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="refresh" content="30">
<title>Servis Panosu – <?= e($u['firma_unvan'] ?: $u['ad_soyad']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800&family=Inter:wght@500;600&display=swap" rel="stylesheet">
<link rel="icon" href="<?= url('assets/marka/favicon.svg') ?>" type="image/svg+xml">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#22074F url(<?= url('assets/marka/desen-mor.svg') ?>) 0 0/140px;color:#fff;font-family:Inter,sans-serif;min-height:100vh;padding:28px}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
header img{height:56px}
header h1{font-family:Outfit;font-size:34px}
#saat{font-family:Outfit;font-weight:800;font-size:44px}
.pano{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.sutun{background:rgba(255,255,255,.07);border-radius:22px;padding:14px;min-height:70vh}
.sutun h2{font-family:Outfit;font-size:24px;display:flex;justify-content:space-between;align-items:center;padding:6px 8px 14px;border-bottom:4px solid var(--r);margin-bottom:14px}
.sutun h2 b{background:var(--r);color:#22074F;border-radius:999px;padding:0 14px}
.kart{background:#fff;color:#1F0C42;border-radius:18px;padding:16px;margin-bottom:12px;border-left:10px solid var(--r)}
.plaka{display:inline-flex;border:3px solid #1F0C42;border-radius:8px;font-family:Outfit;font-weight:800;font-size:28px;letter-spacing:.05em;padding-right:10px;overflow:hidden}
.plaka i{font-style:normal;background:#1E40AF;color:#fff;font-size:12px;display:flex;align-items:flex-end;padding:4px 5px;margin-right:10px}
.kart b{display:block;font-family:Outfit;font-size:22px;margin-top:8px}
.kart small{display:block;color:#6E5A90;font-size:16px;margin-top:4px}
.bos{opacity:.5;text-align:center;padding:30px 0;font-size:18px}
</style></head><body>
<header><img src="<?= url('assets/marka/logo-yatay-beyaz.svg') ?>" alt="Samsun Ustam"><h1><?= e($u['firma_unvan'] ?: $u['ad_soyad']) ?> · Servis Panosu</h1><div id="saat"><?= date('H:i') ?></div></header>
<div class="pano">
<?php foreach ($PANO as $d): $kol = array_filter($liste, fn($r) => $r['durum'] === $d); ?>
  <div class="sutun" style="--r:<?= $renk[$d] ?>"><h2><?= e($durumlar[$d][0]) ?> <b><?= count($kol) ?></b></h2>
    <?php foreach ($kol as $r): ?><div class="kart"><?php $rz = satir_rozet($r); echo str_contains($rz, "plaka") ? $rz : ""; ?><b><?= e($r['kart_baslik'] ?: $r['baslik']) ?></b><small><?= e($r['musteri_ad']) ?><?= $r['teknisyen'] ? ' · 🔧 ' . e($r['teknisyen']) : '' ?></small><small>⏱ <?= zaman_once($r['giris_tarihi']) ?></small></div><?php endforeach; ?>
    <?php if (!$kol): ?><div class="bos">—</div><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<script>setInterval(()=>{const d=new Date();document.getElementById('saat').textContent=d.toLocaleTimeString('tr-TR',{hour:'2-digit',minute:'2-digit'})},10000);</script>
</body></html>
