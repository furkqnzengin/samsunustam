<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    if ($islem === 'kaydet' || $islem === 'ekle') {
        $ad = mb_substr(post('ad'), 0, 80);
        if ($ad === '') { flash('Paket adı boş olamaz.', 'err'); redirect('yonetim/paketler.php'); }
        $vals = [$ad, mb_substr(post('aciklama'), 0, 255) ?: null, mb_substr(post('ozellikler'), 0, 2000) ?: null,
                 mb_substr(post('fiyat'), 0, 60) ?: null, post('one_cikar') ? 1 : 0, (int)post('sira'), post('aktif') ? 1 : 0];
        if ($islem === 'ekle') {
            q('INSERT INTO paketler (ad, aciklama, ozellikler, fiyat, one_cikar, sira, aktif) VALUES (?,?,?,?,?,?,?)', $vals);
            flash('Paket eklendi.');
        } else {
            $vals[] = (int)post('id');
            q('UPDATE paketler SET ad=?, aciklama=?, ozellikler=?, fiyat=?, one_cikar=?, sira=?, aktif=? WHERE id=?', $vals);
            flash('Paket kaydedildi.');
        }
    } elseif ($islem === 'talep_tamam') {
        q("UPDATE paket_talepleri SET durum = 'gorusuldu' WHERE id = ?", [(int)post('id')]);
    }
    redirect('yonetim/paketler.php');
}

$paketler = rows('SELECT p.*, (SELECT COUNT(*) FROM ustalar u WHERE u.paket_id = p.id) AS n FROM paketler p ORDER BY sira, id');
$talepler = rows('SELECT pt.*, u.id AS usta_id, u.ad_soyad, u.telefon, p.ad AS paket FROM paket_talepleri pt JOIN ustalar u ON u.id = pt.usta_id JOIN paketler p ON p.id = pt.paket_id ORDER BY pt.durum = \'yeni\' DESC, pt.id DESC LIMIT 50');

$form = function (?array $p) {
    $p ??= ['id' => 0, 'ad' => '', 'aciklama' => '', 'ozellikler' => '', 'fiyat' => '', 'one_cikar' => 0, 'sira' => 0, 'aktif' => 1];
    ob_start(); ?>
    <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="islem" value="<?= $p['id'] ? 'kaydet' : 'ekle' ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>">
      <label>Paket adı<input class="inp" name="ad" required value="<?= e($p['ad']) ?>"></label>
      <label>Fiyat <small class="muted">(boş bırakırsan "Fiyat bilgisi için bize ulaşın" yazar)</small><input class="inp" name="fiyat" value="<?= e($p['fiyat']) ?>" placeholder="Örn: Aylık 500 ₺"></label>
      <label class="full">Kısa açıklama<input class="inp" name="aciklama" value="<?= e($p['aciklama']) ?>"></label>
      <label class="full">Özellikler <small class="muted">(her satıra bir tane)</small><textarea class="inp" name="ozellikler" rows="4"><?= e($p['ozellikler']) ?></textarea></label>
      <div class="toggles full">
        <label class="chk"><input type="checkbox" name="one_cikar" value="1" <?= $p['one_cikar'] ? 'checked' : '' ?>> Bu paketteki ustaları öne çıkar (üst sıra + Premium rozeti)</label>
        <label class="chk"><input type="checkbox" name="aktif" value="1" <?= $p['aktif'] ? 'checked' : '' ?>> Ustalara göster</label>
        <label class="chk">Sıra <input class="inp" type="number" name="sira" value="<?= (int)$p['sira'] ?>" style="width:80px"></label>
      </div>
      <div class="full"><button class="btn btn-main"><?= $p['id'] ? 'Kaydet' : 'Paketi ekle' ?></button></div>
    </form>
    <?php return ob_get_clean();
};

panel_basla('admin', 'paketler.php', 'Üyelik Paketleri', 'Fiyat alanını boş bırakırsan ustalara fiyat gösterilmez.');
?>
<?php if ($talepler): ?>
<div class="card"><h2>Paket bilgi talepleri</h2><div class="list">
  <?php foreach ($talepler as $t): ?>
    <div class="li"><div class="grow"><a class="link" href="usta.php?id=<?= $t['usta_id'] ?>"><b><?= e($t['ad_soyad']) ?></b></a> → <?= e($t['paket']) ?> <?= durum_pill($t['durum']) ?><br><small class="muted"><?= zaman_once($t['olusturma']) ?> · <?= e(tel_goster($t['telefon'])) ?></small></div>
      <div class="acts"><a class="btn btn-soft" href="tel:<?= e($t['telefon']) ?>">📞 Ara</a><?php if ($t['durum'] === 'yeni'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="talep_tamam"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-ghost">✓ Görüşüldü</button></form><?php endif; ?></div></div>
  <?php endforeach; ?>
</div></div>
<?php endif; ?>

<?php foreach ($paketler as $p): ?>
  <div class="card"><h2><?= e($p['ad']) ?> <span class="muted small"><?= $p['n'] ?> usta<?= $p['aktif'] ? '' : ' · gizli' ?></span></h2><?= $form($p) ?></div>
<?php endforeach; ?>
<div class="card"><h2>Yeni paket</h2><?= $form(null) ?></div>
<?php panel_bitir();
