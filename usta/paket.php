<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];

if (is_post()) {
    csrf_check();
    $p = row('SELECT * FROM paketler WHERE id = ? AND aktif = 1', [(int)post('paket')]);
    if ($p) {
        $var = val("SELECT id FROM paket_talepleri WHERE usta_id = ? AND paket_id = ? AND durum = 'yeni'", [$uid, $p['id']]);
        if (!$var) q('INSERT INTO paket_talepleri (usta_id, paket_id) VALUES (?, ?)', [$uid, $p['id']]);
        flash($p['ad'] . ' paketi için talebin alındı. En kısa sürede seninle iletişime geçeceğiz.');
    }
    redirect('usta/paket.php');
}

$paketler = rows('SELECT * FROM paketler WHERE aktif = 1 ORDER BY sira, id');
$bekleyen = array_map('intval', array_column(rows("SELECT paket_id FROM paket_talepleri WHERE usta_id = ? AND durum = 'yeni'", [$uid]), 'paket_id'));
$tel = ayar('site_whatsapp') ?: ayar('site_telefon');

panel_basla('usta', 'paket.php', 'Üyelik Paketim', 'Mevcut paketin: <b>' . e($u['paket_ad'] ?? 'Standart') . '</b>' . ($u['paket_bitis'] ? ' · Bitiş: ' . e(date('d.m.Y', strtotime($u['paket_bitis']))) : ''));
?>
<div class="packs">
  <?php foreach ($paketler as $p): $mevcut = (int)$u['paket_id'] === (int)$p['id']; ?>
    <div class="pack<?= $p['one_cikar'] ? ' featured' : '' ?>">
      <?php if ($p['one_cikar']): ?><span class="ribbon">★ Önerilen</span><?php endif; ?>
      <h2><?= e($p['ad']) ?></h2>
      <p class="muted"><?= e($p['aciklama']) ?></p>
      <div class="price"><?= $p['fiyat'] ? e($p['fiyat']) : 'Fiyat bilgisi için bize ulaşın' ?></div>
      <ul><?php foreach (array_filter(array_map('trim', explode("\n", (string)$p['ozellikler']))) as $o): ?><li>✓ <?= e($o) ?></li><?php endforeach; ?></ul>
      <?php if ($mevcut): ?><span class="btn btn-soft full">Mevcut paketin</span>
      <?php elseif (in_array((int)$p['id'], $bekleyen, true)): ?><span class="btn btn-soft full">Talebin alındı, aranacaksın</span>
      <?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="paket" value="<?= $p['id'] ?>"><button class="btn btn-main full">Bilgi almak istiyorum</button></form><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($tel): ?><p class="muted center" style="margin-top:18px">Hemen konuşmak istersen: <a class="link" href="<?= e(wa_link($tel, 'Merhaba, Samsun Ustam üyelik paketleri hakkında bilgi almak istiyorum.')) ?>" target="_blank" rel="noopener">WhatsApp'tan yaz</a> · <a class="link" href="tel:<?= e(tel_temizle($tel)) ?>"><?= e(tel_goster($tel)) ?></a></p><?php endif; ?>
<?php panel_bitir();
