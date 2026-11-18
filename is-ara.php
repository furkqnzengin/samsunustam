<?php
// İş arayan ilanı ver (üyelik gerekmez). Düzenleme/kaldırma: is-ara.php?t=<gizli anahtar>
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';
require __DIR__ . '/inc/is.php';

$token = get('t');
$kayit = preg_match('/^[a-f0-9]{32}$/', $token) ? row('SELECT * FROM is_arayanlar WHERE token = ? AND durum <> \'kaldirildi\'', [$token]) : null;
if ($token !== '' && !$kayit) {
    site_basla('İlan bulunamadı');
    echo '<section><div class="wrap"><div class="empty"><div class="big">🔎</div><h3>Bu ilan bulunamadı ya da kaldırılmış.</h3><a class="btn btn-dark" href="' . url('is-ara.php') . '">Yeni ilan ver →</a></div></div></section>';
    site_bitir();
    exit;
}

$hizmetler = rows('SELECT h.*, k.ad AS kat FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id ORDER BY k.sira, h.sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$hata = '';

if (is_post()) {
    csrf_check();
    if (post('web') !== '') redirect('is-ara.php');

    if ($kayit && post('islem') === 'kaldir') {
        q("UPDATE is_arayanlar SET durum = 'kaldirildi' WHERE id = ?", [$kayit['id']]);
        flash('İlanın kaldırıldı. Kolay gelsin, işini bulmuşsundur umarız!');
        redirect('is-ara.php');
    }
    if ($kayit && post('islem') === 'uzat') {
        q('UPDATE is_arayanlar SET bitis = ? WHERE id = ?', [gun_sonra(ARAYAN_SURE_GUN), $kayit['id']]);
        flash('İlanının süresi ' . ARAYAN_SURE_GUN . ' gün uzatıldı.');
        redirect('is-ara.php?t=' . $token);
    }

    $ad = mb_substr(post('ad'), 0, 80);
    $tel = post('telefon');
    $meslek = mb_substr(post('meslek'), 0, 100);
    $hizmetId = (int)post('hizmet') ?: null;
    $ilceId = (int)post('ilce') ?: null;
    $tecrube = post('tecrube');
    $calisma = post('calisma');
    $aciklama = mb_substr(post('aciklama'), 0, 1500);

    if (mb_strlen($ad) < 3) $hata = 'Adını ve soyadını yaz.';
    elseif (!tel_gecerli($tel)) $hata = 'Geçerli bir telefon numarası yaz (05xx xxx xx xx).';
    elseif (mb_strlen($meslek) < 2) $hata = 'Hangi işi aradığını yaz (örn: Oto elektrik kalfası).';
    elseif (!in_array($tecrube, TECRUBE_SEVIYELERI, true) || !in_array($calisma, CALISMA_SEKILLERI, true)) $hata = 'Tecrübe ve çalışma şeklini seç.';
    elseif (!$kayit && !post('kvkk')) $hata = 'Bilgilerinin ustalarla paylaşılmasını onaylaman gerekiyor.';
    elseif (!$kayit && val("SELECT id FROM is_arayanlar WHERE telefon = ? AND durum IN ('beklemede','yayinda') AND bitis >= CURDATE()", [tel_temizle($tel)])) $hata = 'Bu telefon numarasıyla zaten bir ilanın var. Düzenlemek için sana verilen linki kullan.';
    elseif (!$kayit && !hiz_siniri('is_ara', 3, 3600)) $hata = 'Çok fazla deneme yapıldı, lütfen daha sonra tekrar dene.';
    else {
        if ($hizmetId && !val('SELECT id FROM hizmetler WHERE id = ?', [$hizmetId])) $hizmetId = null;
        if ($ilceId && !val('SELECT id FROM ilceler WHERE id = ?', [$ilceId])) $ilceId = null;
        $vals = [$ad, tel_temizle($tel), post('whatsapp') ? 1 : 0, $hizmetId, $meslek, $ilceId, $tecrube, $calisma, $aciklama ?: null];
        if ($kayit) {
            // Düzenlenen ilan tekrar onaya düşer (içerik değişmiş olabilir)
            q("UPDATE is_arayanlar SET ad=?, telefon=?, whatsapp=?, hizmet_id=?, meslek=?, ilce_id=?, tecrube=?, calisma=?, aciklama=?, durum = IF(durum = 'gizli', 'gizli', 'beklemede') WHERE id = ?", [...$vals, $kayit['id']]);
            flash('İlanın güncellendi ve kontrol için onaya gönderildi.');
            redirect('is-ara.php?t=' . $token);
        }
        $yeniToken = bin2hex(random_bytes(16));
        q('INSERT INTO is_arayanlar (ad, telefon, whatsapp, hizmet_id, meslek, ilce_id, tecrube, calisma, aciklama, token, ip, bitis) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [...$vals, $yeniToken, ip(), gun_sonra(ARAYAN_SURE_GUN)]);
        $_SESSION['yeni_arayan'] = $yeniToken;
        redirect('is-ara.php?t=' . $yeniToken . '&yeni=1');
    }
}

$v = fn(string $k) => e(is_post() ? post($k) : (string)($kayit[$k] ?? ''));
$secili = fn(string $k, $val) => (is_post() ? post($k) : (string)($kayit[$k] ?? '')) === (string)$val ? 'selected' : '';
$yeni = $kayit && get('yeni') === '1' && ($_SESSION['yeni_arayan'] ?? '') === $token;

site_basla($kayit ? 'İş arıyorum ilanım' : 'İş arıyorum ilanı ver', "Samsun'da iş mi arıyorsun? Ücretsiz ilan ver, eleman arayan ustalar seni bulsun.");
?>
<section class="authsec"><div class="wrap authgrid">
  <div class="authside">
    <span class="num">İŞ ARIYORUM</span>
    <?php if ($kayit): ?>
      <h1>İlanın</h1>
      <p><?= ilan_durum_pill($kayit['durum']) ?> <span class="muted">· Bitiş: <?= date('d.m.Y', strtotime($kayit['bitis'])) ?></span></p>
      <?php if ($yeni): ?>
        <div class="flash flash-ok" style="margin-top:16px"><b>İlanın alındı!</b> Kontrol edildikten sonra yayına çıkacak.</div>
      <?php endif; ?>
      <div class="savebox">
        <b>🔑 Bu linki sakla</b>
        <p class="muted">İlanını düzenlemek ya da kaldırmak için bu linke ihtiyacın var. Üyelik yok; link kaybolursa bize ulaş.</p>
        <input readonly value="<?= e(arayan_link($token)) ?>" onclick="this.select()">
        <div class="pacts" style="margin:10px 0 0">
          <a class="btn btn-wa" href="<?= e(wa_link($kayit['telefon'], 'Samsun Ustam iş arıyorum ilanımın linki: ' . arayan_link($token))) ?>" target="_blank" rel="noopener">💬 Linki kendime WhatsApp'tan gönder</a>
        </div>
      </div>
      <div class="pacts">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="uzat"><button class="btn btn-soft">⏳ Süreyi <?= ARAYAN_SURE_GUN ?> gün uzat</button></form>
        <form method="post" onsubmit="return confirm('İlanın kaldırılsın mı?')"><?= csrf_field() ?><input type="hidden" name="islem" value="kaldir"><button class="btn btn-line">İşimi buldum, ilanı kaldır</button></form>
      </div>
    <?php else: ?>
      <h1>İş mi arıyorsun? Ustalar seni bulsun.</h1>
      <p class="muted">Samsun'da eleman arayan ustalar ilanını görüp seni arasın. Ücretsiz, üyelik gerekmez.</p>
      <ul class="ticks"><li>📝 2 dakikada ilan</li><li>🔒 Telefonun sadece kayıtlı ustalara görünür</li><li>⏳ İlan <?= ARAYAN_SURE_GUN ?> gün yayında kalır</li></ul>
      <p style="margin-top:18px"><a class="link" href="<?= url('isler.php') ?>">Ustaların iş ilanlarına göz at →</a></p>
    <?php endif; ?>
  </div>
  <div class="auth-card">
    <h2><?= $kayit ? 'İlanı düzenle' : 'İlan bilgilerin' ?></h2>
    <?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
    <form method="post" class="fform">
      <?= csrf_field() ?><input class="hp" name="web" tabindex="-1" autocomplete="off">
      <label>Ad soyad<input name="ad" required maxlength="80" value="<?= $v('ad') ?>" autocomplete="name"></label>
      <div class="two">
        <label>Telefon<input name="telefon" type="tel" inputmode="tel" required placeholder="05xx xxx xx xx" value="<?= $kayit && !is_post() ? e(tel_goster($kayit['telefon'])) : $v('telefon') ?>"></label>
        <label>İlçe<select name="ilce"><option value="">Fark etmez</option><?php foreach ($ilceler as $i): ?><option value="<?= $i['id'] ?>" <?= $secili(is_post() ? 'ilce' : 'ilce_id', $i['id']) ?>><?= e($i['ad']) ?></option><?php endforeach; ?></select></label>
      </div>
      <label class="chk"><input type="checkbox" name="whatsapp" value="1" <?= (is_post() ? post('whatsapp') : ($kayit['whatsapp'] ?? 1)) ? 'checked' : '' ?>> <span>Bu numaradan WhatsApp mesajı alabilirim</span></label>
      <label>Aradığın iş / pozisyon<input name="meslek" required maxlength="100" placeholder="Örn: Oto elektrik kalfası, CNC operatörü, çırak" value="<?= $v('meslek') ?>"></label>
      <label>Alan <small class="muted" style="font-weight:400">(isteğe bağlı, ustalar filtrelerken kullanır)</small>
        <select name="hizmet"><option value="">Seçin</option><?php $kat = ''; foreach ($hizmetler as $h): if ($h['kat'] !== $kat): echo $kat ? '</optgroup>' : ''; $kat = $h['kat']; ?><optgroup label="<?= e($kat) ?>"><?php endif; ?><option value="<?= $h['id'] ?>" <?= $secili(is_post() ? 'hizmet' : 'hizmet_id', $h['id']) ?>><?= e($h['ad']) ?></option><?php endforeach; ?></optgroup></select></label>
      <div class="two">
        <label>Tecrüben<select name="tecrube" required><option value="">Seçin</option><?php foreach (TECRUBE_SEVIYELERI as $t): ?><option <?= $secili('tecrube', $t) ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
        <label>Çalışma şekli<select name="calisma" required><option value="">Seçin</option><?php foreach (CALISMA_SEKILLERI as $c): ?><option <?= $secili('calisma', $c) ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
      </div>
      <label>Kendinden bahset<textarea name="aciklama" rows="5" maxlength="1500" placeholder="Nerelerde çalıştın, neler yapabiliyorsun? Ehliyet, belge, sertifika vb."><?= $v('aciklama') ?></textarea></label>
      <?php if (!$kayit): ?><label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span>Adımın, telefonumun ve ilan bilgilerimin eleman arayan ustalarla paylaşılmasını onaylıyorum. <a class="link" href="<?= url('gizlilik.php') ?>" target="_blank">Gizlilik</a></span></label><?php endif; ?>
      <button class="btn btn-dark btn-lg"><?= $kayit ? 'Kaydet' : 'İlanı gönder →' ?></button>
      <?php if (!$kayit): ?><p class="muted center">İlanın kontrol edildikten sonra yayına çıkar.</p><?php endif; ?>
    </form>
  </div>
</div></section>
<?php site_bitir();
