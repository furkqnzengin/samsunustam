<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
giris_gerekli('admin');

$id = (int)get('id');
$u = row('SELECT u.*, k.eposta, k.son_giris, p.ad AS paket_ad, p.one_cikar FROM ustalar u JOIN kullanicilar k ON k.id = u.kullanici_id LEFT JOIN paketler p ON p.id = u.paket_id WHERE u.id = ?', [$id]);
if (!$u) { flash('Usta bulunamadı.', 'err'); redirect('yonetim/ustalar.php'); }

if (is_post()) {
    csrf_check();
    $islem = post('islem');
    switch ($islem) {
        case 'onayla':
            q("UPDATE ustalar SET durum = 'aktif', red_nedeni = NULL WHERE id = ?", [$id]);
            flash($u['ad_soyad'] . ' onaylandı ve yayına alındı.');
            break;
        case 'reddet':
            q("UPDATE ustalar SET durum = 'reddedildi', red_nedeni = ? WHERE id = ?", [mb_substr(post('neden'), 0, 255) ?: null, $id]);
            flash($u['ad_soyad'] . ' başvurusu reddedildi.', 'info');
            break;
        case 'askiya_al':
            q("UPDATE ustalar SET durum = 'askida' WHERE id = ?", [$id]);
            flash('Usta askıya alındı, sitede görünmüyor.', 'info');
            break;
        case 'aktif_et':
            q("UPDATE ustalar SET durum = 'aktif' WHERE id = ?", [$id]);
            flash('Usta tekrar aktif.');
            break;
        case 'belgeli':
            q('UPDATE ustalar SET belgeli = ? WHERE id = ?', [post('deger') ? 1 : 0, $id]);
            flash(post('deger') ? 'Belgeli rozeti verildi.' : 'Belgeli rozeti kaldırıldı.');
            break;
        case 'paket':
            $pid = (int)post('paket_id') ?: null;
            $bitis = post('paket_bitis');
            $bitis = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bitis) ? $bitis : null;
            q('UPDATE ustalar SET paket_id = ?, paket_bitis = ? WHERE id = ?', [$pid, $bitis, $id]);
            if ($pid) q("UPDATE paket_talepleri SET durum = 'gorusuldu' WHERE usta_id = ? AND paket_id = ?", [$id, $pid]);
            flash('Paket güncellendi.');
            break;
        case 'sil':
            if (post('onay') === 'SIL') {
                foreach (rows('SELECT dosya FROM fotograflar WHERE usta_id = ?', [$id]) as $f) dosya_sil($f['dosya']);
                dosya_sil($u['foto']);
                dosya_sil($u['belge_dosya']);
                @rmdir(ROOT . '/uploads/ustalar/' . $id);
                q('DELETE FROM kullanicilar WHERE id = ?', [$u['kullanici_id']]);
                flash($u['ad_soyad'] . ' ve tüm bilgileri silindi.', 'info');
                redirect('yonetim/ustalar.php');
            }
            flash('Silmek için kutuya SIL yazmalısın.', 'err');
            break;
        case 'sifre':
            $yeni = bin2hex(random_bytes(4));
            q('UPDATE kullanicilar SET sifre = ? WHERE id = ?', [password_hash($yeni, PASSWORD_DEFAULT), $u['kullanici_id']]);
            flash('Yeni geçici şifre: ' . $yeni . ' (ustaya ilet, giriş yapınca değiştirmesini söyle)');
            break;
    }
    if (post('geri') === 'index') redirect('yonetim/');
    redirect('yonetim/usta.php?id=' . $id);
}

$hizmetler = rows('SELECT h.ad FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id WHERE uh.usta_id = ? ORDER BY h.sira', [$id]);
$ilceler = rows('SELECT i.ad FROM usta_ilce ui JOIN ilceler i ON i.id = ui.ilce_id WHERE ui.usta_id = ? ORDER BY i.sira', [$id]);
$fotolar = rows('SELECT * FROM fotograflar WHERE usta_id = ? ORDER BY id DESC', [$id]);
$paketler = rows('SELECT * FROM paketler ORDER BY sira');
$stat = [];
foreach (rows('SELECT tur, SUM(adet) n FROM olaylar WHERE usta_id = ? AND tarih > CURDATE() - INTERVAL 30 DAY GROUP BY tur', [$id]) as $r) $stat[$r['tur']] = (int)$r['n'];
$talepSay = (int)val('SELECT COUNT(*) FROM talepler WHERE usta_id = ?', [$id]);

$btn = fn(string $islem, string $etiket, string $cls, string $ek = '') => '<form method="post">' . csrf_field() . '<input type="hidden" name="islem" value="' . $islem . '">' . $ek . '<button class="btn ' . $cls . '">' . $etiket . '</button></form>';

panel_basla('admin', 'ustalar.php', $u['ad_soyad'], e($u['meslek']) . ' · ' . durum_pill($u['durum']) . ' · Kayıt: ' . e(date('d.m.Y', strtotime($u['olusturma']))));
?>
<div class="card actionbar">
  <?php if ($u['durum'] === 'beklemede' || $u['durum'] === 'reddedildi'): ?>
    <?= $btn('onayla', '✓ Onayla ve yayına al', 'btn-main') ?>
  <?php elseif ($u['durum'] === 'aktif'): ?>
    <?= $btn('askiya_al', 'Askıya al', 'btn-ghost') ?>
  <?php else: ?>
    <?= $btn('aktif_et', 'Tekrar aktif et', 'btn-main') ?>
  <?php endif; ?>
  <?php if ($u['durum'] === 'beklemede'): ?>
    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="islem" value="reddet"><input class="inp" name="neden" placeholder="Red nedeni (ustaya gösterilir)"><button class="btn btn-red">Reddet</button></form>
  <?php endif; ?>
  <a class="btn btn-soft" href="../usta.php?u=<?= e($u['slug']) ?>" target="_blank">Profili gör ↗</a>
</div>

<div class="row">
  <div class="card">
    <h2>Bilgiler</h2>
    <dl class="dl">
      <dt>Telefon</dt><dd><a class="link" href="tel:<?= e($u['telefon']) ?>"><?= e(tel_goster($u['telefon'])) ?></a></dd>
      <dt>WhatsApp</dt><dd><?= $u['whatsapp'] ? '<a class="link" target="_blank" rel="noopener" href="' . e(wa_link($u['whatsapp'])) . '">' . e(tel_goster($u['whatsapp'])) . '</a>' : '—' ?></dd>
      <dt>E-posta</dt><dd><?= e($u['eposta']) ?></dd>
      <dt>Tecrübe</dt><dd><?= $u['tecrube_yil'] ? (int)$u['tecrube_yil'] . ' yıl' : '—' ?></dd>
      <dt>Hizmetler</dt><dd><?= e(implode(', ', array_column($hizmetler, 'ad')) ?: '—') ?></dd>
      <dt>İlçeler</dt><dd><?= e(implode(', ', array_column($ilceler, 'ad')) ?: '—') ?></dd>
      <dt>Özellikler</dt><dd><?= implode(' ', array_filter([$u['kesif_ucretsiz'] ? '<span class="pill p-green">Keşif ücretsiz</span>' : '', $u['acil_hizmet'] ? '<span class="pill p-red">7/24 acil</span>' : ''])) ?: '—' ?></dd>
      <dt>Son giriş</dt><dd><?= $u['son_giris'] ? zaman_once($u['son_giris']) : 'Hiç' ?></dd>
    </dl>
    <?php if ($u['hakkinda']): ?><h3 class="h3">Hakkında</h3><p class="pre"><?= e($u['hakkinda']) ?></p><?php endif; ?>
    <?php if ($u['red_nedeni']): ?><p class="muted">Red nedeni: <?= e($u['red_nedeni']) ?></p><?php endif; ?>
  </div>
  <div>
    <div class="card" style="margin-bottom:20px">
      <h2>Son 30 gün</h2>
      <div class="mini"><div><b><?= sayi($stat['goruntuleme'] ?? 0) ?></b><small>görüntülenme</small></div><div><b><?= sayi($stat['arama'] ?? 0) ?></b><small>arama</small></div><div><b><?= sayi($stat['whatsapp'] ?? 0) ?></b><small>WhatsApp</small></div><div><b><?= $talepSay ?></b><small>toplam talep</small></div></div>
    </div>
    <div class="card" style="margin-bottom:20px">
      <h2>Belge</h2>
      <?php if ($u['belge_dosya']): ?>
        <p><a class="btn btn-soft" href="belge.php?id=<?= $id ?>" target="_blank">📄 Belgeyi aç</a> <?= $u['belgeli'] ? '<span class="pill p-green">✔ Onaylı</span>' : '<span class="pill p-amber">Kontrol bekliyor</span>' ?></p>
      <?php else: ?><p class="muted">Belge yüklenmemiş.</p><?php endif; ?>
      <div style="margin-top:10px"><?= $btn('belgeli', $u['belgeli'] ? 'Belgeli rozetini kaldır' : '✔ Belgeli rozeti ver', $u['belgeli'] ? 'btn-ghost' : 'btn-main', '<input type="hidden" name="deger" value="' . ($u['belgeli'] ? 0 : 1) . '">') ?></div>
    </div>
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="islem" value="paket">
      <h2>Üyelik Paketi</h2>
      <div class="form one">
        <label>Paket<select class="inp" name="paket_id"><option value="">Paket yok</option><?php foreach ($paketler as $p): ?><option value="<?= $p['id'] ?>" <?= (int)$u['paket_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['ad']) ?><?= $p['one_cikar'] ? ' (öne çıkarır)' : '' ?></option><?php endforeach; ?></select></label>
        <label>Bitiş tarihi <small class="muted">(boşsa süresiz)</small><input class="inp" type="date" name="paket_bitis" value="<?= e($u['paket_bitis']) ?>"></label>
      </div>
      <div class="formfoot"><button class="btn btn-main">Paketi kaydet</button></div>
    </form>
  </div>
</div>

<?php if ($fotolar): ?><div class="card"><h2>İş fotoğrafları</h2><div class="photos"><?php foreach ($fotolar as $f): ?><div class="ph"><a href="<?= e(upload_url($f['dosya'])) ?>" target="_blank"><img src="<?= e(upload_url($f['dosya'])) ?>" alt="" loading="lazy"></a></div><?php endforeach; ?></div></div><?php endif; ?>

<div class="card danger">
  <h2>Diğer işlemler</h2>
  <div class="acts wrapacts">
    <?= $btn('sifre', '🔑 Geçici şifre oluştur', 'btn-ghost') ?>
    <form method="post" class="inline-form" onsubmit="return confirm('Bu usta ve tüm bilgileri kalıcı olarak silinecek. Emin misin?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><input class="inp" name="onay" placeholder="Silmek için SIL yaz" style="max-width:180px"><button class="btn btn-red">Ustayı sil</button></form>
  </div>
</div>
<?php panel_bitir();
