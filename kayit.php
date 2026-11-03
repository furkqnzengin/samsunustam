<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';

if (kullanici()) redirect(kullanici()['rol'] === 'admin' ? 'yonetim/' : 'usta/');

$kategoriler = rows('SELECT * FROM kategoriler ORDER BY sira');
$hizmetler = rows('SELECT * FROM hizmetler ORDER BY sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$hata = '';

if (is_post()) {
    csrf_check();
    $ad = mb_substr(preg_replace('/\s+/u', ' ', post('ad_soyad')), 0, 100);
    $tel = post('telefon');
    $eposta = mb_strtolower(post('eposta'));
    $sifre = post('sifre');
    $hizmetId = (int)post('hizmet');
    $ilceIds = post_ids('ilceler');
    $hizmet = $hizmetId ? row('SELECT * FROM hizmetler WHERE id = ?', [$hizmetId]) : null;

    if (post('web') !== '') redirect('');
    if (mb_strlen($ad) < 3) $hata = 'Adınızı ve soyadınızı yazın.';
    elseif (!tel_gecerli($tel)) $hata = 'Geçerli bir telefon numarası yazın (05xx xxx xx xx).';
    elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) $hata = 'Geçerli bir e-posta adresi yazın.';
    elseif (mb_strlen($sifre) < 8) $hata = 'Şifre en az 8 karakter olmalı.';
    elseif (!$hizmet) $hata = 'Mesleğinizi seçin.';
    elseif (!$ilceIds) $hata = 'Hizmet verdiğiniz en az bir ilçe seçin.';
    elseif (!post('kvkk')) $hata = 'Kullanım koşullarını ve gizlilik metnini onaylamanız gerekiyor.';
    elseif (val('SELECT id FROM kullanicilar WHERE eposta = ?', [$eposta])) $hata = 'Bu e-posta ile zaten bir hesap var. Giriş yapmayı deneyin.';
    elseif (!hiz_siniri('kayit', 3, 3600)) $hata = 'Çok fazla deneme yapıldı, lütfen daha sonra tekrar deneyin.';
    else {
        db()->beginTransaction();
        q("INSERT INTO kullanicilar (ad, eposta, telefon, sifre, rol) VALUES (?,?,?,?,'usta')", [$ad, $eposta, tel_temizle($tel), password_hash($sifre, PASSWORD_DEFAULT)]);
        $kid = (int)db()->lastInsertId();
        $paket = val('SELECT id FROM paketler WHERE aktif = 1 AND one_cikar = 0 ORDER BY sira LIMIT 1');
        q('INSERT INTO ustalar (kullanici_id, ad_soyad, slug, meslek, telefon, whatsapp, paket_id) VALUES (?,?,?,?,?,?,?)',
            [$kid, $ad, benzersiz_slug($ad), $hizmet['ad'], tel_temizle($tel), post('wa_ayni') ? tel_temizle($tel) : null, $paket ?: null]);
        $usId = (int)db()->lastInsertId();
        q('INSERT INTO usta_hizmet (usta_id, hizmet_id) VALUES (?, ?)', [$usId, $hizmet['id']]);
        $valid = array_map('intval', array_column($ilceler, 'id'));
        foreach (array_intersect($ilceIds, $valid) as $iid) q('INSERT INTO usta_ilce (usta_id, ilce_id) VALUES (?, ?)', [$usId, $iid]);
        db()->commit();
        giris_yap(['id' => $kid]);
        flash('Hoş geldin! Başvurun alındı. Profilini tamamla; onaylandığında sitede yayına çıkacak.');
        redirect('usta/profil.php');
    }
}
$secIlce = post_ids('ilceler');
site_basla('Usta olarak kaydol', "Samsun'da usta mısın? Ücretsiz profilini oluştur, müşteriler seni bulsun.");
?>
<section class="authsec"><div class="wrap authgrid">
  <div class="authside">
    <span class="num">USTA KAYDI</span>
    <h1>Müşteri seni bulsun.</h1>
    <p class="muted">Samsun'da usta arayanlar profilini görsün, seni doğrudan arasın. Komisyon yok, aracı yok.</p>
    <ul class="ticks"><li>📱 5 dakikada profil</li><li>📊 Kaç kişinin baktığını gör</li><li>💬 İş taleplerini panelinden takip et</li><li>⭐ Yorumlarla güven kazan</li></ul>
  </div>
  <div class="auth-card">
    <h2>Ücretsiz kaydol</h2>
    <?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
    <form method="post" class="fform">
      <?= csrf_field() ?><input class="hp" name="web" tabindex="-1" autocomplete="off">
      <label>Ad Soyad<input name="ad_soyad" required maxlength="100" value="<?= e(post('ad_soyad')) ?>" autocomplete="name"></label>
      <div class="two">
        <label>Telefon<input name="telefon" type="tel" inputmode="tel" placeholder="05xx xxx xx xx" required value="<?= e(post('telefon')) ?>"></label>
        <label>Meslek<select name="hizmet" required><option value="">Seçin</option>
          <?php foreach ($kategoriler as $k): ?><optgroup label="<?= e($k['ad']) ?>"><?php foreach ($hizmetler as $h) if ($h['kategori_id'] == $k['id']): ?><option value="<?= $h['id'] ?>" <?= (int)post('hizmet') === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['ad']) ?></option><?php endif; ?></optgroup><?php endforeach; ?>
        </select></label>
      </div>
      <label class="chk"><input type="checkbox" name="wa_ayni" value="1" <?= !is_post() || post('wa_ayni') ? 'checked' : '' ?>> Bu numaradan WhatsApp mesajı alabilirim</label>
      <label>E-posta<input name="eposta" type="email" required value="<?= e(post('eposta')) ?>" autocomplete="email"></label>
      <label>Şifre (en az 8 karakter)<input name="sifre" type="password" minlength="8" required autocomplete="new-password"></label>
      <fieldset><legend>Hizmet verdiğin ilçeler</legend><div class="checks">
        <?php foreach ($ilceler as $i): ?><label class="cb"><input type="checkbox" name="ilceler[]" value="<?= $i['id'] ?>" <?= in_array((int)$i['id'], $secIlce, true) ? 'checked' : '' ?>><span><?= e($i['ad']) ?></span></label><?php endforeach; ?>
      </div></fieldset>
      <label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span>Bilgilerimin sitede yayınlanmasını ve <a href="<?= url('gizlilik.php') ?>" target="_blank">gizlilik metnini</a> onaylıyorum.</span></label>
      <button class="btn btn-dark btn-lg">Kaydol ve profilimi oluştur →</button>
      <p class="muted center">Zaten hesabın var mı? <a class="link" href="<?= url('giris.php') ?>">Giriş yap</a></p>
    </form>
  </div>
</div></section>
<?php site_bitir();
