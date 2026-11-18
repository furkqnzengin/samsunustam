"""Samsun Ustam sosyal medya tasarım kiti: ortak renkler, maskot ve sayfa iskeleti."""
import json, os

HERE = os.path.dirname(os.path.abspath(__file__))
MARK = json.load(open(os.path.join(HERE, 'mark.json')))

MOR, KOYU, SARI, LILA, TURUNCU, GRI, EN_KOYU = '#852FFF', '#4609A8', '#FFDA00', '#DCC5FF', '#FF8431', '#7D699E', '#22074F'


def maskot(boy=300, govde='#fff', goz=MOR, bak=(0, 0), don=0, sinif='', stil=''):
    """Logodaki 'B' karakteri. bak=(x,y) gözlerin baktığı yön (birim: göz çapı ~ 10)."""
    x0, y0, x1, y1 = MARK['bbox']
    w, h = x1 - x0, y1 - y0
    dx, dy = bak
    return (f'<svg class="maskot {sinif}" style="width:{boy}px;transform:rotate({don}deg);{stil}" viewBox="{x0-2:.1f} {y0-2:.1f} {w+4:.1f} {h+4:.1f}" xmlns="http://www.w3.org/2000/svg">'
            f'<path fill="{govde}" d="{MARK["mark"]}"/>'
            f'<g class="gozler" fill="{goz}" transform="translate({dx} {dy})"><path d="{MARK["eyeL"]}"/><path d="{MARK["eyeR"]}"/></g></svg>')


CSS = """
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:var(--W);height:var(--H);overflow:hidden}
body{font-family:Inter,system-ui,sans-serif;color:#1F0C42}
.c{position:relative;width:var(--W);height:var(--H);overflow:hidden;display:flex;flex-direction:column;padding:var(--pad)}
h1,h2,h3,.o{font-family:Outfit,Inter,sans-serif;letter-spacing:-.02em}
.bg-mor{background:#852FFF url(desen-mor.svg) 0 0/150px;color:#fff}
.bg-koyu{background:#4609A8 url(desen-mor.svg) 0 0/150px;color:#fff}
.bg-ekoyu{background:#22074F url(desen-mor.svg) 0 0/150px;color:#fff}
.bg-acik{background:#fff url(desen-acik.svg) 0 0/150px}
.bg-lila{background:#DCC5FF}
.bg-sari{background:#FFDA00;color:#22074F}
.bg-turuncu{background:#FF8431;color:#fff}
.top{display:flex;justify-content:space-between;align-items:center;position:relative;z-index:3}
.logo{height:var(--logo)}
.pill{display:inline-flex;align-items:center;gap:10px;font-family:Outfit,Inter,sans-serif;font-weight:700;font-size:calc(var(--u)*1.25);padding:.45em 1em;border-radius:999px;background:#FFDA00;color:#22074F}
.pill.mor{background:#852FFF;color:#fff}.pill.beyaz{background:#fff;color:#4609A8}.pill.koyu{background:#22074F;color:#fff}.pill.turuncu{background:#FF8431;color:#fff}
.main{flex:1;display:flex;flex-direction:column;justify-content:center;position:relative;z-index:2}
.h-xl{font-size:calc(var(--u)*4.8);line-height:.98;font-weight:800}
.h-l{font-size:calc(var(--u)*3.7);line-height:1.04;font-weight:800}
.h-m{font-size:calc(var(--u)*2.9);line-height:1.06;font-weight:800}
.sari-t{color:#FFDA00}.mor-t{color:#852FFF}.lila-t{color:#DCC5FF}.turuncu-t{color:#FF8431}.koyu-t{color:#4609A8}
.alt{font-size:calc(var(--u)*1.55);line-height:1.35;margin-top:calc(var(--u)*1.2);opacity:.92;max-width:88%}
.isaretle{background:linear-gradient(transparent 62%,#FFDA00 62%,#FFDA00 92%,transparent 92%);padding:0 .08em}
.foot{display:flex;justify-content:space-between;align-items:center;position:relative;z-index:3;font-family:Outfit,Inter,sans-serif;font-weight:700;font-size:calc(var(--u)*1.35)}
.url{display:inline-flex;align-items:center;gap:.5em;padding:.5em 1.1em;border-radius:999px;background:#22074F;color:#fff}
.bg-acik .url,.bg-lila .url,.bg-sari .url{background:#852FFF;color:#fff}
.kart{background:#fff;color:#1F0C42;border-radius:calc(var(--u)*1.6);padding:calc(var(--u)*1.4) calc(var(--u)*1.6);box-shadow:0 20px 60px rgba(34,7,79,.25)}
.liste{display:flex;flex-direction:column;gap:calc(var(--u)*.75);margin-top:calc(var(--u)*1.5)}
.li{display:flex;gap:calc(var(--u)*1);align-items:center;font-size:calc(var(--u)*1.5);font-weight:600;background:rgba(255,255,255,.14);border-radius:calc(var(--u)*1.2);padding:calc(var(--u)*.85) calc(var(--u)*1.2)}
.bg-acik .li,.bg-lila .li,.bg-sari .li{background:#fff;box-shadow:0 8px 24px rgba(70,9,168,.10)}
.li .n{flex:none;width:calc(var(--u)*2.6);height:calc(var(--u)*2.6);border-radius:50%;display:grid;place-items:center;background:#FFDA00;color:#22074F;font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.35)}
.li .e{flex:none;font-size:calc(var(--u)*2)}
.chips{display:flex;flex-wrap:wrap;gap:calc(var(--u)*.7);margin-top:calc(var(--u)*1.8)}
.chip{font-family:Outfit,Inter,sans-serif;font-weight:700;font-size:calc(var(--u)*1.45);padding:.5em 1em;border-radius:999px;background:#fff;color:#4609A8}
.chip.s{background:#FFDA00;color:#22074F}.chip.m{background:#852FFF;color:#fff}.chip.k{background:#4609A8;color:#fff}
.maskot{display:block}
.abs{position:absolute;z-index:1}
.yildiz{color:#FFDA00;letter-spacing:.08em}
.sticker{position:absolute;z-index:4;font-family:Outfit,Inter,sans-serif;font-weight:800;border-radius:999px;padding:.4em 1em;transform:rotate(-8deg);box-shadow:0 10px 30px rgba(0,0,0,.18)}
.balon{position:absolute;z-index:4;background:#fff;color:#22074F;font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.6);padding:.55em .9em;border-radius:1em;box-shadow:0 14px 34px rgba(34,7,79,.25)}
.balon:after{content:"";position:absolute;width:22px;height:22px;background:#fff;bottom:-9px;left:40px;transform:rotate(45deg);border-radius:4px}
.ui{background:#fff;color:#1F0C42;border-radius:calc(var(--u)*1.6);box-shadow:0 30px 80px rgba(34,7,79,.35);overflow:hidden;font-size:calc(var(--u)*1.1)}
.ui .uh{background:#22074F;color:#fff;padding:calc(var(--u)*.9) calc(var(--u)*1.2);display:flex;justify-content:space-between;align-items:center;font-family:Outfit,Inter,sans-serif;font-weight:700}
.ui .ub{padding:calc(var(--u)*1.1) calc(var(--u)*1.2);display:flex;flex-direction:column;gap:calc(var(--u)*.6)}
.ui .row{display:flex;justify-content:space-between;gap:10px;padding:calc(var(--u)*.45) 0;border-bottom:1px dashed #E9DFF8}
.ui .row:last-child{border-bottom:0}
.ui .k{color:#6E5A90}.ui b{font-family:Outfit,Inter,sans-serif}
.ui .tot{background:#4609A8;color:#fff;border-radius:calc(var(--u)*.8);padding:calc(var(--u)*.7) calc(var(--u)*1);display:flex;justify-content:space-between;font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.4)}
.tag{display:inline-block;background:#F0E7FF;color:#4609A8;border-radius:999px;padding:.2em .7em;font-weight:700;font-size:.85em}
.tag.y{background:#FFF6C2;color:#7a5600}.tag.g{background:#E3FBEA;color:#127a3c}
.poll{background:#fff;border-radius:calc(var(--u)*1.6);padding:calc(var(--u)*1.6);color:#22074F;box-shadow:0 30px 80px rgba(34,7,79,.3);text-align:center}
.poll .q{font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*2.2);line-height:1.1;margin-bottom:calc(var(--u)*1.2)}
.poll .opt{display:block;border:3px solid #E9DFF8;border-radius:999px;padding:calc(var(--u)*.8);font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.7);margin-top:calc(var(--u)*.8);background:linear-gradient(90deg,#DCC5FF var(--p,0%),#fff var(--p,0%))}
.linkst{display:inline-flex;align-items:center;gap:.5em;background:#fff;color:#852FFF;font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.7);padding:.6em 1.2em;border-radius:1em;box-shadow:0 14px 34px rgba(0,0,0,.2)}
.soru{background:#fff;border-radius:calc(var(--u)*1.6);overflow:hidden;box-shadow:0 30px 80px rgba(34,7,79,.3);color:#22074F;text-align:center}
.soru .sq{background:linear-gradient(135deg,#852FFF,#FF8431);color:#fff;padding:calc(var(--u)*1.4);font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*2)}
.soru .sa{padding:calc(var(--u)*1.6);color:#A99BC4;font-size:calc(var(--u)*1.5);font-weight:600}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:calc(var(--u)*.9);margin-top:calc(var(--u)*1.8)}
.step{background:#fff;color:#22074F;border-radius:calc(var(--u)*1.4);padding:calc(var(--u)*1.3);box-shadow:0 14px 40px rgba(34,7,79,.18)}
.step .num{width:calc(var(--u)*3);height:calc(var(--u)*3);border-radius:50%;background:#852FFF;color:#fff;display:grid;place-items:center;font-family:Outfit,Inter,sans-serif;font-weight:800;font-size:calc(var(--u)*1.6);margin-bottom:calc(var(--u)*.8)}
.step h3{font-size:calc(var(--u)*1.8);line-height:1.1}
.step p{color:#6E5A90;font-size:calc(var(--u)*1.15);margin-top:.4em;line-height:1.35}
.big{font-family:Outfit,Inter,sans-serif;font-weight:900;line-height:.9}
.halka{position:absolute;border-radius:50%;z-index:0}
"""


def sayfa(govde, W=1080, H=1080, sinif='bg-mor'):
    story = H > W
    u = 24 if not story else 26
    pad = '72px 72px 64px' if not story else '110px 80px 120px'
    logo = 66 if not story else 76
    return f'''<!doctype html><html lang="tr"><head><meta charset="utf-8">
<link rel="stylesheet" href="fonts/fonts.css">
<style>:root{{--W:{W}px;--H:{H}px;--u:{u}px;--pad:{pad};--logo:{logo}px}}{CSS}</style></head>
<body><div class="c {sinif}">{govde}</div></body></html>'''


def ust(logo='logo-yatay-beyaz.svg', sag=''):
    return f'<div class="top"><img class="logo" src="{logo}">{sag}</div>'


def alt_bant(sol='📍 Samsun · 17 ilçe', koyu=False):
    return f'<div class="foot"><span>{sol}</span><span class="url">🌐 samsunustam.com</span></div>'
