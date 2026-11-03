<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const MAKS_FOTO = 12;

if (is_post()) {
    csrf_check();
    if (post('islem') === 'sil') {
        $f = row('SELECT * FROM fotograflar WHERE id = ? AND usta_id = ?', [(int)post('id'), $uid]);
        if ($f) {
            dosya_sil($f['dosya']);
            q('DELETE FROM fotograflar WHERE id = ?', [$f['id']]);
            flash('Fotoğraf silindi.');
        }
    } else {
        $mevcut = (int)val('SELECT COUNT(*) FROM fotograflar WHERE usta_id = ?', [$uid]);
        $files = $_FILES['fotolar'] ?? null;
        $eklenen = 0;
        $hatalar = [];
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $n => $name) {
                if ($files['error'][$n] === UPLOAD_ERR_NO_FILE) continue;
                if ($mevcut + $eklenen >= MAKS_FOTO) { $hatalar[] = 'En fazla ' . MAKS_FOTO . ' fotoğraf yükleyebilirsin.'; break; }
                $file = ['name' => $name, 'tmp_name' => $files['tmp_name'][$n], 'error' => $files['error'][$n], 'size' => $files['size'][$n]];
                try {
                    $yol = resim_yukle($file, 'ustalar/' . $uid);
                    q('INSERT INTO fotograflar (usta_id, dosya) VALUES (?, ?)', [$uid, $yol]);
                    $eklenen++;
                } catch (RuntimeException $ex) {
                    $hatalar[] = $name . ': ' . $ex->getMessage();
                }
            }
        }
        if ($eklenen) flash("$eklenen fotoğraf eklendi.");
        foreach (array_unique($hatalar) as $h) flash($h, 'err');
        if (!$eklenen && !$hatalar) flash('Fotoğraf seçmedin.', 'err');
    }
    redirect('usta/fotograflar.php');
}

$fotolar = rows('SELECT * FROM fotograflar WHERE usta_id = ? ORDER BY id DESC', [$uid]);
panel_basla('usta', 'fotograflar.php', 'İş Fotoğraflarım', 'Yaptığın işlerin fotoğrafları müşterilerin sana güvenmesini sağlar. En fazla ' . MAKS_FOTO . ' fotoğraf.');
?>
<div class="card">
  <h2>Fotoğraf ekle <span class="muted small"><?= count($fotolar) ?>/<?= MAKS_FOTO ?></span></h2>
  <?php if (count($fotolar) < MAKS_FOTO): ?>
  <form method="post" enctype="multipart/form-data" class="inline-form">
    <?= csrf_field() ?>
    <input class="inp" type="file" name="fotolar[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <button class="btn btn-main">Yükle</button>
  </form>
  <small class="muted">Birden fazla fotoğraf seçebilirsin. JPG, PNG veya WEBP, her biri en fazla 5 MB.</small>
  <?php else: ?><p class="muted">Fotoğraf sınırına ulaştın. Yeni eklemek için önce birini sil.</p><?php endif; ?>
</div>

<div class="card">
  <h2>Fotoğraflarım</h2>
  <?php if ($fotolar): ?>
  <div class="photos">
    <?php foreach ($fotolar as $f): ?>
      <div class="ph"><img src="<?= e(upload_url($f['dosya'])) ?>" alt="" loading="lazy">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= $f['id'] ?>"><button class="btn btn-red" onclick="return confirm('Bu fotoğraf silinsin mi?')">Sil</button></form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="muted">Henüz fotoğraf eklemedin.</p><?php endif; ?>
</div>
<?php panel_bitir();
