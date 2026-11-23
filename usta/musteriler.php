<?php
// Müşteri kartları: bakiye ve etiket filtresi, arama, Excel
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$bakiye = get('bakiye');
$etiket = mb_substr(get('etiket'), 0, 60);
$ara = mb_substr(get('q'), 0, 60);
$t = servis_tutar_sql(); $o = servis_odenen_sql();
$where = ['m.usta_id = ?']; $params = [$uid];
if ($etiket !== '') { $where[] = 'm.etiket = ?'; $params[] = $etiket; }
if ($ara !== '') {
    $rakam = preg_replace('/\D/', '', $ara);
    $where[] = '(m.ad_soyad LIKE ? OR m.unvan LIKE ? OR m.eposta LIKE ?' . (strlen($rakam) >= 3 ? ' OR m.telefon LIKE ? OR m.tc_vkn LIKE ?' : '') . ')';
    array_push($params, "%$ara%", "%$ara%", "%$ara%");
    if (strlen($rakam) >= 3) array_push($params, "%$rakam%", "%$rakam%");
}
$sql = "SELECT * FROM (SELECT m.*, (SELECT COALESCE(SUM($t - $o), 0) FROM servisler s WHERE s.musteri_id = m.id AND s.durum <> 'iptal') AS bakiye,
          (SELECT COUNT(*) FROM servisler s WHERE s.musteri_id = m.id) AS servis_say
        FROM musteriler m WHERE " . implode(' AND ', $where) . ') x'
    . ($bakiye === 'borclu' ? ' WHERE bakiye > 0.009' : ($bakiye === 'alacakli' ? ' WHERE bakiye < -0.009' : ($bakiye === 'sifir' ? ' WHERE ABS(bakiye) <= 0.009' : '')))
    . ' ORDER BY ad_soyad LIMIT 1000';
$liste = rows($sql, $params);
$toplamBakiye = array_sum(array_column($liste, 'bakiye'));

if (get('csv') === '1') {
    csv_gonder('musteriler.csv', ['Ad soyad', 'Ünvan', 'TC / Vergi no', 'Vergi dairesi', 'Telefon', 'E-posta', 'Adres', 'Şehir', 'Etiket', 'Açıklama', 'Servis sayısı', 'Bakiye'],
        array_map(fn($m) => [$m['ad_soyad'], $m['unvan'], $m['tc_vkn'], $m['vergi_dairesi'], $m['telefon'] ? tel_goster($m['telefon']) : '', $m['eposta'], $m['adres'], $m['sehir'],
            $m['etiket'], $m['aciklama'], $m['servis_say'], number_format((float)$m['bakiye'], 2, ',', '')], $liste));
}
$etiketler = array_column(rows('SELECT DISTINCT etiket FROM musteriler WHERE usta_id = ? AND etiket IS NOT NULL ORDER BY etiket', [$uid]), 'etiket');
$qs = fn(array $ek) => '?' . http_build_query(array_filter(array_merge(['bakiye' => $bakiye, 'etiket' => $etiket, 'q' => $ara], $ek), 'strlen'));

panel_basla('usta', 'musteriler.php', 'Müşteri Kartları', count($liste) . ' müşteri · Toplam bakiye: <b class="' . ($toplamBakiye > 0.009 ? 'down' : '') . '">' . para($toplamBakiye) . '</b>');
?>
<div class="card">
  <form method="get" class="filtre2">
    <select class="inp" name="bakiye"><?php foreach (['' => 'Bakiye: tümü', 'borclu' => 'Borçlu olanlar', 'alacakli' => 'Fazla ödeyenler', 'sifir' => 'Bakiyesi sıfır'] as $k => $ad): ?><option value="<?= $k ?>" <?= $bakiye === $k ? 'selected' : '' ?>><?= $ad ?></option><?php endforeach; ?></select>
    <select class="inp" name="etiket"><option value="">Tüm etiketler</option><?php foreach ($etiketler as $et): ?><option <?= $et === $etiket ? 'selected' : '' ?>><?= e($et) ?></option><?php endforeach; ?></select>
    <input class="inp grow" name="q" value="<?= e($ara) ?>" placeholder="Ad soyad, ünvan, telefon, TC / vergi no…">
    <button class="btn btn-main">🔍 Filtrele</button>
    <a class="btn btn-soft" href="<?= e($qs(['csv' => '1'])) ?>">⬇ Excel</a>
    <a class="btn btn-main" href="musteri.php">+ Yeni müşteri</a>
  </form>
  <?php if ($liste): ?><div class="scroll"><table class="tbl">
    <tr><th>Ad soyad / ünvan</th><th class="hide-m">TC / Vergi no</th><th class="hide-m">E-posta</th><th>Telefon</th><th class="hide-m">Etiket</th><th class="hide-m">Servis</th><th>Bakiye</th></tr>
    <?php foreach ($liste as $m): ?>
      <tr onclick="location='musteri.php?id=<?= $m['id'] ?>'" style="cursor:pointer">
        <td><b><?= e($m['unvan'] ?: $m['ad_soyad']) ?></b><?= $m['unvan'] ? '<small class="muted" style="display:block">' . e($m['ad_soyad']) . '</small>' : '' ?></td>
        <td class="hide-m"><?= e((string)$m['tc_vkn']) ?></td><td class="hide-m"><small><?= e((string)$m['eposta']) ?></small></td>
        <td><?php if ($m['telefon']): ?><?= e(tel_goster($m['telefon'])) ?> <a href="<?= e(wa_link($m['telefon'])) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()" title="WhatsApp">💬</a><?php endif; ?></td>
        <td class="hide-m"><?= $m['etiket'] ? '<span class="pill p-blue">' . e($m['etiket']) . '</span>' : '' ?></td>
        <td class="hide-m"><?= (int)$m['servis_say'] ?></td>
        <td><b class="<?= $m['bakiye'] > 0.009 ? 'down' : ($m['bakiye'] < -0.009 ? 'up' : 'muted') ?>"><?= para($m['bakiye']) ?></b></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php else: ?><div class="emptybox"><div style="font-size:40px">👤</div><p>Müşteri bulunamadı.</p><a class="btn btn-main" href="musteri.php">+ Yeni müşteri</a></div><?php endif; ?>
</div>
<?php panel_bitir();
