<?php
// Servis kabul 3. adım: servis talepleri (yeni kayıtta servis burada oluşturulur)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const MAKS_FOTO_TALEP = 20;

$id = (int)get('id');
$s = $id ? row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/serviste.php'); }
$taslak = kabul_taslak();
$kart = $s ? row('SELECT * FROM kartlar WHERE id = ?', [(int)$s['kart_id']]) : ($taslak['kart_id'] ? row('SELECT * FROM kartlar WHERE id = ? AND usta_id = ?', [(int)$taslak['kart_id'], $uid]) : null);
$musteri = $s ? row('SELECT * FROM musteriler WHERE id = ?', [(int)$s['musteri_id']]) : ($taslak['musteri_id'] ? row('SELECT * FROM musteriler WHERE id = ? AND usta_id = ?', [(int)$taslak['musteri_id'], $uid]) : null);
if (!$s && !$kart) { flash('Önce bilgileri girin.', 'info'); redirect('usta/kabul-kart.php'); }
if (!$s && !$musteri) { flash('Önce müşteri bilgilerini girin.', 'info'); redirect('usta/kabul-musteri.php'); }
$sektor = $kart['sektor'] ?? $s['sektor'];
$tanim = sektorler()[$sektor];
$sv = $tanim['servis'];
$hata = '';

if (is_post()) {
    csrf_check();
    $giris = post('giris_tarihi');
    $giris = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $giris) ? str_replace('T', ' ', $giris) . ':00' : date('Y-m-d H:i:s');
    $sigorta = array_filter(['sirket' => mb_substr(post('sg_sirket'), 0, 80), 'dosya' => mb_substr(post('sg_dosya'), 0, 60),
        'eksper' => mb_substr(post('sg_eksper'), 0, 80), 'tarih' => preg_match('/^\d{4}-\d{2}-\d{2}$/', post('sg_tarih')) ? post('sg_tarih') : ''], 'strlen');
    $hasar = json_decode(post('ekspertiz') ?: '{}', true);
    $hasar = is_array($hasar) ? array_intersect_key(array_filter($hasar, fn($v) => isset(HASAR_TURLERI[$v])), EKSPERTIZ_PARCALAR) : [];
    $getiren = trim(mb_substr(post('getiren_ad'), 0, 100) . (post('getiren_tel') !== '' ? ' · ' . tel_goster(tel_temizle(post('getiren_tel'))) : ''), ' ·');
    $vals = [
        'giris_tarihi' => $giris,
        'danisman' => mb_substr(post('danisman'), 0, 100) ?: null,
        'teknisyen' => mb_substr(post('teknisyen'), 0, 100) ?: null,
        'getiren' => $getiren ?: null,
        'km' => !empty($sv['km']) && post('km') !== '' ? max(0, (int)preg_replace('/\D/', '', post('km'))) : ($s['km'] ?? null),
        'yakit' => !empty($sv['yakit']) ? max(0, min(100, (int)post('yakit'))) : ($s['yakit'] ?? null),
        'ekspertiz' => !empty($sv['ekspertiz']) ? json_encode((object)$hasar, JSON_UNESCAPED_UNICODE) : ($s['ekspertiz'] ?? null),
        'sigorta' => !empty($sv['sigorta']) ? ($sigorta ? json_encode($sigorta, JSON_UNESCAPED_UNICODE) : null) : ($s['sigorta'] ?? null),
        'sikayet' => mb_substr(post('sikayet'), 0, 3000) ?: null,
        'musteri_notu' => mb_substr(post('musteri_notu'), 0, 3000) ?: null,
        'tahmini_teslim' => preg_match('/^\d{4}-\d{2}-\d{2}$/', post('tahmini_teslim')) ? post('tahmini_teslim') : null,
        'etiket' => mb_substr(post('etiket'), 0, 60) ?: null,
        'iskonto' => para_oku(post('iskonto')),
    ];
    $kalemler = kalemleri_oku();
    db()->beginTransaction();
    if ($s) {
        if (post('hazir')) $vals['durum'] = 'hazir';
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($vals)));
        q("UPDATE servisler SET $set, guncelleme = NOW() WHERE id = ?", [...array_values($vals), $id]);
    } else {
        $no = (int)val('SELECT COALESCE(MAX(no), 0) + 1 FROM servisler WHERE usta_id = ?', [$uid]);
        $vals += [
            'usta_id' => $uid, 'no' => $no, 'sektor' => $sektor, 'durum' => post('hazir') ? 'hazir' : 'kabul', 'kdv' => 0, 'kontrol' => '[]',
            'rapor_token' => bin2hex(random_bytes(16)), 'kart_id' => $kart['id'], 'musteri_id' => $musteri['id'],
            'anahtar' => $kart['anahtar'], 'baslik' => $kart['baslik'], 'ozel' => $kart['ozel'],
            'musteri_ad' => $musteri['ad_soyad'], 'musteri_tel' => $musteri['telefon'], 'musteri_firma' => $musteri['tip'] === 'kurumsal' ? $musteri['unvan'] : null,
        ];
        q('INSERT INTO servisler (' . implode(', ', array_keys($vals)) . ') VALUES (' . in_ph($vals) . ')', array_values($vals));
        $id = (int)db()->lastInsertId();
    }
    kalemleri_kaydet($id, $kalemler);
    db()->commit();
    // Fotoğraflar
    $files = $_FILES['fotolar'] ?? null;
    if ($files && is_array($files['name'])) {
        $mevcut = (int)val('SELECT COUNT(*) FROM servis_fotolari WHERE servis_id = ?', [$id]);
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($mevcut >= MAKS_FOTO_TALEP) { flash('Bir kayda en fazla ' . MAKS_FOTO_TALEP . ' fotoğraf eklenebilir.', 'err'); break; }
            try {
                $yol = resim_yukle(['name' => $name, 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]], 'servis/' . $uid);
                q('INSERT INTO servis_fotolari (servis_id, dosya, etiket) VALUES (?,?,?)', [$id, $yol, 'once']);
                $mevcut++;
            } catch (RuntimeException $ex) {
                flash($name . ': ' . $ex->getMessage(), 'err');
            }
        }
    }
    if (!$s) {
        unset($_SESSION['kabul']);
        flash('Servis kaydı oluşturuldu (#' . $vals['no'] . '). Kabul formunu müşteriye imzalatabilirsin.');
        redirect('usta/kabul-form.php?id=' . $id);
    }
    flash(post('hazir') ? 'Kayıt "teslime hazır" olarak işaretlendi.' : 'Servis talepleri kaydedildi.');
    redirect(post('ileri') ? 'usta/kabul-form.php?id=' . $id : 'usta/kabul-talep.php?id=' . $id);
}

$kalemler = $s ? rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$id]) : [];
$fotolar = $s ? rows('SELECT * FROM servis_fotolari WHERE servis_id = ? ORDER BY id', [$id]) : [];
$hasar = $s ? (json_decode((string)$s['ekspertiz'], true) ?: []) : [];
$sg = $s ? (json_decode((string)$s['sigorta'], true) ?: []) : [];
[$getAd, $getTel] = array_pad(explode(' · ', (string)($s['getiren'] ?? '')), 2, '');
$sonKm = !$s && $kart ? val('SELECT km FROM servisler WHERE kart_id = ? AND km IS NOT NULL ORDER BY giris_tarihi DESC LIMIT 1', [$kart['id']]) : null;

panel_basla('usta', 'kabul', 'Servis Kabul', kabul_baslik($s, $kart, ''));
?>
<?= kabul_sekmeleri($s, 'talep', $tanim) ?>
<form method="post" enctype="multipart/form-data" class="adimgovde" id="sform">
  <?= csrf_field() ?>
  <?php if (!$s): ?><div class="bilgikutu" style="margin-bottom:16px"><?= kart_rozet($kart, 'k') ?> &nbsp; <b><?= e($kart['baslik']) ?></b> · Müşteri: <b><?= e($musteri['unvan'] ?: $musteri['ad_soyad']) ?></b></div><?php endif; ?>
  <div class="kutu"><h3>📝 Servis Talepleri</h3><div class="ic"><div class="fgrid">
    <label class="frow"><span>Giriş tarihi</span><input class="inp" type="datetime-local" name="giris_tarihi" value="<?= e(date('Y-m-d\TH:i', strtotime($s['giris_tarihi'] ?? 'now'))) ?>"></label>
    <label class="frow"><span>Servis danışmanı</span><?= personel_sec('danisman', $u, 'danisman', $s['danisman'] ?? $u['ad_soyad']) ?></label>
    <label class="frow"><span>Teknisyen</span><?= personel_sec('teknisyen', $u, 'teknisyen', $s['teknisyen'] ?? null) ?></label>
    <label class="frow"><span>Tahmini teslim</span><input class="inp" type="date" name="tahmini_teslim" value="<?= e((string)($s['tahmini_teslim'] ?? '')) ?>"></label>
    <div class="frow wide"><span><?= in_array($sektor, ['arac', 'agir'], true) ? 'Aracı getiren' : 'Getiren / irtibat' ?></span>
      <details class="acilir" <?= $getAd ? 'open' : '' ?>><summary>Sahibinden farklıysa bilgilerini ekle</summary><div class="fgrid">
        <label class="frow"><span>Ad soyad</span><input class="inp" name="getiren_ad" maxlength="100" value="<?= e($getAd) ?>"></label>
        <label class="frow"><span>Telefon</span><input class="inp" name="getiren_tel" type="tel" value="<?= e($getTel) ?>"></label></div></details></div>
    <?php if (!empty($sv['km'])): ?>
      <label class="frow"><span><?= e($sv['km']) ?></span><input class="inp" name="km" inputmode="numeric" value="<?= e((string)($s['km'] ?? '')) ?>" placeholder="<?= $sonKm ? 'Son kayıt: ' . sayi($sonKm) : '0' ?>"></label>
    <?php endif; ?>
    <?php if (!empty($sv['yakit'])): ?>
      <div class="frow"><span>Yakıt seviyesi</span><span class="yakitgir"><input type="range" name="yakit" min="0" max="100" step="5" value="<?= (int)($s['yakit'] ?? 50) ?>" oninput="this.nextElementSibling.textContent='%'+this.value"><b>%<?= (int)($s['yakit'] ?? 50) ?></b></span></div>
    <?php endif; ?>
    <?php if (!empty($sv['ekspertiz'])): ?>
      <div class="frow wide" style="align-items:start"><span>Ekspertiz / hasar</span><?= hasar_semasi($hasar) ?></div>
    <?php endif; ?>
    <div class="frow wide"><span>Fotoğraf / belge</span><span><input type="file" name="fotolar[]" accept="image/*" multiple> <small class="muted">Telefon veya tabletle çekebilir ya da cihazdan seçebilirsin.</small>
      <?php if ($fotolar): ?><span class="photos" style="margin-top:10px;grid-template-columns:repeat(auto-fill,minmax(90px,1fr))"><?php foreach ($fotolar as $f): ?><a class="ph" href="<?= e(upload_url($f['dosya'])) ?>" target="_blank"><img src="<?= e(upload_url($f['dosya'])) ?>" alt=""></a><?php endforeach; ?></span><?php endif; ?></span></div>
    <label class="frow wide"><span>Müşteri talepleri</span><textarea class="inp" name="sikayet" rows="3" placeholder="Müşterinin şikâyeti ve istekleri"><?= e((string)($s['sikayet'] ?? '')) ?></textarea></label>
    <?php if (!empty($sv['sigorta'])): ?>
      <div class="frow wide"><span>Sigorta hasar</span><details class="acilir" <?= $sg ? 'open' : '' ?>><summary>Sigorta hasar dosyası varsa bilgilerini ekle</summary><div class="fgrid">
        <label class="frow"><span>Sigorta şirketi</span><input class="inp" name="sg_sirket" value="<?= e($sg['sirket'] ?? '') ?>"></label>
        <label class="frow"><span>Dosya no</span><input class="inp" name="sg_dosya" value="<?= e($sg['dosya'] ?? '') ?>"></label>
        <label class="frow"><span>Eksper</span><input class="inp" name="sg_eksper" value="<?= e($sg['eksper'] ?? '') ?>"></label>
        <label class="frow"><span>Hasar tarihi</span><input class="inp" type="date" name="sg_tarih" value="<?= e($sg['tarih'] ?? '') ?>"></label></div></details></div>
    <?php endif; ?>
    <label class="frow"><span>Etiket</span><input class="inp" name="etiket" maxlength="60" list="dl-etiket" value="<?= e((string)($s['etiket'] ?? '')) ?>" placeholder="Örn: Garanti, Filo, Acil"></label>
  </div></div></div>

  <div class="kutu"><h3>🧾 Yapılacak İşler <small>Tahmini; işlem adımında güncellenebilir</small></h3><div class="ic">
    <?= kalem_editoru($kalemler, $tanim, (int)$u['kdv_varsayilan'], (int)($s['kdv'] ?? 0), (float)($s['iskonto'] ?? 0)) ?>
  </div></div>

  <div class="kutu"><h3>💬 Müşteriye İletilecek Notlar</h3><div class="ic">
    <textarea class="inp" name="musteri_notu" rows="3" style="width:100%" placeholder="Kabul ve teslim formunda müşteriye görünür"><?= e((string)($s['musteri_notu'] ?? '')) ?></textarea>
  </div>
  <div class="alt">
    <a class="btn btn-ghost" href="kabul-musteri.php<?= $s ? '?id=' . $id : '' ?>">← Geri</a>
    <span><button class="btn btn-soft" name="hazir" value="1">🛠️ Teslime hazıra al</button> <?php if ($s): ?><button class="btn btn-ghost">Kaydet</button> <?php endif; ?><button class="btn btn-main" name="ileri" value="1"><?= $s ? 'İleri →' : 'Kaydı oluştur →' ?></button></span>
  </div></div>
</form>
<?= etiket_datalist($uid) ?>
<?= kalem_js() ?>
<?php panel_bitir();
