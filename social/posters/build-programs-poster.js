const { chromium } = require('/opt/node-tools/node_modules/playwright');
const path = require('path');
const R = path.join(__dirname, '..', '..'), K = path.join(R, 'social/carousel-kit/assets');
const L = n => 'file://' + R + '/assets/img/' + n;
const MOON = 'file://' + K + '/moon-gold.png';
const PIC = 'file://' + K + '/ruth-cutout.png', EY = 'file://' + K + '/ey-logo.png', LOGO = L('timshi-digitals-logo.jpg');
const courses = ['Artificial Intelligence Academy', 'Digital Marketing', 'Graphic Design', 'Cybersecurity', 'Training of Trainers', 'Online Gender Violence'];
const tick = `<span class="tick"><svg width="30" height="30" viewBox="0 0 30 30"><path d="M7 15.5l5.5 5.5L23 10" stroke="#050a20" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`;
const html = `<style>*{box-sizing:border-box;margin:0}body{width:1080px;height:1350px;position:relative;overflow:hidden;font-family:'Helvetica Neue',Arial,'Liberation Sans',sans-serif;color:#fff;background:radial-gradient(circle at 80% 0,#1c3270 0,#0a1330 45%,#050a20 100%)}
.serif{font-family:Georgia,'Liberation Serif','DejaVu Serif',serif}.g{color:#ffd34d}.abs{position:absolute}
.frame{position:absolute;inset:26px;border:2px solid #ffc21a;border-radius:6px}.frame2{position:absolute;inset:40px;border:1px solid rgba(255,194,26,.35)}
.tick{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#ffd34d,#e0a010);display:inline-grid;place-items:center;flex:0 0 auto;box-shadow:0 6px 16px -6px rgba(255,194,26,.7)}
.row{display:flex;align-items:center;gap:20px;font-size:41px;font-weight:700;white-space:nowrap}
.card{position:absolute;left:70px;right:70px;top:410px;height:470px;border-radius:26px;background:linear-gradient(160deg,rgba(255,255,255,.10),rgba(255,255,255,.03));border:2px solid rgba(255,194,26,.55);box-shadow:0 30px 60px -30px rgba(0,0,0,.6)}
.cl{display:grid;grid-template-columns:1.35fr 1fr;gap:16px 20px;font-size:28px;font-weight:600;white-space:nowrap}.cl div{display:flex;align-items:center;gap:14px}.cl i{width:14px;height:14px;border-radius:50%;background:#ffc21a;flex:0 0 auto}
.logos{position:absolute;left:30px;right:30px;bottom:24px;height:84px;background:#fff;border-radius:16px;display:flex;align-items:center;justify-content:space-around;padding:0 20px}.logos img{max-height:52px}
.btn{position:absolute;left:50%;margin-left:-270px;top:1035px;width:540px;height:84px;border-radius:50px;background:linear-gradient(135deg,#ffd34d,#ffc21a 55%,#e0a010);color:#050a20;font-size:40px;font-weight:900;letter-spacing:.08em;display:grid;place-items:center;box-shadow:0 18px 36px -14px rgba(255,194,26,.7)}
</style>
<div class="frame"></div><div class="frame2"></div>
<div class="abs" style="top:58px;right:84px;width:108px;height:108px;border-radius:50%;box-shadow:0 0 56px 26px rgba(255,194,26,.35)"></div><img class="abs" src="${MOON}" style="top:34px;right:56px;width:156px;height:156px">
<div class="abs" style="top:62px;left:80px;display:flex;align-items:center;gap:24px"><img src="${LOGO}" style="width:104px;height:104px;border-radius:22px"><div class="serif" style="font-size:64px;font-weight:700;letter-spacing:.01em">Timshi <span class="g">Digitals</span></div></div>
<div class="abs" style="top:196px;left:80px;right:80px;height:2px;background:linear-gradient(90deg,#ffc21a,rgba(255,194,26,0))"></div>
<div class="abs" style="top:226px;left:84px;right:60px;display:flex;flex-direction:column;gap:26px"><div class="row">${tick}Customer Service Training for Corporates</div><div class="row">${tick}Digital Ads</div></div>
<div class="card">
 <div class="abs" style="top:26px;left:34px;right:34px;display:flex;align-items:center;gap:22px">${tick}<div class="serif g" style="font-size:60px;font-weight:700">The WiDB Program</div></div>
 <div class="abs" style="top:106px;left:34px;font-size:30px;font-weight:600;letter-spacing:.01em">An initiative of Microsoft, ILO, ITC &amp; EY</div>
 <div class="abs cl" style="top:162px;left:40px;right:30px">${courses.map(c => `<div><i></i>${c}</div>`).join('')}</div>
 <div class="logos"><img src="${L('partner-widb.jpg')}" style="max-height:64px"><img src="${L('partner-microsoft.jpg')}"><img src="${L('partner-ilo.jpg')}"><img src="${L('partner-itc.jpg')}"><img src="${EY}" style="max-height:60px"></div>
</div>
<div class="abs" style="top:902px;left:80px;right:80px;display:flex;align-items:center;gap:36px">
 <div style="width:190px;height:190px;border-radius:50%;border:5px solid #ffc21a;overflow:hidden;background:radial-gradient(circle at 40% 35%,#ffd34d,#ffc21a);flex:0 0 auto"><img src="${PIC}" style="width:250px;margin:8px 0 0 -32px"></div>
 <div><div style="font-size:30px;letter-spacing:.24em;font-weight:800" class="g">LEAD TRAINER</div><div class="serif" style="font-size:72px;font-weight:700;line-height:1.05;margin-top:6px">Ruth Jackson</div></div></div>
<div class="abs serif" style="top:1108px;left:0;right:0;text-align:center;font-size:52px;font-weight:700">coachruthjackson.com</div>
<div class="abs" style="top:1170px;left:0;right:0;text-align:center;font-size:36px;font-weight:700">+254 729 384374</div>
<div class="abs" style="top:1218px;left:50%;margin-left:-130px;width:260px;height:48px;border-radius:30px;background:linear-gradient(135deg,#ffd34d,#ffc21a 55%,#e0a010);color:#050a20;font-size:26px;font-weight:900;letter-spacing:.12em;display:grid;place-items:center">LINK IN BIO</div>
<div class="abs" style="top:1280px;left:0;right:0;text-align:center;font-size:19px;color:#c2cdec;letter-spacing:.01em">TikTok @timshidigitals  |  Instagram @timshidigitalswith_ruthjackson  |  Facebook Ruth Jackson</div>`;
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
  const pg = await b.newPage({ viewport: { width: 1080, height: 1350 } });
  const fs = require('fs'); const f = path.join(__dirname, '.tmp.html'); fs.writeFileSync(f, '<!doctype html><meta charset=utf-8><body>' + html);
  await pg.goto('file://' + f); await pg.waitForTimeout(300);
  await pg.screenshot({ path: path.join(__dirname, 'programs-poster.jpg'), type: 'jpeg', quality: 93 });
  fs.unlinkSync(f); await b.close();
})();
