<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';

if ($k = kullanici()) redirect($k['rol'] === 'admin' ? 'yonetim/' : 'usta/');

$hata = '';
if (is_post()) {
    csrf_check();
    $eposta = mb_strtolower(post('eposta'));
    $sifre = post('sifre');
    if (!hiz_siniri('giris', 8, 900)) {
        $hata = 'Çok fazla hatalı deneme. 15 dakika sonra tekrar deneyin.';
    } else {
        $k = row('SELECT * FROM kullanicilar WHERE eposta = ?', [$eposta]);
        if ($k && password_verify($sifre, $k['sifre'])) {
            if (password_needs_rehash($k['sifre'], PASSWORD_DEFAULT)) {
                q('UPDATE kullanicilar SET sifre = ? WHERE id = ?', [password_hash($sifre, PASSWORD_DEFAULT), $k['id']]);
            }
            unset($_SESSION['rl']['giris']);
            giris_yap($k);
            $sonra = $_SESSION['sonra'] ?? '';
            unset($_SESSION['sonra']);
            global $CFG;
            $panel = $k['rol'] === 'admin' ? 'yonetim/' : 'usta/';
            if ($sonra && str_starts_with($sonra, $CFG['base'] . '/' . $panel)) {
                header('Location: ' . $sonra);
                exit;
            }
            redirect($panel);
        }
        $hata = 'E-posta veya şifre hatalı.';
    }
}
site_basla('Giriş yap');
?>
<section class="authsec"><div class="wrap">
  <div class="auth-card narrow">
    <h2>Giriş yap</h2>
    <p class="muted">Usta ve yönetici girişi</p>
    <?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
    <form method="post" class="fform">
      <?= csrf_field() ?>
      <label>E-posta<input name="eposta" type="email" required value="<?= e(post('eposta')) ?>" autocomplete="email"></label>
      <label>Şifre<input name="sifre" type="password" required autocomplete="current-password"></label>
      <button class="btn btn-dark btn-lg">Giriş yap</button>
      <p class="muted center">Şifreni mi unuttun? <?= ayar('site_telefon') ? 'Bize ulaş: ' . e(tel_goster(ayar('site_telefon'))) : 'Site yöneticisine ulaş.' ?></p>
      <p class="muted center">Usta mısın, hesabın yok mu? <a class="link" href="<?= url('kayit.php') ?>">Ücretsiz kaydol</a></p>
    </form>
  </div>
</div></section>
<?php site_bitir();
