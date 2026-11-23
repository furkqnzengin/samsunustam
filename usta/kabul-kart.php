<?php
// Servis kabul 1. adım: araç / cihaz / iş bilgileri (kart)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];

if (get('yeni') === '1') { unset($_SESSION['kabul']); redirect('usta/kabul-kart.php' . (get('sektor') ? '?sektor=' . urlencode(get('sektor')) : '')); }

$id = (int)get('id');
$s = $id ? row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/serviste.php'); }
$taslak = kabul_taslak();
$kartId = $s ? (int)$s['kart_id'] : (int)$taslak['kart_id'];
$kart = $kartId ? row('SELECT * FROM kartlar WHERE id = ? AND usta_id = ?', [$kartId, $uid]) : null;
$sektor = $kart['sektor'] ?? ($s['sektor'] ?? (isset(sektorler()[get('sektor')]) ? get('sektor') : usta_sektor($u)));
$tanim = sektorler()[$sektor];
$anahtarAlan = current(array_filter($tanim['alanlar'], fn($a) => !empty($a[5]))) ?: null;
$hata = '';
$on = [];

// Plaka / seri no ile kayıtlı kart arama (yeni kayıtta)
if (!$s && get('ara') !== '' && $anahtarAlan) {
    $a = anahtar_norm(get('ara'));
    $bul = $a !== '' ? row('SELECT * FROM kartlar WHERE usta_id = ? AND anahtar = ? ORDER BY id DESC LIMIT 1', [$uid, $a]) : null;
    if ($bul) {
        $_SESSION['kabul'] = ['kart_id' => (int)$bul['id'], 'musteri_id' => $bul['musteri_id'] ? (int)$bul['musteri_id'] : null];
        $n = (int)val('SELECT COUNT(*) FROM servisler WHERE kart_id = ?', [$bul['id']]);
        flash('Kayıtlı ' . tr_lower($tanim['kayit']) . ' bulundu' . ($n ? " ($n servis kaydı var)" : '') . '. Bilgileri kontrol edip devam edin.');
        redirect('usta/kabul-kart.php');
    }
    unset($_SESSION['kabul']);
    $kart = null;
    $on[$anahtarAlan[0]] = tr_upper(get('ara'));
    flash('Bu ' . tr_lower($anahtarAlan[1]) . ' ile kayıt yok; yeni ' . tr_lower($tanim['kayit']) . ' kartı açılacak.', 'info');
}

if (is_post()) {
    csrf_check();
    if ($kart && post('islem') === 'belge_sil') {
        dosya_sil($kart['belge']);
        q('UPDATE kartlar SET belge = NULL WHERE id = ?', [$kart['id']]);
        flash('Belge kaldırıldı.', 'info');
        redirect('usta/kabul-kart.php' . ($s ? '?id=' . $id : ''));
    }
    [$ozel, $anahtar, $hata] = kart_oku($tanim);
    if (!$hata) {
        $belge = $kart['belge'] ?? null;
        if (!empty($_FILES['belge']['name'])) {
            try {
                $yeniBelge = belge_kaydet($_FILES['belge'], belge_kontrol($_FILES['belge']), $uid);
                dosya_sil($belge);
                $belge = $yeniBelge;
            } catch (RuntimeException $ex) {
                $hata = 'Belge: ' . $ex->getMessage();
            }
        }
    }
    if (!$hata) {
        $baslik = mb_substr(servis_baslik($sektor, $ozel), 0, 200);
        $json = json_encode($ozel, JSON_UNESCAPED_UNICODE);
        $notlar = mb_substr(post('notlar'), 0, 2000) ?: null;
        if (!$kart && $anahtar) $kart = row('SELECT * FROM kartlar WHERE usta_id = ? AND anahtar = ? ORDER BY id DESC LIMIT 1', [$uid, $anahtar]);
        if ($kart) {
            q('UPDATE kartlar SET anahtar = ?, baslik = ?, ozel = ?, belge = ?, notlar = ?, guncelleme = NOW() WHERE id = ?', [$anahtar, $baslik, $json, $belge, $notlar, $kart['id']]);
            $kid = (int)$kart['id'];
        } else {
            q('INSERT INTO kartlar (usta_id, sektor, anahtar, baslik, ozel, belge, notlar) VALUES (?,?,?,?,?,?,?)', [$uid, $sektor, $anahtar, $baslik, $json, $belge, $notlar]);
            $kid = (int)db()->lastInsertId();
        }
        $kart = row('SELECT * FROM kartlar WHERE id = ?', [$kid]);
        if ($s) {
            servis_kart_kopyala($id, $kart);
            flash('Bilgiler kaydedildi.');
            redirect(post('ileri') ? 'usta/kabul-musteri.php?id=' . $id : 'usta/kabul-kart.php?id=' . $id);
        }
        $_SESSION['kabul'] = ['kart_id' => $kid, 'musteri_id' => $kart['musteri_id'] ? (int)$kart['musteri_id'] : ($taslak['musteri_id'] ?? null)];
        redirect('usta/kabul-musteri.php');
    }
}

$ozel = $hata ? array_map(fn($v) => is_string($v) ? $v : '', (array)($_POST['ozel'] ?? [])) : ($kart ? (json_decode((string)$kart['ozel'], true) ?: []) : $on);
$gecmis = $kart ? (int)val('SELECT COUNT(*) FROM servisler WHERE kart_id = ?', [$kart['id']]) : 0;

panel_basla('usta', 'kabul', 'Servis Kabul', kabul_baslik($s, $kart, $tanim['ikon'] . ' ' . e($tanim['ad'])));
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
<?= kabul_sekmeleri($s, 'kart', $tanim) ?>
<form method="post" enctype="multipart/form-data" class="adimgovde" id="sform">
  <?= csrf_field() ?>
  <?php if (!$s && !$kart): ?>
    <div class="sektor-sec"><small class="muted">Kayıt türü:</small>
      <?php foreach (sektorler() as $k => $t): ?><a class="<?= $k === $sektor ? 'on' : '' ?>" href="?sektor=<?= $k ?>"><?= $t['ikon'] ?> <?= e($t['ad']) ?></a><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="kutu">
    <h3><?= $tanim['ikon'] ?> <?= e($tanim['kayit']) ?> Bilgileri <?php if ($gecmis): ?><small><?= $gecmis ?> servis kaydı</small><?php endif; ?></h3>
    <div class="ic"><div class="fgrid">
      <?php foreach ($tanim['alanlar'] as $a): $v = (string)($ozel[$a[0]] ?? ''); ?>
        <?php if (!empty($a[5]) && !$s): ?>
          <label class="frow"><span><?= e($a[1]) ?><?= !empty($a[4]) ? ' *' : '' ?></span>
            <span class="aracsatir"><input class="inp anahtar upper" name="ozel[<?= $a[0] ?>]" id="anahtarInp" value="<?= e($v) ?>" <?= !empty($a[4]) ? 'required' : '' ?> autocomplete="off" placeholder="<?= e(tr_upper($a[1])) ?>">
              <button type="button" class="btn btn-main" id="araBtn" title="Kayıtlı mı?">🔍 Ara</button></span></label>
        <?php else: ?>
          <?= kart_alani($a, $v) ?>
        <?php endif; ?>
      <?php endforeach; ?>
      <label class="frow"><span>Belge</span>
        <span><?php if (!empty($kart['belge'])): ?><a class="link" href="kart-belge.php?id=<?= (int)$kart['id'] ?>" target="_blank">📄 Belgeyi aç</a> · <?php endif; ?>
        <input type="file" name="belge" accept="application/pdf,image/jpeg,image/png"><small class="muted" style="display:block;font-weight:400">Ruhsat, garanti belgesi vb. (PDF/JPG/PNG, 1 adet)</small></span></label>
      <label class="frow wide"><span><?= e($tanim['kayit']) ?> notları</span><textarea class="inp" name="notlar" rows="2"><?= e($hata ? post('notlar') : (string)($kart['notlar'] ?? '')) ?></textarea></label>
    </div></div>
    <div class="alt">
      <span><?php if ($kart): ?><a class="btn btn-soft" href="kart.php?id=<?= (int)$kart['id'] ?>">🕘 Servis geçmişi</a><?php endif; ?>
        <?php if (!empty($kart['belge'])): ?><button class="btn btn-ghost" name="islem" value="belge_sil" formnovalidate onclick="return confirm('Belge kaldırılsın mı?')">Belgeyi kaldır</button><?php endif; ?></span>
      <span><?php if ($s): ?><button class="btn btn-ghost">Kaydet</button> <?php endif; ?><button class="btn btn-main" name="ileri" value="1">İleri →</button></span>
    </div>
  </div>
</form>
<script>
document.querySelectorAll('input.upper').forEach(i => i.addEventListener('input', () => { const p = i.selectionStart; i.value = i.value.toLocaleUpperCase('tr-TR'); i.setSelectionRange(p, p); }));
const ab = document.getElementById('araBtn');
if (ab) { const inp = document.getElementById('anahtarInp');
  ab.onclick = () => { if (inp.value.trim().length >= 3) location = '?ara=' + encodeURIComponent(inp.value) + '&sektor=<?= e($sektor) ?>'; else inp.focus(); };
  inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); ab.click(); } }); }
</script>
<?php panel_bitir();
