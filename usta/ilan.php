<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/is.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$id = (int)get('id');
$l = $id ? row('SELECT * FROM ilanlar WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$l) { flash('İlan bulunamadı.', 'err'); redirect('usta/ilanlar.php'); }

$hizmetler = rows('SELECT h.*, k.ad AS kat FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id ORDER BY k.sira, h.sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');
$hata = '';

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    if ($l && $islem === 'basvuru') {
        $d = post('durum');
        if (in_array($d, ['yeni', 'goruldu', 'olumlu', 'olumsuz'], true)) {
            q('UPDATE ilan_basvurulari SET durum = ? WHERE id = ? AND ilan_id = ?', [$d, (int)post('bid'), $id]);
        }
        redirect('usta/ilan.php?id=' . $id . '#b' . (int)post('bid'));
    }
    if ($l && $islem === 'durum') {
        $d = post('durum');
        if (in_array($d, ['aktif', 'pasif', 'doldu'], true) && $l['durum'] !== 'gizli') {
            $bitis = $d === 'aktif' && $l['bitis'] < date('Y-m-d') ? gun_sonra(ILAN_SURE_GUN) : $l['bitis'];
            q('UPDATE ilanlar SET durum = ?, bitis = ? WHERE id = ?', [$d, $bitis, $id]);
            flash(['aktif' => 'İlan yayında.', 'pasif' => 'İlan durduruldu.', 'doldu' => 'İlan "pozisyon doldu" olarak kapatıldı.'][$d]);
        }
        redirect('usta/ilan.php?id=' . $id);
    }
    if ($l && $islem === 'uzat') {
        q('UPDATE ilanlar SET bitis = ? WHERE id = ?', [gun_sonra(ILAN_SURE_GUN), $id]);
        flash('İlan süresi ' . ILAN_SURE_GUN . ' gün uzatıldı.');
        redirect('usta/ilan.php?id=' . $id);
    }
    if ($l && $islem === 'sil') {
        q('DELETE FROM ilanlar WHERE id = ?', [$id]);
        flash('İlan silindi.', 'info');
        redirect('usta/ilanlar.php');
    }

    // Kaydet
    $baslik = mb_substr(post('baslik'), 0, 120);
    $hizmetId = (int)post('hizmet') ?: null;
    $ilceId = (int)post('ilce') ?: null;
    $calisma = post('calisma');
    $tecrube = post('tecrube');
    $aciklama = mb_substr(post('aciklama'), 0, 3000);
    if (mb_strlen($baslik) < 3) $hata = 'İlan başlığını yaz (örn: Oto elektrik kalfası aranıyor).';
    elseif (!in_array($calisma, CALISMA_SEKILLERI, true) || !in_array($tecrube, TECRUBE_SEVIYELERI, true)) $hata = 'Çalışma şekli ve tecrübe seç.';
    elseif (mb_strlen($aciklama) < 20) $hata = 'İş tanımını biraz daha açık yaz (en az 20 karakter).';
    elseif (!post('tel_goster') && !post('basvuru_al')) $hata = 'Adaylar sana ulaşabilsin: telefonunu göster ya da başvuru al seçeneğinden en az birini işaretle.';
    elseif (!$l && (int)val("SELECT COUNT(*) FROM ilanlar WHERE usta_id = ? AND olusturma > NOW() - INTERVAL 1 DAY", [$uid]) >= 5) $hata = 'Bir günde en fazla 5 ilan açabilirsin.';
    else {
        if ($hizmetId && !val('SELECT id FROM hizmetler WHERE id = ?', [$hizmetId])) $hizmetId = null;
        if ($ilceId && !val('SELECT id FROM ilceler WHERE id = ?', [$ilceId])) $ilceId = null;
        $vals = [$baslik, $hizmetId, $ilceId, $calisma, $tecrube, mb_substr(post('ucret'), 0, 80) ?: null, $aciklama, post('tel_goster') ? 1 : 0, post('basvuru_al') ? 1 : 0];
        if ($l) {
            q('UPDATE ilanlar SET baslik=?, hizmet_id=?, ilce_id=?, calisma=?, tecrube=?, ucret=?, aciklama=?, tel_goster=?, basvuru_al=? WHERE id=?', [...$vals, $id]);
            flash('İlan kaydedildi.');
        } else {
            q('INSERT INTO ilanlar (baslik, hizmet_id, ilce_id, calisma, tecrube, ucret, aciklama, tel_goster, basvuru_al, usta_id, bitis) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [...$vals, $uid, gun_sonra(ILAN_SURE_GUN)]);
            $id = (int)db()->lastInsertId();
            flash($u['durum'] === 'aktif' ? 'İlanın yayında! ' . ILAN_SURE_GUN . ' gün boyunca iş ilanlarında görünecek.' : 'İlanın kaydedildi. Profilin onaylanınca yayına çıkacak.');
        }
        redirect('usta/ilan.php?id=' . $id);
    }
}

$v = fn(string $k, string $d = '') => e(is_post() ? post($k) : (string)($l[$k] ?? $d));
$sec = fn(string $pk, string $lk, $val) => ((is_post() ? post($pk) : (string)($l[$lk] ?? '')) === (string)$val) ? 'selected' : '';
$chk = fn(string $k) => (is_post() ? post($k) : ($l[$k] ?? 1)) ? 'checked' : '';
$basvurular = $l ? rows('SELECT * FROM ilan_basvurulari WHERE ilan_id = ? ORDER BY durum = \'yeni\' DESC, id DESC', [$id]) : [];

panel_basla('usta', 'ilanlar.php', $l ? $l['baslik'] : 'Yeni eleman ilanı', $l ? ilan_durum_pill($l['bitis'] < date('Y-m-d') && $l['durum'] === 'aktif' ? 'pasif' : $l['durum']) . ' · Bitiş: ' . date('d.m.Y', strtotime($l['bitis'])) . ' · ' . (int)$l['goruntulenme'] . ' görüntülenme' : 'Aradığın elemanı tarif et.');
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>

<?php if ($l): ?>
<div class="card actionbar">
  <?php if ($l['durum'] === 'aktif' && $u['durum'] === 'aktif' && $l['bitis'] >= date('Y-m-d')): ?><a class="btn btn-soft" href="../isler.php?id=<?= $id ?>" target="_blank">İlanı sitede gör ↗</a><?php endif; ?>
  <?php if ($l['durum'] === 'gizli'): ?><span class="pill p-red">Bu ilan yönetici tarafından yayından kaldırıldı.</span>
  <?php else: foreach (['aktif' => ['▶ Yayına al', 'btn-main'], 'pasif' => ['⏸ Durdur', 'btn-ghost'], 'doldu' => ['✓ Pozisyon doldu', 'btn-ghost']] as $d => [$et, $cl]) if ($l['durum'] !== $d || ($d === 'aktif' && $l['bitis'] < date('Y-m-d'))): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="durum"><input type="hidden" name="durum" value="<?= $d ?>"><button class="btn <?= $cl ?>"><?= $et ?></button></form>
  <?php endif; endif; ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="uzat"><button class="btn btn-ghost">⏳ <?= ILAN_SURE_GUN ?> gün uzat</button></form>
</div>

<div class="card" id="basvurular">
  <h2>📥 Başvurular <span class="muted small"><?= count($basvurular) ?></span></h2>
  <?php if ($basvurular): ?><div class="list">
    <?php foreach ($basvurular as $b): ?>
      <div class="li" id="b<?= $b['id'] ?>"><div class="av sm"><?= e(initials($b['ad'])) ?></div>
        <div class="grow"><b><?= e($b['ad']) ?></b> <?= ilan_durum_pill($b['durum']) ?><br><small>📞 <?= e(tel_goster($b['telefon'])) ?> · <?= zaman_once($b['olusturma']) ?></small>
          <?php if ($b['mesaj']): ?><p class="pre"><?= e($b['mesaj']) ?></p><?php endif; ?>
          <div class="acts wrapacts">
            <a class="btn btn-main" href="tel:<?= e($b['telefon']) ?>">📞 Ara</a>
            <a class="btn btn-soft" href="<?= e(wa_link($b['telefon'], 'Merhaba ' . $b['ad'] . ', Samsun Ustam\'daki "' . $l['baslik'] . '" ilanıma başvurunuz için yazıyorum. ' . $u['ad_soyad'])) ?>" target="_blank" rel="noopener">💬 WhatsApp</a>
            <?php foreach (['olumlu' => '✓ Görüşülecek', 'olumsuz' => '✕ Uygun değil', 'goruldu' => 'Görüldü'] as $d => $et) if ($b['durum'] !== $d): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="basvuru"><input type="hidden" name="bid" value="<?= $b['id'] ?>"><input type="hidden" name="durum" value="<?= $d ?>"><button class="btn btn-ghost"><?= $et ?></button></form>
            <?php endif; ?>
          </div></div></div>
    <?php endforeach; ?>
  </div><?php else: ?><p class="muted">Henüz başvuru yok. <a class="link" href="../is-arayanlar.php" target="_blank">İş arayanlara göz atıp</a> adaylara kendin de ulaşabilirsin.</p><?php endif; ?>
</div>
<?php endif; ?>

<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="islem" value="kaydet">
  <h2><?= $l ? 'İlanı düzenle' : 'İlan bilgileri' ?></h2>
  <div class="form">
    <label class="full">İlan başlığı *<input class="inp" name="baslik" required maxlength="120" placeholder="Örn: Oto elektrik kalfası aranıyor" value="<?= $v('baslik') ?>"></label>
    <label>Meslek / alan<select class="inp" name="hizmet"><option value="">Seçin</option><?php $kat = ''; foreach ($hizmetler as $h): if ($h['kat'] !== $kat): echo $kat ? '</optgroup>' : ''; $kat = $h['kat']; ?><optgroup label="<?= e($kat) ?>"><?php endif; ?><option value="<?= $h['id'] ?>" <?= $sec('hizmet', 'hizmet_id', $h['id']) ?>><?= e($h['ad']) ?></option><?php endforeach; ?></optgroup></select></label>
    <label>Çalışma yeri (ilçe)<select class="inp" name="ilce"><option value="">Seçin</option><?php foreach ($ilceler as $i): ?><option value="<?= $i['id'] ?>" <?= $sec('ilce', 'ilce_id', $i['id']) ?>><?= e($i['ad']) ?></option><?php endforeach; ?></select></label>
    <label>Çalışma şekli *<select class="inp" name="calisma" required><option value="">Seçin</option><?php foreach (CALISMA_SEKILLERI as $c): ?><option <?= $sec('calisma', 'calisma', $c) ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
    <label>Tecrübe *<select class="inp" name="tecrube" required><option value="">Seçin</option><?php foreach (TECRUBE_SEVIYELERI as $t): ?><option <?= $sec('tecrube', 'tecrube', $t) ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
    <label class="full">Ücret <small class="muted">(isteğe bağlı)</small><input class="inp" name="ucret" maxlength="80" placeholder="Örn: Asgari ücret + SGK, Günlük 1.500 ₺, Görüşülür" value="<?= $v('ucret') ?>"></label>
    <label class="full">İş tanımı *<textarea class="inp" name="aciklama" rows="6" required maxlength="3000" placeholder="Ne iş yapılacak, çalışma saatleri, aranan özellikler (ehliyet, belge), yemek/yol, SGK vb."><?= $v('aciklama') ?></textarea></label>
    <div class="toggles full">
      <label class="chk"><input type="checkbox" name="tel_goster" value="1" <?= $chk('tel_goster') ?>> İlanda telefonumu ve WhatsApp numaramı göster</label>
      <label class="chk"><input type="checkbox" name="basvuru_al" value="1" <?= $chk('basvuru_al') ?>> Site üzerinden başvuru al (başvurular bu sayfaya gelir)</label>
    </div>
  </div>
  <div class="formfoot"><a class="btn btn-ghost" href="ilanlar.php">Vazgeç</a>&nbsp;<button class="btn btn-main"><?= $l ? 'Kaydet' : 'İlanı yayınla' ?></button></div>
</form>

<?php if ($l): ?>
<form method="post" class="card danger inline-form" onsubmit="return confirm('İlan ve başvuruları silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><span class="muted grow">İlanı tamamen silmek istersen:</span><button class="btn btn-red">İlanı sil</button></form>
<?php endif; ?>
<?php panel_bitir();
