<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    try {
        if ($islem === 'kat_ekle' || $islem === 'kat_duzenle') {
            $ad = mb_substr(post('ad'), 0, 100);
            if ($ad === '') throw new RuntimeException('Kategori adı boş olamaz.');
            $ikon = mb_substr(post('ikon') ?: '🔧', 0, 8);
            $acik = mb_substr(post('aciklama'), 0, 255);
            if ($islem === 'kat_ekle') {
                $sira = (int)val('SELECT COALESCE(MAX(sira),0)+1 FROM kategoriler');
                q('INSERT INTO kategoriler (ad, slug, ikon, aciklama, sira) VALUES (?,?,?,?,?)', [$ad, slugify($ad), $ikon, $acik, $sira]);
                flash("\"$ad\" kategorisi eklendi.");
            } else {
                q('UPDATE kategoriler SET ad = ?, ikon = ?, aciklama = ?, sira = ? WHERE id = ?', [$ad, $ikon, $acik, (int)post('sira'), (int)post('id')]);
                flash('Kategori güncellendi.');
            }
        } elseif ($islem === 'kat_sil') {
            $n = (int)val('SELECT COUNT(*) FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id WHERE h.kategori_id = ?', [(int)post('id')]);
            if ($n) throw new RuntimeException("Bu kategoride hizmet veren $n usta kaydı var, önce hizmetleri taşıyın.");
            q('DELETE FROM kategoriler WHERE id = ?', [(int)post('id')]);
            flash('Kategori silindi.', 'info');
        } elseif ($islem === 'hiz_ekle') {
            $ad = mb_substr(post('ad'), 0, 120);
            if ($ad === '') throw new RuntimeException('Hizmet adı boş olamaz.');
            $kid = (int)post('kategori_id');
            $sira = (int)val('SELECT COALESCE(MAX(sira),0)+1 FROM hizmetler WHERE kategori_id = ?', [$kid]);
            q('INSERT INTO hizmetler (kategori_id, ad, slug, sira) VALUES (?,?,?,?)', [$kid, $ad, slugify($ad), $sira]);
            flash("\"$ad\" hizmeti eklendi.");
        } elseif ($islem === 'hiz_duzenle') {
            $ad = mb_substr(post('ad'), 0, 120);
            if ($ad === '') throw new RuntimeException('Hizmet adı boş olamaz.');
            q('UPDATE hizmetler SET ad = ?, kategori_id = ? WHERE id = ?', [$ad, (int)post('kategori_id'), (int)post('id')]);
            flash('Hizmet güncellendi.');
        } elseif ($islem === 'hiz_sil') {
            $n = (int)val('SELECT COUNT(*) FROM usta_hizmet WHERE hizmet_id = ?', [(int)post('id')]);
            if ($n) throw new RuntimeException("Bu hizmeti veren $n usta var, silinemez. İstersen adını değiştir.");
            q('DELETE FROM hizmetler WHERE id = ?', [(int)post('id')]);
            flash('Hizmet silindi.', 'info');
        } elseif ($islem === 'populer') {
            ayar_kaydet('populer_hizmetler', implode(',', array_column(rows('SELECT ad FROM hizmetler WHERE id IN (' . in_ph(post_ids('ids')) . ')', post_ids('ids') ?: [0]), 'ad')));
            flash('Ana sayfa arama listesi güncellendi.');
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() === '23000' ? 'Bu isimde bir kayıt zaten var.' : 'Kaydedilemedi.', 'err');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    redirect('yonetim/kategoriler.php');
}

$kategoriler = rows('SELECT * FROM kategoriler ORDER BY sira, id');
$hizmetler = rows('SELECT h.*, (SELECT COUNT(*) FROM usta_hizmet uh WHERE uh.hizmet_id = h.id) AS n FROM hizmetler h ORDER BY h.sira, h.id');
$populer = array_map('trim', explode(',', ayar('populer_hizmetler')));

panel_basla('admin', 'kategoriler.php', 'Kategoriler & Hizmetler', 'Sitedeki hizmet listesini buradan yönet.');
?>
<div class="card">
  <h2>Yeni kategori</h2>
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="islem" value="kat_ekle">
    <input class="inp" name="ikon" placeholder="🔧" style="width:64px"><input class="inp" name="ad" placeholder="Kategori adı" required><input class="inp grow" name="aciklama" placeholder="Kısa açıklama"><button class="btn btn-main">Ekle</button></form>
</div>

<?php foreach ($kategoriler as $k): ?>
<div class="card">
  <details>
    <summary class="cat-sum"><span><?= e($k['ikon']) ?> <b><?= e($k['ad']) ?></b> <small class="muted"><?= count(array_filter($hizmetler, fn($h) => $h['kategori_id'] == $k['id'])) ?> hizmet</small></span><span class="btn btn-soft">Düzenle</span></summary>
    <form method="post" class="inline-form" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="islem" value="kat_duzenle"><input type="hidden" name="id" value="<?= $k['id'] ?>">
      <input class="inp" name="ikon" value="<?= e($k['ikon']) ?>" style="width:64px"><input class="inp" name="ad" value="<?= e($k['ad']) ?>" required><input class="inp grow" name="aciklama" value="<?= e($k['aciklama']) ?>"><input class="inp" type="number" name="sira" value="<?= (int)$k['sira'] ?>" style="width:80px" title="Sıra"><button class="btn btn-main">Kaydet</button></form>
    <form method="post" style="margin-top:8px" onsubmit="return confirm('Kategori ve içindeki hizmetler silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="kat_sil"><input type="hidden" name="id" value="<?= $k['id'] ?>"><button class="btn btn-red">Kategoriyi sil</button></form>
  </details>
  <div class="svc">
    <?php foreach ($hizmetler as $h) if ($h['kategori_id'] == $k['id']): ?>
      <details class="svc-item"><summary><?= e($h['ad']) ?> <small class="muted"><?= $h['n'] ?></small></summary>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="islem" value="hiz_duzenle"><input type="hidden" name="id" value="<?= $h['id'] ?>">
          <input class="inp" name="ad" value="<?= e($h['ad']) ?>" required>
          <select class="inp" name="kategori_id"><?php foreach ($kategoriler as $kk): ?><option value="<?= $kk['id'] ?>" <?= $kk['id'] == $h['kategori_id'] ? 'selected' : '' ?>><?= e($kk['ad']) ?></option><?php endforeach; ?></select>
          <button class="btn btn-main">Kaydet</button></form>
        <?php if (!$h['n']): ?><form method="post" onsubmit="return confirm('Hizmet silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="hiz_sil"><input type="hidden" name="id" value="<?= $h['id'] ?>"><button class="btn btn-red">Sil</button></form><?php endif; ?>
      </details>
    <?php endif; ?>
  </div>
  <form method="post" class="inline-form" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="islem" value="hiz_ekle"><input type="hidden" name="kategori_id" value="<?= $k['id'] ?>"><input class="inp grow" name="ad" placeholder="Bu kategoriye yeni hizmet ekle" required><button class="btn btn-soft">+ Ekle</button></form>
</div>
<?php endforeach; ?>

<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="islem" value="populer">
  <h2>Ana sayfa arama cümlesi</h2>
  <p class="muted">"Bana bir ___ lazım" listesinde en üstte görünecek hizmetleri seç.</p>
  <div class="checks" style="margin-top:12px"><?php foreach ($hizmetler as $h): ?><label class="cb"><input type="checkbox" name="ids[]" value="<?= $h['id'] ?>" <?= in_array($h['ad'], $populer, true) ? 'checked' : '' ?>><span><?= e($h['ad']) ?></span></label><?php endforeach; ?></div>
  <div class="formfoot"><button class="btn btn-main">Kaydet</button></div>
</form>
<?php panel_bitir();
