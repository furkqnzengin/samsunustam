<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';

$u = row("SELECT u.*, p.one_cikar, p.ad AS paket_ad FROM ustalar u LEFT JOIN paketler p ON p.id = u.paket_id WHERE u.slug = ?", [get('u')]);
$k = kullanici();
// Onaylanmamış profili sadece sahibi ve yönetici görebilir
$onizleme = $u && $u['durum'] !== 'aktif' && $k && ($k['rol'] === 'admin' || (int)$k['id'] === (int)$u['kullanici_id']);
if (!$u || ($u['durum'] !== 'aktif' && !$onizleme)) {
    http_response_code(404);
    site_basla('Usta bulunamadı');
    echo '<section><div class="wrap"><div class="empty"><div class="big">🤷</div><h3>Bu usta bulunamadı.</h3><p>Profil kaldırılmış ya da henüz yayında değil.</p><a class="btn btn-dark" href="' . url('ara.php') . '">Ustalara göz at →</a></div></div></section>';
    site_bitir();
    exit;
}
$uid = (int)$u['id'];
$ilceler = rows('SELECT i.* FROM usta_ilce ui JOIN ilceler i ON i.id = ui.ilce_id WHERE ui.usta_id = ? ORDER BY i.sira', [$uid]);
$tumIlceler = rows('SELECT * FROM ilceler ORDER BY sira');

// Formlar
$formHata = ['yorum' => '', 'talep' => ''];
if (is_post() && !$onizleme) {
    csrf_check();
    $tip = post('form');
    // Basit bot tuzağı: gizli alan doluysa sessizce geç
    if (post('web') !== '') redirect('usta.php?u=' . $u['slug']);

    if ($tip === 'yorum') {
        $ad = mb_substr(post('ad'), 0, 80);
        $mah = mb_substr(post('mahalle'), 0, 80);
        $puan = (int)post('puan');
        $metin = mb_substr(post('metin'), 0, 1500);
        if ($ad === '' || $puan < 1 || $puan > 5 || mb_strlen($metin) < 10) $formHata['yorum'] = 'Adınızı, puanınızı ve en az 10 karakterlik yorumunuzu yazın.';
        elseif (!hiz_siniri('yorum', 3, 3600)) $formHata['yorum'] = 'Çok fazla yorum gönderdiniz, lütfen daha sonra tekrar deneyin.';
        else {
            q('INSERT INTO yorumlar (usta_id, ad, mahalle, puan, metin, ip) VALUES (?,?,?,?,?,?)', [$uid, $ad, $mah ?: null, $puan, $metin, ip()]);
            flash('Teşekkürler! Yorumunuz kontrol edildikten sonra yayınlanacak.');
            redirect('usta.php?u=' . $u['slug'] . '#yorumlar');
        }
    } elseif ($tip === 'talep') {
        $ad = mb_substr(post('ad'), 0, 80);
        $tel = post('telefon');
        $ilceId = (int)post('ilce') ?: null;
        $mesaj = mb_substr(post('mesaj'), 0, 1500);
        if ($ad === '' || !tel_gecerli($tel) || mb_strlen($mesaj) < 10) $formHata['talep'] = 'Adınızı, geçerli bir telefon numarası ve en az 10 karakterlik mesajınızı yazın.';
        elseif (!post('kvkk')) $formHata['talep'] = 'Bilgilerinizin ustayla paylaşılmasını onaylamanız gerekiyor.';
        elseif (!hiz_siniri('talep', 5, 3600)) $formHata['talep'] = 'Çok fazla talep gönderdiniz, lütfen daha sonra tekrar deneyin.';
        else {
            q('INSERT INTO talepler (usta_id, ad, telefon, ilce_id, mesaj, ip) VALUES (?,?,?,?,?,?)', [$uid, $ad, tel_temizle($tel), $ilceId, $mesaj, ip()]);
            flash('Talebiniz ustaya iletildi. Usta sizi telefonla arayacak.');
            redirect('usta.php?u=' . $u['slug']);
        }
    }
}

if (!$onizleme && empty($_SESSION['gorulen'][$uid])) {
    olay_kaydet($uid, 'goruntuleme');
    $_SESSION['gorulen'][$uid] = 1;
}

$hizmetler = rows('SELECT h.* FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id WHERE uh.usta_id = ? ORDER BY h.sira', [$uid]);
$fotolar = rows('SELECT * FROM fotograflar WHERE usta_id = ? ORDER BY id DESC', [$uid]);
$yorumlar = rows("SELECT * FROM yorumlar WHERE usta_id = ? AND durum = 'yayinda' ORDER BY id DESC LIMIT 50", [$uid]);
$dagilim = array_fill(1, 5, 0);
foreach ($yorumlar as $y) $dagilim[(int)$y['puan']]++;
$ilceAdlari = array_column($ilceler, 'ad');

site_basla($u['ad_soyad'] . ' – ' . $u['meslek'], $u['ad_soyad'] . ', ' . ($ilceAdlari ? implode(', ', $ilceAdlari) : 'Samsun') . ' bölgesinde ' . tr_lower($u['meslek']) . '. Yorumlar, puan ve iletişim bilgileri.');
?>
<?php if ($onizleme): ?><div class="wrap" style="margin-top:16px"><div class="flash flash-info">Önizleme: Bu profil henüz yayında değil (durum: <?= e($u['durum']) ?>). Ziyaretçiler göremez.</div></div><?php endif; ?>

<section class="profile"><div class="wrap">
  <div class="phead">
    <?= $u['foto'] ? '<img class="pav" src="' . e(upload_url($u['foto'])) . '" alt="' . e($u['ad_soyad']) . '">' : '<div class="pav">' . e(initials($u['ad_soyad'])) . '</div>' ?>
    <div class="pinfo">
      <div class="badges">
        <?php if (premium_mu($u)): ?><span class="tag y">★ Premium</span><?php endif; ?>
        <?php if ($u['belgeli']): ?><span class="tag">✔ Belgeli usta</span><?php endif; ?>
        <?php if ($u['kesif_ucretsiz']): ?><span class="tag">🎁 Keşif ücretsiz</span><?php endif; ?>
        <?php if ($u['acil_hizmet']): ?><span class="tag r">🚨 7/24 acil</span><?php endif; ?>
      </div>
      <h1><?= e($u['ad_soyad']) ?></h1>
      <p class="pjob"><?= e($u['meslek']) ?><?= $u['tecrube_yil'] ? ' · ' . (int)$u['tecrube_yil'] . ' yıl tecrübe' : '' ?></p>
      <p class="muted">📍 <?= e($ilceAdlari ? implode(', ', $ilceAdlari) : 'Samsun') ?></p>
    </div>
    <div class="pscore">
      <?php if ($u['yorum_sayisi']): ?><b><?= puan_fmt($u['puan']) ?></b><span class="stars"><?= yildiz($u['puan']) ?></span><small><?= (int)$u['yorum_sayisi'] ?> yorum</small>
      <?php else: ?><b>Yeni</b><small>Henüz yorum yok</small><?php endif; ?>
    </div>
  </div>
  <div class="pacts">
    <a class="btn btn-dark btn-lg" href="<?= url('git.php?u=' . $uid . '&t=arama') ?>" rel="nofollow">📞 <?= e(tel_goster($u['telefon'])) ?></a>
    <?php if ($u['whatsapp']): ?><a class="btn btn-wa btn-lg" href="<?= url('git.php?u=' . $uid . '&t=whatsapp') ?>" rel="nofollow">💬 WhatsApp'tan yaz</a><?php endif; ?>
    <a class="btn btn-line btn-lg" href="#talep">✉️ İş talebi gönder</a>
  </div>

  <div class="pgrid">
    <div class="pmain">
      <?php if ($u['hakkinda']): ?><div class="box"><h2>Hakkında</h2><p class="pre"><?= e($u['hakkinda']) ?></p></div><?php endif; ?>

      <?php if ($hizmetler): ?><div class="box"><h2>Hizmetler</h2><div class="chips"><?php foreach ($hizmetler as $h): ?><a href="<?= url('ara.php?h=' . $h['slug']) ?>"><?= e($h['ad']) ?></a><?php endforeach; ?></div></div><?php endif; ?>

      <?php if ($fotolar): ?><div class="box"><h2>İşlerinden fotoğraflar</h2><div class="pgal"><?php foreach ($fotolar as $f): ?><a href="<?= e(upload_url($f['dosya'])) ?>" target="_blank"><img src="<?= e(upload_url($f['dosya'])) ?>" alt="<?= e($u['ad_soyad']) ?> iş fotoğrafı" loading="lazy"></a><?php endforeach; ?></div></div><?php endif; ?>

      <div class="box" id="yorumlar"><h2>Yorumlar <?= $u['yorum_sayisi'] ? '<small class="muted">(' . (int)$u['yorum_sayisi'] . ')</small>' : '' ?></h2>
        <?php if ($yorumlar): ?>
          <div class="dist"><?php for ($s = 5; $s >= 1; $s--): ?><div><span><?= $s ?> ★</span><i><b style="width:<?= round($dagilim[$s] / count($yorumlar) * 100) ?>%"></b></i><span><?= $dagilim[$s] ?></span></div><?php endfor; ?></div>
          <?php foreach ($yorumlar as $y): ?>
            <div class="rev"><div class="rh"><b><?= e($y['ad']) ?></b><?= $y['mahalle'] ? '<small class="muted"> · ' . e($y['mahalle']) . '</small>' : '' ?><span class="stars"><?= yildiz($y['puan']) ?></span><small class="muted"><?= zaman_once($y['olusturma']) ?></small></div>
              <p><?= nl2br(e($y['metin'])) ?></p>
              <?php if ($y['usta_yaniti']): ?><div class="reply"><b>Ustanın yanıtı:</b> <?= nl2br(e($y['usta_yaniti'])) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?><p class="muted">Henüz yorum yok. Bu ustayla çalıştıysan ilk yorumu sen yaz.</p><?php endif; ?>

        <details class="rform" <?= $formHata['yorum'] ? 'open' : '' ?>><summary class="btn btn-soft">⭐ Yorum yaz</summary>
          <?php if ($formHata['yorum']): ?><div class="flash flash-err"><?= e($formHata['yorum']) ?></div><?php endif; ?>
          <form method="post" class="fform">
            <?= csrf_field() ?><input type="hidden" name="form" value="yorum"><input class="hp" name="web" tabindex="-1" autocomplete="off">
            <div class="stars-in"><?php for ($s = 5; $s >= 1; $s--): ?><input type="radio" name="puan" id="p<?= $s ?>" value="<?= $s ?>" <?= (int)post('puan') === $s ? 'checked' : '' ?>><label for="p<?= $s ?>">★</label><?php endfor; ?></div>
            <div class="two"><label>Adınız<input name="ad" maxlength="80" required value="<?= e(post('form') === 'yorum' ? post('ad') : '') ?>"></label>
            <label>Mahalle (isteğe bağlı)<input name="mahalle" maxlength="80" value="<?= e(post('form') === 'yorum' ? post('mahalle') : '') ?>"></label></div>
            <label>Yorumunuz<textarea name="metin" rows="4" maxlength="1500" required><?= e(post('form') === 'yorum' ? post('metin') : '') ?></textarea></label>
            <button class="btn btn-dark">Yorumu gönder</button>
            <small class="muted">Yorumlar yayınlanmadan önce kontrol edilir.</small>
          </form>
        </details>
      </div>
    </div>

    <aside class="pside">
      <div class="box" id="talep"><h2>İş talebi gönder</h2><p class="muted">Ne yaptırmak istediğini yaz, usta seni arasın.</p>
        <?php if ($formHata['talep']): ?><div class="flash flash-err"><?= e($formHata['talep']) ?></div><?php endif; ?>
        <form method="post" class="fform">
          <?= csrf_field() ?><input type="hidden" name="form" value="talep"><input class="hp" name="web" tabindex="-1" autocomplete="off">
          <label>Adınız<input name="ad" maxlength="80" required value="<?= e(post('form') === 'talep' ? post('ad') : '') ?>"></label>
          <label>Telefon<input name="telefon" type="tel" inputmode="tel" placeholder="05xx xxx xx xx" required value="<?= e(post('form') === 'talep' ? post('telefon') : '') ?>"></label>
          <label>İlçe<select name="ilce"><option value="">Seçin</option><?php foreach ($tumIlceler as $i): ?><option value="<?= $i['id'] ?>" <?= (int)post('ilce') === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['ad']) ?></option><?php endforeach; ?></select></label>
          <label>Ne yaptırmak istiyorsun?<textarea name="mesaj" rows="4" maxlength="1500" required placeholder="Örn: Salondaki priz çalışmıyor, sigorta atıyor."><?= e(post('form') === 'talep' ? post('mesaj') : '') ?></textarea></label>
          <label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span>Adımın ve telefonumun bu ustayla paylaşılmasını onaylıyorum. <a href="<?= url('gizlilik.php') ?>" target="_blank">Gizlilik</a></span></label>
          <button class="btn btn-dark">Talebi gönder</button>
        </form>
      </div>
      <div class="box tip"><b>💡 İpucu</b><p class="muted">Ustayla fiyatı ve işin kapsamını iş başlamadan netleştir. İş bitince yorum yazarak diğer Samsunlulara yardımcı ol.</p></div>
    </aside>
  </div>
</div></section>
<?php site_bitir();
