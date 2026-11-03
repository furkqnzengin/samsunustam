<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

$durum = get('durum');
$ara = mb_substr(get('q'), 0, 60);
$where = ['1=1'];
$params = [];
if (in_array($durum, ['beklemede', 'aktif', 'askida', 'reddedildi'], true)) { $where[] = 'u.durum = ?'; $params[] = $durum; }
if ($ara !== '') {
    $rakam = preg_replace('/\D/', '', $ara);
    if (strlen($rakam) >= 3) {
        $where[] = '(u.ad_soyad LIKE ? OR u.meslek LIKE ? OR u.telefon LIKE ?)';
        array_push($params, "%$ara%", "%$ara%", "%$rakam%");
    } else {
        $where[] = '(u.ad_soyad LIKE ? OR u.meslek LIKE ?)';
        array_push($params, "%$ara%", "%$ara%");
    }
}
if (get('premium') === '1') $where[] = 'p.one_cikar = 1';

$ustalar = rows('SELECT u.*, p.ad AS paket_ad, p.one_cikar, k.eposta,
    (SELECT GROUP_CONCAT(i.ad SEPARATOR \', \') FROM usta_ilce ui JOIN ilceler i ON i.id = ui.ilce_id WHERE ui.usta_id = u.id) AS ilceler
    FROM ustalar u JOIN kullanicilar k ON k.id = u.kullanici_id LEFT JOIN paketler p ON p.id = u.paket_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY u.durum = \'beklemede\' DESC, u.id DESC LIMIT 300', $params);
$sayac = [];
foreach (rows('SELECT durum, COUNT(*) n FROM ustalar GROUP BY durum') as $r) $sayac[$r['durum']] = (int)$r['n'];

panel_basla('admin', $durum === 'beklemede' ? 'ustalar.php?durum=beklemede' : 'ustalar.php', $durum === 'beklemede' ? 'Onay Bekleyenler' : 'Ustalar');
?>
<div class="filterbar">
  <a class="<?= $durum === '' ? 'on' : '' ?>" href="ustalar.php">Tümü <b><?= array_sum($sayac) ?></b></a>
  <?php foreach (['beklemede' => 'Onay bekleyen', 'aktif' => 'Aktif', 'askida' => 'Askıda', 'reddedildi' => 'Reddedilen'] as $d => $ad): ?>
    <a class="<?= $durum === $d ? 'on' : '' ?>" href="?durum=<?= $d ?>"><?= $ad ?> <b><?= $sayac[$d] ?? 0 ?></b></a>
  <?php endforeach; ?>
  <form class="fsearch" method="get"><?php if ($durum): ?><input type="hidden" name="durum" value="<?= e($durum) ?>"><?php endif; ?><input class="inp" name="q" placeholder="İsim, meslek, telefon…" value="<?= e($ara) ?>"></form>
</div>
<div class="card">
  <?php if ($ustalar): ?><div class="scroll"><table class="tbl">
    <tr><th>Usta</th><th>Meslek</th><th class="hide-m">İlçeler</th><th class="hide-m">Puan</th><th class="hide-m">Paket</th><th>Durum</th><th></th></tr>
    <?php foreach ($ustalar as $u): ?>
      <tr><td><a class="who" href="usta.php?id=<?= $u['id'] ?>"><?= $u['foto'] ? '<img class="av sm" src="' . e(upload_url($u['foto'])) . '" alt="">' : '<div class="av sm">' . e(initials($u['ad_soyad'])) . '</div>' ?><div><b><?= e($u['ad_soyad']) ?></b><small><?= e(tel_goster($u['telefon'])) ?></small></div></a></td>
        <td><?= e($u['meslek']) ?><?= $u['belgeli'] ? ' <span title="Belgeli">✔</span>' : ($u['belge_dosya'] ? ' <span class="pill p-amber" title="Belge kontrol bekliyor">belge</span>' : '') ?></td>
        <td class="hide-m"><small><?= e(mb_strimwidth((string)$u['ilceler'], 0, 40, '…')) ?></small></td>
        <td class="hide-m"><?= $u['yorum_sayisi'] ? '★ ' . puan_fmt($u['puan']) . ' <small class="muted">(' . $u['yorum_sayisi'] . ')</small>' : '—' ?></td>
        <td class="hide-m"><span class="pill <?= premium_mu($u) ? 'p-blue' : 'p-green' ?>"><?= e($u['paket_ad'] ?? '—') ?></span></td>
        <td><?= durum_pill($u['durum']) ?></td>
        <td><a class="btn btn-soft" href="usta.php?id=<?= $u['id'] ?>"><?= $u['durum'] === 'beklemede' ? 'İncele' : 'Düzenle' ?></a></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Bu listede usta yok.</p><?php endif; ?>
</div>
<?php panel_bitir();
