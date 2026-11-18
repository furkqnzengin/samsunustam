// Reels için ortak yardımcılar: zamana bağlı (deterministik) animasyon.
// Her reel sayfası window.SURE (saniye), window.CUES ([zaman, ses]) ve render(t) tanımlar.
const clamp = (x, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const seg = (t, a, b) => clamp((t - a) / (b - a));           // a..b arası 0→1
const lerp = (a, b, p) => a + (b - a) * p;
const E = {
  out: p => 1 - Math.pow(1 - p, 3),
  in: p => p * p * p,
  io: p => (p < .5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2),
  back: p => { const c1 = 1.70158, c3 = c1 + 1; return 1 + c3 * Math.pow(p - 1, 3) + c1 * Math.pow(p - 1, 2); },
  elastic: p => p === 0 ? 0 : p === 1 ? 1 : Math.pow(2, -10 * p) * Math.sin((p * 10 - .75) * (2 * Math.PI / 3)) + 1,
};
const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];
// Sahne görünürlüğü: a'da gir, b'de çık (fade süresi f)
function sahne(el, t, a, b, f = .25) {
  const o = Math.min(seg(t, a, a + f), 1 - seg(t, b - f, b));
  el.style.opacity = o; el.style.visibility = o <= 0.001 ? 'hidden' : 'visible';
  return o;
}
function stil(el, o) { for (const k in o) el.style[k] = o[k]; }
// Pop-in: ölçek + saydamlık
function pop(el, t, a, d = .45, ease = E.back) {
  const p = seg(t, a, a + d); const s = ease(p);
  el.style.opacity = clamp(p * 3); el.style.transform = `scale(${lerp(.4, 1, s)})`;
  return p;
}
// Kaydırarak gir
function kaydir(el, t, a, d = .5, dx = 0, dy = 80, ekstra = '') {
  const p = E.out(seg(t, a, a + d));
  el.style.opacity = clamp(p * 1.5); el.style.transform = `translate(${dx * (1 - p)}px, ${dy * (1 - p)}px) ${ekstra}`;
  return p;
}
// Daktilo efekti
function yaz(el, t, a, metin, hiz = 14) {
  const n = Math.floor(clamp((t - a) * hiz, 0, metin.length));
  el.textContent = metin.slice(0, n);
  el.classList.toggle('imlec', t >= a - .3 && (n < metin.length || Math.floor(t * 2) % 2 === 0));
  return n;
}
// Göz kırpma: verilen zamanlarda maskot gözlerini kapat
function kirp(svg, t, anlar) {
  const g = svg.querySelector('.gozler'); if (!g) return;
  let s = 1; for (const a of anlar) { const d = Math.abs(t - a); if (d < .09) s = Math.min(s, lerp(.08, 1, d / .09)); }
  g.style.transformBox = 'fill-box'; g.style.transformOrigin = 'center';
  g.style.transform = (g.dataset.bak || '') + ` scaleY(${s})`;
}
function bak(svg, dx, dy) { const g = svg.querySelector('.gozler'); g.dataset.bak = `translate(${dx}px, ${dy}px)`; }
// Sayı sayma
function say(el, t, a, d, hedef, bicim = n => n.toLocaleString('tr-TR')) {
  el.textContent = bicim(Math.round(lerp(0, hedef, E.out(seg(t, a, a + d)))));
}
