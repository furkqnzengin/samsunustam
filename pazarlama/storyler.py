"""20 Instagram story (1080x1920). Etkileşim etiketleri (anket, soru, link) Instagram'da üzerine eklenebilir;
burada yerleri ve görünümleri tasarımın parçası olarak gösterilir."""
from kit import *

LB, LM = 'logo-yatay-beyaz.svg', 'logo-yatay.svg'
P = []


def ekle(ad, sinif, govde):
    P.append((ad, sayfa(govde, 1080, 1920, sinif)))


def link(metin='samsunustam.com'):
    return f'<div style="display:flex;justify-content:center;margin-top:60px;position:relative;z-index:4"><span class="linkst">🔗 {metin}</span></div>'


def sayac(n):  # üstte "1/3" çizgileri
    return '<div style="display:flex;gap:10px;margin-bottom:40px">' + ''.join(
        f'<i style="flex:1;height:8px;border-radius:9px;background:{"#FFDA00" if i < n[0] else "rgba(255,255,255,.35)"}"></i>' for i in range(n[1])) + '</div>'


# 1 Merhaba Samsun
ekle('01-merhaba', 'bg-mor', f'''
<div class="main" style="align-items:center;text-align:center">
 {maskot(460, '#fff', MOR, (0, -5), -6)}
 <h1 class="h-xl" style="margin-top:70px">Merhaba<br><span class="sari-t">Samsun!</span> 👋</h1>
 <p class="alt" style="margin:30px auto 0;font-size:44px">Ustanı bulmanın en kolay yolu artık burada.</p>
</div>
<div style="display:flex;justify-content:center"><img src="{LB}" style="height:110px"></div>''')

# 2 Anket: kışa hazır mısın
ekle('02-anket-kis', 'bg-koyu', ust(LB) + f'''
<div class="main">
 <div style="font-size:160px;text-align:center">🔥</div>
 <div class="poll" style="margin-top:40px"><div class="q">Kombi bakımını bu yıl yaptırdın mı?</div>
  <span class="opt" style="--p:0%">✅ Evet, yaptırdım</span><span class="opt" style="--p:0%">😬 Henüz değil</span></div>
 <p class="alt" style="text-align:center;margin:44px auto 0;font-size:40px">"Henüz değil" diyorsan kombi ustan bir tık uzağında 👇</p>
</div>''' + link('Kombi ustası bul'))

# 3 Acil
ekle('03-acil', 'bg-ekoyu', ust(LB, '<span class="pill turuncu">7/24</span>') + f'''
<div class="main">
 <h1 class="h-xl">Acil mi?</h1>
 <h2 class="h-l turuncu-t" style="margin-top:10px">Gece yarısı da<br>usta var.</h2>
 <div class="liste" style="margin-top:60px">
  <div class="li" style="font-size:44px"><span class="e" style="font-size:56px">🔑</span>Çilingir</div>
  <div class="li" style="font-size:44px"><span class="e" style="font-size:56px">💧</span>Su kaçağı</div>
  <div class="li" style="font-size:44px"><span class="e" style="font-size:56px">⚡</span>Elektrik arızası</div>
  <div class="li" style="font-size:44px"><span class="e" style="font-size:56px">🛻</span>Oto çekici</div>
 </div>
</div>
<div class="abs" style="right:-40px;bottom:360px">{maskot(280, '#FFDA00', EN_KOYU, (-8, -4), -12)}</div>''' + link())

# 4 Usta kaydı 3 adım
ekle('04-usta-kayit', 'bg-sari', ust(LM, '<span class="pill koyu">ÜCRETSİZ</span>') + '''
<div class="main">
 <h1 class="h-xl" style="color:#22074F">Usta mısın?</h1>
 <h2 class="h-l mor-t" style="margin-top:8px">3 adımda<br>listelen.</h2>
 <div class="liste" style="margin-top:60px">
  <div class="li" style="font-size:42px"><span class="n" style="width:84px;height:84px;font-size:40px">1</span>Ücretsiz kaydol</div>
  <div class="li" style="font-size:42px"><span class="n" style="width:84px;height:84px;font-size:40px">2</span>Belgeni ve fotoğraflarını yükle</div>
  <div class="li" style="font-size:42px"><span class="n" style="width:84px;height:84px;font-size:40px">3</span>Onaylan, telefonun çalsın</div>
 </div>
</div>''' + link('Ücretsiz kaydol'))

# 5 Servis takip mockup
ekle('05-servis-panel', 'bg-koyu', ust(LB, '<span class="pill">USTALAR İÇİN</span>') + '''
<div class="main">
 <h1 class="h-l">Atölyeni<br><span class="sari-t">cebinden yönet.</span></h1>
 <div class="ui" style="margin-top:60px;font-size:34px">
  <div class="uh" style="font-size:36px"><span>🚗 55 ABC 123</span><span class="tag g">İşlemde</span></div>
  <div class="ub" style="gap:14px">
   <div class="row"><span class="k">Müşteri</span><b>Murat T.</b></div>
   <div class="row"><span class="k">Kilometre</span><b>118.500</b></div>
   <div class="row"><span class="k">✓ Yağ · ✓ Filtre · ✓ Balata</span><b></b></div>
   <div class="row"><span class="k">Toplam</span><b>6.900 ₺</b></div>
   <div class="row"><span class="k">Alınan</span><b>5.000 ₺</b></div>
   <div class="tot" style="font-size:40px"><span>Kalan</span><span>1.900 ₺</span></div>
  </div></div>
 <p class="alt" style="margin-top:40px;font-size:38px">Plaka, işlem, tahsilat, fotoğraf, rapor: hepsi tek ekranda.</p>
</div>''' + link('Usta paneli'))

# 6 Gelir gider
ekle('06-kasa', 'bg-acik', ust(LM) + '''
<div class="main">
 <span class="pill mor" style="align-self:flex-start">💰 GELİR - GİDER</span>
 <h1 class="h-l koyu-t" style="margin-top:30px">Kazancını<br><span class="mor-t">tahminle değil,</span><br>rakamla bil.</h1>
 <div class="kart" style="margin-top:60px"><div style="color:#6E5A90;font-size:32px;font-weight:600">Bu ay net</div><div class="big" style="font-size:120px;color:#4609A8">30.550 ₺</div>
  <div style="display:flex;gap:16px;margin-top:20px;font-size:30px;font-weight:700"><span style="color:#127a3c">+48.200 gelir</span><span style="color:#d64545">−17.650 gider</span></div></div>
 <p style="margin-top:18px;color:#6E5A90;font-size:26px">* Rakamlar örnektir.</p>
</div>''' + link('Ücretsiz dene'))

# 7 Rapor WhatsApp
ekle('07-rapor', 'bg-mor', ust(LB) + '''
<div class="main" style="align-items:center;text-align:center">
 <div style="width:560px;height:720px;background:#fff;border-radius:40px;box-shadow:0 40px 90px rgba(0,0,0,.3);transform:rotate(-4deg);padding:50px;color:#22074F;text-align:left">
  <div class="o" style="font-weight:800;color:#852FFF;font-size:26px;letter-spacing:.12em">SERVİS RAPORU</div><div class="o" style="font-weight:900;font-size:76px">#128</div>
  <div style="height:16px;background:#F0E7FF;border-radius:8px;margin:26px 0 12px"></div><div style="height:16px;width:72%;background:#F0E7FF;border-radius:8px"></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:30px"><div style="aspect-ratio:1;background:#DCC5FF;border-radius:16px"></div><div style="aspect-ratio:1;background:#DCC5FF;border-radius:16px"></div></div>
  <div style="height:80px;background:#4609A8;border-radius:18px;margin-top:40px"></div></div>
 <div class="sticker" style="position:relative;margin-top:-40px;background:#25D366;color:#fff;font-size:44px">💬 Müşteriye gönderildi ✓✓</div>
 <h1 class="h-m" style="margin-top:70px">Profesyonel servis raporu,<br><span class="sari-t">tek tuşla.</span></h1>
</div>''' + link())

# 8 İş arıyorum
ekle('08-is-ariyorum', 'bg-ekoyu', ust(LB, '<span class="pill">KARİYER</span>') + f'''
<div class="main">
 <div style="font-size:180px">🙋</div>
 <h1 class="h-xl" style="margin-top:20px">İş mi<br><span class="sari-t">arıyorsun?</span></h1>
 <p class="alt" style="font-size:44px;margin-top:40px">Ücretsiz ilan ver; eleman arayan Samsunlu ustalar seni bulsun.</p>
 <div class="chips" style="margin-top:50px"><span class="chip s" style="font-size:36px">Kalfa</span><span class="chip" style="font-size:36px">Çırak</span><span class="chip" style="font-size:36px">Usta</span><span class="chip s" style="font-size:36px">Operatör</span></div>
</div>''' + link('İş arıyorum ilanı ver'))

# 9 Eleman arıyorum
ekle('09-eleman', 'bg-sari', ust(LM, '<span class="pill koyu">USTALAR İÇİN</span>') + f'''
<div class="main">
 <div style="font-size:180px">📢</div>
 <h1 class="h-xl" style="color:#22074F;margin-top:20px">Eleman mı<br>arıyorsun?</h1>
 <div class="liste" style="margin-top:50px">
  <div class="li" style="font-size:40px"><span class="e">📝</span>Ücretsiz ilan aç</div>
  <div class="li" style="font-size:40px"><span class="e">📥</span>Başvurular paneline gelsin</div>
  <div class="li" style="font-size:40px"><span class="e">🙋</span>İş arayanlara göz at</div>
 </div>
</div>
<div class="abs" style="right:-50px;top:250px">{maskot(260, MOR, '#fff', (-8, 3), 14)}</div>''' + link('Eleman ilanı ver'))

# 10-12 Kombi ipuçları (seri)
kombi = [
    ('Basıncı kontrol et', 'Kombinin su basıncı genelde 1-2 bar aralığında olmalı. Sürekli düşüyorsa kaçak olabilir.', '🌡️'),
    ('Petekleri temizlet', 'Alt tarafı soğuk kalan petek tortu doldu demektir. Temizlik hem ısıtır hem fatura düşürür.', '🧽'),
    ('Yılda bir bakım', 'Yanma ayarı, filtre ve baca kontrolü için kışa girmeden bakım yaptır.', '🔧'),
]
for i, (b, t, e) in enumerate(kombi):
    ekle(f'{10 + i:02d}-kombi-ipucu-{i + 1}', 'bg-mor' if i != 1 else 'bg-koyu', ust(LB, '<span class="pill">💡 KOMBİ İPUCU</span>') + f'''
<div class="main">{sayac((i + 1, 3))}
 <div style="font-size:200px;line-height:1">{e}</div>
 <div class="big sari-t" style="font-size:120px;margin-top:40px">{i + 1}/3</div>
 <h1 class="h-l" style="margin-top:20px">{b}</h1>
 <p class="alt" style="font-size:44px;margin-top:30px">{t}</p>
</div>
<div class="abs" style="right:60px;bottom:330px">{maskot(170, '#fff', MOR, ((-1) ** i * 7, -4), (-1) ** i * 10)}</div>''' + (link('Kombi ustası bul') if i == 2 else '<div style="text-align:center;font-family:Outfit;font-weight:700;font-size:36px;opacity:.85">Devamı için dokun ▶</div>'))

# 13 Su kaçağı
ekle('13-su-kacagi', 'bg-acik', ust(LM, '<span class="pill mor">💧 USTA TAVSİYESİ</span>') + '''
<div class="main">
 <h1 class="h-l koyu-t">Faturan neden<br><span class="mor-t">bu kadar yüksek?</span></h1>
 <div class="liste" style="margin-top:60px">
  <div class="li" style="font-size:40px"><span class="n" style="width:80px;height:80px">1</span>Musluklar kapalı, sayaç dönüyor</div>
  <div class="li" style="font-size:40px"><span class="n" style="width:80px;height:80px">2</span>Duvarda nem ve küf lekesi</div>
  <div class="li" style="font-size:40px"><span class="n" style="width:80px;height:80px">3</span>Zeminde ılıklık ya da kabarma</div>
 </div>
 <p class="alt" style="color:#4609A8;font-size:40px;margin-top:50px">Bunlardan biri varsa su kaçağı tespiti yaptır. Duvarı kırmadan bulan ustalar burada.</p>
</div>''' + link('Su kaçağı ustası bul'))

# 14 Tır bakım
ekle('14-tir', 'bg-koyu', ust(LB, '<span class="pill">🚛 AĞIR VASITA</span>') + '''
<div class="main">
 <div style="font-size:260px;line-height:1">🚛</div>
 <h1 class="h-xl" style="margin-top:30px">Yolda<br><span class="sari-t">kalma.</span></h1>
 <p class="alt" style="font-size:44px;margin-top:30px">Tır bakım servisi, havalı fren, takograf, dorse tamiri. Samsun'un ağır vasıta ustaları tek yerde.</p>
</div>''' + link('Ağır vasıta ustaları'))

# 15 Sanayi
ekle('15-sanayi', 'bg-ekoyu', ust(LB, '<span class="pill">🏭 SANAYİ</span>') + '''
<div class="main">
 <h1 class="h-l">Tornacı mı,<br>kaynakçı mı,<br><span class="sari-t">CNC mi?</span></h1>
 <div class="chips" style="margin-top:60px;gap:18px"><span class="chip s" style="font-size:38px">Tornacı</span><span class="chip" style="font-size:38px">CNC işleme</span><span class="chip" style="font-size:38px">Kaynakçı</span><span class="chip s" style="font-size:38px">Hidrolik</span><span class="chip" style="font-size:38px">Bobinaj</span><span class="chip" style="font-size:38px">Rektefiye</span><span class="chip s" style="font-size:38px">Lazer kesim</span></div>
 <p class="alt" style="font-size:42px;margin-top:50px">Dükkân adresiyle, "Haritada aç" ile.</p>
</div>
<div class="abs" style="right:-60px;bottom:330px;font-size:300px;transform:rotate(15deg);opacity:.9">⚙️</div>''' + link('Sanayi ustaları'))

# 16 Elektrik güvenliği
ekle('16-elektrik', 'bg-turuncu', ust(LB, '<span class="pill koyu">⚡ DİKKAT</span>') + '''
<div class="main">
 <h1 class="h-xl">Sigorta<br>sürekli mi<br><span style="color:#22074F">atıyor?</span></h1>
 <p class="alt" style="font-size:46px;margin-top:40px">Kendin müdahale etme. Bu bir aşırı yük ya da kaçak akım işareti olabilir.</p>
 <div class="kart" style="margin-top:50px;font-size:40px;font-weight:700;color:#22074F">✅ Elektrikçi çağır<br>✅ Prizleri aşırı yükleme<br>✅ Kaçak akım rölesini test ettir</div>
</div>''' + link('Elektrikçi bul'))

# 17 Soru kutusu
ekle('17-soru', 'bg-mor', ust(LB) + f'''
<div class="main">
 <div class="soru"><div class="sq">Hangi ustayı bulmakta zorlanıyorsun?</div><div class="sa">Buraya yaz…</div></div>
 <p class="alt" style="text-align:center;margin:50px auto 0;font-size:42px">Cevaplarına göre yeni hizmet kategorileri ekliyoruz. 💜</p>
</div>
<div class="abs" style="left:50%;bottom:300px;transform:translateX(-50%)">{maskot(300, '#fff', MOR, (0, -7), 0)}</div>''')

# 18 Senin ilçen
ilceler = ['Atakum', 'İlkadım', 'Canik', 'Tekkeköy', 'Bafra', 'Çarşamba', 'Terme', 'Vezirköprü', 'Havza', 'Kavak', 'Ladik', 'Alaçam', '19 Mayıs', 'Asarcık', 'Ayvacık', 'Salıpazarı', 'Yakakent']
ekle('18-ilcen', 'bg-lila', ust(LM) + '''
<div class="main">
 <h1 class="h-l koyu-t">Senin ilçen<br><span class="mor-t">hangisi?</span> 📍</h1>
 <div class="chips" style="margin-top:50px;gap:16px">''' + ''.join(f'<span class="chip {"m" if i % 3 == 0 else ("s" if i % 3 == 1 else "k")}" style="font-size:36px">{x}</span>' for i, x in enumerate(ilceler)) + '''</div>
 <p class="alt" style="color:#4609A8;font-size:40px;margin-top:40px">17 ilçenin hepsinde yerel ustalar.</p>
</div>''' + link())

# 19 Yorum yaz
ekle('19-yorum', 'bg-koyu', ust(LB) + f'''
<div class="main" style="align-items:center;text-align:center">
 <div class="yildiz" style="font-size:130px">★★★★★</div>
 <h1 class="h-l" style="margin-top:30px">Ustandan<br><span class="sari-t">memnun kaldın mı?</span></h1>
 <p class="alt" style="margin:40px auto 0;font-size:44px">İki satır yorum yaz, başka Samsunlulara yol göster.</p>
</div>
<div class="abs" style="left:60px;bottom:340px">{maskot(200, '#FFDA00', EN_KOYU, (6, -6), -10)}</div>''' + link('Yorum yaz'))

# 20 Kapanış
ekle('20-kapanis', 'bg-mor', f'''
<div class="main" style="align-items:center;text-align:center">
 <img src="logo-dikey-beyaz.svg" style="width:640px">
 <h1 class="h-l" style="margin-top:80px">Aracı yok.<br><span class="sari-t">Komisyon yok.</span></h1>
 <p class="alt" style="margin:30px auto 0;font-size:44px">Samsun'un ustaları tek adreste.</p>
</div>''' + link() + '<div style="height:40px"></div>')
