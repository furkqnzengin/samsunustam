<?php
// Araç / cihaz kartları: arama, yaklaşan muayene-sigorta hatırlatmaları, Excel
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$tanim = sektorler()[usta_sektor($u)];
$ara = mb_substr(get('q'), 0, 60);
$sadeceHat = get('hat') === '1';

$where = ['k.usta_id = ?']; $params = [$uid];
if ($ara !== '') {
    $where[] = '(k.baslik LIKE ? OR k.anahtar LIKE ? OR k.ozel LIKE ? OR m.ad_soyad LIKE ? OR m.unvan LIKE ?)';
    array_push($params, "%$ara%", '%' . anahtar_norm($ara) . '%', "%$ara%", "%$ara%", "%$ara%");
}
$liste = rows('SELECT k.*, m.ad_soyad, m.unvan, m.telefon, (SELECT COUNT(*) FROM servisler s WHERE s.kart_id = k.id) AS servis_say,
    (SELECT MAX(giris_tarihi) FROM servisler s WHERE s.kart_id = k.id) AS son
    FROM kartlar k LEFT JOIN musteriler m ON m.id = k.musteri_id WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(son, k.olusturma) DESC LIMIT 1000', $params);
foreach ($liste as &$k) $k['hat'] = kart_hatirlatmalar($k);
unset($k);
if ($sadeceHat) $liste = array_values(array_filter($liste, fn($k) => $k['hat']));
$hatSay = count(array_filter($liste, fn($k) => $k['hat']));

if (get('csv') === '1') {
    csv_gonder('kartlar.csv', ['Kart', 'Tür', 'Müşteri', 'Telefon', 'Servis sayısı', 'Son servis', 'Bilgiler'],
        array_map(function ($k) {
            $o = json_decode((string)$k['ozel'], true) ?: [];
            return [$k['baslik'], sektorler()[$k['sektor']]['kayit'] ?? $k['sektor'], $k['unvan'] ?: $k['ad_soyad'], $k['telefon'] ? tel_goster($k['telefon']) : '', $k['servis_say'],
                $k['son'] ? date('d.m.Y', strtotime($k['son'])) : '', implode(' | ', array_map(fn($a, $b) => "$a: $b", array_keys(array_filter($o, 'strlen')), array_filter($o, 'strlen')))];
        }, $liste));
}

panel_basla('usta', 'kartlar.php', $tanim['kartlar'], count($liste) . ' kayıt' . ($hatSay ? ' · <span class="down">' . $hatSay . ' hatırlatma</span>' : ''));
?>
<div class="card">
  <form method="get" class="filtre2">
    <input class="inp grow" name="q" value="<?= e($ara) ?>" placeholder="<?= in_array(usta_sektor($u), ['arac', 'agir'], true) ? 'Plaka, marka, şasi no, müşteri…' : 'Seri no, model, müşteri…' ?>">
    <label class="chk"><input type="checkbox" name="hat" value="1" <?= $sadeceHat ? 'checked' : '' ?> onchange="this.form.submit()"> Sadece yaklaşan muayene / sigorta / garanti</label>
    <button class="btn btn-main">🔍 Ara</button>
    <a class="btn btn-soft" href="?<?= e(http_build_query(array_filter(['q' => $ara, 'hat' => $sadeceHat ? '1' : '', 'csv' => '1']))) ?>">⬇ Excel</a>
    <a class="btn btn-main" href="kabul-kart.php?yeni=1">+ Yeni kayıt</a>
  </form>
  <?php if ($liste): ?><div class="scroll"><table class="tbl">
    <tr><th><?= e($tanim['kayit']) ?></th><th>Bilgi</th><th class="hide-m">Müşteri</th><th class="hide-m">Servis</th><th class="hide-m">Son servis</th><th>Hatırlatma</th></tr>
    <?php foreach ($liste as $k): ?>
      <tr onclick="location='kart.php?id=<?= $k['id'] ?>'" style="cursor:pointer"><td><?= kart_rozet($k, 'k') ?></td><td><b><?= e($k['baslik']) ?></b></td>
        <td class="hide-m"><?= e($k['unvan'] ?: (string)$k['ad_soyad']) ?></td><td class="hide-m"><?= (int)$k['servis_say'] ?></td>
        <td class="hide-m"><small><?= $k['son'] ? date('d.m.Y', strtotime($k['son'])) : '—' ?></small></td><td><?= hatirlatma_rozet($k['hat']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php else: ?><div class="emptybox"><div style="font-size:40px"><?= $tanim['ikon'] ?></div><p>Kayıt bulunamadı.</p></div><?php endif; ?>
</div>
<?php panel_bitir();
