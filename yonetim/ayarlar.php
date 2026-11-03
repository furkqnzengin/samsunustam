<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$k = giris_gerekli('admin');

if (is_post()) {
    csrf_check();
    if (post('islem') === 'site') {
        $tel = post('site_telefon');
        $wa = post('site_whatsapp');
        if (($tel !== '' && !tel_gecerli($tel)) || ($wa !== '' && !tel_gecerli($wa))) {
            flash('Telefon numarası geçersiz.', 'err');
        } else {
            ayar_kaydet('site_telefon', $tel !== '' ? tel_temizle($tel) : '');
            ayar_kaydet('site_whatsapp', $wa !== '' ? tel_temizle($wa) : '');
            ayar_kaydet('site_eposta', mb_strtolower(post('site_eposta')));
            flash('Site ayarları kaydedildi.');
        }
    } elseif (post('islem') === 'sifre') {
        $kayit = row('SELECT sifre FROM kullanicilar WHERE id = ?', [$k['id']]);
        if (!password_verify(post('mevcut'), $kayit['sifre'])) flash('Mevcut şifre yanlış.', 'err');
        elseif (mb_strlen(post('yeni')) < 8) flash('Yeni şifre en az 8 karakter olmalı.', 'err');
        elseif (post('yeni') !== post('yeni2')) flash('Yeni şifreler aynı değil.', 'err');
        else { q('UPDATE kullanicilar SET sifre = ? WHERE id = ?', [password_hash(post('yeni'), PASSWORD_DEFAULT), $k['id']]); flash('Şifre değiştirildi.'); }
    }
    redirect('yonetim/ayarlar.php');
}

panel_basla('admin', 'ayarlar.php', 'Site Ayarları');
?>
<div class="row even">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="islem" value="site">
    <h2>İletişim bilgileri</h2>
    <p class="muted">Sitenin alt kısmında, gizlilik sayfasında ve usta paneli paket sayfasında görünür.</p>
    <div class="form one" style="margin-top:12px">
      <label>Telefon<input class="inp" name="site_telefon" type="tel" value="<?= e(ayar('site_telefon') ? tel_goster(ayar('site_telefon')) : '') ?>" placeholder="0362 xxx xx xx"></label>
      <label>WhatsApp<input class="inp" name="site_whatsapp" type="tel" value="<?= e(ayar('site_whatsapp') ? tel_goster(ayar('site_whatsapp')) : '') ?>" placeholder="05xx xxx xx xx"></label>
      <label>E-posta<input class="inp" name="site_eposta" type="email" value="<?= e(ayar('site_eposta')) ?>"></label>
    </div>
    <div class="formfoot"><button class="btn btn-main">Kaydet</button></div>
  </form>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="islem" value="sifre">
    <h2>Yönetici şifresi</h2>
    <div class="form one">
      <label>Mevcut şifre<input class="inp" type="password" name="mevcut" required autocomplete="current-password"></label>
      <label>Yeni şifre<input class="inp" type="password" name="yeni" minlength="8" required autocomplete="new-password"></label>
      <label>Yeni şifre (tekrar)<input class="inp" type="password" name="yeni2" minlength="8" required autocomplete="new-password"></label>
    </div>
    <div class="formfoot"><button class="btn btn-main">Şifreyi değiştir</button></div>
  </form>
</div>
<?php panel_bitir();
