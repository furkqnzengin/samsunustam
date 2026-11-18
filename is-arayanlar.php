<?php
// İş arayanlar listesi. İletişim bilgileri sadece giriş yapmış ustalara ve yöneticiye görünür.
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';
require __DIR__ . '/inc/is.php';

$k = kullanici();
$iletisim = (bool)$k; // usta veya yönetici

$hizmetler = rows('SELECT h.*, k.ad AS kat FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id ORDER BY k.sira, h.sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$where = ["a.durum = 'yayinda'", 'a.bitis >= CURDATE()'];
$params = [];
$hf = (int)get('h');
$if = (int)get('i');
$cf = get('c');
$qf = mb_substr(get('q'), 0, 60);
if ($hf) { $where[] = 'a.hizmet_id = ?'; $params[] = $hf; }
if ($if) { $where[] = '(a.ilce_id = ? OR a.ilce_id IS NULL)'; $params[] = $if; }
if (in_array($cf, CALISMA_SEKILLERI, true)) { $where[] = 'a.calisma = ?'; $params[] = $cf; }
if ($qf !== '') { $where[] = '(a.meslek LIKE ? OR a.aciklama LIKE ?)'; array_push($params, "%$qf%", "%$qf%"); }
$liste = rows('SELECT a.*, h.ad AS hizmet_ad, i.ad AS ilce_ad FROM is_arayanlar a LEFT JOIN hizmetler h ON h.id = a.hizmet_id LEFT JOIN ilceler i ON i.id = a.ilce_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY a.olusturma DESC LIMIT 150', $params);
$ilanSay = ilan_sayisi();

site_basla('İş arayanlar', "Samsun'da iş arayan kalfa, çırak ve teknik elemanlar. Eleman arıyorsan iş arayanlara göz at.");
?>
<section class="pagehead"><div class="wrap">
  <span class="num">KARİYER</span>
  <h1>Samsun'da iş arayanlar</h1>
  <p class="muted">Eleman arayan ustalar için: kalfa, çırak ve teknik eleman adayları.</p>
  <div class="career-switch"><a href="<?= url('isler.php') ?>">💼 İş ilanları <b><?= $ilanSay ?></b></a><a class="on" href="<?= url('is-arayanlar.php') ?>">🙋 İş arayanlar <b><?= count($liste) ?></b></a><a class="cta" href="<?= url('is-ara.php') ?>">+ İş arıyorum ilanı ver</a></div>
  <?php if (!$iletisim): ?><div class="flash flash-info" style="margin-top:16px">🔒 Adayların telefon numaraları sadece kayıtlı ustalara görünür. <a class="link" href="<?= url('giris.php') ?>">Giriş yap</a> ya da <a class="link" href="<?= url('kayit.php') ?>">ücretsiz usta kaydı</a> oluştur.</div><?php endif; ?>
  <form class="filters" method="get">
    <select name="h"><option value="">Tüm alanlar</option><?php $kat = ''; foreach ($hizmetler as $h): if ($h['kat'] !== $kat): echo $kat ? '</optgroup>' : ''; $kat = $h['kat']; ?><optgroup label="<?= e($kat) ?>"><?php endif; ?><option value="<?= $h['id'] ?>" <?= $hf === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['ad']) ?></option><?php endforeach; ?></optgroup></select>
    <select name="i"><option value="">Tüm ilçeler</option><?php foreach ($ilceler as $i): ?><option value="<?= $i['id'] ?>" <?= $if === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['ad']) ?></option><?php endforeach; ?></select>
    <input name="q" placeholder="Pozisyon ara (örn: kalfa)" value="<?= e($qf) ?>">
    <button class="btn btn-dark">Filtrele</button>
  </form>
</div></section>

<section style="padding-top:8px"><div class="wrap">
  <?php if ($liste): ?>
    <div class="jobs">
      <?php foreach ($liste as $a): ?>
        <div class="job seeker">
          <div class="jtop"><div class="av"><?= e(initials($a['ad'])) ?></div><div><b><?= e($a['meslek']) ?></b><small class="muted" style="display:block"><?= e($iletisim ? $a['ad'] : mb_substr($a['ad'], 0, 1) . '*** ' . mb_substr((string)(explode(' ', trim($a['ad']))[1] ?? ''), 0, 1) . '.') ?></small></div></div>
          <div class="jmeta"><span>📍 <?= e($a['ilce_ad'] ?? 'Samsun geneli') ?></span><span>🕒 <?= e($a['calisma']) ?></span><span>🎓 <?= e($a['tecrube']) ?></span><?php if ($a['hizmet_ad']): ?><span>🔧 <?= e($a['hizmet_ad']) ?></span><?php endif; ?></div>
          <?php if ($a['aciklama']): ?><p class="muted pre"><?= e(mb_strimwidth($a['aciklama'], 0, 260, '…')) ?></p><?php endif; ?>
          <div class="jfoot">
            <?php if ($iletisim): ?>
              <span class="pacts" style="margin:0"><a class="btn btn-dark" href="tel:<?= e($a['telefon']) ?>">📞 <?= e(tel_goster($a['telefon'])) ?></a><?php if ($a['whatsapp']): ?><a class="btn btn-wa" href="<?= e(wa_link($a['telefon'], 'Merhaba ' . $a['ad'] . ', Samsun Ustam\'daki iş arıyorum ilanınız için yazıyorum.')) ?>" target="_blank" rel="noopener">💬 WhatsApp</a><?php endif; ?></span>
            <?php else: ?>
              <span class="muted">📞 <?= e(tel_maskele($a['telefon'])) ?> · <a class="link" href="<?= url('giris.php') ?>">Görmek için giriş yap</a></span>
            <?php endif; ?>
            <small class="muted"><?= zaman_once($a['olusturma']) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty"><div class="big">🙋</div><h3>Bu aramaya uygun iş arayan yok.</h3><p>İş arıyorsan ilk ilanı sen ver.</p><a class="btn btn-dark" href="<?= url('is-ara.php') ?>">İş arıyorum ilanı ver →</a></div>
  <?php endif; ?>
</div></section>
<?php site_bitir();
