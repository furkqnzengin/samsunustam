<?php
// Serviste bugün: açık kayıtlar (liste / pano), durum sayaçları, Excel
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$tanim = sektorler()[usta_sektor($u)];
$durumlar = servis_durumlari();
$PANO = ['kabul', 'islemde', 'parca', 'hazir', 'teslim'];

$durum = get('durum');
$gorunum = get('g') === 'pano' ? 'pano' : 'liste';
$ara = mb_substr(get('q'), 0, 60);
$etiket = mb_substr(get('etiket'), 0, 60);

// "Serviste bugün" = açık kayıtlar + bugün teslim edilenler
$where = ["s.usta_id = $uid", "(s.durum IN ('kabul','islemde','parca','hazir') OR (s.durum = 'teslim' AND DATE(s.teslim_tarihi) = CURDATE()))"];
$params = [];
if (isset($durumlar[$durum]) && $durum !== 'iptal') { $where[] = 's.durum = ?'; $params[] = $durum; }
if ($etiket !== '') { $where[] = 's.etiket = ?'; $params[] = $etiket; }
if ($ara !== '') {
    $where[] = '(s.baslik LIKE ? OR s.musteri_ad LIKE ? OR s.anahtar LIKE ? OR s.no = ?)';
    array_push($params, "%$ara%", "%$ara%", '%' . anahtar_norm($ara) . '%', (int)$ara);
}
$liste = rows(servis_liste_sql(implode(' AND ', $where)) . ' ORDER BY giris_tarihi DESC LIMIT 500', $params);

if (get('csv') === '1') {
    csv_gonder('serviste-bugun-' . date('Y-m-d') . '.csv', ['Kayıt no', 'Giriş', 'Müşteri', $tanim['kayit'], 'Toplam', 'Ödenen', 'Teknisyen', 'Durum'],
        array_map(fn($r) => [$r['no'], date('d.m.Y H:i', strtotime($r['giris_tarihi'])), $r['musteri_ad'], $r['baslik'], number_format((float)$r['toplam'], 2, ',', ''),
            number_format((float)$r['odenen'], 2, ',', ''), $r['teknisyen'], $durumlar[$r['durum']][0]], $liste));
}

$say = [];
foreach (rows("SELECT durum, COUNT(*) n FROM servisler WHERE usta_id = ? AND (durum IN ('kabul','islemde','parca','hazir') OR (durum = 'teslim' AND DATE(teslim_tarihi) = CURDATE())) GROUP BY durum", [$uid]) as $r) $say[$r['durum']] = (int)$r['n'];
$etiketler = array_column(rows('SELECT DISTINCT etiket FROM servisler WHERE usta_id = ? AND etiket IS NOT NULL ORDER BY etiket', [$uid]), 'etiket');
$qs = fn(array $ek) => '?' . http_build_query(array_filter(array_merge(['durum' => $durum, 'g' => $gorunum, 'q' => $ara, 'etiket' => $etiket], $ek), 'strlen'));

panel_basla('usta', 'serviste.php' . ($durum ? '?durum=' . $durum : ''), 'Serviste Bugün', 'Atölyedeki açık kayıtlar ve bugün teslim edilenler');
?>
<div class="durumlar">
  <a class="<?= $durum === '' ? 'on' : '' ?>" href="<?= e($qs(['durum' => ''])) ?>">Tümü <b><?= array_sum($say) ?></b></a>
  <?php foreach ($PANO as $d): ?><a class="<?= $durum === $d ? 'on' : '' ?>" href="<?= e($qs(['durum' => $d])) ?>"><?= e($durumlar[$d][0]) ?> <b><?= $say[$d] ?? 0 ?></b></a><?php endforeach; ?>
</div>
<div class="filtre2">
  <form method="get" class="inline-form" style="flex:1"><input type="hidden" name="g" value="<?= $gorunum ?>"><?php if ($durum): ?><input type="hidden" name="durum" value="<?= e($durum) ?>"><?php endif; ?>
    <select class="inp" name="etiket" onchange="this.form.submit()"><option value="">Tüm etiketler</option><?php foreach ($etiketler as $et): ?><option <?= $et === $etiket ? 'selected' : '' ?>><?= e($et) ?></option><?php endforeach; ?></select>
    <input class="inp grow" name="q" value="<?= e($ara) ?>" placeholder="Kayıt no, müşteri, plaka…"></form>
  <span class="gorunum"><a class="<?= $gorunum === 'liste' ? 'on' : '' ?>" href="<?= e($qs(['g' => 'liste'])) ?>">☰ Liste</a><a class="<?= $gorunum === 'pano' ? 'on' : '' ?>" href="<?= e($qs(['g' => 'pano'])) ?>">▦ Servis panosu</a></span>
  <a class="btn btn-ghost" href="tv.php" target="_blank">📺 TV modu</a>
  <a class="btn btn-soft" href="<?= e($qs(['csv' => '1'])) ?>">⬇ Excel</a>
  <a class="btn btn-main" href="kabul-kart.php?yeni=1">+ Servis kabul</a>
</div>

<?php if ($gorunum === 'pano'): ?>
  <div class="pano">
    <?php foreach ($PANO as $d): $kol = array_filter($liste, fn($r) => $r['durum'] === $d); ?>
      <div class="sutun"><h4><?= e($durumlar[$d][0]) ?> <b><?= count($kol) ?></b></h4>
        <?php foreach ($kol as $r): ?>
          <a class="pkart" href="servis-kayit.php?id=<?= $r['id'] ?>"><div class="ust"><?php $rz = satir_rozet($r); echo str_contains($rz, "plaka") ? $rz : "<span></span>"; ?><small>#<?= (int)$r['no'] ?></small></div>
            <b><?= e($r['kart_baslik'] ?: $r['baslik']) ?></b><small><?= e($r['musteri_ad']) ?><?= $r['teknisyen'] ? ' · 🔧 ' . e($r['teknisyen']) : '' ?></small>
            <small><?= zaman_once($r['giris_tarihi']) ?> · <?= para($r['toplam']) ?></small></a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card">
    <?php if ($liste): ?><div class="scroll"><table class="tbl">
      <tr><th>Kayıt no</th><th>Giriş</th><th>Müşteri</th><th><?= e($tanim['kayit']) ?></th><th class="hide-m">Toplam</th><th class="hide-m">Teknisyen</th><th>Durum</th></tr>
      <?php foreach ($liste as $r): ?>
        <tr onclick="location='servis-kayit.php?id=<?= $r['id'] ?>'" style="cursor:pointer"><td><b>#<?= (int)$r['no'] ?></b></td><td><small><?= date('d.m.Y H:i', strtotime($r['giris_tarihi'])) ?></small></td>
          <td><?= e($r['musteri_ad']) ?></td><td><?= satir_rozet($r) ?><small class="muted" style="display:block"><?= e($r['kart_baslik'] ?: $r['baslik']) ?></small></td>
          <td class="hide-m"><?= para($r['toplam']) ?></td><td class="hide-m"><?= e($r['teknisyen'] ?: '—') ?></td><td><?= servis_durum_pill($r['durum']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
    <?php else: ?><div class="emptybox"><div style="font-size:40px"><?= $tanim['ikon'] ?></div><p>Şu an serviste kayıt yok.</p><a class="btn btn-main" href="kabul-kart.php?yeni=1">+ Servis kabul</a></div><?php endif; ?>
  </div>
<?php endif; ?>
<?php panel_bitir();
