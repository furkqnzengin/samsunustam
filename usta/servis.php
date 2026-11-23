<?php
// Servis geçmişi: tarih / etiket / durum filtreleri, arama, Excel
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$tanim = sektorler()[usta_sektor($u)];
$durumlar = servis_durumlari();

$donem = get('d', 'ay');
$bas = get('bas'); $bit = get('bit');
$etiket = mb_substr(get('etiket'), 0, 60);
$durum = get('durum');
$ara = mb_substr(get('q'), 0, 60);
$borclu = get('borclu') === '1';

[$t0, $t1] = match ($donem) {
    'bugun' => [date('Y-m-d'), date('Y-m-d')],
    'hafta' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
    'yil' => [date('Y-01-01'), date('Y-m-d')],
    'tumu' => [null, null],
    'aralik' => [preg_match('/^\d{4}-\d{2}-\d{2}$/', $bas) ? $bas : null, preg_match('/^\d{4}-\d{2}-\d{2}$/', $bit) ? $bit : null],
    default => [date('Y-m-01'), date('Y-m-d')],
};
$where = ["s.usta_id = $uid"];
$params = [];
if ($t0) { $where[] = 'DATE(s.giris_tarihi) >= ?'; $params[] = $t0; }
if ($t1) { $where[] = 'DATE(s.giris_tarihi) <= ?'; $params[] = $t1; }
if ($etiket !== '') { $where[] = 's.etiket = ?'; $params[] = $etiket; }
if (isset($durumlar[$durum])) { $where[] = 's.durum = ?'; $params[] = $durum; }
if ($ara !== '') {
    $where[] = '(s.baslik LIKE ? OR s.musteri_ad LIKE ? OR s.musteri_firma LIKE ? OR s.anahtar LIKE ? OR s.musteri_tel LIKE ? OR s.no = ?)';
    array_push($params, "%$ara%", "%$ara%", "%$ara%", '%' . anahtar_norm($ara) . '%', '%' . (preg_replace('/\D/', '', $ara) ?: '#') . '%', (int)$ara);
}
$sql = servis_liste_sql(implode(' AND ', $where)) . ($borclu ? " WHERE durum <> 'iptal' AND toplam - odenen > 0.009" : '') . ' ORDER BY giris_tarihi DESC LIMIT 1000';
$liste = rows($sql, $params);

if (get('csv') === '1') {
    csv_gonder('servis-gecmisi.csv', ['Kayıt no', 'Giriş', 'Teslim', 'Müşteri', $tanim['kayit'], 'Toplam', 'Ödenen', 'Kalan', 'Teknisyen', 'Etiket', 'Durum'],
        array_map(fn($r) => [$r['no'], date('d.m.Y H:i', strtotime($r['giris_tarihi'])), $r['teslim_tarihi'] ? date('d.m.Y H:i', strtotime($r['teslim_tarihi'])) : '',
            $r['musteri_ad'], $r['baslik'], number_format((float)$r['toplam'], 2, ',', ''), number_format((float)$r['odenen'], 2, ',', ''),
            number_format((float)$r['toplam'] - (float)$r['odenen'], 2, ',', ''), $r['teknisyen'], $r['etiket'], $durumlar[$r['durum']][0]], $liste));
}

$toplam = array_sum(array_map(fn($r) => $r['durum'] === 'iptal' ? 0 : (float)$r['toplam'], $liste));
$kalan = array_sum(array_map(fn($r) => $r['durum'] === 'iptal' ? 0 : max(0, (float)$r['toplam'] - (float)$r['odenen']), $liste));
$etiketler = array_column(rows('SELECT DISTINCT etiket FROM servisler WHERE usta_id = ? AND etiket IS NOT NULL ORDER BY etiket', [$uid]), 'etiket');
$qs = fn(array $ek) => '?' . http_build_query(array_filter(array_merge(['d' => $donem, 'bas' => $bas, 'bit' => $bit, 'etiket' => $etiket, 'durum' => $durum, 'q' => $ara, 'borclu' => $borclu ? '1' : ''], $ek), 'strlen'));

panel_basla('usta', 'servis.php', 'Servis Geçmişi', count($liste) . ' kayıt · toplam ' . para($toplam) . ($kalan > 0 ? ' · <span class="down">alacak ' . para($kalan) . '</span>' : ''));
?>
<div class="card">
  <div class="filtre2">
    <span class="tarihler"><?php foreach (['bugun' => 'Bugün', 'hafta' => 'Bu hafta', 'ay' => 'Bu ay', 'yil' => 'Bu yıl', 'tumu' => 'Tümü'] as $k => $ad): ?><a class="<?= $donem === $k ? 'on' : '' ?>" href="<?= e($qs(['d' => $k, 'bas' => '', 'bit' => ''])) ?>"><?= $ad ?></a><?php endforeach; ?></span>
  </div>
  <form method="get" class="filtre2"><input type="hidden" name="d" value="aralik">
    <input class="inp" type="date" name="bas" value="<?= e($t0 ?? '') ?>"> – <input class="inp" type="date" name="bit" value="<?= e($t1 ?? '') ?>">
    <select class="inp" name="etiket"><option value="">Tüm etiketler</option><?php foreach ($etiketler as $et): ?><option <?= $et === $etiket ? 'selected' : '' ?>><?= e($et) ?></option><?php endforeach; ?></select>
    <select class="inp" name="durum"><option value="">Tüm durumlar</option><?php foreach ($durumlar as $k => [$ad]): ?><option value="<?= $k ?>" <?= $durum === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select>
    <label class="chk"><input type="checkbox" name="borclu" value="1" <?= $borclu ? 'checked' : '' ?>> Borcu olanlar</label>
    <input class="inp grow" name="q" value="<?= e($ara) ?>" placeholder="Kayıt no, müşteri, plaka, telefon…">
    <button class="btn btn-main">🔍 Filtrele</button>
    <a class="btn btn-soft" href="<?= e($qs(['csv' => '1'])) ?>">⬇ Excel</a>
  </form>
  <?php if ($liste): ?><div class="scroll"><table class="tbl">
    <tr><th>Kayıt no</th><th>Giriş</th><th class="hide-m">Teslim</th><th>Müşteri</th><th><?= e($tanim['kayit']) ?></th><th>Toplam</th><th class="hide-m">Kalan</th><th class="hide-m">Teknisyen</th><th>Durum</th></tr>
    <?php foreach ($liste as $r): $k = (float)$r['toplam'] - (float)$r['odenen']; ?>
      <tr onclick="location='servis-kayit.php?id=<?= $r['id'] ?>'" style="cursor:pointer"><td><b>#<?= (int)$r['no'] ?></b><?= $r['etiket'] ? '<small class="muted" style="display:block">🏷 ' . e($r['etiket']) . '</small>' : '' ?></td>
        <td><small><?= date('d.m.Y', strtotime($r['giris_tarihi'])) ?></small></td><td class="hide-m"><small><?= $r['teslim_tarihi'] ? date('d.m.Y', strtotime($r['teslim_tarihi'])) : '—' ?></small></td>
        <td><?= e($r['musteri_ad']) ?></td><td><?= satir_rozet($r) ?></td><td><?= para($r['toplam']) ?></td>
        <td class="hide-m"><?= $k > 0.009 && $r['durum'] !== 'iptal' ? '<b class="down">' . para($k) . '</b>' : '<span class="up">✓</span>' ?></td>
        <td class="hide-m"><?= e($r['teknisyen'] ?: '—') ?></td><td><?= servis_durum_pill($r['durum']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php else: ?><div class="emptybox"><div style="font-size:40px">🕘</div><p>Bu filtrelere uygun kayıt yok.</p></div><?php endif; ?>
</div>
<?php panel_bitir();
