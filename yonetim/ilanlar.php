<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/is.php';
giris_gerekli('admin');

$sekme = get('s') === 'ilan' ? 'ilan' : 'arayan';

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    $hid = (int)post('id');
    if ($islem === 'arayan_durum' && in_array(post('durum'), ['yayinda', 'gizli', 'beklemede'], true)) {
        q('UPDATE is_arayanlar SET durum = ? WHERE id = ?', [post('durum'), $hid]);
        flash(post('durum') === 'yayinda' ? 'İlan yayına alındı.' : 'İlan güncellendi.');
    } elseif ($islem === 'arayan_sil') {
        q('DELETE FROM is_arayanlar WHERE id = ?', [$hid]);
        flash('İş arayan ilanı silindi.', 'info');
    } elseif ($islem === 'ilan_durum' && in_array(post('durum'), ['aktif', 'gizli'], true)) {
        q('UPDATE ilanlar SET durum = ? WHERE id = ?', [post('durum'), $hid]);
        flash(post('durum') === 'gizli' ? 'İlan yayından kaldırıldı.' : 'İlan tekrar yayında.');
    } elseif ($islem === 'ilan_sil') {
        q('DELETE FROM ilanlar WHERE id = ?', [$hid]);
        flash('Eleman ilanı silindi.', 'info');
    }
    if (post('geri') === 'index') redirect('yonetim/');
    redirect('yonetim/ilanlar.php?s=' . $sekme . (get('d') ? '&d=' . urlencode(get('d')) : ''));
}

$d = get('d');
if ($sekme === 'arayan') {
    if (!in_array($d, ['beklemede', 'yayinda', 'gizli', 'kaldirildi'], true)) $d = 'beklemede';
    $liste = rows('SELECT a.*, h.ad AS hizmet_ad, i.ad AS ilce_ad FROM is_arayanlar a LEFT JOIN hizmetler h ON h.id = a.hizmet_id LEFT JOIN ilceler i ON i.id = a.ilce_id WHERE a.durum = ? ORDER BY a.id DESC LIMIT 200', [$d]);
    $sayac = [];
    foreach (rows('SELECT durum, COUNT(*) n FROM is_arayanlar GROUP BY durum') as $r) $sayac[$r['durum']] = (int)$r['n'];
} else {
    $liste = rows('SELECT l.*, u.ad_soyad, u.slug, i.ad AS ilce_ad, (SELECT COUNT(*) FROM ilan_basvurulari b WHERE b.ilan_id = l.id) AS basvuru
        FROM ilanlar l JOIN ustalar u ON u.id = l.usta_id LEFT JOIN ilceler i ON i.id = l.ilce_id ORDER BY l.id DESC LIMIT 200');
}
$bekleyen = (int)val("SELECT COUNT(*) FROM is_arayanlar WHERE durum = 'beklemede'");

$btn = fn(string $islem, int $id, string $et, string $cl, string $durum = '') => '<form method="post"' . (str_ends_with($islem, 'sil') ? ' onsubmit="return confirm(\'Silinsin mi?\')"' : '') . '>' . csrf_field()
    . '<input type="hidden" name="islem" value="' . $islem . '"><input type="hidden" name="id" value="' . $id . '">' . ($durum ? '<input type="hidden" name="durum" value="' . $durum . '">' : '') . '<button class="btn ' . $cl . '">' . $et . '</button></form>';

panel_basla('admin', 'ilanlar.php', 'İş İlanları', 'İş arayan ilanları sen onaylayınca yayına çıkar. Ustaların eleman ilanları doğrudan yayınlanır; uygunsuz olanı kaldırabilirsin.');
?>
<div class="filterbar">
  <a class="<?= $sekme === 'arayan' ? 'on' : '' ?>" href="?s=arayan">🙋 İş arayanlar <?= $bekleyen ? '<b>' . $bekleyen . ' onay</b>' : '' ?></a>
  <a class="<?= $sekme === 'ilan' ? 'on' : '' ?>" href="?s=ilan">💼 Eleman ilanları</a>
</div>

<?php if ($sekme === 'arayan'): ?>
<div class="filterbar">
  <?php foreach (['beklemede' => 'Onay bekleyen', 'yayinda' => 'Yayında', 'gizli' => 'Gizli', 'kaldirildi' => 'Sahibi kaldırdı'] as $k => $ad): ?>
    <a class="<?= $d === $k ? 'on' : '' ?>" href="?s=arayan&d=<?= $k ?>"><?= $ad ?> <b><?= $sayac[$k] ?? 0 ?></b></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <?php if ($liste): ?><div class="list"><?php foreach ($liste as $a): ?>
    <div class="li"><div class="av sm"><?= e(initials($a['ad'])) ?></div>
      <div class="grow"><b><?= e($a['meslek']) ?></b> · <?= e($a['ad']) ?> <?= ilan_durum_pill($a['durum']) ?><br>
        <small>📞 <?= e(tel_goster($a['telefon'])) ?> · 📍 <?= e($a['ilce_ad'] ?? 'Fark etmez') ?> · <?= e($a['calisma']) ?> · <?= e($a['tecrube']) ?><?= $a['hizmet_ad'] ? ' · ' . e($a['hizmet_ad']) : '' ?> · <?= zaman_once($a['olusturma']) ?> · Bitiş <?= date('d.m.Y', strtotime($a['bitis'])) ?></small>
        <?php if ($a['aciklama']): ?><p class="pre"><?= e($a['aciklama']) ?></p><?php endif; ?>
        <div class="acts wrapacts">
          <?= $a['durum'] !== 'yayinda' && $a['durum'] !== 'kaldirildi' ? $btn('arayan_durum', (int)$a['id'], '✓ Yayınla', 'btn-main', 'yayinda') : '' ?>
          <?= $a['durum'] !== 'gizli' && $a['durum'] !== 'kaldirildi' ? $btn('arayan_durum', (int)$a['id'], 'Gizle', 'btn-ghost', 'gizli') : '' ?>
          <?= $btn('arayan_sil', (int)$a['id'], 'Sil', 'btn-red') ?>
        </div></div></div>
  <?php endforeach; ?></div><?php else: ?><p class="muted">Bu listede ilan yok.</p><?php endif; ?>
</div>
<?php else: ?>
<div class="card">
  <?php if ($liste): ?><div class="scroll"><table class="tbl">
    <tr><th>İlan</th><th class="hide-m">Usta</th><th class="hide-m">İlçe</th><th class="hide-m">Başvuru</th><th>Durum</th><th></th></tr>
    <?php foreach ($liste as $l): ?>
      <tr><td><a class="link" href="../isler.php?id=<?= $l['id'] ?>" target="_blank"><?= e($l['baslik']) ?></a><small class="muted" style="display:block"><?= e($l['calisma']) ?> · <?= date('d.m.Y', strtotime($l['olusturma'])) ?></small></td>
        <td class="hide-m"><a class="link" href="../usta.php?u=<?= e($l['slug']) ?>" target="_blank"><?= e($l['ad_soyad']) ?></a></td>
        <td class="hide-m"><?= e($l['ilce_ad'] ?? '—') ?></td><td class="hide-m"><?= (int)$l['basvuru'] ?></td>
        <td><?= ilan_durum_pill($l['bitis'] < date('Y-m-d') && $l['durum'] === 'aktif' ? 'pasif' : $l['durum']) ?></td>
        <td><div class="acts"><?= $l['durum'] === 'gizli' ? $btn('ilan_durum', (int)$l['id'], 'Yayına al', 'btn-soft', 'aktif') : $btn('ilan_durum', (int)$l['id'], 'Kaldır', 'btn-ghost', 'gizli') ?><?= $btn('ilan_sil', (int)$l['id'], 'Sil', 'btn-red') ?></div></td></tr>
    <?php endforeach; ?>
  </table></div><?php else: ?><p class="muted">Henüz eleman ilanı yok.</p><?php endif; ?>
</div>
<?php endif; ?>
<?php panel_bitir();
