<?php
// Servis kabul 2. adım: müşteri bilgileri
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$id = (int)get('id');
$s = $id ? row('SELECT * FROM servisler WHERE id = ? AND usta_id = ?', [$id, $uid]) : null;
if ($id && !$s) { flash('Kayıt bulunamadı.', 'err'); redirect('usta/serviste.php'); }
$taslak = kabul_taslak();
$kart = $s ? row('SELECT * FROM kartlar WHERE id = ?', [(int)$s['kart_id']]) : ($taslak['kart_id'] ? row('SELECT * FROM kartlar WHERE id = ? AND usta_id = ?', [(int)$taslak['kart_id'], $uid]) : null);
if (!$s && !$kart) { flash('Önce bilgileri girin.', 'info'); redirect('usta/kabul-kart.php'); }
$tanim = sektorler()[$kart['sektor'] ?? $s['sektor']];

$mid = $s ? (int)$s['musteri_id'] : (int)($taslak['musteri_id'] ?? 0);
if (get('m') !== '') $mid = (int)get('m'); // aramadan seçilen müşteri
$m = $mid ? row('SELECT * FROM musteriler WHERE id = ? AND usta_id = ?', [$mid, $uid]) : null;
if (get('yeni') === '1') $m = null;
$hata = '';

if (is_post()) {
    csrf_check();
    [$v, $hata] = musteri_oku();
    if (!$hata) {
        $secili = (int)post('musteri_id') ?: null;
        if ($secili && !val('SELECT id FROM musteriler WHERE id = ? AND usta_id = ?', [$secili, $uid])) $secili = null;
        $mid = musteri_kaydet($uid, $v, $secili);
        $m = row('SELECT * FROM musteriler WHERE id = ?', [$mid]);
        if ($kart) q('UPDATE kartlar SET musteri_id = ? WHERE id = ?', [$mid, $kart['id']]);
        if ($s) {
            servis_musteri_kopyala($id, $m);
            flash('Müşteri bilgileri kaydedildi.');
            redirect(post('ileri') ? 'usta/kabul-talep.php?id=' . $id : 'usta/kabul-musteri.php?id=' . $id);
        }
        $_SESSION['kabul']['musteri_id'] = $mid;
        redirect('usta/kabul-talep.php');
    }
}

$deger = $hata ? array_merge($_POST, ['tip' => post('tip')]) : $m;
panel_basla('usta', 'kabul', 'Servis Kabul', kabul_baslik($s, $kart, ''));
?>
<?php if ($hata): ?><div class="flash flash-err"><?= e($hata) ?></div><?php endif; ?>
<?= kabul_sekmeleri($s, 'musteri', $tanim) ?>
<form method="post" class="adimgovde" id="sform">
  <?= csrf_field() ?><input type="hidden" name="musteri_id" value="<?= $m ? (int)$m['id'] : '' ?>">
  <div class="kutu">
    <h3>👤 Müşteri Bilgileri <?php if ($m): ?><small>Kayıtlı müşteri · <?= para(musteri_bakiye((int)$m['id'])) ?> bakiye</small><?php endif; ?></h3>
    <div class="ic">
      <label class="frow wide oneri" style="margin-bottom:16px"><span>Müşteri ara</span>
        <span style="position:relative"><input class="inp" id="mara" placeholder="Ad soyad, ticari ünvan, telefon veya TC / vergi no (en az 3 karakter)" autocomplete="off">
          <span class="oneri-liste" id="moneri" hidden></span></span></label>
      <?php if ($m): ?><div class="bilgikutu" style="margin-bottom:16px">Seçili müşteri: <b><?= e($m['unvan'] ?: $m['ad_soyad']) ?></b>. Bilgileri aşağıdan güncelleyebilirsin. <a class="link" href="?<?= $s ? 'id=' . $id . '&' : '' ?>yeni=1">Farklı/yeni müşteri gir →</a></div><?php endif; ?>
      <?= musteri_formu($deger) ?>
    </div>
    <div class="alt">
      <a class="btn btn-ghost" href="kabul-kart.php<?= $s ? '?id=' . $id : '' ?>">← Geri</a>
      <span><?php if ($s): ?><button class="btn btn-ghost">Kaydet</button> <?php endif; ?><button class="btn btn-main" name="ileri" value="1">İleri →</button></span>
    </div>
  </div>
</form>
<?= etiket_datalist($uid) ?>
<script>
(function () {
  const inp = document.getElementById('mara'), lst = document.getElementById('moneri'); let zam;
  inp.addEventListener('input', () => { clearTimeout(zam); const q = inp.value.trim();
    if (q.length < 3) { lst.hidden = false; lst.innerHTML = '<a>En az 3 karakter girin</a>'; return; }
    zam = setTimeout(async () => { const r = await fetch('musteri-ara.php?q=' + encodeURIComponent(q)); const d = await r.json();
      lst.hidden = false;
      lst.innerHTML = d.length ? d.map(x => `<a href="?<?= $s ? 'id=' . $id . '&' : '' ?>m=${x.id}"><b>${x.ad.replace(/</g,'&lt;')}</b><small>${x.alt.replace(/</g,'&lt;')}</small></a>`).join('') : '<a>Kayıtlı müşteri bulunamadı. Aşağıya yeni müşteri bilgilerini gir.</a>';
    }, 250); });
  document.addEventListener('click', e => { if (!e.target.closest('.oneri')) lst.hidden = true; });
})();
</script>
<?php panel_bitir();
