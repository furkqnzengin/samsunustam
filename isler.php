<?php
// Ustaların eleman ilanları: liste (isler.php) ve ilan detayı + başvuru (isler.php?id=)
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';
require __DIR__ . '/inc/is.php';

$id = (int)get('id');

if ($id) {
    $l = row(ilan_sql('l.id = ?'), [$id]);
    if (!$l) {
        http_response_code(404);
        site_basla('İlan bulunamadı');
        echo '<section><div class="wrap"><div class="empty"><div class="big">📭</div><h3>Bu ilan yayında değil.</h3><p>İlan kaldırılmış ya da süresi dolmuş olabilir.</p><a class="btn btn-dark" href="' . url('isler.php') . '">Diğer ilanlara bak →</a></div></div></section>';
        site_bitir();
        exit;
    }
    $hata = '';
    if (is_post() && $l['basvuru_al']) {
        csrf_check();
        if (post('web') !== '') redirect('isler.php?id=' . $id);
        $ad = mb_substr(post('ad'), 0, 80);
        $tel = post('telefon');
        $mesaj = mb_substr(post('mesaj'), 0, 1500);
        if (mb_strlen($ad) < 3 || !tel_gecerli($tel)) $hata = 'Adını ve geçerli bir telefon numarası yaz.';
        elseif (!post('kvkk')) $hata = 'Bilgilerinin ustayla paylaşılmasını onaylaman gerekiyor.';
        elseif (val('SELECT id FROM ilan_basvurulari WHERE ilan_id = ? AND telefon = ?', [$id, tel_temizle($tel)])) $hata = 'Bu ilana bu telefon numarasıyla zaten başvurdun.';
        elseif (!hiz_siniri('basvuru', 6, 3600)) $hata = 'Çok fazla başvuru yaptın, lütfen daha sonra tekrar dene.';
        else {
            q('INSERT INTO ilan_basvurulari (ilan_id, ad, telefon, mesaj, ip) VALUES (?,?,?,?,?)', [$id, $ad, tel_temizle($tel), $mesaj ?: null, ip()]);
            flash('Başvurun ustaya iletildi. Uygun görürse seni arayacak. Kolay gelsin!');
            redirect('isler.php?id=' . $id);
        }
    }
    if (empty($_SESSION['ilan_gor'][$id])) {
        q('UPDATE ilanlar SET goruntulenme = goruntulenme + 1 WHERE id = ?', [$id]);
        $_SESSION['ilan_gor'][$id] = 1;
    }
    site_basla($l['baslik'] . ' – İş ilanı', $l['ad_soyad'] . ' eleman arıyor: ' . $l['baslik'] . ', ' . ($l['ilce_ad'] ?? 'Samsun') . '. ' . $l['calisma'] . '.');
    ?>
<section class="profile"><div class="wrap">
  <a class="link" href="<?= url('isler.php') ?>">← Tüm iş ilanları</a>
  <div class="phead" style="margin-top:14px">
    <div class="pav">💼</div>
    <div class="pinfo">
      <div class="badges"><span class="tag"><?= e($l['calisma']) ?></span><span class="tag"><?= e($l['tecrube']) ?></span><?php if ($l['ucret']): ?><span class="tag y">💰 <?= e($l['ucret']) ?></span><?php endif; ?></div>
      <h1><?= e($l['baslik']) ?></h1>
      <p class="pjob">📍 <?= e($l['ilce_ad'] ?? 'Samsun') ?><?= $l['hizmet_ad'] ? ' · ' . e($l['hizmet_ad']) : '' ?></p>
      <p class="muted">İlan tarihi: <?= date('d.m.Y', strtotime($l['olusturma'])) ?> · Son başvuru: <?= date('d.m.Y', strtotime($l['bitis'])) ?></p>
    </div>
  </div>
  <div class="pgrid" style="margin-top:24px">
    <div class="pmain">
      <div class="box"><h2>İş tanımı</h2><p class="pre"><?= e($l['aciklama']) ?></p></div>
      <div class="box"><h2>İlanı veren</h2>
        <a class="employer" href="<?= url('usta.php?u=' . $l['usta_slug']) ?>">
          <?= $l['foto'] ? '<img class="av" src="' . e(upload_url($l['foto'])) . '" alt="">' : '<div class="av">' . e(initials($l['ad_soyad'])) . '</div>' ?>
          <div><b><?= e($l['ad_soyad']) ?></b><small class="muted" style="display:block"><?= e($l['usta_meslek']) ?><?= $l['usta_adres'] ? ' · ' . e($l['usta_adres']) : '' ?></small></div><span class="link">Profili gör →</span>
        </a>
        <?php if ($l['tel_goster']): ?><div class="pacts" style="margin:16px 0 0">
          <a class="btn btn-dark" href="tel:<?= e(tel_temizle($l['usta_tel'])) ?>">📞 <?= e(tel_goster($l['usta_tel'])) ?></a>
          <?php if ($l['usta_wa']): ?><a class="btn btn-wa" href="<?= e(wa_link($l['usta_wa'], 'Merhaba, Samsun Ustam\'daki "' . $l['baslik'] . '" ilanınız için yazıyorum.')) ?>" target="_blank" rel="noopener">💬 WhatsApp</a><?php endif; ?>
        </div><?php endif; ?>
      </div>
    </div>
    <aside class="pside">
      <?php if ($l['basvuru_al']): ?>
      <div class="box" id="basvur"><h2>Başvur</h2><p class="muted">Bilgilerini bırak, usta seni arasın.</p>
        <?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
        <form method="post" class="fform">
          <?= csrf_field() ?><input class="hp" name="web" tabindex="-1" autocomplete="off">
          <label>Ad soyad<input name="ad" required maxlength="80" value="<?= e(post('ad')) ?>"></label>
          <label>Telefon<input name="telefon" type="tel" inputmode="tel" required placeholder="05xx xxx xx xx" value="<?= e(post('telefon')) ?>"></label>
          <label>Kendinden kısaca bahset<textarea name="mesaj" rows="4" maxlength="1500" placeholder="Örn: 3 yıl oto elektrik kalfalığı yaptım, ehliyetim var."><?= e(post('mesaj')) ?></textarea></label>
          <label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span>Adımın ve telefonumun bu ilanı veren ustayla paylaşılmasını onaylıyorum. <a class="link" href="<?= url('gizlilik.php') ?>" target="_blank">Gizlilik</a></span></label>
          <button class="btn btn-dark">Başvuruyu gönder</button>
        </form>
      </div>
      <?php endif; ?>
      <div class="box tip"><b>💡 İş arıyor musun?</b><p class="muted">Ücretsiz iş arıyorum ilanı ver, ustalar seni bulsun.</p><a class="btn btn-soft" href="<?= url('is-ara.php') ?>" style="margin-top:8px">İş arıyorum ilanı ver →</a></div>
    </aside>
  </div>
</div></section>
<?php
    site_bitir();
    exit;
}

// ---- Liste ----
$hizmetler = rows('SELECT h.*, k.ad AS kat FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id ORDER BY k.sira, h.sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$where = ['1=1'];
$params = [];
$hf = (int)get('h');
$if = (int)get('i');
$cf = get('c');
$qf = mb_substr(get('q'), 0, 60);
if ($hf) { $where[] = 'l.hizmet_id = ?'; $params[] = $hf; }
if ($if) { $where[] = 'l.ilce_id = ?'; $params[] = $if; }
if (in_array($cf, CALISMA_SEKILLERI, true)) { $where[] = 'l.calisma = ?'; $params[] = $cf; }
if ($qf !== '') { $where[] = '(l.baslik LIKE ? OR l.aciklama LIKE ? OR h.ad LIKE ?)'; array_push($params, "%$qf%", "%$qf%", "%$qf%"); }
$ilanlar = rows(ilan_sql(implode(' AND ', $where)) . ' ORDER BY l.olusturma DESC LIMIT 100', $params);
$arayanSay = (int)val("SELECT COUNT(*) FROM is_arayanlar WHERE durum = 'yayinda' AND bitis >= CURDATE()");

site_basla('İş ilanları', "Samsun'daki ustaların eleman ilanları: kalfa, çırak, usta ve teknik eleman iş ilanları.");
?>
<section class="pagehead"><div class="wrap">
  <span class="num">KARİYER</span>
  <h1>Samsun'da iş ilanları</h1>
  <p class="muted">Ustalar eleman arıyor: kalfa, çırak, usta ve teknik eleman ilanları.</p>
  <div class="career-switch"><a class="on" href="<?= url('isler.php') ?>">💼 İş ilanları <b><?= count($ilanlar) ?></b></a><a href="<?= url('is-arayanlar.php') ?>">🙋 İş arayanlar <b><?= $arayanSay ?></b></a><a class="cta" href="<?= url('is-ara.php') ?>">+ İş arıyorum ilanı ver</a></div>
  <form class="filters" method="get">
    <select name="h"><option value="">Tüm meslekler</option><?php $kat = ''; foreach ($hizmetler as $h): if ($h['kat'] !== $kat): echo $kat ? '</optgroup>' : ''; $kat = $h['kat']; ?><optgroup label="<?= e($kat) ?>"><?php endif; ?><option value="<?= $h['id'] ?>" <?= $hf === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['ad']) ?></option><?php endforeach; ?></optgroup></select>
    <select name="i"><option value="">Tüm ilçeler</option><?php foreach ($ilceler as $i): ?><option value="<?= $i['id'] ?>" <?= $if === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['ad']) ?></option><?php endforeach; ?></select>
    <select name="c"><option value="">Tüm çalışma şekilleri</option><?php foreach (CALISMA_SEKILLERI as $c): ?><option <?= $cf === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <button class="btn btn-dark">Filtrele</button>
  </form>
</div></section>

<section style="padding-top:8px"><div class="wrap">
  <?php if ($ilanlar): ?>
    <div class="jobs">
      <?php foreach ($ilanlar as $l): ?>
        <a class="job" href="<?= url('isler.php?id=' . $l['id']) ?>">
          <div class="jtop"><b><?= e($l['baslik']) ?></b><?php if ($l['ucret']): ?><span class="tag y">💰 <?= e($l['ucret']) ?></span><?php endif; ?></div>
          <div class="jmeta"><span>📍 <?= e($l['ilce_ad'] ?? 'Samsun') ?></span><span>🕒 <?= e($l['calisma']) ?></span><span>🎓 <?= e($l['tecrube']) ?></span></div>
          <p class="muted"><?= e(mb_strimwidth($l['aciklama'], 0, 140, '…')) ?></p>
          <div class="jfoot"><span><?= e($l['ad_soyad']) ?> · <?= e($l['usta_meslek']) ?></span><small class="muted"><?= zaman_once($l['olusturma']) ?></small></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty"><div class="big">💼</div><h3>Şu an bu aramaya uygun iş ilanı yok.</h3><p>İş arıyorum ilanı ver; ustalar eleman ararken seni görsün.</p><a class="btn btn-dark" href="<?= url('is-ara.php') ?>">İş arıyorum ilanı ver →</a></div>
  <?php endif; ?>
  <div class="join" style="margin-top:36px">
    <div><h2>Usta mısın? Eleman mı arıyorsun?</h2><p>Usta panelinden ücretsiz eleman ilanı aç, başvurular doğrudan sana gelsin. İş arayanlar listesine de göz atabilirsin.</p><a class="btn btn-dark" href="<?= url(kullanici() && kullanici()['rol'] === 'usta' ? 'usta/ilanlar.php' : 'kayit.php') ?>">Eleman ilanı ver →</a></div>
    <ul><li>📢 Ücretsiz ilan</li><li>📥 Başvurular paneline gelir</li><li>🙋 İş arayanları gör</li></ul>
  </div>
</div></section>
<?php site_bitir();
