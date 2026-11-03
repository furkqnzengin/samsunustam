<?php
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/panel.php';
$u = aktif_usta();
$uid = (int)$u['id'];

if (is_post()) {
    csrf_check();
    $yanit = mb_substr(post('yanit'), 0, 1000);
    q("UPDATE yorumlar SET usta_yaniti = ? WHERE id = ? AND usta_id = ? AND durum = 'yayinda'", [$yanit !== '' ? $yanit : null, (int)post('id'), $uid]);
    flash($yanit !== '' ? 'Yanıtın kaydedildi.' : 'Yanıtın kaldırıldı.');
    redirect('usta/yorumlar.php#y' . (int)post('id'));
}

$yorumlar = rows("SELECT * FROM yorumlar WHERE usta_id = ? AND durum = 'yayinda' ORDER BY id DESC", [$uid]);
$bekleyen = (int)val("SELECT COUNT(*) FROM yorumlar WHERE usta_id = ? AND durum = 'beklemede'", [$uid]);

panel_basla('usta', 'yorumlar.php', 'Yorumlarım', 'Müşterilerinin yorumlarına kibarca yanıt vermek güven kazandırır.');
?>
<div class="stats three">
  <div class="stat"><div class="ic">⭐</div><div><small>Ortalama Puan</small><b><?= $u['yorum_sayisi'] ? puan_fmt($u['puan']) : '—' ?></b></div></div>
  <div class="stat"><div class="ic">💬</div><div><small>Yayındaki Yorum</small><b><?= count($yorumlar) ?></b></div></div>
  <div class="stat"><div class="ic">⏳</div><div><small>Kontrol Bekleyen</small><b><?= $bekleyen ?></b></div></div>
</div>
<div class="card">
  <?php if ($yorumlar): ?><div class="list">
    <?php foreach ($yorumlar as $y): ?>
      <div class="li" id="y<?= $y['id'] ?>"><div class="grow">
        <b><?= e($y['ad']) ?></b><?= $y['mahalle'] ? ' <small>· ' . e($y['mahalle']) . '</small>' : '' ?> <span class="stars"><?= yildiz($y['puan']) ?></span> <small><?= zaman_once($y['olusturma']) ?></small>
        <p><?= nl2br(e($y['metin'])) ?></p>
        <form method="post" class="reply-form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $y['id'] ?>">
          <textarea class="inp" name="yanit" rows="2" maxlength="1000" placeholder="Yoruma yanıt yaz (sitede yorumun altında görünür)"><?= e($y['usta_yaniti']) ?></textarea>
          <button class="btn btn-soft"><?= $y['usta_yaniti'] ? 'Yanıtı güncelle' : 'Yanıtla' ?></button>
        </form>
      </div></div>
    <?php endforeach; ?>
  </div><?php else: ?><p class="muted">Henüz yayında yorum yok. İşini bitirdiğin müşterilere profil linkini gönderip yorum yazmalarını isteyebilirsin:</p>
    <p><input class="inp" style="width:100%" readonly value="<?= e((isset($_SERVER['HTTP_HOST']) ? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . url('usta.php?u=' . $u['slug'])) ?>#yorumlar" onclick="this.select()"></p>
  <?php endif; ?>
</div>
<?php panel_bitir();
