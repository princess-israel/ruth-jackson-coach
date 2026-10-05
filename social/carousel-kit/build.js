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
const swipe = b => `<div class="abs" style="left:60px;bottom:${b}px;display:flex;align-items:center;gap:14px;border:3px solid #ffc21a;border-radius:40px;padding:10px 26px;font-size:28px;font-weight:800;letter-spacing:.16em;color:#ffd34d">SWIPE<svg width="40" height="22" viewBox="0 0 40 22"><path d="M2 11h34M27 3l9 8-9 8" stroke="#ffd34d" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>`;
const swipeSm = () => `<div class="abs" style="right:60px;bottom:22px;display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,#ffd34d,#ffc21a 60%,#e0a010);border-radius:30px;padding:7px 20px;font-size:20px;font-weight:900;letter-spacing:.14em;color:#050a20;box-shadow:0 8px 20px -8px rgba(255,194,26,.7)">SWIPE<svg width="28" height="16" viewBox="0 0 40 22"><path d="M2 11h34M27 3l9 8-9 8" stroke="#050a20" stroke-width="4.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></div>`;
const cover = () => `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(290, 36, 26)}
<div class="abs" style="top:250px;left:60px;width:700px;white-space:nowrap;font-size:128px;line-height:1.02;font-weight:900">${T.hook.join('<br>')}</div>
<div class="abs g" style="top:700px;left:60px;width:520px;font-size:68px;line-height:1.1;font-weight:800">${T.gold}</div>${ruth(640, 480, 610, 560)}${swipe(70)}`;
const tip = i => { const [h, t] = T.tips[i]; return `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(210, 40, 36)}
<div class="abs" style="top:260px;left:60px;width:150px;height:150px;border-radius:50%;background:#ffc21a;color:#050a20;display:grid;place-items:center;font-size:88px;font-weight:800;box-shadow:0 14px 34px -10px rgba(255,194,26,.7)">${i + 1}</div>
<div class="abs" style="top:450px;left:60px;width:640px;font-size:92px;line-height:1.04;font-weight:900">${h}</div>
<div class="abs g" style="top:760px;left:60px;width:470px;font-size:46px;line-height:1.28;font-weight:700">${t}</div>${ruth(520, 560, 790, 450)}
${swipe(125)}<div class="abs" style="left:60px;bottom:70px;display:flex;gap:14px">${[0, 1, 2, 3].map(k => `<i style="width:16px;height:16px;border-radius:50%;background:${k == i ? '#ffc21a' : '#33447f'}"></i>`).join('')}</div>`; };
const cta = () => `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(210, 40, 36)}
<div class="abs" style="top:250px;left:60px;width:900px;font-size:96px;line-height:1.05;font-weight:900;white-space:nowrap">You can learn<br>this with Ruth.</div>
<div class="abs g" style="top:490px;left:60px;width:640px;font-size:48px;line-height:1.15;font-weight:800">${T.course}</div>
<div class="abs" style="top:650px;left:60px;background:#25D366;color:#fff;font-size:46px;font-weight:800;padding:24px 70px;border-radius:70px">WhatsApp</div>
<div class="abs" style="top:830px;left:60px;font-size:27px;line-height:1.65;width:520px;white-space:nowrap">TikTok @timshidigitals<br>Instagram ${HANDLE}<br>Facebook Ruth Jackson<br>coachruthjackson.com</div>${ruth(520, 560, 790, 450)}${swipe(70)}`;
const icon = k => ({
  follow: '<svg width="56" height="56" viewBox="0 0 56 56"><circle cx="24" cy="19" r="9" fill="#050a20"/><path d="M6 46c0-10 8-16 18-16s18 6 18 16z" fill="#050a20"/><path d="M45 14v14M38 21h14" stroke="#050a20" stroke-width="5" stroke-linecap="round"/></svg>',
  save: '<svg width="56" height="56" viewBox="0 0 56 56"><path d="M14 6h28v44L28 38 14 50z" fill="#050a20"/></svg>',
  share: '<svg width="56" height="56" viewBox="0 0 56 56"><path d="M50 7L5 24l16 7 5 18 8-12 12 8z" fill="#050a20"/></svg>' })[k];
const cursor = (x, y, s = 1) => `<svg class="abs" style="left:${x}px;top:${y}px;filter:drop-shadow(0 6px 8px rgba(0,0,0,.5))" width="${54 * s}" height="${70 * s}" viewBox="0 0 30 40"><path d="M2 2 L2 31 L10 24 L16 37 L22 34 L16 22 L27 22 Z" fill="#fff" stroke="#050a20" stroke-width="2.5" stroke-linejoin="round"/></svg>`;
const ripple = (cx, cy) => [60, 100, 140].map((d, i) => `<div class="abs" style="left:${cx - d / 2}px;top:${cy - d / 2}px;width:${d}px;height:${d}px;border-radius:50%;border:${4 - i}px solid rgba(255,211,77,${.75 - i * .22})"></div>`).join('');
const num = n => `<div style="width:92px;height:92px;border-radius:50%;background:#ffc21a;color:#050a20;display:grid;place-items:center;font-size:56px;font-weight:900;flex:0 0 auto;box-shadow:0 12px 28px -10px rgba(255,194,26,.8)">${n}</div>`;
const card = (top, inner) => `<div class="abs" style="left:60px;right:60px;top:${top}px;height:270px;border-radius:30px;background:linear-gradient(160deg,#1c3270,#0d1840);border:2px solid rgba(255,194,26,.6);box-shadow:0 30px 60px -30px rgba(0,0,0,.7);display:flex;align-items:center;gap:26px;padding:0 34px;overflow:visible">${inner}</div>`;
const then = top => `<div class="abs" style="left:50%;margin-left:-70px;top:${top}px;width:140px;height:44px;border-radius:30px;background:#ffc21a;color:#050a20;font-size:24px;font-weight:900;letter-spacing:.18em;display:grid;place-items:center;z-index:3;box-shadow:0 8px 20px -6px rgba(0,0,0,.6)">THEN</div>`;
const outline = (d, w = 4) => `<svg width="64" height="64" viewBox="0 0 56 56"><path d="${d}" stroke="#c2cdec" stroke-width="${w}" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
const friend = (bg, l) => `<div style="width:66px;height:66px;border-radius:50%;background:${bg};border:4px solid #0d1840;margin-left:${l}px;display:grid;place-items:center"><svg width="38" height="38" viewBox="0 0 48 48"><circle cx="24" cy="17" r="9" fill="#050a20"/><path d="M6 44c0-10 8-16 18-16s18 6 18 16z" fill="#050a20"/></svg></div>`;
const follow = () => `<style>${base}</style><div class="handle">${HANDLE}</div>${moon(170, 40, 40)}
<div class="abs" style="top:130px;left:60px;font-size:100px;line-height:1;font-weight:900">Enjoyed this?</div>
<div class="abs g" style="top:252px;left:60px;font-size:46px;font-weight:800">Do these 3 things:</div>
${card(340, `${num(1)}<div style="width:128px;height:128px;border-radius:50%;border:4px solid #ffc21a;overflow:hidden;background:radial-gradient(circle at 40% 35%,#ffd34d,#ffc21a);flex:0 0 auto"><img src="${PIC}" style="width:160px;margin:6px 0 0 -16px"></div><div style="flex:1"><div style="font-size:68px;font-weight:900;line-height:1">Follow</div><div class="g" style="font-size:30px;font-weight:700;margin-top:8px;white-space:nowrap">for more free tips</div></div><div style="position:relative;width:250px;height:100%;flex:0 0 auto"><div class="abs" style="left:10px;top:92px;width:220px;height:84px;border-radius:44px;background:linear-gradient(135deg,#ffd34d,#ffc21a 60%,#e0a010);color:#050a20;font-size:40px;font-weight:900;display:grid;place-items:center;box-shadow:0 14px 30px -10px rgba(255,194,26,.8)">+ Follow</div>${cursor(214, 152)}</div>`)}
${card(658, `${num(2)}<div style="flex:1"><div style="font-size:64px;font-weight:900;line-height:1;white-space:nowrap">Save for later</div><div class="g" style="font-size:30px;font-weight:700;margin-top:8px;white-space:nowrap">so you can come back to it</div></div><div style="position:relative;width:330px;height:100%;flex:0 0 auto;display:flex;align-items:center;gap:20px">${outline('M28 48C8 34 6 18 18 14c6-2 10 2 10 2s4-4 10-2c12 4 10 20-10 34z')}${outline('M8 26a20 17 0 1 1 9 14l-9 4z')}${outline('M50 7L5 24l16 7 5 18 8-12 12 8z')}<div style="position:relative;width:84px;height:84px;margin-left:-4px"><div class="abs" style="left:-14px;top:-14px;width:112px;height:112px;border-radius:50%;background:rgba(255,194,26,.25);box-shadow:0 0 40px 10px rgba(255,194,26,.5)"></div><div class="abs" style="left:0;top:0;width:84px;height:84px;border-radius:50%;background:linear-gradient(135deg,#ffd34d,#ffc21a 60%,#e0a010);display:grid;place-items:center"><svg width="46" height="46" viewBox="0 0 56 56"><path d="M14 6h28v44L28 38 14 50z" fill="#050a20"/></svg></div></div>${cursor(300, 128, .9)}</div>`)}
${card(976, `${num(3)}<div style="flex:1"><div style="font-size:48px;font-weight:900;line-height:1.02;white-space:nowrap">Share with a friend</div><div class="g" style="font-size:30px;font-weight:700;margin-top:8px;white-space:nowrap">who needs to hear this</div></div><div style="display:flex;align-items:center;flex:0 0 auto;position:relative">${friend('#ffd34d', 0)}${friend('#ffffff', -22)}${friend('#5b7fe0', -22)}<div style="margin-left:14px;width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#ffd34d,#ffc21a 60%,#e0a010);display:grid;place-items:center;box-shadow:0 14px 30px -10px rgba(255,194,26,.8)"><svg width="52" height="52" viewBox="0 0 56 56"><path d="M50 7L5 24l16 7 5 18 8-12 12 8z" fill="#050a20"/></svg></div></div>`)}${swipeSm()}`;
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
  const pg = await b.newPage({ viewport: { width: 1080, height: 1350 } });
  const out = path.join(R, 'social/carousels-v2', slug); fs.mkdirSync(out, { recursive: true });
  const slides = process.env.COVER_ONLY ? [cover()] : [cover(), tip(0), tip(1), tip(2), tip(3), cta(), follow()];
  for (let i = 0; i < slides.length; i++) {
    const f = path.join(K, '.tmp.html'); fs.writeFileSync(f, '<!doctype html><meta charset=utf-8><body>' + slides[i]);
    await pg.goto('file://' + f); await pg.waitForTimeout(250);
    await pg.screenshot({ path: path.join(out, String(i + 1).padStart(2, '0') + '.jpg'), type: 'jpeg', quality: 92 });
  }
  fs.unlinkSync(path.join(K, '.tmp.html')); await b.close();
})();
