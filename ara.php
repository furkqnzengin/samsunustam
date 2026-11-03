<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';

$hSlug = get('h');
$kSlug = get('k');
$iSlug = get('i');
$metin = mb_substr(get('q'), 0, 60);
$acil = get('acil') === '1';

$hizmet = $hSlug ? row('SELECT h.*, k.slug AS kslug FROM hizmetler h JOIN kategoriler k ON k.id = h.kategori_id WHERE h.slug = ?', [$hSlug]) : null;
$kategori = $kSlug ? row('SELECT * FROM kategoriler WHERE slug = ?', [$kSlug]) : null;
if ($hizmet && !$kategori) $kategori = row('SELECT * FROM kategoriler WHERE id = ?', [$hizmet['kategori_id']]);
$ilce = $iSlug ? row('SELECT * FROM ilceler WHERE slug = ?', [$iSlug]) : null;

// Aynı oturumda aynı hizmeti tekrar tekrar saymamak için
if ($hizmet && empty($_SESSION['aranan'][$hizmet['id']])) {
    arama_kaydet((int)$hizmet['id']);
    $_SESSION['aranan'][$hizmet['id']] = 1;
}

$where = ['1=1'];
$params = [];
if ($hizmet) {
    $where[] = 'EXISTS (SELECT 1 FROM usta_hizmet uh WHERE uh.usta_id = u.id AND uh.hizmet_id = ?)';
    $params[] = $hizmet['id'];
} elseif ($kategori) {
    $where[] = 'EXISTS (SELECT 1 FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id WHERE uh.usta_id = u.id AND h.kategori_id = ?)';
    $params[] = $kategori['id'];
}
if ($ilce) {
    $where[] = 'EXISTS (SELECT 1 FROM usta_ilce ui WHERE ui.usta_id = u.id AND ui.ilce_id = ?)';
    $params[] = $ilce['id'];
}
if ($metin !== '') {
    $where[] = '(u.ad_soyad LIKE ? OR u.meslek LIKE ? OR u.hakkinda LIKE ?)';
    $like = '%' . $metin . '%';
    array_push($params, $like, $like, $like);
}
$order = $acil ? 'u.acil_hizmet DESC, ' : '';
$order .= "(p.one_cikar = 1 AND (u.paket_bitis IS NULL OR u.paket_bitis >= CURDATE())) DESC, u.puan DESC, u.yorum_sayisi DESC, u.id DESC";
$ustalar = rows(usta_kart_sql(implode(' AND ', $where), $order) . ' LIMIT 60', $params);

$kategoriler = rows('SELECT * FROM kategoriler ORDER BY sira');
$hizmetler = rows('SELECT * FROM hizmetler ORDER BY sira');
$ilceler = rows('SELECT * FROM ilceler ORDER BY sira');

$ne = $hizmet ? $hizmet['ad'] : ($kategori ? $kategori['ad'] : 'Usta');
$nerede = $ilce ? ek_de($ilce['ad']) : "Samsun'da";
$baslik = $hizmet || $kategori ? "$nerede $ne" : ($ilce ? "$nerede ustalar" : "Samsun'daki ustalar");

site_basla($baslik, "$nerede güvenilir " . tr_lower($ne) . " ustaları: puanlar, yorumlar ve iletişim bilgileri.");
?>
<section class="pagehead"><div class="wrap">
  <span class="num">USTA BUL</span>
  <h1><?= e($baslik) ?></h1>
  <p class="muted"><?= count($ustalar) ? count($ustalar) . ' usta bulundu' : 'Bu aramaya uygun usta bulunamadı' ?><?= $acil ? ' · 7/24 acil hizmet verenler önce' : '' ?></p>
  <form class="filters" method="get">
    <select name="h" onchange="this.form.k.value='';this.form.submit()">
      <option value="">Tüm hizmetler</option>
      <?php foreach ($kategoriler as $k): ?><optgroup label="<?= e($k['ad']) ?>">
        <?php foreach ($hizmetler as $h) if ($h['kategori_id'] == $k['id']): ?><option value="<?= e($h['slug']) ?>" <?= $hizmet && $hizmet['id'] == $h['id'] ? 'selected' : '' ?>><?= e($h['ad']) ?></option><?php endif; ?>
      </optgroup><?php endforeach; ?>
    </select>
    <input type="hidden" name="k" value="<?= $hizmet ? '' : e($kategori['slug'] ?? '') ?>">
    <select name="i" onchange="this.form.submit()">
      <option value="">Tüm ilçeler</option>
      <?php foreach ($ilceler as $i): ?><option value="<?= e($i['slug']) ?>" <?= $ilce && $ilce['id'] == $i['id'] ? 'selected' : '' ?>><?= e($i['ad']) ?></option><?php endforeach; ?>
    </select>
    <input name="q" placeholder="İsim veya kelime ara" value="<?= e($metin) ?>">
    <?php if ($acil): ?><input type="hidden" name="acil" value="1"><?php endif; ?>
    <button class="btn btn-dark">Ara</button>
  </form>
  <?php if ($kategori && !$hizmet): ?>
    <div class="chips"><?php foreach ($hizmetler as $h) if ($h['kategori_id'] == $kategori['id']): ?><a href="?h=<?= e($h['slug']) ?><?= $ilce ? '&i=' . e($ilce['slug']) : '' ?>"><?= e($h['ad']) ?></a><?php endif; ?></div>
  <?php endif; ?>
</div></section>

<section style="padding-top:8px"><div class="wrap">
  <?php if ($ustalar): ?>
    <div class="ustalar"><?php foreach ($ustalar as $u) echo usta_karti($u); ?></div>
  <?php else: ?>
    <div class="empty"><div class="big">🔎</div><h3>Henüz bu aramaya uygun usta yok.</h3>
      <p><?= $ilce ? 'İlçe seçimini kaldırıp tüm Samsun\'da aramayı deneyebilirsin.' : 'Farklı bir hizmet ya da ilçe seçmeyi dene.' ?></p>
      <?php if ($ilce): ?><a class="btn btn-dark" href="?<?= http_build_query(array_filter(['h' => $hSlug, 'k' => $kSlug])) ?>">Tüm Samsun'da ara →</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div></section>
<?php site_bitir();
