"""3 dikey reels videosunun sahneleri (HTML + zaman çizelgesi)."""
from kit import *

EK_CSS = """
.c{padding:0}
.sc{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:120px 80px}
.imlec:after{content:"";display:inline-block;width:6px;height:1em;background:#852FFF;margin-left:6px;vertical-align:-.12em}
.cardu{display:flex;align-items:center;gap:26px;background:#fff;color:#22074F;border-radius:36px;padding:30px 34px;box-shadow:0 30px 70px rgba(34,7,79,.3);width:860px;text-align:left}
.cardu .av{width:120px;height:120px;border-radius:32px;background:#F0E7FF;color:#852FFF;display:grid;place-items:center;font-family:Outfit;font-weight:800;font-size:46px;flex:none}
.cardu h3{font-size:48px}.cardu p{color:#6E5A90;font-size:32px;margin-top:4px}
.cardu .sc2{margin-left:auto;text-align:center;background:#F8F4FF;border-radius:24px;padding:14px 20px;font-family:Outfit;font-weight:800;font-size:44px;color:#4609A8}
.btnr{display:inline-flex;align-items:center;gap:16px;font-family:Outfit;font-weight:800;font-size:56px;padding:30px 60px;border-radius:999px;background:#FFDA00;color:#22074F;box-shadow:0 20px 50px rgba(0,0,0,.25)}
.ikon{position:absolute;font-size:150px}
.bigt{font-family:Outfit;font-weight:800;line-height:1.02;letter-spacing:-.02em}
.say{font-family:Outfit;font-weight:900}
"""


def reel_sayfa(govde, sure, cues, js):
    html = sayfa(govde, 1080, 1920, 'bg-mor')
    html = html.replace('</style>', EK_CSS + '</style>')
    script = f'<script src="reel-ortak.js"></script><script>window.SURE={sure};window.CUES={cues};\nfunction render(t){{\n{js}\n}}\nrender(0);</script>'
    return html.replace('</body>', script + '</body>')


R = []

# ---------------------------------------------------------------- REEL 1
r1 = f'''
<div class="sc" id="s1">
  <div id="m1">{maskot(420, '#fff', MOR, (0, 0), 0)}</div>
  <div class="bigt" id="t1" style="font-size:118px;margin-top:70px">Evde bir şey mi<br><span class="sari-t">bozuldu?</span></div>
</div>
<div class="sc" id="s2">
  <div class="ikon" id="i1" style="left:120px;top:330px">💧</div><div class="ikon" id="i2" style="right:120px;top:420px">⚡</div>
  <div class="ikon" id="i3" style="left:150px;bottom:420px">🔥</div><div class="ikon" id="i4" style="right:150px;bottom:340px">🔑</div>
  <div class="bigt" style="font-size:96px"><div id="p1">Su mu kaçırıyor?</div><div id="p2" class="sari-t">Sigorta mı atıyor?</div><div id="p3">Kombi mi çalışmıyor?</div></div>
</div>
<div class="sc bg-acik" id="s3" style="justify-content:flex-start;padding-top:260px">
  <img src="logo-yatay.svg" style="height:100px;margin-bottom:80px" id="l3">
  <div class="kart" id="k3" style="width:900px;text-align:left;padding:46px 50px">
    <div class="o" style="font-weight:800;font-size:58px;line-height:1.25;color:#22074F">Bana bir <span id="y1" class="mor-t"></span><br>lazım, <span id="y2" class="mor-t"></span></div>
    <div class="btnr" id="b3" style="margin-top:36px;background:#22074F;color:#fff;font-size:44px;padding:22px 44px">Ustaları göster →</div>
  </div>
  <div style="display:flex;flex-direction:column;gap:28px;margin-top:70px">
    <div class="cardu" id="u1"><div class="av">AY</div><div><h3>Ahmet Y.</h3><p>Elektrikçi · Atakum</p></div><div class="sc2">4.9★</div></div>
    <div class="cardu" id="u2"><div class="av">MK</div><div><h3>Mehmet K.</h3><p>Elektrikçi · Atakum</p></div><div class="sc2">4.8★</div></div>
  </div>
</div>
<div class="sc bg-koyu" id="s4">
  <div class="btnr" id="b4">📞 Ara</div>
  <div class="bigt" id="t4" style="font-size:112px;margin-top:90px">Aracı yok.<br><span class="sari-t">Komisyon yok.</span></div>
  <p class="alt" id="t4b" style="font-size:50px;margin:40px auto 0">Ustanla doğrudan konuş.</p>
</div>
<div class="sc" id="s5">
  <img src="logo-dikey-beyaz.svg" id="l5" style="width:760px">
  <div class="linkst" id="w5" style="margin-top:80px;font-size:52px">🔗 samsunustam.com</div>
  <p class="alt" id="t5" style="font-size:44px;margin:50px auto 0">Samsun'un 17 ilçesinde güvenilir ustalar.</p>
</div>
'''
c1 = [[0.15, 'whoosh'], [0.55, 'pop'], [1.0, 'pop'], [2.6, 'whoosh'], [2.9, 'pop'], [3.3, 'pop'], [3.7, 'pop'], [4.1, 'pop'],
      [5.4, 'whoosh'], [6.0, 'yazi:1.4'], [7.6, 'yazi:0.7'], [8.5, 'click'], [8.75, 'whoosh'], [9.05, 'whoosh'],
      [10.2, 'whoosh'], [10.6, 'ding'], [11.3, 'pop'], [12.9, 'whoosh'], [13.05, 'shimmer'], [14.0, 'pop']]
j1 = '''
const m1=$('#m1 svg');
if (sahne($('#s1'),t,0,2.6)>0){ const p=seg(t,.15,.85); const y=lerp(-900,0,E.elastic(p)); $('#m1').style.transform=`translateY(${y}px) rotate(${lerp(-25,0,E.out(p))}deg)`;
  kaydir($('#t1'),t,.9,.5,0,60); bak(m1, Math.sin(t*2)*5, -3); kirp(m1,t,[1.8]); }
if (sahne($('#s2'),t,2.6,5.5)>0){ ['#i1','#i2','#i3','#i4'].forEach((s,i)=>{const e=$(s); pop(e,t,2.9+i*.4,.5); e.style.transform+=` rotate(${Math.sin(t*3+i)*12}deg) translateY(${Math.sin(t*4+i)*14}px)`;});
  ['#p1','#p2','#p3'].forEach((s,i)=>kaydir($(s),t,2.9+i*.4,.45,0,50)); }
if (sahne($('#s3'),t,5.4,10.3)>0){ kaydir($('#l3'),t,5.5,.4,0,-40); kaydir($('#k3'),t,5.6,.5,0,80);
  yaz($('#y1'),t,6.0,'elektrikçi',7); yaz($('#y2'),t,7.6,"Atakum'da.",13); if(t<7.6) $('#y2').classList.remove('imlec'); if(t>7.5) $('#y1').classList.remove('imlec');
  const b=$('#b3'); const pr=seg(t,8.45,8.6)-seg(t,8.6,8.8); b.style.transform=`scale(${1-pr*.08})`;
  kaydir($('#u1'),t,8.75,.5,900,0); kaydir($('#u2'),t,9.05,.5,900,0); }
if (sahne($('#s4'),t,10.2,12.95)>0){ const b=$('#b4'); pop(b,t,10.35,.5); const r=Math.sin(t*40)*Math.max(0,1-seg(t,10.6,11.1))*6; b.style.transform+=` rotate(${r}deg)`;
  kaydir($('#t4'),t,10.9,.5,0,60); kaydir($('#t4b'),t,11.3,.5,0,40); }
if (sahne($('#s5'),t,12.9,15.2,.3)>0){ pop($('#l5'),t,13.0,.7); pop($('#w5'),t,14.0,.5); kaydir($('#t5'),t,14.2,.5,0,30); }
'''
R.append(('reel-1-evde-bir-sey-mi-bozuldu', reel_sayfa(r1, 15, c1, j1), 15))

# ---------------------------------------------------------------- REEL 2
r2 = f'''
<div class="sc bg-koyu" id="s1">
  <span class="pill" id="p1" style="font-size:40px">🛠️ USTALAR İÇİN</span>
  <div class="bigt" id="t1" style="font-size:120px;margin-top:50px">Defter, kalem<br><span class="sari-t">tarih oldu.</span></div>
  <div id="m1" style="margin-top:70px">{maskot(260, '#FFDA00', EN_KOYU, (0, 0), 8)}</div>
</div>
<div class="sc bg-acik" id="s2" style="justify-content:flex-start;padding-top:200px">
  <div class="bigt koyu-t" id="t2" style="font-size:80px">Araç geldi 🚗</div>
  <div class="ui" id="ui" style="width:920px;margin-top:50px;font-size:40px;text-align:left">
    <div class="uh" style="font-size:44px"><span>Kayıt #128</span><span class="tag" id="dur" style="font-size:34px">Kabul edildi</span></div>
    <div class="ub" style="gap:18px">
      <div class="row"><span class="k">Plaka</span><b id="pl" style="font-size:48px"></b></div>
      <div class="row" id="r1"><span class="k">Motor yağı ×5</span><b>2.250 ₺</b></div>
      <div class="row" id="r2"><span class="k">Yağ filtresi</span><b>350 ₺</b></div>
      <div class="row" id="r3"><span class="k">Fren balatası</span><b>1.800 ₺</b></div>
      <div class="row" id="r4"><span class="k">İşçilik</span><b>1.500 ₺</b></div>
      <div class="row"><span class="k">Toplam</span><b><span id="top">0</span> ₺</b></div>
      <div class="row" id="r5"><span class="k">💵 Alınan</span><b class="up" style="color:#127a3c"><span id="aln">0</span> ₺</b></div>
      <div class="tot" style="font-size:52px"><span>Kalan</span><span><span id="kal">0</span> ₺</span></div>
    </div>
  </div>
  <div id="ph" style="display:flex;gap:20px;margin-top:40px"><div style="width:200px;height:200px;border-radius:26px;background:#DCC5FF;display:grid;place-items:center;font-size:80px">📷</div><div style="width:200px;height:200px;border-radius:26px;background:#DCC5FF;display:grid;place-items:center;font-size:80px">🔧</div></div>
</div>
<div class="sc bg-mor" id="s3">
  <div style="width:620px;height:780px;background:#fff;border-radius:44px;box-shadow:0 40px 90px rgba(0,0,0,.3);padding:56px;color:#22074F;text-align:left" id="rp">
    <div class="o" style="font-weight:800;color:#852FFF;font-size:30px;letter-spacing:.12em">SERVİS RAPORU</div><div class="o" style="font-weight:900;font-size:84px">#128</div>
    <div style="height:18px;background:#F0E7FF;border-radius:9px;margin:30px 0 14px"></div><div style="height:18px;width:70%;background:#F0E7FF;border-radius:9px"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:34px"><div style="aspect-ratio:1;background:#DCC5FF;border-radius:18px"></div><div style="aspect-ratio:1;background:#DCC5FF;border-radius:18px"></div></div>
    <div style="height:90px;background:#4609A8;border-radius:20px;margin-top:44px"></div></div>
  <div class="sticker" id="wa" style="position:relative;margin-top:-50px;background:#25D366;color:#fff;font-size:50px">💬 Müşteriye gönderildi ✓✓</div>
  <div class="bigt" id="t3" style="font-size:84px;margin-top:80px">Raporu WhatsApp'tan<br><span class="sari-t">tek tuşla gönder.</span></div>
</div>
<div class="sc bg-ekoyu" id="s4">
  <div class="bigt" style="font-size:70px" id="t4a">Bu ay net kazanç</div>
  <div class="say sari-t" style="font-size:190px;margin-top:20px"><span id="net">0</span> ₺</div>
  <div style="display:flex;gap:30px;margin-top:40px;font-family:Outfit;font-weight:800;font-size:48px" id="t4b"><span style="color:#7CF0A6">+48.200 gelir</span><span style="color:#FF9A9A">−17.650 gider</span></div>
  <p class="alt" style="font-size:36px;margin:50px auto 0;opacity:.7" id="t4c">* Rakamlar örnektir.</p>
</div>
<div class="sc" id="s5">
  <img src="logo-dikey-beyaz.svg" id="l5" style="width:740px">
  <div class="bigt" id="t5" style="font-size:72px;margin-top:70px">Usta paneli <span class="sari-t">ücretsiz.</span></div>
  <div class="linkst" id="w5" style="margin-top:60px;font-size:52px">🔗 samsunustam.com</div>
</div>
'''
c2 = [[0.2, 'whoosh'], [0.5, 'pop'], [1.0, 'whoosh'], [2.2, 'pop'], [3.0, 'whoosh'], [3.6, 'yazi:0.8'], [4.6, 'pop'], [5.1, 'pop'], [5.6, 'pop'], [6.1, 'pop'],
      [6.6, 'kasa'], [7.6, 'kasa'], [8.6, 'ding'], [9.0, 'pop'], [9.4, 'pop'], [10.3, 'whoosh'], [10.9, 'whoosh'], [11.4, 'ding'], [13.2, 'whoosh'], [13.5, 'say:1.4'], [15.0, 'kasa'],
      [15.6, 'whoosh'], [15.75, 'shimmer'], [16.8, 'pop']]
j2 = '''
if (sahne($('#s1'),t,0,3.05)>0){ pop($('#p1'),t,.4,.5); kaydir($('#t1'),t,.8,.6,0,80); const m=$('#m1 svg'); pop($('#m1'),t,2.1,.6); kirp(m,t,[2.8]); bak(m,Math.sin(t*3)*5,0); }
if (sahne($('#s2'),t,3.0,10.4)>0){ kaydir($('#t2'),t,3.05,.4,-200,0); kaydir($('#ui'),t,3.2,.5,0,120); yaz($('#pl'),t,3.6,'55 ABC 123',11);
  ['#r1','#r2','#r3','#r4'].forEach((s,i)=>kaydir($(s),t,4.6+i*.5,.35,120,0));
  const top=Math.round(lerp(0,5900,E.out(seg(t,6.6,7.3)))); $('#top').textContent=top.toLocaleString('tr-TR');
  kaydir($('#r5'),t,7.5,.35,120,0); const aln=t>=7.6?4000:0; $('#aln').textContent=aln.toLocaleString('tr-TR');
  const kal=Math.max(0,top-Math.round(lerp(0,4000,E.out(seg(t,7.6,8.1))))); $('#kal').textContent=kal.toLocaleString('tr-TR');
  const d=$('#dur'); if(t>=8.6){d.textContent='Teslime hazır ✓';d.className='tag g';d.style.transform=`scale(${1+.3*(1-seg(t,8.6,9))})`;}else{d.textContent='İşlemde';d.className='tag y';}
  pop($('#ph'),t,9.0,.5); }
if (sahne($('#s3'),t,10.3,13.3)>0){ kaydir($('#rp'),t,10.35,.6,0,-200,`rotate(${lerp(-12,-4,E.out(seg(t,10.35,11)))}deg)`); pop($('#wa'),t,11.35,.5); kaydir($('#t3'),t,11.6,.5,0,50); }
if (sahne($('#s4'),t,13.2,15.7)>0){ kaydir($('#t4a'),t,13.25,.4,0,40); say($('#net'),t,13.5,1.4,30550); kaydir($('#t4b'),t,14.9,.4,0,40); kaydir($('#t4c'),t,15.1,.4,0,20); }
if (sahne($('#s5'),t,15.6,18.2,.3)>0){ pop($('#l5'),t,15.7,.7); kaydir($('#t5'),t,16.4,.5,0,40); pop($('#w5'),t,16.8,.5); }
'''
R.append(('reel-2-atolyeni-cebinden-yonet', reel_sayfa(r2, 18, c2, j2), 18))

# ---------------------------------------------------------------- REEL 3
r3 = f'''
<div class="sc bg-sari" id="s1">
  <div style="font-size:220px" id="e1">📢</div>
  <div class="bigt" id="t1" style="font-size:120px;color:#22074F;margin-top:30px">Eleman mı<br>arıyorsun?</div>
  <div class="cardu" id="k1" style="margin-top:70px"><div class="av">💼</div><div><h3>CNC operatörü aranıyor</h3><p>Tekkeköy · Tam zamanlı</p></div></div>
</div>
<div class="sc bg-koyu" id="s2">
  <div style="font-size:220px" id="e2">🙋</div>
  <div class="bigt" id="t2" style="font-size:120px;margin-top:30px">İş mi<br><span class="sari-t">arıyorsun?</span></div>
  <div class="cardu" id="k2" style="margin-top:70px"><div class="av">CÖ</div><div><h3>Oto elektrik kalfası</h3><p>Atakum · 3-5 yıl tecrübe</p></div></div>
</div>
<div class="sc bg-mor" id="s3">
  <div id="ma" class="abs" style="left:130px;top:620px">{maskot(300, '#FFDA00', EN_KOYU, (8, 0), 0)}</div>
  <div id="mb" class="abs" style="right:130px;top:620px">{maskot(300, '#fff', MOR, (-8, 0), 0)}</div>
  <div id="kalp" class="abs" style="left:50%;top:640px;font-size:180px;transform:translateX(-50%)">🤝</div>
  <div class="bigt" id="t3" style="font-size:100px;margin-top:700px">İkisi de<br><span class="sari-t">burada buluşuyor.</span></div>
</div>
<div class="sc bg-ekoyu" id="s4">
  <div class="liste" style="width:880px;margin:0">
    <div class="li" id="a1" style="font-size:50px;background:rgba(255,255,255,.12)"><span class="e" style="font-size:64px">📝</span>Ücretsiz ilan</div>
    <div class="li" id="a2" style="font-size:50px;background:rgba(255,255,255,.12)"><span class="e" style="font-size:64px">📥</span>Başvurular panele gelir</div>
    <div class="li" id="a3" style="font-size:50px;background:rgba(255,255,255,.12)"><span class="e" style="font-size:64px">🔒</span>Telefonlar sadece ustalara</div>
  </div>
</div>
<div class="sc" id="s5">
  <img src="logo-dikey-beyaz.svg" id="l5" style="width:700px">
  <div class="bigt" id="t5" style="font-size:76px;margin-top:80px">İş ilanları <span class="sari-t">yayında.</span></div>
  <div class="linkst" id="w5" style="margin-top:60px;font-size:48px">🔗 samsunustam.com/isler</div>
</div>
'''
c3 = [[0.15, 'whoosh'], [0.4, 'pop'], [0.9, 'pop'], [1.8, 'whoosh'], [3.0, 'whoosh'], [3.3, 'pop'], [3.8, 'pop'], [4.7, 'whoosh'],
      [6.2, 'whoosh'], [6.45, 'whoosh'], [7.2, 'ding'], [7.6, 'pop'], [9.2, 'whoosh'], [9.5, 'pop'], [10.0, 'pop'], [10.5, 'pop'],
      [11.9, 'whoosh'], [12.3, 'shimmer'], [13.2, 'pop']]
j3 = '''
if (sahne($('#s1'),t,0,3.05)>0){ pop($('#e1'),t,.35,.5); $('#e1').style.transform+=` rotate(${Math.sin(t*6)*8}deg)`; kaydir($('#t1'),t,.7,.5,0,60); kaydir($('#k1'),t,1.8,.5,-900,0); }
if (sahne($('#s2'),t,3.0,6.25)>0){ pop($('#e2'),t,3.3,.5); $('#e2').style.transform+=` rotate(${Math.sin(t*5)*10}deg)`; kaydir($('#t2'),t,3.6,.5,0,60); kaydir($('#k2'),t,4.7,.5,900,0); }
if (sahne($('#s3'),t,6.2,9.3)>0){ const pa=E.back(seg(t,6.2,6.9)), pb=E.back(seg(t,6.45,7.15));
  $('#ma').style.transform=`translateX(${lerp(-700,40,pa)}px) rotate(${lerp(-30,8,pa)}deg)`; $('#mb').style.transform=`translateX(${lerp(700,-40,pb)}px) rotate(${lerp(30,-8,pb)}deg)`;
  const a=$('#ma svg'), b=$('#mb svg'); bak(a,8,-2); bak(b,-8,-2); kirp(a,t,[8.2]); kirp(b,t,[8.25]);
  const k=$('#kalp'); const p=seg(t,7.15,7.6); k.style.opacity=clamp(p*2); k.style.transform=`translateX(-50%) translateY(${-140*E.out(p)}px) scale(${lerp(.3,1,E.back(p))+Math.sin(t*8)*.04*p})`;
  kaydir($('#t3'),t,7.6,.5,0,60); }
if (sahne($('#s4'),t,9.2,12.0)>0){ ['#a1','#a2','#a3'].forEach((s,i)=>kaydir($(s),t,9.5+i*.5,.4,-200,0)); }
if (sahne($('#s5'),t,11.9,15.2,.3)>0){ kaydir($('#l5'),t,12.0,.6,0,80); kaydir($('#t5'),t,12.6,.5,0,40); pop($('#w5'),t,13.2,.5); }
'''
R.append(('reel-3-is-ve-eleman', reel_sayfa(r3, 15, c3, j3), 15))
