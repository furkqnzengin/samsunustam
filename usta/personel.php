<?php
// Personel: servis danışmanı ve teknisyenler (servise atanır)
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];
const GOREVLER = ['danisman' => 'Servis danışmanı', 'teknisyen' => 'Teknisyen / usta', 'diger' => 'Diğer'];

if (is_post()) {
    csrf_check();
    $pid = (int)post('id');
    if (post('islem') === 'sil') { q('DELETE FROM personel WHERE id = ? AND usta_id = ?', [$pid, $uid]); flash('Personel silindi.', 'info'); }
    elseif (post('islem') === 'aktif') { q('UPDATE personel SET aktif = 1 - aktif WHERE id = ? AND usta_id = ?', [$pid, $uid]); }
    else {
        $ad = mb_substr(preg_replace('/\s+/u', ' ', post('ad')), 0, 100);
        $tel = post('telefon');
        if (mb_strlen($ad) < 2) flash('Personelin adını yazın.', 'err');
        elseif ($tel !== '' && !tel_gecerli($tel)) flash('Telefon numarası geçersiz.', 'err');
        else {
            $g = isset(GOREVLER[post('gorev')]) ? post('gorev') : 'teknisyen';
            if ($pid) q('UPDATE personel SET ad = ?, gorev = ?, telefon = ? WHERE id = ? AND usta_id = ?', [$ad, $g, $tel !== '' ? tel_temizle($tel) : null, $pid, $uid]);
            else q('INSERT INTO personel (usta_id, ad, gorev, telefon) VALUES (?,?,?,?)', [$uid, $ad, $g, $tel !== '' ? tel_temizle($tel) : null]);
            flash($pid ? 'Personel güncellendi.' : 'Personel eklendi.');
        }
    }
    redirect('usta/personel.php');
}
$liste = rows('SELECT p.*, (SELECT COUNT(*) FROM servisler s WHERE s.usta_id = p.usta_id AND (s.teknisyen = p.ad OR s.danisman = p.ad)) AS is_say FROM personel p WHERE p.usta_id = ? ORDER BY p.aktif DESC, p.ad', [$uid]);
$duz = (int)get('duzenle');
$d = $duz ? row('SELECT * FROM personel WHERE id = ? AND usta_id = ?', [$duz, $uid]) : null;

panel_basla('usta', 'personel.php', 'Personel', 'Servis kayıtlarında danışman ve teknisyen olarak seçilir. Sen listede her zaman varsın.');
?>
<div class="row">
  <div class="card"><h2>Personel listesi</h2>
    <?php if ($liste): ?><div class="list"><?php foreach ($liste as $p): ?>
      <div class="li" style="<?= $p['aktif'] ? '' : 'opacity:.55' ?>"><div class="av sm"><?= e(initials($p['ad'])) ?></div>
        <div class="grow"><b><?= e($p['ad']) ?></b> <span class="pill p-blue"><?= e(GOREVLER[$p['gorev']]) ?></span><?= $p['aktif'] ? '' : ' <span class="pill p-red">Pasif</span>' ?><br>
          <small><?= $p['telefon'] ? e(tel_goster($p['telefon'])) . ' · ' : '' ?><?= (int)$p['is_say'] ?> servis kaydı</small></div>
        <div class="acts"><a class="btn btn-soft" href="?duzenle=<?= $p['id'] ?>">Düzenle</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="islem" value="aktif"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-ghost"><?= $p['aktif'] ? 'Pasif yap' : 'Aktif yap' ?></button></form>
          <form method="post" onsubmit="return confirm('Personel silinsin mi? Eski kayıtlarda adı kalır.')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-red">Sil</button></form></div></div>
    <?php endforeach; ?></div><?php else: ?><p class="muted">Henüz personel eklemedin.</p><?php endif; ?>
  </div>
  <form method="post" class="kutu"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d ? (int)$d['id'] : '' ?>">
    <h3><?= $d ? 'Personeli düzenle' : '+ Personel ekle' ?></h3>
    <div class="ic"><div class="fgrid" style="grid-template-columns:1fr">
      <label class="frow"><span>Ad soyad</span><input class="inp" name="ad" required maxlength="100" value="<?= e($d['ad'] ?? '') ?>"></label>
      <label class="frow"><span>Görev</span><select class="inp" name="gorev"><?php foreach (GOREVLER as $k => $ad): ?><option value="<?= $k ?>" <?= ($d['gorev'] ?? 'teknisyen') === $k ? 'selected' : '' ?>><?= $ad ?></option><?php endforeach; ?></select></label>
      <label class="frow"><span>Telefon</span><input class="inp" name="telefon" type="tel" value="<?= e(!empty($d['telefon']) ? tel_goster($d['telefon']) : '') ?>"></label>
    </div></div>
    <div class="alt"><?php if ($d): ?><a class="btn btn-ghost" href="personel.php">Vazgeç</a><?php else: ?><span></span><?php endif; ?><button class="btn btn-main"><?= $d ? 'Kaydet' : 'Ekle' ?></button></div>
  </form>
</div>
<?php panel_bitir();
