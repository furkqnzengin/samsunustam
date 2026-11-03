<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$kategoriler = rows('SELECT * FROM kategoriler ORDER BY sira');
$hizmetler = rows('SELECT * FROM hizmetler ORDER BY sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$hata = '';

if (is_post()) {
    csrf_check();
    $islem = post('islem');

    if ($islem === 'foto_sil') {
        dosya_sil($u['foto']);
        q('UPDATE ustalar SET foto = NULL WHERE id = ?', [$uid]);
        flash('Profil fotoğrafı kaldırıldı.');
        redirect('usta/profil.php');
    }

    if ($islem === 'belge') {
        $f = $_FILES['belge'] ?? null;
        try {
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Dosya seçin.');
            if ($f['size'] > 8 * 1024 * 1024) throw new RuntimeException('Belge en fazla 8 MB olabilir.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
            if (!$ext) throw new RuntimeException('Belge PDF, JPG veya PNG olmalı.');
            $dir = ROOT . '/uploads/belgeler';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ad = 'belgeler/' . $uid . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], ROOT . '/uploads/' . $ad)) throw new RuntimeException('Belge kaydedilemedi.');
            dosya_sil($u['belge_dosya']);
            q('UPDATE ustalar SET belge_dosya = ? WHERE id = ?', [$ad, $uid]);
            flash('Belgen yüklendi. Yönetici kontrol ettikten sonra profilinde "Belgeli" rozeti görünecek.');
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'err');
        }
        redirect('usta/profil.php#belge');
    }

    // Profil bilgileri
    $ad = mb_substr(preg_replace('/\s+/u', ' ', post('ad_soyad')), 0, 100);
    $meslek = mb_substr(post('meslek'), 0, 100);
    $tel = post('telefon');
    $wa = post('whatsapp');
    $tecrube = post('tecrube_yil') === '' ? null : max(0, min(70, (int)post('tecrube_yil')));
    $hakkinda = mb_substr(post('hakkinda'), 0, 2000);
    $hIds = array_intersect(post_ids('hizmetler'), array_map('intval', array_column($hizmetler, 'id')));
    $iIds = array_intersect(post_ids('ilceler'), array_map('intval', array_column($ilceler, 'id')));

    if (mb_strlen($ad) < 3) $hata = 'Ad soyad en az 3 karakter olmalı.';
    elseif ($meslek === '') $hata = 'Meslek / unvan alanını doldurun.';
    elseif (!tel_gecerli($tel)) $hata = 'Geçerli bir telefon numarası yazın.';
    elseif ($wa !== '' && !tel_gecerli($wa)) $hata = 'Geçerli bir WhatsApp numarası yazın ya da boş bırakın.';
    elseif (!$hIds) $hata = 'En az bir hizmet seçin.';
    elseif (!$iIds) $hata = 'En az bir ilçe seçin.';
    else {
        $foto = $u['foto'];
        if (!empty($_FILES['foto']['name'])) {
            try {
                $yeni = resim_yukle($_FILES['foto'], 'ustalar/' . $uid);
                dosya_sil($foto);
                $foto = $yeni;
            } catch (RuntimeException $ex) {
                $hata = 'Profil fotoğrafı: ' . $ex->getMessage();
            }
        }
        if (!$hata) {
            db()->beginTransaction();
            $slug = $ad !== $u['ad_soyad'] ? benzersiz_slug($ad, $uid) : $u['slug'];
            // Reddedilmiş başvuru düzenlenince tekrar onaya düşer
            $durum = $u['durum'] === 'reddedildi' ? 'beklemede' : $u['durum'];
            q('UPDATE ustalar SET ad_soyad=?, slug=?, meslek=?, telefon=?, whatsapp=?, tecrube_yil=?, hakkinda=?, kesif_ucretsiz=?, acil_hizmet=?, foto=?, durum=? WHERE id=?', [
                $ad, $slug, $meslek, tel_temizle($tel), $wa !== '' ? tel_temizle($wa) : null, $tecrube, $hakkinda ?: null,
                post('kesif_ucretsiz') ? 1 : 0, post('acil_hizmet') ? 1 : 0, $foto, $durum, $uid,
            ]);
            q('DELETE FROM usta_hizmet WHERE usta_id = ?', [$uid]);
            foreach ($hIds as $h) q('INSERT INTO usta_hizmet (usta_id, hizmet_id) VALUES (?,?)', [$uid, $h]);
            q('DELETE FROM usta_ilce WHERE usta_id = ?', [$uid]);
            foreach ($iIds as $i) q('INSERT INTO usta_ilce (usta_id, ilce_id) VALUES (?,?)', [$uid, $i]);
            db()->commit();
            flash($durum !== $u['durum'] ? 'Profilin kaydedildi ve tekrar onaya gönderildi.' : 'Profilin kaydedildi.');
            redirect('usta/profil.php');
        }
    }
}

$secH = is_post() ? post_ids('hizmetler') : array_map('intval', array_column(rows('SELECT hizmet_id FROM usta_hizmet WHERE usta_id = ?', [$uid]), 'hizmet_id'));
$secI = is_post() ? post_ids('ilceler') : array_map('intval', array_column(rows('SELECT ilce_id FROM usta_ilce WHERE usta_id = ?', [$uid]), 'ilce_id'));
$v = fn(string $k) => e(is_post() ? post($k) : (string)($u[$k] ?? ''));
$chk = fn(string $k) => (is_post() ? post($k) : $u[$k]) ? 'checked' : '';

panel_basla('usta', 'profil.php', 'Profilim', 'Müşterilerin gördüğü bilgileri buradan düzenle.');
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?>
  <h2>Profil Bilgilerim <a href="../usta.php?u=<?= e($u['slug']) ?>" target="_blank">Profilimi gör ↗</a></h2>
  <div class="form">
    <div class="full photo-row">
      <?= $u['foto'] ? '<img class="av xl" src="' . e(upload_url($u['foto'])) . '" alt="">' : '<div class="av xl">' . e(initials($u['ad_soyad'])) . '</div>' ?>
      <label>Profil fotoğrafı <small class="muted">(JPG/PNG, en fazla 5 MB)</small><input class="inp" type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
    </div>
    <label>Ad Soyad<input class="inp" name="ad_soyad" required maxlength="100" value="<?= $v('ad_soyad') ?>"></label>
    <label>Meslek / Unvan<input class="inp" name="meslek" required maxlength="100" value="<?= $v('meslek') ?>" placeholder="Örn: Elektrikçi"></label>
    <label>Telefon<input class="inp" name="telefon" type="tel" required value="<?= $v('telefon') ?>"></label>
    <label>WhatsApp <small class="muted">(boş bırakılabilir)</small><input class="inp" name="whatsapp" type="tel" value="<?= $v('whatsapp') ?>"></label>
    <label>Tecrübe (yıl)<input class="inp" name="tecrube_yil" type="number" min="0" max="70" value="<?= $v('tecrube_yil') ?>"></label>
    <div class="toggles">
      <label class="chk"><input type="checkbox" name="kesif_ucretsiz" value="1" <?= $chk('kesif_ucretsiz') ?>> Keşif ücretsiz</label>
      <label class="chk"><input type="checkbox" name="acil_hizmet" value="1" <?= $chk('acil_hizmet') ?>> 7/24 acil hizmet veriyorum</label>
    </div>
    <label class="full">Hakkımda<textarea class="inp" name="hakkinda" rows="4" maxlength="2000" placeholder="Kaç yıldır bu işi yapıyorsun, hangi işlerde uzmansın, nasıl çalışırsın?"><?= $v('hakkinda') ?></textarea></label>

    <fieldset class="full" id="hizmetler"><legend>Verdiğim hizmetler</legend>
      <?php foreach ($kategoriler as $k): ?>
        <details <?= array_intersect($secH, array_map('intval', array_column(array_filter($hizmetler, fn($h) => $h['kategori_id'] == $k['id']), 'id'))) ? 'open' : '' ?>><summary><?= e($k['ikon'] . ' ' . $k['ad']) ?></summary>
          <div class="checks"><?php foreach ($hizmetler as $h) if ($h['kategori_id'] == $k['id']): ?><label class="cb"><input type="checkbox" name="hizmetler[]" value="<?= $h['id'] ?>" <?= in_array((int)$h['id'], $secH, true) ? 'checked' : '' ?>><span><?= e($h['ad']) ?></span></label><?php endif; ?></div>
        </details>
      <?php endforeach; ?>
    </fieldset>

    <fieldset class="full"><legend>Hizmet verdiğim ilçeler</legend>
      <div class="checks"><?php foreach ($ilceler as $i): ?><label class="cb"><input type="checkbox" name="ilceler[]" value="<?= $i['id'] ?>" <?= in_array((int)$i['id'], $secI, true) ? 'checked' : '' ?>><span><?= e($i['ad']) ?></span></label><?php endforeach; ?></div>
    </fieldset>
  </div>
  <div class="formfoot"><button class="btn btn-main">Kaydet</button></div>
</form>

<?php if ($u['foto']): ?>
<form method="post" style="margin:-8px 0 20px"><?= csrf_field() ?><input type="hidden" name="islem" value="foto_sil"><button class="btn btn-red" onclick="return confirm('Profil fotoğrafı silinsin mi?')">Profil fotoğrafını kaldır</button></form>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card" id="belge">
  <?= csrf_field() ?><input type="hidden" name="islem" value="belge">
  <h2>Ustalık Belgesi</h2>
  <p class="muted">Ustalık belgesi, MYK belgesi veya oda kaydı yükle. Belge sitede yayınlanmaz, sadece yönetici görür. Onaylanınca profilinde <b>✔ Belgeli</b> rozeti çıkar.</p>
  <p style="margin:12px 0"><?= $u['belgeli'] ? '<span class="pill p-green">✔ Belgen onaylandı</span>' : ($u['belge_dosya'] ? '<span class="pill p-amber">Belge yüklendi, kontrol bekliyor</span>' : '<span class="pill p-blue">Henüz belge yüklenmedi</span>') ?></p>
  <div class="inline-form"><input class="inp" type="file" name="belge" accept="application/pdf,image/jpeg,image/png" required><button class="btn btn-main">Yükle</button></div>
</form>
<?php panel_bitir();
