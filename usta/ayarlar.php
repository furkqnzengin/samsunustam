<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$k = kullanici();

if (is_post()) {
    csrf_check();
    $kayit = row('SELECT * FROM kullanicilar WHERE id = ?', [$k['id']]);
    if (!password_verify(post('mevcut'), $kayit['sifre'])) {
        flash('Mevcut şifren yanlış.', 'err');
    } elseif (post('islem') === 'eposta') {
        $ep = mb_strtolower(post('eposta'));
        if (!filter_var($ep, FILTER_VALIDATE_EMAIL)) flash('Geçerli bir e-posta yaz.', 'err');
        elseif (val('SELECT id FROM kullanicilar WHERE eposta = ? AND id <> ?', [$ep, $k['id']])) flash('Bu e-posta başka bir hesapta kullanılıyor.', 'err');
        else { q('UPDATE kullanicilar SET eposta = ? WHERE id = ?', [$ep, $k['id']]); flash('E-posta adresin güncellendi.'); }
    } elseif (post('islem') === 'sifre') {
        $yeni = post('yeni');
        if (mb_strlen($yeni) < 8) flash('Yeni şifre en az 8 karakter olmalı.', 'err');
        elseif ($yeni !== post('yeni2')) flash('Yeni şifreler birbiriyle aynı değil.', 'err');
        else { q('UPDATE kullanicilar SET sifre = ? WHERE id = ?', [password_hash($yeni, PASSWORD_DEFAULT), $k['id']]); flash('Şifren değiştirildi.'); }
    }
    redirect('usta/ayarlar.php');
}

panel_basla('usta', 'ayarlar.php', 'Hesap Ayarları');
?>
<div class="row even">
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="islem" value="eposta">
    <h2>E-posta adresi</h2>
    <div class="form one">
      <label>E-posta<input class="inp" type="email" name="eposta" required value="<?= e($k['eposta']) ?>"></label>
      <label>Mevcut şifre<input class="inp" type="password" name="mevcut" required autocomplete="current-password"></label>
    </div>
    <div class="formfoot"><button class="btn btn-main">Kaydet</button></div>
  </form>
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="islem" value="sifre">
    <h2>Şifre değiştir</h2>
    <div class="form one">
      <label>Mevcut şifre<input class="inp" type="password" name="mevcut" required autocomplete="current-password"></label>
      <label>Yeni şifre (en az 8 karakter)<input class="inp" type="password" name="yeni" minlength="8" required autocomplete="new-password"></label>
      <label>Yeni şifre (tekrar)<input class="inp" type="password" name="yeni2" minlength="8" required autocomplete="new-password"></label>
    </div>
    <div class="formfoot"><button class="btn btn-main">Şifreyi değiştir</button></div>
  </form>
</div>
<div class="card">
  <h2>Hesabını silmek mi istiyorsun?</h2>
  <p class="muted">Profilinin ve tüm bilgilerinin silinmesi için site yöneticisine ulaş<?= ayar('site_eposta') ? ': <a class="link" href="mailto:' . e(ayar('site_eposta')) . '">' . e(ayar('site_eposta')) . '</a>' : '.' ?></p>
</div>
<?php panel_bitir();
