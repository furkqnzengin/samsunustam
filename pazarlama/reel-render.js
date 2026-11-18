// Kullanım: node reel-render.js <html> <kare_klasörü> [fps] [--still t1,t2,...]
const { chromium } = require('playwright'); const fs = require('fs');
const [, , html, out, fpsArg, stillFlag, stills] = process.argv; const fps = +(fpsArg || 30);
(async () => { const b = await chromium.launch(); const p = await (await b.newContext({ viewport: { width: 1080, height: 1920 } })).newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto('file://' + html); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(300);
  fs.mkdirSync(out, { recursive: true });
  const sure = await p.evaluate(() => window.SURE); fs.writeFileSync(out + '/cues.json', JSON.stringify(await p.evaluate(() => ({ sure: window.SURE, cues: window.CUES }))));
  const zamanlar = stillFlag === '--still' ? stills.split(',').map(Number) : [...Array(Math.round(sure * fps)).keys()].map(i => i / fps);
  for (let i = 0; i < zamanlar.length; i++) { await p.evaluate(t => render(t), zamanlar[i]);
    await p.screenshot({ path: `${out}/${stillFlag === '--still' ? 's' + zamanlar[i] : String(i).padStart(5, '0')}.jpg`, type: 'jpeg', quality: 90 }); }
  if (errs.length) console.log('JS HATA', errs.slice(0, 3)); await b.close(); })();
