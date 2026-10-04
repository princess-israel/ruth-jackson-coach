// Usage: node build.js <topic-slug>   (topics live in topics.json; output: social/carousels-v2/<slug>/01..06.jpg)
const { chromium } = require('/opt/node-tools/node_modules/playwright');
const fs = require('fs'), path = require('path');
const K = __dirname, R = path.join(K, '..', '..');
const slug = process.argv[2] || 'ai';
const T = JSON.parse(fs.readFileSync(path.join(K, 'topics.json'), 'utf8'))[slug];
if (!T) { console.error('Unknown topic ' + slug); process.exit(1); }
const PIC = 'file://' + K + '/assets/ruth-cutout.png', MOON = 'file://' + K + '/assets/moon-gold.png';
const HANDLE = '@timshidigitalswith_ruthjackson';
const base = `*{box-sizing:border-box;margin:0}body{width:1080px;height:1350px;position:relative;overflow:hidden;font-family:'Helvetica Neue',Arial,'Liberation Sans',sans-serif;color:#fff;background:radial-gradient(circle at 70% 10%,#14245e 0,#0a1330 50%,#050a20 100%)}
.g{color:#ffd34d}.abs{position:absolute}.disc{position:absolute;border-radius:50%;background:radial-gradient(circle at 40% 35%,#ffd34d,#ffc21a 60%,#e0a010)}
.handle{position:absolute;top:62px;left:60px;font-size:28px;font-weight:600;letter-spacing:.02em;white-space:nowrap}`;
const moon = (size, top, right) => `<div class="abs" style="top:${top + size * .19}px;right:${right + size * .16}px;width:${size * .62}px;height:${size * .62}px;border-radius:50%;box-shadow:0 0 ${size * .3}px ${size * .13}px rgba(255,194,26,.35)"></div><img class="abs" src="${MOON}" style="top:${top}px;right:${right}px;width:${size}px;height:${size}px">`;
const ruth = (w, left, top, disc) => `<div class="disc" style="width:${disc}px;height:${disc}px;left:${left + 40}px;top:${top + 90}px"></div><img class="abs" src="${PIC}" style="width:${w}px;left:${left}px;top:${top}px">`;
const cover = () => `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(290, 36, 26)}
<div class="abs" style="top:250px;left:60px;width:620px;font-size:128px;line-height:1.02;font-weight:900">${T.hook.join('<br>')}</div>
<div class="abs g" style="top:700px;left:60px;width:520px;font-size:68px;line-height:1.1;font-weight:800">${T.gold}</div>${ruth(640, 480, 610, 560)}`;
const tip = i => { const [h, t] = T.tips[i]; return `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(210, 40, 36)}
<div class="abs" style="top:260px;left:60px;width:150px;height:150px;border-radius:50%;background:#ffc21a;color:#050a20;display:grid;place-items:center;font-size:88px;font-weight:800;box-shadow:0 14px 34px -10px rgba(255,194,26,.7)">${i + 1}</div>
<div class="abs" style="top:450px;left:60px;width:640px;font-size:92px;line-height:1.04;font-weight:900">${h}</div>
<div class="abs g" style="top:760px;left:60px;width:470px;font-size:46px;line-height:1.28;font-weight:700">${t}</div>${ruth(520, 560, 790, 450)}
<div class="abs" style="left:60px;bottom:70px;display:flex;gap:14px">${[0, 1, 2, 3].map(k => `<i style="width:16px;height:16px;border-radius:50%;background:${k == i ? '#ffc21a' : '#33447f'}"></i>`).join('')}</div>`; };
const cta = () => `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(210, 40, 36)}
<div class="abs" style="top:250px;left:60px;width:900px;font-size:96px;line-height:1.05;font-weight:900;white-space:nowrap">You can learn<br>this with Ruth.</div>
<div class="abs g" style="top:490px;left:60px;width:640px;font-size:48px;line-height:1.15;font-weight:800">${T.course}</div>
<div class="abs" style="top:650px;left:60px;background:#25D366;color:#fff;font-size:46px;font-weight:800;padding:24px 70px;border-radius:70px">WhatsApp</div>
<div class="abs" style="top:830px;left:60px;font-size:27px;line-height:1.65;width:520px;white-space:nowrap">TikTok @timshidigitals<br>Instagram ${HANDLE}<br>Facebook Ruth Jackson<br>coachruthjackson.com</div>${ruth(520, 560, 790, 450)}`;
const icon = k => ({
  follow: '<svg width="56" height="56" viewBox="0 0 56 56"><circle cx="24" cy="19" r="9" fill="#050a20"/><path d="M6 46c0-10 8-16 18-16s18 6 18 16z" fill="#050a20"/><path d="M45 14v14M38 21h14" stroke="#050a20" stroke-width="5" stroke-linecap="round"/></svg>',
  save: '<svg width="56" height="56" viewBox="0 0 56 56"><path d="M14 6h28v44L28 38 14 50z" fill="#050a20"/></svg>',
  share: '<svg width="56" height="56" viewBox="0 0 56 56"><path d="M50 7L5 24l16 7 5 18 8-12 12 8z" fill="#050a20"/></svg>' })[k];
const follow = () => {
  const rows = [['follow', 'Follow', 'for practical tips from Ruth'], ['save', 'Save for later', 'so you can come back to it'], ['share', 'Share with a friend', 'who needs to hear this']];
  return `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(210, 40, 36)}
<div class="abs" style="top:230px;left:60px;font-size:112px;line-height:1.02;font-weight:900">Enjoyed<br>this?</div>
${rows.map((r, i) => `<div class="abs" style="top:${520 + i * 175}px;left:60px;display:flex;align-items:center;gap:28px"><div style="width:112px;height:112px;border-radius:50%;background:linear-gradient(135deg,#ffd34d,#ffc21a 60%,#e0a010);display:grid;place-items:center;box-shadow:0 14px 34px -10px rgba(255,194,26,.7);flex:0 0 auto">${icon(r[0])}</div><div><div style="font-size:54px;font-weight:900;line-height:1.05;white-space:nowrap">${r[1]}</div><div class="g" style="font-size:32px;font-weight:700;margin-top:6px;white-space:nowrap">${r[2]}</div></div></div>`).join('')}
${ruth(430, 650, 930, 380)}`; };
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
  const pg = await b.newPage({ viewport: { width: 1080, height: 1350 } });
  const out = path.join(R, 'social/carousels-v2', slug); fs.mkdirSync(out, { recursive: true });
  const slides = [cover(), tip(0), tip(1), tip(2), tip(3), cta(), follow()];
  for (let i = 0; i < slides.length; i++) {
    const f = path.join(K, '.tmp.html'); fs.writeFileSync(f, '<!doctype html><meta charset=utf-8><body>' + slides[i]);
    await pg.goto('file://' + f); await pg.waitForTimeout(250);
    await pg.screenshot({ path: path.join(out, String(i + 1).padStart(2, '0') + '.jpg'), type: 'jpeg', quality: 92 });
  }
  fs.unlinkSync(path.join(K, '.tmp.html')); await b.close();
})();
