<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/site.php';
$mail = ayar('site_eposta');
$tel = ayar('site_telefon');
site_basla('Gizlilik ve KVKK Aydınlatma Metni');
?>
<section class="pagehead"><div class="wrap narrowtext">
  <span class="num">GİZLİLİK</span>
  <h1>Gizlilik ve KVKK Aydınlatma Metni</h1>
  <p class="muted">Son güncelleme: <?= tarih_tr('2026-11-03') ?></p>
  <div class="box prose">
    <p><b>Not:</b> Bu metin genel bir taslaktır. Yayına almadan önce bir hukukçuya kontrol ettirmeniz önerilir.</p>
    <h2>Hangi bilgileri topluyoruz?</h2>
    <ul>
      <li><b>Ustalar:</b> ad soyad, telefon, WhatsApp numarası, e-posta, meslek, hizmet verilen ilçeler, profil ve iş fotoğrafları, yüklenen belgeler.</li>
      <li><b>Ziyaretçiler:</b> iş talebi formunda yazdığınız ad, telefon, ilçe ve mesaj; yorum formunda yazdığınız ad, mahalle, puan ve yorum.</li>
      <li><b>Teknik bilgiler:</b> kötüye kullanımı önlemek için IP adresi ve oturum çerezi. Reklam ya da izleme çerezi kullanmıyoruz.</li>
    </ul>
    <h2>Bilgileri ne için kullanıyoruz?</h2>
    <ul>
      <li>Usta profillerini sitede yayınlamak ve müşterilerin ustalara ulaşmasını sağlamak.</li>
      <li>İş taleplerini ilgili ustaya iletmek. Talep formundaki ad ve telefonunuz sadece seçtiğiniz ustayla paylaşılır.</li>
      <li>Yorumları kontrol edip yayınlamak. Yorumlarda sadece adınız ve (yazdıysanız) mahalleniz görünür.</li>
      <li>Ustalara profil görüntülenme ve tıklanma istatistiklerini (kişisel bilgi içermeden) göstermek.</li>
    </ul>
    <h2>Bilgileri kimlerle paylaşıyoruz?</h2>
    <p>Bilgileriniz satılmaz ve reklam amacıyla üçüncü kişilerle paylaşılmaz. Yasal zorunluluk hâlinde yetkili kurumlarla paylaşılabilir.</p>
    <h2>Haklarınız</h2>
    <p>6698 sayılı KVKK'nın 11. maddesi kapsamında bilgilerinize erişme, düzeltilmesini veya silinmesini isteme haklarınız vardır. Ustalar profillerini panelden düzenleyebilir; hesap silme ve diğer talepler için bize ulaşabilirsiniz<?= $mail ? ': <a class="link" href="mailto:' . e($mail) . '">' . e($mail) . '</a>' : '.' ?><?= $tel ? ' · ' . e(tel_goster($tel)) : '' ?></p>
  </div>
</div></section>
<?php site_bitir();
