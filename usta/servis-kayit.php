<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const MAKS_SERVIS_FOTO = 20;

$id = (int)get('id');

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
$s = $id ? row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/servis.php'); }

$sektor = $s['sektor'] ?? usta_sektor($u);
if (!$s && isset(sektorler()[get('sektor')])) $sektor = get('sektor');
$tanim = sektorler()[$sektor];
$hata = '';

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    $geri = fn() => redirect('usta/servis-kayit.php?id=' . $id);

    if ($s && $islem === 'odeme_sil') {
        q('DELETE o FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE o.id = ? AND s.usta_id = ?', [(int)post('oid'), $uid]);
        flash('Ödeme silindi.', 'info');
        $geri();
    }
    if ($s && $islem === 'foto_sil') {
        $f = row('SELECT f.* FROM servis_fotolari f JOIN servisler s ON s.id = f.servis_id WHERE f.id = ? AND s.usta_id = ?', [(int)post('fid'), $uid]);
        if ($f) { dosya_sil($f['dosya']); q('DELETE FROM servis_fotolari WHERE id = ?', [$f['id']]); flash('Fotoğraf silindi.', 'info'); }
        $geri();
    }
    if ($s && $islem === 'durum') {
        $d = post('durum');
        if (isset(servis_durumlari()[$d])) {
            q('UPDATE servisler SET durum = ?, teslim_tarihi = ' . ($d === 'teslim' ? 'COALESCE(teslim_tarihi, NOW())' : 'teslim_tarihi') . ', guncelleme = NOW() WHERE id = ?', [$d, $id]);
            flash('Durum güncellendi: ' . servis_durumlari()[$d][0]);
        }
        $geri();
    }
    if ($s && $islem === 'sil') {
        foreach (rows('SELECT dosya FROM servis_fotolari WHERE servis_id = ?', [$id]) as $f) dosya_sil($f['dosya']);
        q('DELETE FROM servisler WHERE id = ?', [$id]);
        flash('Kayıt #' . $s['no'] . ' silindi.', 'info');
        redirect('usta/servis.php');
    }

    if ($islem === 'kaydet') {
        $ozel = [];
        $anahtar = null;
        foreach ($tanim['alanlar'] as $a) {
            [$k, $etiket, $tip] = $a;
            $v = mb_substr(trim((string)($_POST['ozel'][$k] ?? '')), 0, $tip === 'textarea' ? 2000 : 120);
            if ($tip === 'select' && $v !== '' && !in_array($v, $a[3], true)) $v = '';
            if ($k === 'plaka' || $k === 'dorse' || $k === 'arac_plaka') $v = tr_upper($v);
            $ozel[$k] = $v;
            if (!empty($a[4]) && $v === '') $hata = "\"$etiket\" alanını doldur.";
            if (!empty($a[5]) && $anahtar === null && $v !== '') $anahtar = anahtar_norm($v);
        }
        $musteri = mb_substr(post('musteri_ad'), 0, 100);
        $tel = post('musteri_tel');
        if (!$hata && $musteri === '') $hata = 'Müşteri adını yaz.';
        if (!$hata && $tel !== '' && !tel_gecerli($tel)) $hata = 'Müşteri telefonu geçersiz (05xx xxx xx xx).';

        $kalemler = [];
        foreach ((array)($_POST['k_aciklama'] ?? []) as $i => $acik) {
            $acik = mb_substr(trim((string)$acik), 0, 200);
            if ($acik === '') continue;
            $adet = (float)str_replace(',', '.', (string)($_POST['k_adet'][$i] ?? '1'));
            $kalemler[] = [
                isset(KALEM_TURLERI[$_POST['k_tur'][$i] ?? '']) ? $_POST['k_tur'][$i] : 'iscilik',
                $acik, $adet > 0 ? round($adet, 2) : 1, para_oku((string)($_POST['k_fiyat'][$i] ?? '0')),
            ];
        }
        $kontrol = array_values(array_intersect((array)($_POST['kontrol'] ?? []), $tanim['kontrol']));
        $durum = isset(servis_durumlari()[post('durum')]) ? post('durum') : 'kabul';
        $giris = post('giris_tarihi');
        $giris = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $giris) ? str_replace('T', ' ', $giris) . ':00' : date('Y-m-d H:i:s');
        $tahmini = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('tahmini_teslim')) ? post('tahmini_teslim') : null;
        $kdv = in_array((int)post('kdv'), [0, 1, 10, 20], true) ? (int)post('kdv') : 0;

        if (!$hata) {
            $vals = [
                'musteri_ad' => $musteri, 'musteri_tel' => $tel !== '' ? tel_temizle($tel) : null, 'musteri_firma' => mb_substr(post('musteri_firma'), 0, 120) ?: null,
                'anahtar' => $anahtar, 'baslik' => mb_substr(servis_baslik($sektor, $ozel), 0, 200), 'ozel' => json_encode($ozel, JSON_UNESCAPED_UNICODE),
                'sikayet' => mb_substr(post('sikayet'), 0, 3000) ?: null, 'yapilan' => mb_substr(post('yapilan'), 0, 3000) ?: null,
                'kontrol' => json_encode($kontrol, JSON_UNESCAPED_UNICODE), 'durum' => $durum, 'giris_tarihi' => $giris, 'tahmini_teslim' => $tahmini,
                'sonraki_bakim' => mb_substr(post('sonraki_bakim'), 0, 120) ?: null, 'garanti' => mb_substr(post('garanti'), 0, 120) ?: null,
                'iskonto' => para_oku(post('iskonto')), 'kdv' => $kdv, 'notlar' => mb_substr(post('notlar'), 0, 3000) ?: null,
            ];
            db()->beginTransaction();
            if ($s) {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($vals)));
                q("UPDATE servisler SET $set, teslim_tarihi = " . ($durum === 'teslim' ? 'COALESCE(teslim_tarihi, NOW())' : 'teslim_tarihi') . ', guncelleme = NOW() WHERE id = ?', [...array_values($vals), $id]);
                q('DELETE FROM servis_kalemleri WHERE servis_id = ?', [$id]);
            } else {
                $no = (int)val('SELECT COALESCE(MAX(no), 0) + 1 FROM servisler WHERE usta_id = ?', [$uid]);
                $vals += ['usta_id' => $uid, 'no' => $no, 'sektor' => $sektor, 'rapor_token' => bin2hex(random_bytes(16))];
                if ($durum === 'teslim') $vals['teslim_tarihi'] = date('Y-m-d H:i:s');
                q('INSERT INTO servisler (' . implode(', ', array_keys($vals)) . ') VALUES (' . in_ph($vals) . ')', array_values($vals));
                $id = (int)db()->lastInsertId();
                $pesin = para_oku(post('pesinat'));
                if ($pesin > 0) {
                    q('INSERT INTO servis_odemeleri (servis_id, tutar, yontem, tarih, aciklama) VALUES (?,?,?,CURDATE(),?)',
                        [$id, $pesin, isset(ODEME_YONTEMLERI[post('pesinat_yontem')]) ? post('pesinat_yontem') : 'nakit', 'Kayıt sırasında alınan']);
                }
            }
            foreach ($kalemler as $i => [$tur, $acik, $adet, $fiyat]) {
                q('INSERT INTO servis_kalemleri (servis_id, tur, aciklama, adet, birim_fiyat, sira) VALUES (?,?,?,?,?,?)', [$id, $tur, $acik, $adet, $fiyat, $i]);
            }
            db()->commit();
            flash($s ? 'Kayıt kaydedildi.' : 'Kayıt #' . ($vals['no'] ?? '') . ' oluşturuldu. Fotoğraf ve ödeme ekleyebilirsin.');
            $capa = '';
            if ($s && post('odeme_ekle')) { tahsilat_ekle($id); $capa = '#tahsilat'; }
            if ($s && post('foto_ekle')) { servis_foto_yukle($id, $uid); $capa = '#foto'; }
            elseif ($s) servis_foto_yukle($id, $uid); // "Kaydet"e basılınca seçili fotoğraf da kaybolmasın
            redirect('usta/servis-kayit.php?id=' . $id . $capa);
        }
    }
}

// Form değerleri: hata varsa gönderilen, yoksa kayıttaki
$ozel = $s ? (json_decode((string)$s['ozel'], true) ?: []) : [];
$kontrolSecili = $s ? (json_decode((string)$s['kontrol'], true) ?: []) : [];
$kalemler = $s ? rows('SELECT * FROM servis_kalemleri WHERE servis_id = ? ORDER BY sira, id', [$id]) : [];
if ($hata) {
    $ozel = array_map(fn($v) => is_string($v) ? $v : '', (array)($_POST['ozel'] ?? []));
    $kontrolSecili = (array)($_POST['kontrol'] ?? []);
    $kalemler = [];
    foreach ((array)($_POST['k_aciklama'] ?? []) as $i => $a) $kalemler[] = ['tur' => $_POST['k_tur'][$i] ?? 'iscilik', 'aciklama' => $a, 'adet' => $_POST['k_adet'][$i] ?? 1, 'birim_fiyat' => para_oku((string)($_POST['k_fiyat'][$i] ?? '0'))];
}
$val = fn(string $k, string $d = '') => e($hata ? post($k) : (string)($s[$k] ?? $d));
$odemeler = $s ? rows('SELECT * FROM servis_odemeleri WHERE servis_id = ? ORDER BY tarih, id', [$id]) : [];
$fotolar = $s ? rows('SELECT * FROM servis_fotolari WHERE servis_id = ? ORDER BY etiket, id', [$id]) : [];
$h = $s ? servis_hesap($s, $kalemler, $odemeler) : null;
$gecmis = ($s && $s['anahtar']) ? (int)val('SELECT COUNT(*) FROM servisler WHERE usta_id = ? AND anahtar = ? AND id <> ?', [$uid, $s['anahtar'], $id]) : 0;
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$giris = $s ? date('Y-m-d\TH:i', strtotime($s['giris_tarihi'])) : date('Y-m-d\TH:i');

panel_basla('usta', 'servis.php', $s ? 'Kayıt #' . $s['no'] . ' · ' . $s['baslik'] : 'Yeni ' . tr_lower($tanim['kayit']) . ' kaydı',
    $s ? servis_durum_pill($s['durum']) . ' · Giriş: ' . date('d.m.Y H:i', strtotime($s['giris_tarihi'])) : $tanim['ikon'] . ' ' . e($tanim['ad']));
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>

<?php if ($s): $wa = $s['musteri_tel'] ? wa_link($s['musteri_tel'], "Merhaba {$s['musteri_ad']}, #{$s['no']} numaralı {$s['baslik']} kaydınızın servis raporu: " . rapor_url($s) . "\n" . $u['ad_soyad']) : ''; ?>
<div class="card actionbar">
  <a class="btn btn-main" href="servis-rapor.php?id=<?= $id ?>" target="_blank">📄 Rapor oluştur</a>
  <?php if ($wa): ?><a class="btn btn-soft" href="<?= e($wa) ?>" target="_blank" rel="noopener">💬 Raporu WhatsApp'tan gönder</a><?php endif; ?>
  <button type="button" class="btn btn-ghost" onclick="navigator.clipboard.writeText('<?= e(rapor_url($s)) ?>').then(()=>this.textContent='✓ Link kopyalandı')">🔗 Rapor linkini kopyala</button>
  <div class="inline-form" style="margin-left:auto">
    <select class="inp" id="hizli_durum" aria-label="Durum"><?php foreach (servis_durumlari() as $k => [$ad]): ?><option value="<?= $k ?>" <?= $s['durum'] === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select></div>
</div>
<div class="stats three">
  <div class="stat"><div class="ic">🧾</div><div><small>Toplam</small><b><?= para($h['toplam']) ?></b></div></div>
  <div class="stat"><div class="ic">💵</div><div><small>Alınan</small><b><?= para($h['odenen']) ?></b></div></div>
  <div class="stat"><div class="ic" style="background:<?= $h['kalan'] > 0.009 ? 'var(--amber100)' : 'var(--g100)' ?>">⏳</div><div><small>Kalan</small><b><?= para(max(0, $h['kalan'])) ?></b><?= $h['kalan'] < -0.009 ? '<span class="down">Fazla ödeme: ' . para(-$h['kalan']) . '</span>' : '' ?></div></div>
</div>
<?php endif; ?>

<form method="post" class="card servis-form" id="sform" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="islem" value="kaydet">
  <?php if (!$s): ?>
    <div class="sektor-sec"><small class="muted">Kayıt türü:</small>
      <?php foreach (sektorler() as $k => $t): ?><a class="<?= $k === $sektor ? 'on' : '' ?>" href="?sektor=<?= $k ?>"><?= $t['ikon'] ?> <?= e($t['ad']) ?></a><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2>👤 Müşteri</h2>
  <div class="form three-col">
    <label>Ad soyad *<input class="inp" name="musteri_ad" required maxlength="100" value="<?= $val('musteri_ad') ?>"></label>
    <label>Telefon<input class="inp" name="musteri_tel" type="tel" inputmode="tel" placeholder="05xx xxx xx xx" value="<?= $s && !$hata && $s['musteri_tel'] ? e(tel_goster($s['musteri_tel'])) : $val('musteri_tel') ?>"></label>
    <label>Firma <small class="muted">(varsa)</small><input class="inp" name="musteri_firma" maxlength="120" value="<?= $val('musteri_firma') ?>"></label>
  </div>

  <h2><?= $tanim['ikon'] ?> <?= e($tanim['kayit']) ?> bilgileri</h2>
  <div id="gecmis" class="notice n-amber" style="<?= $gecmis ? '' : 'display:none' ?>"><?= $gecmis ? "Bu kayıt daha önce $gecmis kez gelmiş. <a href=\"servis.php?durum=tumu&q=" . e(urlencode($s['anahtar'])) . "\">Geçmişi gör →</a>" : '' ?></div>
  <div class="form three-col">
    <?php foreach ($tanim['alanlar'] as $a): [$k, $et, $tip] = $a; $v = (string)($ozel[$k] ?? ''); $req = !empty($a[4]); $ank = !empty($a[5]); ?>
      <label class="<?= $tip === 'textarea' ? 'full' : '' ?>"><?= e($et) ?><?= $req ? ' *' : '' ?>
        <?php if ($tip === 'select'): ?>
          <select class="inp" name="ozel[<?= $k ?>]" <?= $req ? 'required' : '' ?>><option value="">Seçin</option><?php foreach ($a[3] as $o): ?><option <?= $v === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
        <?php elseif ($tip === 'textarea'): ?>
          <textarea class="inp" name="ozel[<?= $k ?>]" rows="3"><?= e($v) ?></textarea>
        <?php else: ?>
          <input class="inp<?= $ank ? ' anahtar' : '' ?><?= in_array($k, ['plaka', 'dorse', 'arac_plaka'], true) ? ' upper' : '' ?>" name="ozel[<?= $k ?>]" type="<?= $tip === 'number' ? 'number' : ($tip === 'date' ? 'date' : 'text') ?>" <?= $tip === 'number' ? 'min="0" step="any"' : '' ?> value="<?= e($v) ?>" <?= $req ? 'required' : '' ?> <?= $ank ? 'autocomplete="off"' : '' ?>>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>

  <div class="form">
    <label>Müşterinin şikâyeti / talebi<textarea class="inp" name="sikayet" rows="3" placeholder="Müşteri ne söyledi?"><?= $val('sikayet') ?></textarea></label>
    <label>Yapılan işlemler / tespitler<textarea class="inp" name="yapilan" rows="3" placeholder="Ne yaptın, ne tespit ettin?"><?= $val('yapilan') ?></textarea></label>
  </div>

  <h2>✅ Kontrol listesi</h2>
  <div class="checks"><?php foreach ($tanim['kontrol'] as $c): ?><label class="cb"><input type="checkbox" name="kontrol[]" value="<?= e($c) ?>" <?= in_array($c, $kontrolSecili, true) ? 'checked' : '' ?>><span><?= e($c) ?></span></label><?php endforeach; ?></div>

  <h2>🧾 İşçilik ve parçalar</h2>
  <div class="hizli"><small class="muted">Hızlı ekle:</small><?php foreach ($tanim['hizli'] as [$tur, $ad]): ?><button type="button" class="chipbtn" data-tur="<?= $tur ?>" data-ad="<?= e($ad) ?>">+ <?= e($ad) ?></button><?php endforeach; ?></div>
  <div class="kalemler">
    <div class="krow khead"><span>Tür</span><span>Açıklama</span><span>Adet</span><span>Birim fiyat (₺)</span><span>Tutar</span><span></span></div>
    <div id="kbody">
      <?php foreach ($kalemler ?: [['tur' => 'iscilik', 'aciklama' => '', 'adet' => 1, 'birim_fiyat' => '']] as $k): ?>
        <div class="krow">
          <select class="inp" name="k_tur[]"><?php foreach (KALEM_TURLERI as $tk => $ta): ?><option value="<?= $tk ?>" <?= $k['tur'] === $tk ? 'selected' : '' ?>><?= $ta ?></option><?php endforeach; ?></select>
          <input class="inp" name="k_aciklama[]" maxlength="200" placeholder="Açıklama" value="<?= e((string)$k['aciklama']) ?>">
          <input class="inp num" name="k_adet[]" inputmode="decimal" value="<?= e(rtrim(rtrim((string)$k['adet'], '0'), '.') ?: '1') ?>">
          <input class="inp num" name="k_fiyat[]" inputmode="decimal" placeholder="0,00" value="<?= $k['birim_fiyat'] !== '' ? $fmt($k['birim_fiyat']) : '' ?>">
          <b class="ktutar">0,00</b>
          <button type="button" class="kdel" title="Sil">✕</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-soft" id="kekle">+ Satır ekle</button>
  </div>
  <div class="totals">
    <div><span>Ara toplam</span><b id="t_ara">0,00 ₺</b></div>
    <div><span>İndirim (₺)</span><input class="inp num" name="iskonto" inputmode="decimal" value="<?= $s && !$hata ? ((float)$s['iskonto'] ? $fmt($s['iskonto']) : '') : e(post('iskonto')) ?>" placeholder="0,00"></div>
    <div><span>KDV</span><select class="inp" name="kdv"><?php foreach ([0 => 'KDV yok', 1 => '%1', 10 => '%10', 20 => '%20'] as $kv => $ka): ?><option value="<?= $kv ?>" <?= (int)($hata ? post('kdv') : ($s['kdv'] ?? 0)) === $kv ? 'selected' : '' ?>><?= $ka ?></option><?php endforeach; ?></select></div>
    <div class="grand"><span>Genel toplam</span><b id="t_top">0,00 ₺</b></div>
  </div>

  <h2>📅 Durum ve teslim</h2>
  <div class="form three-col">
    <label>Durum<select class="inp" name="durum"><?php foreach (servis_durumlari() as $k => [$ad]): ?><option value="<?= $k ?>" <?= ($hata ? post('durum') : ($s['durum'] ?? 'kabul')) === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select></label>
    <label>Giriş tarihi<input class="inp" type="datetime-local" name="giris_tarihi" value="<?= e($giris) ?>"></label>
    <label>Tahmini teslim<input class="inp" type="date" name="tahmini_teslim" value="<?= $val('tahmini_teslim') ?>"></label>
    <?php if ($tanim['bakim']): ?><label>Sonraki bakım<input class="inp" name="sonraki_bakim" maxlength="120" placeholder="<?= e($tanim['bakim']) ?>" value="<?= $val('sonraki_bakim') ?>"></label><?php endif; ?>
    <label>Garanti<input class="inp" name="garanti" maxlength="120" placeholder="Örn: İşçilik 6 ay" value="<?= $val('garanti') ?>"></label>
    <?php if (!$s): ?>
      <label>Peşin alınan ödeme (₺)<input class="inp num" name="pesinat" inputmode="decimal" placeholder="0,00" value="<?= e(post('pesinat')) ?>"></label>
      <label>Ödeme şekli<select class="inp" name="pesinat_yontem"><?php foreach (ODEME_YONTEMLERI as $k => $ad): ?><option value="<?= $k ?>"><?= $ad ?></option><?php endforeach; ?></select></label>
    <?php endif; ?>
    <label class="full">İç not <small class="muted">(sadece sen görürsün, rapora çıkmaz)</small><textarea class="inp" name="notlar" rows="2"><?= $val('notlar') ?></textarea></label>
  </div>
  <div class="formfoot sticky"><a class="btn btn-ghost" href="servis.php">Vazgeç</a><button class="btn btn-main"><?= $s ? 'Kaydet' : 'Kaydı oluştur' ?></button></div>
</form>

<?php if ($s): ?>
<div class="row even">
  <div class="card" id="tahsilat">
    <h2>💵 Tahsilatlar</h2>
    <?php if ($odemeler): ?><div class="list"><?php foreach ($odemeler as $o): ?>
      <div class="li"><div class="grow"><b><?= para($o['tutar']) ?></b> <small class="muted">· <?= ODEME_YONTEMLERI[$o['yontem']] ?> · <?= date('d.m.Y', strtotime($o['tarih'])) ?></small><?= $o['aciklama'] ? '<br><small class="muted">' . e($o['aciklama']) . '</small>' : '' ?></div>
        <form method="post" onsubmit="return confirm('Ödeme silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="odeme_sil"><input type="hidden" name="oid" value="<?= $o['id'] ?>"><button class="btn btn-red">Sil</button></form></div>
    <?php endforeach; ?></div><?php else: ?><p class="muted">Henüz ödeme alınmadı.</p><?php endif; ?>
    <div class="odeme-ozet"><span>Toplam <b><?= para($h['toplam']) ?></b></span><span>Alınan <b><?= para($h['odenen']) ?></b></span><span>Kalan <b class="<?= $h['kalan'] > 0.009 ? 'down' : 'up' ?>"><?= para(max(0, $h['kalan'])) ?></b></span></div>
    <?php if ($h['toplam'] <= 0): ?><p class="notice n-amber" style="margin:10px 0 0">Bu kaydın tutarı 0 görünüyor. Yukarıdaki <b>İşçilik ve parçalar</b> bölümüne fiyatları yaz; tahsilat eklediğinde fiyatlar da kaydedilir.</p><?php endif; ?>
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
<?php endif; ?>

<template id="ksablon"><div class="krow">
  <select class="inp" name="k_tur[]"><?php foreach (KALEM_TURLERI as $tk => $ta): ?><option value="<?= $tk ?>"><?= $ta ?></option><?php endforeach; ?></select>
  <input class="inp" name="k_aciklama[]" maxlength="200" placeholder="Açıklama"><input class="inp num" name="k_adet[]" inputmode="decimal" value="1"><input class="inp num" name="k_fiyat[]" inputmode="decimal" placeholder="0,00"><b class="ktutar">0,00</b><button type="button" class="kdel" title="Sil">✕</button>
</div></template>
<script>
(function () {
  const body = document.getElementById('kbody'), sablon = document.getElementById('ksablon'), form = document.getElementById('sform');
  const oku = s => { s = (s || '').replace(/[₺\s]/g, ''); if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.'); else if (/^\d{1,3}(\.\d{3})+$/.test(s)) s = s.replace(/\./g, ''); const n = parseFloat(s); return isNaN(n) ? 0 : n; };
  const yaz = n => n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  function hesapla() {
    let ara = 0;
    body.querySelectorAll('.krow').forEach(r => {
      const t = oku(r.querySelector('[name="k_adet[]"]').value) * oku(r.querySelector('[name="k_fiyat[]"]').value);
      r.querySelector('.ktutar').textContent = yaz(t); ara += t;
    });
    const isk = Math.min(ara, oku(form.iskonto.value)), kdv = parseInt(form.kdv.value, 10) || 0;
    document.getElementById('t_ara').textContent = yaz(ara) + ' ₺';
    const top = (ara - isk) * (1 + kdv / 100);
    document.getElementById('t_top').textContent = yaz(top) + ' ₺';
    const ot = document.getElementById('o_tutar');
    if (ot && !oElle) { const k = top - parseFloat(ot.dataset.odenen || '0'); ot.value = k > 0.009 ? yaz(k) : ''; }
  }
  function ekle(tur, ad) {
    const bos = [...body.querySelectorAll('.krow')].find(r => !r.querySelector('[name="k_aciklama[]"]').value);
    const r = bos || sablon.content.firstElementChild.cloneNode(true);
    if (!bos) body.appendChild(r);
    if (tur) r.querySelector('select').value = tur;
    if (ad) r.querySelector('[name="k_aciklama[]"]').value = ad;
    r.querySelector(ad ? '[name="k_fiyat[]"]' : '[name="k_aciklama[]"]').focus();
    hesapla();
  }
  document.getElementById('kekle').onclick = () => ekle();
  document.querySelectorAll('.chipbtn').forEach(b => b.onclick = () => ekle(b.dataset.tur, b.dataset.ad));
  body.addEventListener('click', e => { if (e.target.classList.contains('kdel')) { const rs = body.querySelectorAll('.krow'); if (rs.length > 1) e.target.closest('.krow').remove(); else rs[0].querySelectorAll('input').forEach(i => i.value = i.name === 'k_adet[]' ? '1' : ''); hesapla(); } });
  const oTutar = document.getElementById('o_tutar');
  let oElle = false, kirli = false, gonderiliyor = false;
  if (oTutar) oTutar.addEventListener('input', () => oElle = true);
  form.addEventListener('input', hesapla); form.addEventListener('change', hesapla); hesapla();
  // Formla ilişkili her alan (form="sform" olanlar dahil) değişince "kaydedilmemiş" say
  document.addEventListener('input', e => { if (e.target.form === form) kirli = true; });
  document.addEventListener('change', e => { if (e.target.form === form) kirli = true; });
  form.addEventListener('submit', () => gonderiliyor = true);
  window.addEventListener('beforeunload', e => { if (kirli && !gonderiliyor) { e.preventDefault(); e.returnValue = ''; } });
  document.querySelectorAll('form:not(#sform)').forEach(f => f.addEventListener('submit', ev => {
    if (kirli && !confirm('Kaydedilmemiş değişikliklerin var ve kaybolacak. Yine de devam edilsin mi?')) ev.preventDefault(); else gonderiliyor = true;
  }));
  const hd = document.getElementById('hizli_durum');
  if (hd) hd.addEventListener('change', () => { form.durum.value = hd.value; gonderiliyor = true; form.requestSubmit ? form.requestSubmit() : form.submit(); });
  document.querySelectorAll('input.upper').forEach(i => i.addEventListener('input', () => { const p = i.selectionStart; i.value = i.value.toLocaleUpperCase('tr-TR'); i.setSelectionRange(p, p); }));

  // Plaka / seri no girilince geçmiş kaydı bul ve boş alanları doldur
  const ank = document.querySelector('input.anahtar'), kutu = document.getElementById('gecmis');
  <?php if (!$s): ?>
  if (ank) ank.addEventListener('change', async () => {
    if (ank.value.trim().length < 3) return;
    try {
      const r = await fetch('servis-ara.php?a=' + encodeURIComponent(ank.value)); const d = await r.json();
      if (!d.bulundu) { kutu.style.display = 'none'; return; }
      const doldur = (n, v) => { const el = form.querySelector('[name="' + n + '"]'); if (el && !el.value && v) el.value = v; };
      doldur('musteri_ad', d.musteri_ad); doldur('musteri_tel', d.musteri_tel); doldur('musteri_firma', d.musteri_firma);
      Object.entries(d.ozel || {}).forEach(([k, v]) => { if (k !== 'km') doldur('ozel[' + k + ']', v); });
      kutu.innerHTML = 'Bu kayıt daha önce ' + d.adet + ' kez gelmiş (son: #' + d.son_no + ', ' + d.son_tarih + '). Müşteri bilgileri dolduruldu. <a href="servis-kayit.php?id=' + d.son_id + '" target="_blank">Son kaydı aç →</a>';
      kutu.style.display = '';
    } catch (e) {}
  });
  <?php endif; ?>
})();
</script>
<?php panel_bitir();
