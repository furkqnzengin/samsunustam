<?php
require __DIR__ . '/../inc/init.php';

function kurulu_mu(): bool
{
    try {
        return (int)val("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'admin'") > 0;
    } catch (Throwable) {
        return false;
    }
}

$hata = '';
$dbHata = '';
try {
    db();
} catch (Throwable $ex) {
    $dbHata = 'Veritabanına bağlanılamadı. config.php içindeki bilgileri kontrol edin. (' . $ex->getMessage() . ')';
}

if (!$dbHata && is_post() && !kurulu_mu()) {
    csrf_check();
    $ad = post('ad');
    $eposta = mb_strtolower(post('eposta'));
    $sifre = post('sifre');
    if ($ad === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) $hata = 'Ad ve geçerli bir e-posta girin.';
    elseif (mb_strlen($sifre) < 8) $hata = 'Şifre en az 8 karakter olmalı.';
    else {
        // Tabloları oluştur
        $sql = file_get_contents(ROOT . '/sql/schema.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) db()->exec($stmt);

        $seed = require ROOT . '/sql/seed.php';
        db()->beginTransaction();
        if ((int)val('SELECT COUNT(*) FROM kategoriler') === 0) {
            foreach ($seed['kategoriler'] as $ki => [$kad, $ikon, $acik, $hizmetler]) {
                q('INSERT INTO kategoriler (ad, slug, ikon, aciklama, sira) VALUES (?,?,?,?,?)', [$kad, slugify($kad), $ikon, $acik, $ki]);
                $kid = (int)db()->lastInsertId();
                foreach ($hizmetler as $hi => $h) {
                    q('INSERT INTO hizmetler (kategori_id, ad, slug, sira) VALUES (?,?,?,?)', [$kid, $h, slugify($h), $hi]);
                }
            }
        }
        if ((int)val('SELECT COUNT(*) FROM ilceler') === 0) {
            foreach ($seed['ilceler'] as $i => $il) q('INSERT INTO ilceler (ad, slug, sira) VALUES (?,?,?)', [$il, slugify($il), $i]);
        }
        if ((int)val('SELECT COUNT(*) FROM paketler') === 0) {
            foreach ($seed['paketler'] as $i => [$pad, $pacik, $poz, $one]) {
                q('INSERT INTO paketler (ad, aciklama, ozellikler, fiyat, one_cikar, sira) VALUES (?,?,?,NULL,?,?)', [$pad, $pacik, $poz, $one, $i]);
            }
        }
        ayar_kaydet('populer_hizmetler', implode(',', $seed['populer_hizmetler']));
        ayar_kaydet('site_eposta', $eposta);
        q("INSERT INTO kullanicilar (ad, eposta, sifre, rol) VALUES (?,?,?,'admin')", [$ad, $eposta, password_hash($sifre, PASSWORD_DEFAULT)]);
        $k = row('SELECT * FROM kullanicilar WHERE eposta = ?', [$eposta]);
        db()->commit();
        giris_yap($k);
        flash('Kurulum tamamlandı! Güvenlik için sunucudaki "kurulum" klasörünü silin.');
        redirect('yonetim/');
    }
}
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kurulum – Samsun Ustam</title>
<link rel="stylesheet" href="<?= url('assets/site.css') ?>">
</head><body>
<div class="auth-wrap"><div class="auth-card">
  <a class="logo" href="<?= url() ?>"><span>Samsun<b>Ustam</b></span></a>
  <h1>Kurulum</h1>
  <?php if ($dbHata): ?>
    <div class="flash flash-err"><?= e($dbHata) ?></div>
  <?php elseif (kurulu_mu()): ?>
    <div class="flash flash-ok">Kurulum zaten tamamlanmış. Güvenlik için sunucudaki "kurulum" klasörünü silin.</div>
    <a class="btn btn-dark" href="<?= url('giris.php') ?>">Giriş yap →</a>
  <?php else: ?>
    <p class="muted">Veritabanı tabloları oluşturulacak, kategoriler, ilçeler ve paketler eklenecek. Yönetici hesabını oluşturun.</p>
    <?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
    <form method="post" class="fform">
      <?= csrf_field() ?>
      <label>Adınız<input name="ad" required value="<?= e(post('ad')) ?>"></label>
      <label>E-posta<input type="email" name="eposta" required value="<?= e(post('eposta')) ?>"></label>
      <label>Şifre (en az 8 karakter)<input type="password" name="sifre" minlength="8" required></label>
      <button class="btn btn-dark">Kurulumu başlat</button>
    </form>
  <?php endif; ?>
</div></div>
</body></html>
