<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
$u = aktif_usta();
$uid = (int)$u['id'];

$ay = preg_match('/^\d{4}-\d{2}$/', get('ay')) ? get('ay') : date('Y-m');
$bas = $ay . '-01';
$bit = date('Y-m-t', strtotime($bas));

if (is_post()) {
    csrf_check();
    if (post('islem') === 'sil') {
        q('DELETE FROM kasa WHERE id = ? AND usta_id = ?', [(int)post('id'), $uid]);
        flash('Kayıt silindi.', 'info');
    } else {
        $tur = post('tur') === 'gelir' ? 'gelir' : 'gider';
        $tutar = para_oku(post('tutar'));
        $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', post('tarih')) ? post('tarih') : date('Y-m-d');
        $kat = mb_substr(post('kategori'), 0, 60) ?: 'Diğer';
        if ($tutar <= 0) flash('Tutarı yaz.', 'err');
        else {
            q('INSERT INTO kasa (usta_id, tur, kategori, tutar, tarih, aciklama) VALUES (?,?,?,?,?,?)', [$uid, $tur, $kat, $tutar, $tarih, mb_substr(post('aciklama'), 0, 200) ?: null]);
            flash(($tur === 'gelir' ? 'Gelir' : 'Gider') . ' eklendi: ' . para($tutar));
            $ay = substr($tarih, 0, 7);
        }
    }
    redirect('usta/kasa.php?ay=' . $ay);
}

// Ayın hareketleri: servis tahsilatları + elle girilen gelir/giderler
$hareket = rows("SELECT 'tahsilat' AS kaynak, o.id, 'gelir' AS tur, CONCAT('Servis #', s.no) AS kategori, o.tutar, o.tarih,
        CONCAT(s.baslik, ' · ', s.musteri_ad) AS aciklama, s.id AS servis_id, o.yontem
      FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE s.usta_id = ? AND o.tarih BETWEEN ? AND ?
    UNION ALL
    SELECT 'kasa', k.id, k.tur, k.kategori, k.tutar, k.tarih, k.aciklama, NULL, NULL FROM kasa k WHERE k.usta_id = ? AND k.tarih BETWEEN ? AND ?
    ORDER BY tarih DESC, id DESC", [$uid, $bas, $bit, $uid, $bas, $bit]);

if (get('csv') === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="gelir-gider-' . $ay . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel'in Türkçe karakterleri doğru açması için
    fputcsv($out, ['Tarih', 'Tür', 'Kategori', 'Açıklama', 'Tutar'], ';');
    foreach ($hareket as $h) {
        fputcsv($out, [date('d.m.Y', strtotime($h['tarih'])), $h['tur'] === 'gelir' ? 'Gelir' : 'Gider', $h['kategori'], (string)$h['aciklama'],
            ($h['tur'] === 'gider' ? '-' : '') . number_format((float)$h['tutar'], 2, ',', '')], ';');
    }
    exit;
}

$tahsilat = $digerGelir = $gider = 0.0;
$giderKat = [];
foreach ($hareket as $h) {
    if ($h['kaynak'] === 'tahsilat') $tahsilat += (float)$h['tutar'];
    elseif ($h['tur'] === 'gelir') $digerGelir += (float)$h['tutar'];
    else { $gider += (float)$h['tutar']; $giderKat[$h['kategori']] = ($giderKat[$h['kategori']] ?? 0) + (float)$h['tutar']; }
}
arsort($giderKat);
$net = $tahsilat + $digerGelir - $gider;

// Son 6 ay gelir / gider
$aylar = [];
for ($i = 5; $i >= 0; $i--) $aylar[date('Y-m', strtotime("$bas -$i month"))] = ['g' => 0.0, 'c' => 0.0];
$ilk = array_key_first($aylar) . '-01';
foreach (rows("SELECT DATE_FORMAT(o.tarih, '%Y-%m') ay, SUM(o.tutar) n FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE s.usta_id = ? AND o.tarih BETWEEN ? AND ? GROUP BY DATE_FORMAT(o.tarih, '%Y-%m')", [$uid, $ilk, $bit]) as $r) if (isset($aylar[$r['ay']])) $aylar[$r['ay']]['g'] += (float)$r['n'];
foreach (rows("SELECT DATE_FORMAT(tarih, '%Y-%m') ay, tur, SUM(tutar) n FROM kasa WHERE usta_id = ? AND tarih BETWEEN ? AND ? GROUP BY DATE_FORMAT(tarih, '%Y-%m'), tur", [$uid, $ilk, $bit]) as $r) if (isset($aylar[$r['ay']])) $aylar[$r['ay']][$r['tur'] === 'gelir' ? 'g' : 'c'] += (float)$r['n'];
$max = max(1, ...array_values(array_map(fn($a) => max($a['g'], $a['c']), $aylar)));

$tutar = servis_tutar_sql();
$odenen = servis_odenen_sql();
$alacaklar = rows("SELECT * FROM (SELECT s.id, s.no, s.baslik, s.musteri_ad, s.musteri_tel, s.giris_tarihi, $tutar - $odenen AS kalan FROM servisler s WHERE s.usta_id = ? AND s.durum <> 'iptal') x WHERE kalan > 0.009 ORDER BY giris_tarihi LIMIT 50", [$uid]);
$toplamAlacak = array_sum(array_column($alacaklar, 'kalan'));

$ayAdlari = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$ayAd = fn(string $ym) => $ayAdlari[(int)substr($ym, 5, 2) - 1] . ' ' . substr($ym, 0, 4);
$onceki = date('Y-m', strtotime("$bas -1 month"));
$sonraki = date('Y-m', strtotime("$bas +1 month"));

panel_basla('usta', 'kasa.php', 'Gelir - Gider', 'Servis tahsilatların otomatik gelir olarak yazılır. Giderlerini ve diğer gelirlerini buradan ekle.');
?>
<div class="filterbar monthbar">
  <a href="?ay=<?= $onceki ?>">← <?= e($ayAd($onceki)) ?></a>
  <b class="curmonth"><?= e($ayAd($ay)) ?></b>
  <?php if ($ay < date('Y-m')): ?><a href="?ay=<?= $sonraki ?>"><?= e($ayAd($sonraki)) ?> →</a><?php endif; ?>
  <a class="fsearch btn btn-soft" href="?ay=<?= $ay ?>&csv=1">⬇ Excel'e aktar</a>
</div>

<div class="stats">
  <div class="stat"><div class="ic">🛠️</div><div><small>Servis tahsilatları</small><b><?= para($tahsilat) ?></b></div></div>
  <div class="stat"><div class="ic">➕</div><div><small>Diğer gelirler</small><b><?= para($digerGelir) ?></b></div></div>
  <div class="stat"><div class="ic" style="background:var(--red100)">➖</div><div><small>Giderler</small><b><?= para($gider) ?></b></div></div>
  <div class="stat"><div class="ic">📊</div><div><small>Net kazanç</small><b class="<?= $net < 0 ? 'down' : '' ?>"><?= para($net) ?></b></div></div>
</div>

<div class="row">
  <div class="card">
    <h2>Son 6 ay</h2>
    <div class="chart2">
      <?php foreach ($aylar as $ym => $a): ?>
        <div class="col"><div class="bars2"><i class="g" style="height:<?= round($a['g'] / $max * 100) ?>%" title="Gelir <?= e(para($a['g'])) ?>"></i><i class="c" style="height:<?= round($a['c'] / $max * 100) ?>%" title="Gider <?= e(para($a['c'])) ?>"></i></div><span><?= e(mb_substr($ayAd($ym), 0, 3)) ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="legend"><span><i class="g"></i> Gelir</span><span><i class="c"></i> Gider</span></div>
  </div>
  <form method="post" class="card"><?= csrf_field() ?>
    <h2>Yeni gelir / gider</h2>
    <div class="seg"><label><input type="radio" name="tur" value="gider" checked><span>➖ Gider</span></label><label><input type="radio" name="tur" value="gelir"><span>➕ Gelir</span></label></div>
    <div class="form one">
      <label>Kategori<input class="inp" name="kategori" list="katlar" required placeholder="Seç ya da yaz" autocomplete="off"></label>
      <datalist id="katlar"><?php foreach (array_unique([...GIDER_KATEGORILERI, ...GELIR_KATEGORILERI]) as $k): ?><option value="<?= e($k) ?>"><?php endforeach; ?></datalist>
      <label>Tutar (₺)<input class="inp num" name="tutar" inputmode="decimal" required placeholder="0,00"></label>
      <label>Tarih<input class="inp" type="date" name="tarih" value="<?= $ay === date('Y-m') ? date('Y-m-d') : $bas ?>"></label>
      <label>Açıklama<input class="inp" name="aciklama" maxlength="200" placeholder="Örn: Yağ filtresi alımı (10 adet)"></label>
    </div>
    <div class="formfoot"><button class="btn btn-main">Ekle</button></div>
  </form>
</div>

<div class="row">
  <div class="card">
    <h2><?= e($ayAd($ay)) ?> hareketleri <span class="muted small"><?= count($hareket) ?> kayıt</span></h2>
    <?php if ($hareket): ?><div class="list">
      <?php foreach ($hareket as $h): ?>
        <div class="li"><div class="kico <?= $h['tur'] ?>"><?= $h['tur'] === 'gelir' ? '+' : '−' ?></div>
          <div class="grow"><b><?= e($h['kategori']) ?></b> <small class="muted">· <?= date('d.m.Y', strtotime($h['tarih'])) ?><?= $h['yontem'] ? ' · ' . ODEME_YONTEMLERI[$h['yontem']] : '' ?></small>
            <?php if ($h['aciklama']): ?><br><small class="muted"><?= $h['servis_id'] ? '<a class="link" href="servis-kayit.php?id=' . (int)$h['servis_id'] . '">' . e($h['aciklama']) . '</a>' : e($h['aciklama']) ?></small><?php endif; ?></div>
          <b class="<?= $h['tur'] === 'gelir' ? 'up' : 'down' ?>" style="white-space:nowrap"><?= $h['tur'] === 'gelir' ? '+' : '−' ?><?= para($h['tutar']) ?></b>
          <?php if ($h['kaynak'] === 'kasa'): ?><form method="post" onsubmit="return confirm('Silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"><input type="hidden" name="id" value="<?= $h['id'] ?>"><button class="kdel" title="Sil">✕</button></form><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div><?php else: ?><p class="muted">Bu ay hareket yok.</p><?php endif; ?>
  </div>
  <div>
    <?php if ($giderKat): ?><div class="card"><h2>Giderlerin dağılımı</h2><div class="bars">
      <?php foreach ($giderKat as $k => $t): ?><div class="bar"><span><?= e($k) ?></span><div class="track"><div class="fill" style="width:<?= round($t / max($giderKat) * 100) ?>%;background:linear-gradient(90deg,#f08a8a,#d64545)"></div></div><span style="font-size:12px"><?= e(para($t)) ?></span></div><?php endforeach; ?>
    </div></div><?php endif; ?>
    <div class="card"><h2>Alacaklar <span class="muted small">toplam <?= para($toplamAlacak) ?></span></h2>
      <?php if ($alacaklar): ?><div class="list"><?php foreach ($alacaklar as $a): ?>
        <div class="li"><div class="grow"><a class="link" href="servis-kayit.php?id=<?= $a['id'] ?>">#<?= (int)$a['no'] ?> <?= e($a['baslik']) ?></a><br><small class="muted"><?= e($a['musteri_ad']) ?> · <?= date('d.m.Y', strtotime($a['giris_tarihi'])) ?></small></div>
          <b class="down" style="white-space:nowrap"><?= para($a['kalan']) ?></b>
          <?php if ($a['musteri_tel']): ?><a class="btn btn-soft" href="<?= e(wa_link($a['musteri_tel'], "Merhaba {$a['musteri_ad']}, #{$a['no']} numaralı {$a['baslik']} kaydınızdan " . para($a['kalan']) . " ödemeniz kalmıştır. Bilginize. " . $u['ad_soyad'])) ?>" target="_blank" rel="noopener" title="WhatsApp ile hatırlat">💬</a><?php endif; ?></div>
      <?php endforeach; ?></div><?php else: ?><p class="muted">Bekleyen alacak yok. 👍</p><?php endif; ?>
    </div>
  </div>
</div>
<?php panel_bitir();
