<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
require __DIR__ . '/../inc/servis.php';
$u = aktif_usta();
$uid = (int)$u['id'];

if (is_post()) {
    csrf_check();
    $sek = post('servis_sektor');
    q('UPDATE ustalar SET servis_sektor = ?, rapor_notu = ? WHERE id = ?', [isset(sektorler()[$sek]) ? $sek : null, mb_substr(post('rapor_notu'), 0, 1500) ?: null, $uid]);
    flash('Servis ayarların kaydedildi.');
    redirect('usta/servis.php');
}

$sektor = usta_sektor($u);
$tanim = sektorler()[$sektor];
$durumlar = servis_durumlari();

$f = get('durum', 'acik');
$ara = mb_substr(get('q'), 0, 60);
$where = ['s.usta_id = ?'];
$params = [$uid];
if ($f === 'acik') $where[] = "s.durum IN ('kabul','islemde','parca','hazir')";
elseif ($f === 'borclu') $where[] = "s.durum <> 'iptal'";
elseif (isset($durumlar[$f])) { $where[] = 's.durum = ?'; $params[] = $f; }
if ($ara !== '') {
    $where[] = '(s.baslik LIKE ? OR s.musteri_ad LIKE ? OR s.musteri_firma LIKE ? OR s.anahtar LIKE ? OR s.musteri_tel LIKE ? OR s.no = ?)';
    $like = "%$ara%";
    array_push($params, $like, $like, $like, '%' . anahtar_norm($ara) . '%', '%' . (preg_replace('/\D/', '', $ara) ?: '#') . '%', (int)$ara);
}
$tutar = servis_tutar_sql();
$odenen = servis_odenen_sql();
$sql = "SELECT * FROM (SELECT s.*, $tutar AS toplam, $odenen AS odenen FROM servisler s WHERE " . implode(' AND ', $where) . ') x';
if ($f === 'borclu') $sql .= ' WHERE toplam - odenen > 0.009';
$sql .= " ORDER BY durum IN ('teslim','iptal'), giris_tarihi DESC LIMIT 300";
$liste = rows($sql, $params);

$ozet = row("SELECT
    SUM(s.durum IN ('kabul','islemde','parca','hazir')) AS acik,
    SUM(s.durum = 'hazir') AS hazir,
    SUM(CASE WHEN s.durum <> 'iptal' THEN $tutar - $odenen ELSE 0 END) AS alacak
  FROM servisler s WHERE s.usta_id = ?", [$uid]);
$buAy = (float)val("SELECT COALESCE(SUM(o.tutar),0) FROM servis_odemeleri o JOIN servisler s ON s.id = o.servis_id WHERE s.usta_id = ? AND o.tarih >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [$uid]);

panel_basla('usta', 'servis.php', 'Servis Takip', $tanim['ikon'] . ' ' . e($tanim['ad']) . ' · ' . e($tanim['liste']));
?>
<div class="stats">
  <div class="stat"><div class="ic">🛠️</div><div><small>Açık kayıt</small><b><?= (int)$ozet['acik'] ?></b><span class="up"><?= (int)$ozet['hazir'] ?> tanesi teslime hazır</span></div></div>
  <a class="stat" href="kasa.php"><div class="ic">💰</div><div><small>Bu ay tahsilat</small><b><?= para($buAy) ?></b><span class="up">Gelir-gider →</span></div></a>
  <a class="stat" href="?durum=borclu"><div class="ic" style="background:var(--amber100)">⏳</div><div><small>Alacak (kalan)</small><b><?= para(max(0, (float)$ozet['alacak'])) ?></b><span class="down">Ödenmemiş tutarlar</span></div></a>
  <a class="stat" href="servis-kayit.php"><div class="ic">➕</div><div><small>Yeni kayıt</small><b style="font-size:20px">Yeni <?= e(tr_lower($tanim['kayit'])) ?></b><span class="up">Kayıt aç →</span></div></a>
</div>

<div class="filterbar">
  <?php foreach (['acik' => 'Açık işler', 'hazir' => 'Teslime hazır', 'teslim' => 'Teslim edilen', 'borclu' => 'Borcu olan', 'tumu' => 'Tümü'] as $k => $ad): ?>
    <a class="<?= $f === $k ? 'on' : '' ?>" href="?durum=<?= $k ?>"><?= $ad ?></a>
  <?php endforeach; ?>
  <form class="fsearch" method="get"><input type="hidden" name="durum" value="tumu"><input class="inp" name="q" placeholder="<?= in_array($sektor, ['arac', 'agir'], true) ? 'Plaka, müşteri, telefon…' : 'Müşteri, iş, telefon, no…' ?>" value="<?= e($ara) ?>"></form>
</div>

<div class="card">
  <h2><?= e($tanim['liste']) ?> <a class="btn btn-main" style="color:#fff" href="servis-kayit.php">+ Yeni <?= e(tr_lower($tanim['kayit'])) ?></a></h2>
  <?php if ($liste): ?><div class="scroll"><table class="tbl">
    <tr><th>No</th><th><?= e($tanim['kayit']) ?></th><th class="hide-m">Müşteri</th><th class="hide-m">Giriş</th><th>Durum</th><th>Tutar</th><th class="hide-m">Kalan</th></tr>
    <?php foreach ($liste as $s): $kalan = (float)$s['toplam'] - (float)$s['odenen']; ?>
      <tr onclick="location='servis-kayit.php?id=<?= $s['id'] ?>'" style="cursor:pointer">
        <td><b>#<?= (int)$s['no'] ?></b></td>
        <td><a href="servis-kayit.php?id=<?= $s['id'] ?>"><b><?= e($s['baslik']) ?></b></a><small class="muted" style="display:block"><?= e($s['musteri_ad']) ?></small></td>
        <td class="hide-m"><?= e($s['musteri_firma'] ?: $s['musteri_ad']) ?><small class="muted" style="display:block"><?= $s['musteri_tel'] ? e(tel_goster($s['musteri_tel'])) : '' ?></small></td>
        <td class="hide-m"><small><?= date('d.m.Y', strtotime($s['giris_tarihi'])) ?></small></td>
        <td><?= servis_durum_pill($s['durum']) ?></td>
        <td><?= para($s['toplam']) ?></td>
        <td class="hide-m"><?= $kalan > 0.009 && $s['durum'] !== 'iptal' ? '<b class="down">' . para($kalan) . '</b>' : '<span class="up">Ödendi</span>' ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php else: ?>
    <div class="emptybox"><div style="font-size:40px"><?= $tanim['ikon'] ?></div><p><?= $ara !== '' ? 'Aramaya uygun kayıt yok.' : 'Bu listede kayıt yok.' ?></p><a class="btn btn-main" href="servis-kayit.php">+ İlk kaydını aç</a></div>
  <?php endif; ?>
</div>

<details class="card">
  <summary class="cat-sum"><span>⚙️ <b>Servis ayarları</b> <small class="muted">Sektör ve rapor alt notu</small></span><span class="btn btn-soft">Aç</span></summary>
  <form method="post" class="form one" style="margin-top:14px">
    <?= csrf_field() ?>
    <label>Servis türü <small class="muted">(kayıt formundaki alanlar buna göre değişir)</small>
      <select class="inp" name="servis_sektor">
        <?php foreach (sektorler() as $k => $t): ?><option value="<?= $k ?>" <?= $k === $sektor ? 'selected' : '' ?>><?= $t['ikon'] . ' ' . e($t['ad']) ?></option><?php endforeach; ?>
      </select></label>
    <label>Rapor alt notu <small class="muted">(her raporun altında görünür: garanti şartları, banka/IBAN bilgisi vb.)</small>
      <textarea class="inp" name="rapor_notu" rows="3" maxlength="1500" placeholder="Örn: Yapılan işçilik 6 ay garantilidir. Değişen parçalar müşteriye teslim edilmiştir."><?= e($u['rapor_notu']) ?></textarea></label>
    <div><button class="btn btn-main">Kaydet</button></div>
  </form>
</details>
<?php panel_bitir();
