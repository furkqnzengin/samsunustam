"""20 Instagram postu (1080x1080)."""
from kit import *

L_BEYAZ, L_MOR = 'logo-yatay-beyaz.svg', 'logo-yatay.svg'
P = []


def ekle(ad, sinif, govde):
    P.append((ad, sayfa(govde, 1080, 1080, sinif)))


# 1 Tanıtım
ekle('01-tanitim', 'bg-mor', ust(L_BEYAZ, '<span class="pill">YENİ</span>') + f'''
<div class="main" style="max-width:680px"><h1 class="h-xl">Samsun'un<br>ustaları<br><span class="sari-t">tek adreste.</span></h1>
<p class="alt">Elektrikçiden tornacıya, kombiciden tır servisine. Bul, yorumları oku, tek dokunuşla ara.</p></div>
<div class="abs" style="right:-50px;bottom:110px">{maskot(360, '#fff', MOR, (-7, -4), -10)}</div>
''' + alt_bant())

# 2 Hero
ekle('02-bozuldu', 'bg-acik', ust(L_MOR) + f'''
<div class="main" style="max-width:760px"><h1 class="h-l koyu-t" style="font-size:96px">Evde bir şey mi<br>bozuldu?</h1>
<h2 class="h-l mor-t" style="margin-top:14px"><span class="isaretle">Samsun'da<br>ustası var.</span></h2></div>
<div class="abs" style="right:-60px;bottom:90px">{maskot(330, MOR, '#fff', (-8, -4), 12)}</div>
<div class="balon" style="right:60px;bottom:420px">Hemen ara! 📞</div>
''' + alt_bant())

# 3 Nasıl çalışır
ekle('03-nasil-calisir', 'bg-koyu', ust(L_BEYAZ) + '''
<div class="main"><span class="pill" style="align-self:flex-start">NASIL ÇALIŞIR?</span>
<h1 class="h-l" style="margin-top:22px">Üç adım,<br><span class="sari-t">bir telefon.</span></h1>
<div class="grid3">
 <div class="step"><div class="num">1</div><h3>Ne lazım, söyle</h3><p>Hizmeti ve ilçeni seç.</p></div>
 <div class="step"><div class="num">2</div><h3>Komşuna sor</h3><p>Puanları ve yorumları oku.</p></div>
 <div class="step"><div class="num">3</div><h3>Ara, anlaş</h3><p>Aracı yok, komisyon yok.</p></div>
</div></div>''' + alt_bant())

# 4 Acil 7/24
ekle('04-acil', 'bg-ekoyu', ust(L_BEYAZ, '<span class="pill turuncu">ACİL · 7/24</span>') + f'''
<div class="main"><h1 class="h-m">Gece yarısı da olsa<br><span class="turuncu-t">nöbetçi usta</span> bul.</h1>
<div class="liste" style="max-width:600px">
 <div class="li"><span class="e">🔑</span>Kapıda kaldım: çilingir</div>
 <div class="li"><span class="e">💧</span>Su kaçağı tespiti</div>
 <div class="li"><span class="e">⚡</span>Elektrik arızası</div>
 <div class="li"><span class="e">🔥</span>Kombi arızası</div>
 <div class="li"><span class="e">🛻</span>Oto çekici</div>
</div></div>
<div class="abs" style="right:50px;bottom:140px">{maskot(260, '#FFDA00', EN_KOYU, (0, -6), 6)}</div>''' + alt_bant('🚨 7/24 acil'))

# 5 Kış hazırlığı
ekle('05-kis', 'bg-lila', ust(L_MOR, '<span class="pill mor">MEVSİMİN İŞLERİ</span>') + f'''
<div class="main"><div style="font-size:120px;line-height:1">🌧️</div>
<h1 class="h-l koyu-t" style="margin-top:10px">Karadeniz kışı<br>kapıda.</h1>
<p class="alt" style="color:#4609A8">Soğuk bastırmadan evini hazırla:</p>
<div class="chips"><span class="chip m">🔥 Kombi bakımı</span><span class="chip m">🌡️ Petek temizliği</span><span class="chip k">🏠 Çatı & oluk tamiri</span><span class="chip k">🧱 Mantolama</span></div></div>
<div class="abs" style="right:70px;top:250px">{maskot(220, MOR, '#fff', (0, -5), -6)}</div>''' + alt_bant())

# 6 Kombi ipucu
ekle('06-ipucu-kombi', 'bg-mor', ust(L_BEYAZ, '<span class="pill">💡 USTA TAVSİYESİ</span>') + '''
<div class="main"><h1 class="h-m">Kombi bakımını<br><span class="sari-t">neden yaptırmalısın?</span></h1>
<div class="liste">
 <div class="li"><span class="n">1</span>Doğalgaz faturası düşer, kombi verimli çalışır.</div>
 <div class="li"><span class="n">2</span>Kış ortasında arıza riski azalır.</div>
 <div class="li"><span class="n">3</span>Baca ve gaz kaçağı kontrolüyle güvende olursun.</div>
</div>
<p class="alt" style="font-size:28px">Yılda bir bakım önerilir. Kombi ustanı samsunustam.com'da bul.</p></div>''' + alt_bant())

# 7 Usta mısın
ekle('07-usta-misin', 'bg-sari', ust(L_MOR, '<span class="pill koyu">ÜCRETSİZ</span>') + f'''
<div class="main" style="max-width:700px"><h1 class="h-xl" style="color:#22074F">Usta mısın?</h1>
<h2 class="h-l mor-t" style="margin-top:6px">Müşteri seni<br>bulsun.</h2>
<div class="liste" style="max-width:560px">
 <div class="li"><span class="e">📱</span>5 dakikada profil</div>
 <div class="li"><span class="e">📞</span>Müşteri seni doğrudan arar</div>
 <div class="li"><span class="e">⭐</span>Yorumlarla güven kazan</div>
</div></div>
<div class="abs" style="right:-40px;bottom:110px">{maskot(300, MOR, '#fff', (-8, -2), -10)}</div>''' + alt_bant('👉 Ücretsiz kaydol'))

# 8 Servis takip
ekle('08-servis-takip', 'bg-koyu', ust(L_BEYAZ, '<span class="pill">USTALAR İÇİN</span>') + '''
<div class="main" style="flex-direction:row;align-items:center;gap:40px">
 <div style="flex:1"><h1 class="h-m">Defter, kalem<br><span class="sari-t">tarih oldu.</span></h1>
  <p class="alt">Gelen aracı, yapılan işi, alınan ve kalan ücreti panelinden takip et.</p>
  <div class="chips"><span class="chip s">🚗 Plaka</span><span class="chip">🧾 İşlemler</span><span class="chip">💵 Tahsilat</span><span class="chip">📷 Foto</span></div></div>
 <div class="ui" style="width:440px;transform:rotate(3deg)">
  <div class="uh"><span>Kayıt #128</span><span class="tag g">Teslime hazır</span></div>
  <div class="ub">
   <div class="row"><span class="k">Plaka</span><b>55 ABC 123</b></div>
   <div class="row"><span class="k">Araç</span><b>Ford Focus · 118.500 km</b></div>
   <div class="row"><span class="k">Motor yağı ×5</span><b>2.250 ₺</b></div>
   <div class="row"><span class="k">Fren balatası</span><b>1.800 ₺</b></div>
   <div class="row"><span class="k">İşçilik</span><b>1.500 ₺</b></div>
   <div class="tot"><span>Kalan</span><span>1.900 ₺</span></div>
  </div></div>
</div>''' + alt_bant('🛠️ Usta paneli · ücretsiz'))

# 9 Gelir gider
ekle('09-gelir-gider', 'bg-acik', ust(L_MOR, '<span class="pill mor">USTALAR İÇİN</span>') + '''
<div class="main"><h1 class="h-l koyu-t">Ay sonu <span class="mor-t">ne kazandın?</span></h1>
<p class="alt" style="color:#6E5A90">Tahsilatlar otomatik gelire yazılır. Kira, malzeme, yakıt giderlerini ekle; net kazancını gör.</p>
<div style="display:flex;gap:22px;margin-top:40px">
 <div class="kart" style="flex:1"><div class="k" style="color:#6E5A90;font-weight:600">Gelir</div><div class="big" style="font-size:64px;color:#127a3c">+48.200 ₺</div></div>
 <div class="kart" style="flex:1"><div style="color:#6E5A90;font-weight:600">Gider</div><div class="big" style="font-size:64px;color:#d64545">−17.650 ₺</div></div>
</div>
<div class="kart" style="margin-top:22px;background:#4609A8;color:#fff;display:flex;justify-content:space-between;align-items:center"><span class="o" style="font-size:34px;font-weight:700">Net kazanç</span><span class="big sari-t" style="font-size:72px">30.550 ₺</span></div>
<p style="margin-top:16px;color:#6E5A90;font-size:20px">* Görseldeki rakamlar örnektir.</p></div>''' + alt_bant('💰 Gelir - gider · Excel\'e aktar'))

# 10 Dijital rapor
ekle('10-rapor', 'bg-mor', ust(L_BEYAZ) + f'''
<div class="main" style="max-width:640px"><span class="pill" style="align-self:flex-start">📄 DİJİTAL SERVİS RAPORU</span>
<h1 class="h-l" style="margin-top:24px">Müşterine raporu<br><span class="sari-t">WhatsApp'tan</span> gönder.</h1>
<p class="alt">Yapılan işlemler, parçalar, fotoğraflar ve ödeme bilgisi tek sayfada. Tek tuşla yazdır ya da PDF kaydet.</p></div>
<div class="abs" style="right:70px;bottom:150px"><div style="width:250px;height:320px;background:#fff;border-radius:24px;box-shadow:0 30px 70px rgba(0,0,0,.25);transform:rotate(8deg);padding:26px;color:#22074F">
 <div class="o" style="font-weight:800;color:#852FFF;font-size:16px;letter-spacing:.1em">SERVİS RAPORU</div><div class="o" style="font-weight:900;font-size:40px">#128</div>
 <div style="height:10px;background:#F0E7FF;border-radius:6px;margin:14px 0 8px"></div><div style="height:10px;width:70%;background:#F0E7FF;border-radius:6px"></div>
 <div style="height:10px;background:#F0E7FF;border-radius:6px;margin:8px 0"></div><div style="height:46px;background:#4609A8;border-radius:12px;margin-top:40px"></div></div></div>
<div class="sticker" style="right:40px;bottom:480px;background:#25D366;color:#fff;font-size:28px">💬 Gönderildi ✓✓</div>''' + alt_bant('🛠️ Usta paneli'))

# 11 Kariyer
ekle('11-kariyer', 'bg-ekoyu', ust(L_BEYAZ, '<span class="pill">KARİYER</span>') + '''
<div class="main"><h1 class="h-l">İş arayanla<br><span class="sari-t">eleman arayan</span><br>burada buluşuyor.</h1>
<div style="display:flex;gap:22px;margin-top:40px">
 <div class="kart" style="flex:1"><div style="font-size:54px">🙋</div><h3 class="o" style="font-size:34px;margin-top:6px">İş mi arıyorsun?</h3><p style="color:#6E5A90;font-size:22px;margin-top:6px">Ücretsiz ilan ver, üyelik gerekmez.</p></div>
 <div class="kart" style="flex:1;background:#FFDA00"><div style="font-size:54px">📢</div><h3 class="o" style="font-size:34px;margin-top:6px">Eleman mı arıyorsun?</h3><p style="color:#4a3a00;font-size:22px;margin-top:6px">Kalfa, çırak, usta ilanı aç.</p></div>
</div></div>''' + alt_bant('💼 samsunustam.com/isler'))

# 12 17 ilçe
ilceler = ['Atakum', 'İlkadım', 'Canik', 'Tekkeköy', 'Bafra', 'Çarşamba', 'Terme', 'Vezirköprü', 'Havza', 'Kavak', 'Ladik', 'Alaçam', '19 Mayıs', 'Asarcık', 'Ayvacık', 'Salıpazarı', 'Yakakent']
ekle('12-ilceler', 'bg-acik', ust(L_MOR) + '''
<div class="main"><h1 class="h-l koyu-t"><span class="mor-t">17 ilçenin</span><br>hepsinde.</h1>
<div class="chips" style="gap:14px">''' + ''.join(f'<span class="chip {"m" if i % 3 == 0 else ("s" if i % 3 == 1 else "k")}">{x}</span>' for i, x in enumerate(ilceler)) + '''</div>
<p class="alt" style="color:#6E5A90">Senin ilçen hangisi? Yorumlara yaz 👇</p></div>''' + alt_bant())

# 13 Sanayi
ekle('13-sanayi', 'bg-koyu', ust(L_BEYAZ, '<span class="pill">🏭 SANAYİ & ATÖLYE</span>') + f'''
<div class="main" style="max-width:860px"><h1 class="h-l">Sanayideki ustalar<br><span class="sari-t">bir tık uzağında.</span></h1>
<div class="chips"><span class="chip s">Tornacı</span><span class="chip">CNC işleme</span><span class="chip">Kaynakçı</span><span class="chip">Argon kaynak</span><span class="chip s">Hidrolik tamiri</span><span class="chip">Bobinaj</span><span class="chip">Motor rektefiye</span><span class="chip">Lazer kesim</span><span class="chip s">Sac büküm</span><span class="chip">Paslanmaz imalat</span></div>
<p class="alt">Dükkân adresi ve "Haritada aç" ile kolayca bul.</p></div>
<div class="abs" style="right:-30px;bottom:110px;font-size:190px;opacity:.9;transform:rotate(12deg)">⚙️</div>''' + alt_bant())

# 14 Tır bakım
ekle('14-agir-vasita', 'bg-sari', ust(L_MOR, '<span class="pill koyu">🚛 AĞIR VASITA</span>') + '''
<div class="main"><h1 class="h-l" style="color:#22074F">Tır, kamyon,<br>otobüs bakımı.</h1>
<div class="liste" style="max-width:720px">
 <div class="li"><span class="e">🛞</span>Havalı fren sistemleri</div>
 <div class="li"><span class="e">⚙️</span>Şanzıman & diferansiyel</div>
 <div class="li"><span class="e">⏱️</span>Takograf servisi</div>
 <div class="li"><span class="e">🪝</span>Dorse & treyler tamiri</div>
</div></div>
<div class="abs" style="right:30px;bottom:120px;font-size:220px">🚛</div>''' + alt_bant())

# 15 Oto bakım ipucu
ekle('15-ipucu-oto', 'bg-mor', ust(L_BEYAZ, '<span class="pill">💡 USTA TAVSİYESİ</span>') + f'''
<div class="main"><h1 class="h-m">Aracının bakımı<br><span class="sari-t">ne zaman?</span></h1>
<div class="liste">
 <div class="li"><span class="n">1</span>Kılavuzdaki km veya zaman aralığını takip et.</div>
 <div class="li"><span class="n">2</span>Yağ, filtre ve balatayı birlikte kontrol ettir.</div>
 <div class="li"><span class="n">3</span>Servis raporunu sakla: bir sonraki bakım yazar.</div>
</div></div>
<div class="abs" style="right:70px;top:200px">{maskot(150, '#FFDA00', EN_KOYU, (-8, 4), 12)}</div>''' + alt_bant('🚗 Oto servis ustaları'))

# 16 Yorumlar
ekle('16-yorumlar', 'bg-lila', ust(L_MOR) + f'''
<div class="main"><h1 class="h-l koyu-t">En iyi referans:<br><span class="mor-t">komşunun yorumu.</span></h1>
<div class="kart" style="margin-top:36px;max-width:780px">
 <div class="yildiz" style="font-size:40px">★★★★★</div>
 <p class="o" style="font-size:34px;font-weight:600;line-height:1.25;margin-top:10px">"Gece sigorta attı, yarım saatte geldi. İşini temiz yaptı."</p>
 <p style="color:#6E5A90;font-size:22px;margin-top:12px">— Örnek yorum</p></div>
<p class="alt" style="color:#4609A8">Ustanla çalıştıysan yorum yaz, başka Samsunlulara yol göster.</p></div>
<div class="abs" style="right:60px;bottom:120px">{maskot(170, MOR, '#fff', (-6, -6), 8)}</div>''' + alt_bant())

# 17 Aracı yok
ekle('17-araci-yok', 'bg-ekoyu', ust(L_BEYAZ) + f'''
<div class="main" style="align-items:center;text-align:center">
<h1 class="h-xl">Aracı yok.</h1><h1 class="h-xl sari-t">Komisyon yok.</h1>
<p class="alt" style="margin:24px auto 0">Ustanla doğrudan konuş, fiyatı doğrudan anlaş.</p></div>
<div class="abs" style="left:70px;bottom:120px">{maskot(140, '#fff', MOR, (6, 0), -12)}</div>
<div class="abs" style="right:70px;top:150px">{maskot(110, '#FFDA00', EN_KOYU, (-6, 4), 14)}</div>''' + alt_bant())

# 18 Belgeli usta
ekle('18-belgeli', 'bg-acik', ust(L_MOR, '<span class="pill mor">GÜVEN</span>') + f'''
<div class="main"><div style="display:inline-flex;align-self:flex-start;align-items:center;gap:14px;background:#852FFF;color:#fff;border-radius:999px;padding:16px 30px" class="o"><span style="font-size:44px">✔</span><span style="font-size:38px;font-weight:800">Belgeli usta</span></div>
<h1 class="h-l koyu-t" style="margin-top:30px;max-width:680px">Ustalık belgesi<br><span class="mor-t">kontrol edildi.</span></h1>
<p class="alt" style="color:#6E5A90;max-width:620px">Her usta kayıt olurken belgesini yükler. Belgesi onaylanan ustalar rozet alır.</p></div>
<div class="abs" style="right:-30px;bottom:110px">{maskot(280, MOR, '#fff', (-6, -4), -8)}</div>''' + alt_bant())

# 19 Su kaçağı ipucu
ekle('19-ipucu-su', 'bg-koyu', ust(L_BEYAZ, '<span class="pill">💧 USTA TAVSİYESİ</span>') + '''
<div class="main"><h1 class="h-m">Su kaçağının<br><span class="sari-t">3 sessiz belirtisi</span></h1>
<div class="liste">
 <div class="li"><span class="n">1</span>Musluklar kapalıyken su sayacı dönüyor.</div>
 <div class="li"><span class="n">2</span>Duvarda nem, kabarma ya da küf lekesi var.</div>
 <div class="li"><span class="n">3</span>Su faturası sebepsiz yere arttı.</div>
</div>
<p class="alt" style="font-size:28px">Duvarı kırmadan kaçak tespiti yapan ustalar samsunustam.com'da.</p></div>''' + alt_bant())

# 20 Ne lazımsa
ekle('20-ne-lazimsa', 'bg-mor', ust(L_BEYAZ, '<span class="pill">130+ HİZMET</span>') + f'''
<div class="main"><h1 class="h-l">Ne lazımsa,<br><span class="sari-t">kategorisini seç.</span></h1>
<div class="chips"><span class="chip">🔧 Tamir & montaj</span><span class="chip s">🏠 Tadilat</span><span class="chip">🔥 Kombi & klima</span><span class="chip">🧽 Temizlik</span><span class="chip s">🚚 Nakliyat</span><span class="chip">🪚 Mobilya & cam</span><span class="chip">🚗 Oto</span><span class="chip s">🚛 Ağır vasıta</span><span class="chip">🏭 Sanayi</span><span class="chip">🌿 Bahçe</span></div></div>
<div class="abs" style="right:50px;bottom:130px">{maskot(200, '#fff', MOR, (4, 6), 6)}</div>''' + alt_bant())
