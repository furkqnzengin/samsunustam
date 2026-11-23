<?php
// Servis kabul 5. adım: servis işlemleri (kontrol listesi, işçilik/parça, yapılan işler, durum, tahsilat, fotoğraf)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const MAKS_SERVIS_FOTO = 20;

$id = (int)get('id');
if (!$id) redirect('usta/kabul-kart.php?yeni=1' . (get('sektor') ? '&sektor=' . urlencode(get('sektor')) : '')); // yeni kayıt artık adım adım açılır
$s = row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]);
if (!$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/serviste.php'); }

/** Formla birlikte gönderilen tahsilatı ekler. */
function tahsilat_ekle(int $sid): void
{
    $tutar = para_oku(post('o_tutar'));
    if ($tutar <= 0) { flash('Tahsilat için tutar yazmalısın.', 'err'); return; }
    $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('o_tarih')) ? post('o_tarih') : date('Y-m-d');
    q('INSERT INTO servis_odemeleri (servis_id, tutar, yontem, tarih, aciklama) VALUES (?,?,?,?,?)',
        [$sid, $tutar, isset(ODEME_YONTEMLERI[post('o_yontem')]) ? post('o_yontem') : 'nakit', $tarih, mb_substr(post('o_aciklama'), 0, 200) ?: null]);
    flash(para($tutar) . ' tahsilat eklendi.');
}

/** Formla birlikte gönderilen fotoğrafları yükler. */
function servis_foto_yukle(int $sid, int $ustaId): void
{
    $files = $_FILES['fotolar'] ?? null;
    if (!$files || !is_array($files['name'])) return;
    $mevcut = (int)val('SELECT COUNT(*) FROM servis_fotolari WHERE servis_id = ?', [$sid]);
    $etiket = in_array(post('f_etiket'), ['once', 'sonra', 'diger'], true) ? post('f_etiket') : 'diger';
    $n = 0;
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($mevcut + $n >= MAKS_SERVIS_FOTO) { flash('Bir kayda en fazla ' . MAKS_SERVIS_FOTO . ' fotoğraf eklenebilir.', 'err'); break; }
        try {
            $yol = resim_yukle(['name' => $name, 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]], 'servis/' . $ustaId);
            q('INSERT INTO servis_fotolari (servis_id, dosya, etiket) VALUES (?,?,?)', [$sid, $yol, $etiket]);
            $n++;
        } catch (RuntimeException $ex) {
            flash($name . ': ' . $ex->getMessage(), 'err');
        }
    }
    if ($n) flash("$n fotoğraf eklendi.");
}

$tanim = sektorler()[$s['sektor']] ?? sektorler()['yerinde'];
$kart = $s['kart_id'] ? row('SELECT * FROM kartlar WHERE id = ?', [$s['kart_id']]) : null;

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    $geri = fn() => redirect('usta/servis-kayit.php?id=' . $id);
    if ($islem === 'odeme_sil') {
        q('DELETE o FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE o.id = ? AND s.usta_id = ?', [(int)post('oid'), $uid]);
        flash('Ödeme silindi.', 'info');
        $geri();
    }
    if ($islem === 'foto_sil') {
        $f = row('SELECT f.* FROM servis_fotolari f JOIN servisler s ON s.id = f.servis_id WHERE f.id = ? AND s.usta_id = ?', [(int)post('fid'), $uid]);
        if ($f) { dosya_sil($f['dosya']); q('DELETE FROM servis_fotolari WHERE id = ?', [$f['id']]); flash('Fotoğraf silindi.', 'info'); }
        $geri();
    }
    if ($islem === 'sil') {
        foreach (rows('SELECT dosya FROM servis_fotolari WHERE servis_id = ?', [$id]) as $f) dosya_sil($f['dosya']);
        q('DELETE FROM servisler WHERE id = ?', [$id]);
        flash('Kayıt #' . $s['no'] . ' silindi.', 'info');
        redirect('usta/serviste.php');
    }
    // Kaydet (tahsilat/fotoğraf ekleme ve hızlı durum da formun tamamını kaydeder)
    $durum = isset(servis_durumlari()[post('durum')]) ? post('durum') : $s['durum'];
    $kontrol = array_values(array_intersect((array)($_POST['kontrol'] ?? []), $tanim['kontrol']));
    q('UPDATE servisler SET kontrol = ?, yapilan = ?, durum = ?, tahmini_teslim = ?, sonraki_bakim = ?, garanti = ?, iskonto = ?, notlar = ?, teknisyen = ?,
        teslim_tarihi = ' . ($durum === 'teslim' ? 'COALESCE(teslim_tarihi, NOW())' : 'teslim_tarihi') . ', guncelleme = NOW() WHERE id = ?', [
        json_encode($kontrol, JSON_UNESCAPED_UNICODE), mb_substr(post('yapilan'), 0, 3000) ?: null, $durum,
        preg_match('/^\d{4}-\d{2}-\d{2}$/', post('tahmini_teslim')) ? post('tahmini_teslim') : null,
        mb_substr(post('sonraki_bakim'), 0, 120) ?: null, mb_substr(post('garanti'), 0, 120) ?: null, para_oku(post('iskonto')),
        mb_substr(post('notlar'), 0, 3000) ?: null, mb_substr(post('teknisyen'), 0, 100) ?: null, $id,
    ]);
    kalemleri_kaydet($id, kalemleri_oku());
    flash($durum !== $s['durum'] ? 'Kaydedildi. Durum: ' . servis_durumlari()[$durum][0] : 'Kaydedildi.');
    $capa = '';
    if (post('odeme_ekle')) { tahsilat_ekle($id); $capa = '#tahsilat'; }
    if (post('foto_ekle')) { servis_foto_yukle($id, $uid); $capa = '#foto'; }
    elseif (!empty($_FILES['fotolar']['name'][0])) servis_foto_yukle($id, $uid);
    if (post('ileri')) redirect('usta/kabul-teslim.php?id=' . $id);
    redirect('usta/servis-kayit.php?id=' . $id . $capa);
}

$kalemler = rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$id]);
$odemeler = rows('SELECT * FROM servis_odemeleri WHERE servis_id = ? ORDER BY tarih, id', [$id]);
$fotolar = rows('SELECT * FROM servis_fotolari WHERE servis_id = ? ORDER BY etiket, id', [$id]);
$kontrolSecili = json_decode((string)$s['kontrol'], true) ?: [];
$h = servis_hesap($s, $kalemler, $odemeler);
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$wa = $s['musteri_tel'] ? wa_link($s['musteri_tel'], "Merhaba {$s['musteri_ad']}, #{$s['no']} numaralı {$s['baslik']} kaydınızın servis raporu: " . rapor_url($s) . "\n" . ($u['firma_unvan'] ?: $u['ad_soyad'])) : '';

panel_basla('usta', 'kabul', 'Servis İşlemleri', kabul_baslik($s, $kart, '') . ' · ' . servis_durum_pill($s['durum']));
?>
<?= kabul_sekmeleri($s, 'islem', $tanim) ?>
<div class="adimgovde">
<div class="actionbar" style="margin-bottom:16px">
  <a class="btn btn-main" href="servis-rapor.php?id=<?= $id ?>" target="_blank">📄 Servis raporu</a>
  <?php if ($wa): ?><a class="btn btn-soft" href="<?= e($wa) ?>" target="_blank" rel="noopener">💬 Raporu WhatsApp'tan gönder</a><?php endif; ?>
  <button type="button" class="btn btn-ghost" onclick="navigator.clipboard.writeText('<?= e(rapor_url($s)) ?>').then(()=>this.textContent='✓ Link kopyalandı')">🔗 Rapor linki</button>
  <div class="inline-form" style="margin-left:auto"><select class="inp" id="hizli_durum" aria-label="Durum"><?php foreach (servis_durumlari() as $k => [$ad]): ?><option value="<?= $k ?>" <?= $s['durum'] === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select></div>
</div>
<div class="stats three">
  <div class="stat"><div class="ic">🧾</div><div><small>Toplam</small><b><?= para($h['toplam']) ?></b></div></div>
  <div class="stat"><div class="ic">💵</div><div><small>Alınan</small><b><?= para($h['odenen']) ?></b></div></div>
  <div class="stat"><div class="ic" style="background:<?= $h['kalan'] > 0.009 ? 'var(--amber100)' : 'var(--g100)' ?>">⏳</div><div><small>Kalan</small><b><?= para(max(0, $h['kalan'])) ?></b><?= $h['kalan'] < -0.009 ? '<span class="down">Fazla ödeme: ' . para(-$h['kalan']) . '</span>' : '' ?></div></div>
</div>

<form method="post" enctype="multipart/form-data" id="sform">
  <?= csrf_field() ?>
  <div class="kutu"><h3>✅ Kontrol Listesi</h3><div class="ic">
    <div class="checks"><?php foreach ($tanim['kontrol'] as $c): ?><label class="cb"><input type="checkbox" name="kontrol[]" value="<?= e($c) ?>" <?= in_array($c, $kontrolSecili, true) ? 'checked' : '' ?>><span><?= e($c) ?></span></label><?php endforeach; ?></div>
  </div></div>
  <div class="kutu"><h3>🧾 İşçilik ve Parçalar</h3><div class="ic">
    <?= kalem_editoru($kalemler, $tanim, (int)$u['kdv_varsayilan'], (int)$s['kdv'], (float)$s['iskonto']) ?>
  </div></div>
  <div class="kutu"><h3>🛠️ Yapılan İşlemler ve Teslim</h3><div class="ic"><div class="fgrid">
    <label class="frow wide"><span>Yapılan işlemler / tespitler</span><textarea class="inp" name="yapilan" rows="3" placeholder="Teslim formunda ve raporda görünür"><?= e((string)$s['yapilan']) ?></textarea></label>
    <label class="frow"><span>Durum</span><select class="inp" name="durum"><?php foreach (servis_durumlari() as $k => [$ad]): ?><option value="<?= $k ?>" <?= $s['durum'] === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select></label>
    <label class="frow"><span>Teknisyen</span><?= personel_sec('teknisyen', $u, 'teknisyen', $s['teknisyen']) ?></label>
    <label class="frow"><span>Tahmini teslim</span><input class="inp" type="date" name="tahmini_teslim" value="<?= e((string)$s['tahmini_teslim']) ?>"></label>
    <?php if ($tanim['bakim']): ?><label class="frow"><span>Sonraki bakım</span><input class="inp" name="sonraki_bakim" maxlength="120" placeholder="<?= e($tanim['bakim']) ?>" value="<?= e((string)$s['sonraki_bakim']) ?>"></label><?php endif; ?>
    <label class="frow"><span>Garanti</span><input class="inp" name="garanti" maxlength="120" placeholder="Örn: İşçilik 6 ay" value="<?= e((string)$s['garanti']) ?>"></label>
    <label class="frow wide"><span>İç not <small class="muted">(rapora çıkmaz)</small></span><textarea class="inp" name="notlar" rows="2"><?= e((string)$s['notlar']) ?></textarea></label>
  </div></div>
  <div class="alt"><a class="btn btn-ghost" href="kabul-form.php?id=<?= $id ?>">← Kabul formu</a><span><button class="btn btn-ghost">Kaydet</button> <button class="btn btn-main" name="ileri" value="1">Teslim formu →</button></span></div></div>
</form>

<div class="row even">
  <div class="card" id="tahsilat">
    <h2>💵 Tahsilatlar</h2>
    <div class="odeme-ozet"><span>Toplam <b><?= para($h['toplam']) ?></b></span><span>Alınan <b><?= para($h['odenen']) ?></b></span><span>Kalan <b class="<?= $h['kalan'] > 0.009 ? 'down' : 'up' ?>"><?= para(max(0, $h['kalan'])) ?></b></span></div>
    <?php if ($h['toplam'] <= 0): ?><p class="notice n-amber" style="margin:10px 0 0">Bu kaydın tutarı 0 görünüyor. Yukarıdaki <b>İşçilik ve Parçalar</b> bölümüne fiyatları yaz; tahsilat eklediğinde fiyatlar da kaydedilir.</p><?php endif; ?>
    <?php if ($odemeler): ?><div class="list" style="margin-top:10px"><?php foreach ($odemeler as $o): ?>
      <div class="li"><div class="grow"><b><?= para($o['tutar']) ?></b> <small class="muted">· <?= ODEME_YONTEMLERI[$o['yontem']] ?> · <?= date('d.m.Y', strtotime($o['tarih'])) ?></small><?= $o['aciklama'] ? '<br><small class="muted">' . e($o['aciklama']) . '</small>' : '' ?></div>
        <form method="post" onsubmit="return confirm('Ödeme silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="odeme_sil"><input type="hidden" name="oid" value="<?= $o['id'] ?>"><button class="btn btn-red">Sil</button></form></div>
    <?php endforeach; ?></div><?php endif; ?>
    <div class="form" style="margin-top:14px">
      <label>Tutar (₺)<input class="inp num" form="sform" name="o_tutar" id="o_tutar" inputmode="decimal" placeholder="0,00" value="<?= $h['kalan'] > 0 ? e($fmt($h['kalan'])) : '' ?>" data-odenen="<?= e((string)$h['odenen']) ?>"></label>
      <label>Ödeme şekli<select class="inp" form="sform" name="o_yontem"><?php foreach (ODEME_YONTEMLERI as $k => $ad): ?><option value="<?= $k ?>"><?= $ad ?></option><?php endforeach; ?></select></label>
      <label>Tarih<input class="inp" type="date" form="sform" name="o_tarih" value="<?= date('Y-m-d') ?>"></label>
      <label>Açıklama<input class="inp" form="sform" name="o_aciklama" maxlength="200" placeholder="İsteğe bağlı"></label>
      <div class="full"><button class="btn btn-main" form="sform" name="odeme_ekle" value="1">+ Tahsilat ekle</button> <small class="muted">Formdaki değişiklikler de kaydedilir.</small></div>
    </div>
  </div>
  <div class="card" id="foto">
    <h2>📷 Fotoğraflar <span class="muted small"><?= count($fotolar) ?>/<?= MAKS_SERVIS_FOTO ?></span></h2>
    <div class="inline-form">
      <select class="inp" form="sform" name="f_etiket"><option value="once">İşlem öncesi</option><option value="sonra">İşlem sonrası</option><option value="diger">Diğer</option></select>
      <input class="inp grow" type="file" form="sform" name="fotolar[]" accept="image/*" multiple>
      <button class="btn btn-main" form="sform" name="foto_ekle" value="1">Yükle</button></div>
    <small class="muted">Telefondan açınca kamerayla direkt çekebilirsin. Fotoğraflar raporda da görünür.</small>
    <?php if ($fotolar): ?><div class="photos" style="margin-top:14px"><?php foreach ($fotolar as $f): ?>
      <div class="ph"><a href="<?= e(upload_url($f['dosya'])) ?>" target="_blank"><img src="<?= e(upload_url($f['dosya'])) ?>" alt="" loading="lazy"></a><span class="phtag"><?= ['once' => 'Önce', 'sonra' => 'Sonra', 'diger' => 'Diğer'][$f['etiket']] ?></span>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="foto_sil"><input type="hidden" name="fid" value="<?= $f['id'] ?>"><button class="btn btn-red" onclick="return confirm('Fotoğraf silinsin mi?')">Sil</button></form></div>
    <?php endforeach; ?></div><?php endif; ?>
  </div>
</div>
<form method="post" class="card danger inline-form" onsubmit="return confirm('Bu kayıt, ödemeleri ve fotoğraflarıyla birlikte kalıcı olarak silinecek. Emin misin?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><span class="muted grow">Kaydı tamamen silmek istersen:</span><button class="btn btn-red">Kaydı sil</button></form>
</div>
<?= kalem_js() ?>
<script>
(function () {
  const form = document.getElementById('sform'), oTutar = document.getElementById('o_tutar');
  let oElle = false, kirli = false, gonderiliyor = false;
  const yaz = n => n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  if (oTutar) oTutar.addEventListener('input', () => oElle = true);
  document.addEventListener('kalemtoplam', e => { if (oTutar && !oElle) { const k = e.detail - parseFloat(oTutar.dataset.odenen || '0'); oTutar.value = k > 0.009 ? yaz(k) : ''; } });
  document.addEventListener('input', e => { if (e.target.form === form) kirli = true; });
  document.addEventListener('change', e => { if (e.target.form === form) kirli = true; });
  form.addEventListener('submit', () => gonderiliyor = true);
  window.addEventListener('beforeunload', e => { if (kirli && !gonderiliyor) { e.preventDefault(); e.returnValue = ''; } });
  document.querySelectorAll('form:not(#sform)').forEach(f => f.addEventListener('submit', ev => {
    if (kirli && !confirm('Kaydedilmemiş değişikliklerin var ve kaybolacak. Yine de devam edilsin mi?')) ev.preventDefault(); else gonderiliyor = true;
  }));
  const hd = document.getElementById('hizli_durum');
  hd.addEventListener('change', () => { form.durum.value = hd.value; gonderiliyor = true; form.requestSubmit ? form.requestSubmit() : form.submit(); });
})();
</script>
<?php panel_bitir();
