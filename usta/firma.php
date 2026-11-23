<?php
// Firma ayarları: formlarda ve raporlarda görünen bilgiler, varsayılanlar
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
require __DIR__ . '/../inc/kabul.php';
$u = aktif_usta();
$uid = (int)$u['id'];
$tanim = sektorler()[usta_sektor($u)];

if (is_post()) {
    csrf_check();
    if (post('islem') === 'logo_sil') {
        dosya_sil($u['firma_logo']);
        q('UPDATE ustalar SET firma_logo = NULL WHERE id = ?', [$uid]);
        flash('Logo kaldırıldı.', 'info');
        redirect('usta/firma.php');
    }
    $logo = $u['firma_logo'];
    if (!empty($_FILES['logo']['name'])) {
        try { $yeni = resim_yukle($_FILES['logo'], 'ustalar/' . $uid, 3); dosya_sil($logo); $logo = $yeni; }
        catch (RuntimeException $ex) { flash('Logo: ' . $ex->getMessage(), 'err'); }
    }
    $vno = preg_replace('/\D/', '', post('vergi_no'));
    $sek = post('servis_sektor');
    q('UPDATE ustalar SET firma_unvan = ?, firma_logo = ?, vergi_dairesi = ?, vergi_no = ?, kabul_metni = ?, teslim_metni = ?, rapor_notu = ?, kdv_varsayilan = ?, servis_sektor = ? WHERE id = ?', [
        mb_substr(post('firma_unvan'), 0, 150) ?: null, $logo, mb_substr(post('vergi_dairesi'), 0, 80) ?: null, $vno ?: null,
        mb_substr(post('kabul_metni'), 0, 2000) ?: null, mb_substr(post('teslim_metni'), 0, 2000) ?: null, mb_substr(post('rapor_notu'), 0, 1500) ?: null,
        in_array((int)post('kdv_varsayilan'), KDV_ORANLARI, true) ? (int)post('kdv_varsayilan') : 0,
        isset(sektorler()[$sek]) ? $sek : null, $uid,
    ]);
    flash('Firma ayarları kaydedildi.');
    redirect('usta/firma.php');
}

panel_basla('usta', 'firma.php', 'Firma Ayarları', 'Servis kabul / teslim formlarında ve müşteri raporlarında görünür.');
?>
<form method="post" enctype="multipart/form-data" class="kutu" id="sform">
  <?= csrf_field() ?>
  <h3>🏢 Firma Bilgileri</h3>
  <div class="ic"><div class="fgrid">
    <div class="frow wide"><span>Logo</span><span style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
      <?php if ($u['firma_logo']): ?><img src="<?= e(upload_url($u['firma_logo'])) ?>" alt="" style="max-height:70px;max-width:220px;border:1px solid var(--line);border-radius:10px;padding:6px;background:#fff"><?php endif; ?>
      <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"><?php if ($u['firma_logo']): ?><button class="btn btn-ghost" name="islem" value="logo_sil" formnovalidate>Logoyu kaldır</button><?php endif; ?></span></div>
    <label class="frow"><span>Ticari ünvan</span><input class="inp" name="firma_unvan" maxlength="150" value="<?= e((string)$u['firma_unvan']) ?>" placeholder="<?= e($u['ad_soyad']) ?>"></label>
    <label class="frow"><span>Vergi dairesi</span><input class="inp" name="vergi_dairesi" maxlength="80" value="<?= e((string)$u['vergi_dairesi']) ?>"></label>
    <label class="frow"><span>Vergi no / TC</span><input class="inp" name="vergi_no" inputmode="numeric" maxlength="11" value="<?= e((string)$u['vergi_no']) ?>"></label>
    <div class="frow"><span>Telefon / adres</span><span class="muted" style="font-weight:400"><?= e(tel_goster($u['telefon'])) ?><?= $u['adres'] ? ' · ' . e($u['adres']) : '' ?> <a class="link" href="profil.php">Profilden değiştir</a></span></div>
  </div></div>
  <h3>⚙️ Servis Ayarları</h3>
  <div class="ic"><div class="fgrid">
    <label class="frow"><span>Servis türü</span><select class="inp" name="servis_sektor"><?php foreach (sektorler() as $k => $t): ?><option value="<?= $k ?>" <?= $k === usta_sektor($u) ? 'selected' : '' ?>><?= $t['ikon'] . ' ' . e($t['ad']) ?></option><?php endforeach; ?></select></label>
    <label class="frow"><span>Varsayılan KDV</span><select class="inp" name="kdv_varsayilan"><?php foreach (KDV_ORANLARI as $k): ?><option value="<?= $k ?>" <?= (int)$u['kdv_varsayilan'] === $k ? 'selected' : '' ?>><?= $k ? '%' . $k : 'KDV yok (%0)' ?></option><?php endforeach; ?></select></label>
    <label class="frow wide"><span>Kabul formu metni</span><textarea class="inp" name="kabul_metni" rows="4" placeholder="<?= e(kabul_metni_varsayilan($tanim)) ?>"><?= e((string)$u['kabul_metni']) ?></textarea></label>
    <label class="frow wide"><span>Teslim formu metni</span><textarea class="inp" name="teslim_metni" rows="3" placeholder="<?= e(teslim_metni_varsayilan($tanim)) ?>"><?= e((string)$u['teslim_metni']) ?></textarea></label>
    <label class="frow wide"><span>Rapor alt notu</span><textarea class="inp" name="rapor_notu" rows="3" placeholder="Örn: İşçilik 6 ay garantilidir. IBAN: TR..."><?= e((string)$u['rapor_notu']) ?></textarea></label>
  </div><small class="muted">Metin alanlarını boş bırakırsan gri yazılı varsayılan metin kullanılır.</small></div>
  <div class="alt"><span></span><button class="btn btn-main">Kaydet</button></div>
</form>
<?php panel_bitir();
