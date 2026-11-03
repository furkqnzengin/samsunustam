<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

if (is_post()) {
    csrf_check();
    $y = row('SELECT * FROM yorumlar WHERE id = ?', [(int)post('id')]);
    if ($y) {
        $islem = post('islem');
        if (in_array($islem, ['yayinda', 'gizli', 'beklemede'], true)) {
            q('UPDATE yorumlar SET durum = ? WHERE id = ?', [$islem, $y['id']]);
            flash($islem === 'yayinda' ? 'Yorum yayınlandı.' : 'Yorum gizlendi.');
        } elseif ($islem === 'sil') {
            q('DELETE FROM yorumlar WHERE id = ?', [$y['id']]);
            flash('Yorum silindi.', 'info');
        }
        puan_guncelle((int)$y['usta_id']);
    }
    if (post('geri') === 'index') redirect('yonetim/');
    redirect('yonetim/yorumlar.php?durum=' . urlencode(get('durum', 'beklemede')));
}

$durum = get('durum', 'beklemede');
if (!in_array($durum, ['beklemede', 'yayinda', 'gizli'], true)) $durum = 'beklemede';
$yorumlar = rows('SELECT y.*, u.ad_soyad, u.slug FROM yorumlar y JOIN ustalar u ON u.id = y.usta_id WHERE y.durum = ? ORDER BY y.id DESC LIMIT 200', [$durum]);
$sayac = [];
foreach (rows('SELECT durum, COUNT(*) n FROM yorumlar GROUP BY durum') as $r) $sayac[$r['durum']] = (int)$r['n'];

panel_basla('admin', 'yorumlar.php', 'Yorumlar', 'Ziyaretçi yorumları sen onaylayınca yayına çıkar.');
?>
<div class="filterbar">
  <?php foreach (['beklemede' => 'Onay bekleyen', 'yayinda' => 'Yayında', 'gizli' => 'Gizli'] as $d => $ad): ?>
    <a class="<?= $durum === $d ? 'on' : '' ?>" href="?durum=<?= $d ?>"><?= $ad ?> <b><?= $sayac[$d] ?? 0 ?></b></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <?php if ($yorumlar): ?><div class="list">
    <?php foreach ($yorumlar as $y): ?>
      <div class="li"><div class="grow">
        <b><?= e($y['ad']) ?></b><?= $y['mahalle'] ? ' <small>· ' . e($y['mahalle']) . '</small>' : '' ?> <span class="stars"><?= yildiz($y['puan']) ?></span>
        <small> · <a class="link" href="../usta.php?u=<?= e($y['slug']) ?>" target="_blank"><?= e($y['ad_soyad']) ?></a> hakkında · <?= zaman_once($y['olusturma']) ?> · IP <?= e($y['ip'] ?? '-') ?></small>
        <p><?= nl2br(e($y['metin'])) ?></p>
        <?php if ($y['usta_yaniti']): ?><p class="reply"><b>Usta yanıtı:</b> <?= e($y['usta_yaniti']) ?></p><?php endif; ?>
        <div class="acts wrapacts" style="margin-top:8px">
          <?php foreach (['yayinda' => ['Yayınla', 'btn-main'], 'gizli' => ['Gizle', 'btn-ghost'], 'sil' => ['Sil', 'btn-red']] as $is => [$et, $cl]) if ($is !== $y['durum']): ?>
            <form method="post" <?= $is === 'sil' ? 'onsubmit="return confirm(\'Yorum kalıcı olarak silinsin mi?\')"' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= $y['id'] ?>"><input type="hidden" name="islem" value="<?= $is ?>"><button class="btn <?= $cl ?>"><?= $et ?></button></form>
          <?php endif; ?>
        </div>
      </div></div>
    <?php endforeach; ?>
  </div><?php else: ?><p class="muted">Bu listede yorum yok.</p><?php endif; ?>
</div>
<?php panel_bitir();
