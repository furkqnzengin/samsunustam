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
    }
    redirect('yonetim/paketler.php');
}

$paketler = rows('SELECT p.*, (SELECT COUNT(*) FROM ustalar u WHERE u.paket_id = p.id) AS n FROM paketler p ORDER BY sira, id');

$form = function (?array $p) {
    $p ??= ['id' => 0, 'ad' => '', 'aciklama' => '', 'ozellikler' => '', 'fiyat' => '', 'one_cikar' => 0, 'sira' => 0, 'aktif' => 1];
    ob_start(); ?>
    <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="islem" value="<?= $p['id'] ? 'kaydet' : 'ekle' ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>">
      <label>Paket adı<input class="inp" name="ad" required value="<?= e($p['ad']) ?>"></label>
      <label>Not / fiyat <small class="muted">(sadece senin için, ustalara gösterilmez)</small><input class="inp" name="fiyat" value="<?= e($p['fiyat']) ?>" placeholder="Örn: Aylık 500 ₺"></label>
      <label class="full">Kısa açıklama<input class="inp" name="aciklama" value="<?= e($p['aciklama']) ?>"></label>
      <label class="full">Özellikler <small class="muted">(her satıra bir tane)</small><textarea class="inp" name="ozellikler" rows="4"><?= e($p['ozellikler']) ?></textarea></label>
      <div class="toggles full">
        <label class="chk"><input type="checkbox" name="one_cikar" value="1" <?= $p['one_cikar'] ? 'checked' : '' ?>> Bu paketteki ustaları öne çıkar (üst sıra + Premium rozeti)</label>
        <input type="hidden" name="aktif" value="1">
        <label class="chk">Sıra <input class="inp" type="number" name="sira" value="<?= (int)$p['sira'] ?>" style="width:80px"></label>
      </div>
      <div class="full"><button class="btn btn-main"><?= $p['id'] ? 'Kaydet' : 'Paketi ekle' ?></button></div>
    </form>
    <?php return ob_get_clean();
};

panel_basla('admin', 'paketler.php', 'Premium & Paketler', 'Paketler ustalara gösterilmez. Bir ustayı Premium yapmak için Ustalar → usta detayı → Üyelik Paketi bölümünü kullan.');
?>
<?php foreach ($paketler as $p): ?>
  <div class="card"><h2><?= e($p['ad']) ?> <span class="muted small"><?= $p['n'] ?> usta</span></h2><?= $form($p) ?></div>
<?php endforeach; ?>
<div class="card"><h2>Yeni paket</h2><?= $form(null) ?></div>
<?php panel_bitir();
