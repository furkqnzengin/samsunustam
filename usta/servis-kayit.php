<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const MAKS_SERVIS_FOTO = 20;

$id = (int)get('id');
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

    if ($s && $islem === 'odeme_ekle') {
        $tutar = para_oku(post('tutar'));
        $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('tarih')) ? post('tarih') : date('Y-m-d');
        if ($tutar <= 0) flash('Ödeme tutarını yaz.', 'err');
        else {
            q('INSERT INTO servis_odemeleri (servis_id, tutar, yontem, tarih, aciklama) VALUES (?,?,?,?,?)',
                [$id, $tutar, isset(ODEME_YONTEMLERI[post('yontem')]) ? post('yontem') : 'nakit', $tarih, mb_substr(post('aciklama'), 0, 200) ?: null]);
            flash(para($tutar) . ' tahsilat eklendi.');
        }
        $geri();
    }
    if ($s && $islem === 'odeme_sil') {
        q('DELETE o FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE o.id = ? AND s.usta_id = ?', [(int)post('oid'), $uid]);
        flash('Ödeme silindi.', 'info');
        $geri();
    }
    if ($s && $islem === 'foto_ekle') {
        $mevcut = (int)val('SELECT COUNT(*) FROM servis_fotolari WHERE servis_id = ?', [$id]);
        $files = $_FILES['fotolar'] ?? null;
        $etiket = in_array(post('etiket'), ['once', 'sonra', 'diger'], true) ? post('etiket') : 'diger';
        $n = 0;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                if ($mevcut + $n >= MAKS_SERVIS_FOTO) { flash('Bir kayda en fazla ' . MAKS_SERVIS_FOTO . ' fotoğraf eklenebilir.', 'err'); break; }
                try {
                    $yol = resim_yukle(['name' => $name, 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]], 'servis/' . $uid);
                    q('INSERT INTO servis_fotolari (servis_id, dosya, etiket) VALUES (?,?,?)', [$id, $yol, $etiket]);
                    $n++;
                } catch (RuntimeException $ex) {
                    flash($name . ': ' . $ex->getMessage(), 'err');
                }
            }
        }
        if ($n) flash("$n fotoğraf eklendi.");
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
            flash($s ? 'Kayıt güncellendi.' : 'Kayıt #' . ($vals['no'] ?? '') . ' oluşturuldu. Fotoğraf ve ödeme ekleyebilirsin.');
            redirect('usta/servis-kayit.php?id=' . $id);
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
  <form method="post" class="inline-form" style="margin-left:auto"><?= csrf_field() ?><input type="hidden" name="islem" value="durum">
    <select class="inp" name="durum" onchange="this.form.submit()"><?php foreach (servis_durumlari() as $k => [$ad]): ?><option value="<?= $k ?>" <?= $s['durum'] === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select></form>
</div>
<div class="stats three">
  <div class="stat"><div class="ic">🧾</div><div><small>Toplam</small><b><?= para($h['toplam']) ?></b></div></div>
  <div class="stat"><div class="ic">💵</div><div><small>Alınan</small><b><?= para($h['odenen']) ?></b></div></div>
  <div class="stat"><div class="ic" style="background:<?= $h['kalan'] > 0.009 ? 'var(--amber100)' : 'var(--g100)' ?>">⏳</div><div><small>Kalan</small><b><?= para(max(0, $h['kalan'])) ?></b><?= $h['kalan'] < -0.009 ? '<span class="down">Fazla ödeme: ' . para(-$h['kalan']) . '</span>' : '' ?></div></div>
</div>
<?php endif; ?>

<form method="post" class="card servis-form" id="sform">
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
  <div class="card">
    <h2>💵 Tahsilatlar</h2>
    <?php if ($odemeler): ?><div class="list"><?php foreach ($odemeler as $o): ?>
      <div class="li"><div class="grow"><b><?= para($o['tutar']) ?></b> <small class="muted">· <?= ODEME_YONTEMLERI[$o['yontem']] ?> · <?= date('d.m.Y', strtotime($o['tarih'])) ?></small><?= $o['aciklama'] ? '<br><small class="muted">' . e($o['aciklama']) . '</small>' : '' ?></div>
        <form method="post" onsubmit="return confirm('Ödeme silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="odeme_sil"><input type="hidden" name="oid" value="<?= $o['id'] ?>"><button class="btn btn-red">Sil</button></form></div>
    <?php endforeach; ?></div><?php else: ?><p class="muted">Henüz ödeme alınmadı.</p><?php endif; ?>
    <form method="post" class="form" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="islem" value="odeme_ekle">
      <label>Tutar (₺)<input class="inp num" name="tutar" inputmode="decimal" required placeholder="<?= $h['kalan'] > 0 ? e($fmt($h['kalan'])) : '0,00' ?>" value="<?= $h['kalan'] > 0 ? e($fmt($h['kalan'])) : '' ?>"></label>
      <label>Ödeme şekli<select class="inp" name="yontem"><?php foreach (ODEME_YONTEMLERI as $k => $ad): ?><option value="<?= $k ?>"><?= $ad ?></option><?php endforeach; ?></select></label>
      <label>Tarih<input class="inp" type="date" name="tarih" value="<?= date('Y-m-d') ?>"></label>
      <label>Açıklama<input class="inp" name="aciklama" maxlength="200" placeholder="İsteğe bağlı"></label>
      <div class="full"><button class="btn btn-main">+ Tahsilat ekle</button></div>
    </form>
  </div>
  <div class="card">
    <h2>📷 Fotoğraflar <span class="muted small"><?= count($fotolar) ?>/<?= MAKS_SERVIS_FOTO ?></span></h2>
    <form method="post" enctype="multipart/form-data" class="inline-form"><?= csrf_field() ?><input type="hidden" name="islem" value="foto_ekle">
      <select class="inp" name="etiket"><option value="once">İşlem öncesi</option><option value="sonra">İşlem sonrası</option><option value="diger">Diğer</option></select>
      <input class="inp grow" type="file" name="fotolar[]" accept="image/*" multiple required>
      <button class="btn btn-main">Yükle</button></form>
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
    document.getElementById('t_top').textContent = yaz((ara - isk) * (1 + kdv / 100)) + ' ₺';
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
  form.addEventListener('input', hesapla); form.addEventListener('change', hesapla); hesapla();
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
