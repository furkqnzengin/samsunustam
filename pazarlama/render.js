const { chromium } = require('playwright'); const job = require('./_job.json');
(async () => { const b = await chromium.launch(); const c = await b.newContext({ viewport: { width: job.W, height: job.H } }); const p = await c.newPage();
  for (const [ad, f] of job.items) { await p.goto('file://' + f); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(150);
    const ov = await p.evaluate(() => { const c = document.querySelector('.c'); const r = []; c.querySelectorAll('*').forEach(e => { const b = e.getBoundingClientRect(); if (b.width && (b.right > innerWidth + 1 || b.bottom > innerHeight + 1) && !e.closest('.abs')) r.push(e.className || e.tagName); }); return r.slice(0, 3); });
    if (ov.length) console.log('TAŞMA', ad, ov);
    await p.screenshot({ path: `${job.out}/${ad}.png` }); }
  await b.close(); })();
