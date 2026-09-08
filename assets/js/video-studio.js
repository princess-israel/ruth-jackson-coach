/* =========================================================
   Ruth Jackson — AI Video Studio
   Admin-only tool that drives api/video/*.php (Replicate).
   ========================================================= */
(function () {
  "use strict";

  var SESSION = "rj_admin_session";           // shared with admin.html
  var HISTORY = "rj_video_history";           // local list of generated videos
  var token = "";
  try { token = localStorage.getItem(SESSION) || ""; } catch (e) {}

  // Model catalogue — mirrors the server allow-list in api/video/_video.php.
  var MODELS = {
    seedance: { label: "Seedance 1 Pro — crisp & cinematic", aspect: ["16:9", "9:16", "1:1"], durations: [5, 10], resolutions: ["480p", "720p", "1080p"], defRes: "1080p", hint: "Best all-rounder. Sharp detail, strong camera moves." },
    hailuo:   { label: "Hailuo 02 — lifelike motion",         aspect: [],                      durations: [6, 10], resolutions: ["768p", "1080p"],          defRes: "1080p", hint: "Natural, realistic movement. Fixed 16:9 framing." },
    kling:    { label: "Kling v2.1 Master — photoreal",       aspect: ["16:9", "9:16", "1:1"], durations: [5, 10], resolutions: [],                        defRes: "",      hint: "Very photorealistic people and scenes." },
    veo:      { label: "Google Veo 3 Fast — premium (sound)", aspect: ["16:9", "9:16"],        durations: [],      resolutions: ["720p", "1080p"],         defRes: "1080p", hint: "Top quality and adds native audio. Costs more per video." }
  };
  var MODEL_ORDER = ["seedance", "hailuo", "kling", "veo"];

  var PURPOSES = [
    { key: "lesson",  label: "📚 Course lesson",  aspect: "16:9", scaffold: "A friendly, professional female trainer speaking to camera in a bright modern studio, explaining a concept clearly, warm and encouraging tone, soft daylight." },
    { key: "reel",    label: "📱 Social reel",    aspect: "9:16", scaffold: "Energetic vertical montage for social media, a confident woman working on a laptop and phone, dynamic quick movement, bright and upbeat, modern." },
    { key: "advert",  label: "📣 Advert",         aspect: "16:9", scaffold: "Cinematic advert, confident professionals in a sleek modern office, smiling and collaborating, warm lighting, aspirational and polished." },
    { key: "intro",   label: "🎬 Brand intro",    aspect: "16:9", scaffold: "Elegant brand intro, smooth camera move across a modern workspace with soft golden light, premium and inspiring mood." }
  ];

  var STYLES = [
    "Cinematic", "Corporate clean", "Vibrant & bold", "Documentary", "3D animated", "Warm & natural"
  ];

  var $ = function (id) { return document.getElementById(id); };
  var state = { model: "seedance", purpose: null, styles: {}, pollTimer: null };

  /* ---------- Login gate ---------- */
  function showLogin() {
    var ov = $("vs-login");
    ov.style.display = "grid";
    $("vl-form").onsubmit = async function (e) {
      e.preventDefault();
      var note = $("vl-note");
      note.style.color = "var(--muted)"; note.textContent = "Checking…";
      try {
        var r = await fetch("/api/admin-login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: $("vl-email").value, password: $("vl-pass").value })
        });
        var d = await r.json().catch(function () { return {}; });
        if (!r.ok || !d.ok) { note.style.color = "var(--err)"; note.textContent = d.error || "Incorrect password."; return; }
        try { localStorage.setItem(SESSION, d.token); } catch (e) {}
        location.reload();
      } catch (err) {
        note.style.color = "var(--err)";
        note.textContent = "Couldn't reach the server. The studio needs the live site (coachruthjackson.com).";
      }
    };
  }

  /* ---------- Renderers ---------- */
  function renderPurposes() {
    var wrap = $("purpose-chips"); wrap.innerHTML = "";
    PURPOSES.forEach(function (p) {
      var b = document.createElement("button");
      b.type = "button"; b.className = "chip"; b.textContent = p.label;
      b.onclick = function () {
        state.purpose = p.key;
        Array.prototype.forEach.call(wrap.children, function (c) { c.classList.remove("on"); });
        b.classList.add("on");
        // Prefill the prompt scaffold if the box is empty, and set a matching shape.
        var pr = $("vs-prompt");
        if (!pr.value.trim()) pr.value = p.scaffold;
        var asp = $("vs-aspect");
        if (asp && !asp.disabled) {
          for (var i = 0; i < asp.options.length; i++) { if (asp.options[i].value === p.aspect) { asp.value = p.aspect; break; } }
        }
      };
      wrap.appendChild(b);
    });
  }

  function renderStyles() {
    var wrap = $("style-chips"); wrap.innerHTML = "";
    STYLES.forEach(function (s) {
      var b = document.createElement("button");
      b.type = "button"; b.className = "chip"; b.textContent = s;
      b.onclick = function () {
        state.styles[s] = !state.styles[s];
        b.classList.toggle("on", state.styles[s]);
      };
      wrap.appendChild(b);
    });
  }

  function fillSelect(sel, values, current) {
    sel.innerHTML = "";
    if (!values || !values.length) { sel.disabled = true; var o = document.createElement("option"); o.textContent = "—"; sel.appendChild(o); return; }
    sel.disabled = false;
    values.forEach(function (v) {
      var o = document.createElement("option");
      o.value = String(v);
      o.textContent = (sel.id === "vs-duration") ? (v + "s") : String(v);
      sel.appendChild(o);
    });
    if (current && values.map(String).indexOf(String(current)) !== -1) sel.value = String(current);
  }

  function renderModelControls() {
    var m = MODELS[state.model];
    $("vs-model-hint").textContent = m.hint;
    fillSelect($("vs-aspect"), m.aspect, m.aspect[0]);
    fillSelect($("vs-duration"), m.durations, m.durations[0]);
    fillSelect($("vs-res"), m.resolutions, m.defRes || (m.resolutions[0] || ""));
  }

  function renderModelSelect() {
    var sel = $("vs-model"); sel.innerHTML = "";
    MODEL_ORDER.forEach(function (k) {
      var o = document.createElement("option");
      o.value = k; o.textContent = MODELS[k].label; sel.appendChild(o);
    });
    sel.value = state.model;
    sel.onchange = function () { state.model = sel.value; renderModelControls(); };
    renderModelControls();
  }

  /* ---------- Prompt assembly ---------- */
  function buildPrompt() {
    var base = $("vs-prompt").value.trim();
    var chosen = Object.keys(state.styles).filter(function (k) { return state.styles[k]; });
    if (chosen.length) {
      var map = {
        "Cinematic": "cinematic film look, shallow depth of field, dramatic lighting",
        "Corporate clean": "clean corporate style, bright even lighting, minimal modern setting",
        "Vibrant & bold": "vibrant saturated colours, bold energetic feel",
        "Documentary": "natural documentary style, handheld realism",
        "3D animated": "polished 3D animated style, smooth rendering",
        "Warm & natural": "warm natural light, soft golden tones, inviting mood"
      };
      base += ", " + chosen.map(function (c) { return map[c] || c; }).join(", ");
    }
    return base + ", high quality, 4k, professional";
  }

  /* ---------- Preview stage ---------- */
  function stageShape() {
    var asp = $("vs-aspect");
    var v = (asp && !asp.disabled) ? asp.value : "16:9";
    var stage = $("vs-stage");
    stage.classList.remove("tall", "square");
    if (v === "9:16") stage.classList.add("tall");
    else if (v === "1:1") stage.classList.add("square");
  }

  function stageLoading(msg) {
    stageShape();
    $("vs-stage").innerHTML = '<div><div class="spinner"></div><div class="vs-status">' + msg + '</div></div>';
    $("vs-actions").style.display = "none";
    $("vs-err").style.display = "none";
  }

  function stageError(msg) {
    $("vs-stage").innerHTML = '<div class="muted">Something went wrong.</div>';
    var e = $("vs-err"); e.style.display = "block"; e.textContent = msg;
    $("vs-actions").style.display = "none";
  }

  function stageVideo(url, prompt) {
    stageShape();
    $("vs-stage").innerHTML = '<video src="' + url + '" controls autoplay muted loop playsinline></video>';
    var acts = $("vs-actions"); acts.style.display = "flex";
    $("vs-download").href = url;
    $("vs-download").setAttribute("download", "ruth-video-" + Date.now() + ".mp4");
    $("vs-copy").onclick = function () {
      navigator.clipboard && navigator.clipboard.writeText(url);
      $("vs-copy").textContent = "✓ Copied";
      setTimeout(function () { $("vs-copy").textContent = "🔗 Copy link"; }, 1600);
    };
    $("vs-again").onclick = function () { $("vs-prompt").focus(); $("vs-prompt").scrollIntoView({ behavior: "smooth" }); };
    saveHistory({ url: url, prompt: prompt, ts: Date.now() });
  }

  /* ---------- History (localStorage) ---------- */
  function loadHistory() { try { return JSON.parse(localStorage.getItem(HISTORY) || "[]"); } catch (e) { return []; } }
  function saveHistory(item) {
    var list = loadHistory();
    list.unshift(item);
    list = list.slice(0, 24);
    try { localStorage.setItem(HISTORY, JSON.stringify(list)); } catch (e) {}
    renderHistory();
  }
  function renderHistory() {
    var list = loadHistory();
    var grid = $("vs-history");
    $("vs-history-empty").style.display = list.length ? "none" : "block";
    grid.innerHTML = "";
    list.forEach(function (it) {
      var card = document.createElement("div"); card.className = "hist-card";
      card.innerHTML =
        '<video src="' + it.url + '" muted loop playsinline preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>' +
        '<div class="hist-body"><p>' + (it.prompt ? it.prompt.replace(/</g, "&lt;").slice(0, 90) : "Video") + '</p>' +
        '<div class="hb-actions">' +
        '<a class="btn btn-gold btn-sm" href="' + it.url + '" download>⬇</a>' +
        '<button class="btn btn-ghost btn-sm" data-open>▶ Open</button></div></div>';
      card.querySelector("[data-open]").onclick = function () { window.open(it.url, "_blank"); };
      grid.appendChild(card);
    });
  }

  /* ---------- Generate + poll ---------- */
  async function generate() {
    var prompt = $("vs-prompt").value.trim();
    if (!prompt) { $("vs-prompt").focus(); stageError("Please describe the video you want first."); return; }

    var btn = $("vs-generate");
    btn.disabled = true; btn.textContent = "Starting…";
    stageLoading("Sending your idea to the studio…");

    var payload = {
      token: token,
      model: state.model,
      prompt: buildPrompt(),
      negative_prompt: $("vs-negative").value.trim()
    };
    var asp = $("vs-aspect"), dur = $("vs-duration"), res = $("vs-res");
    if (asp && !asp.disabled) payload.aspect_ratio = asp.value;
    if (dur && !dur.disabled) payload.duration = parseInt(dur.value, 10);
    if (res && !res.disabled) payload.resolution = res.value;

    try {
      var r = await fetch("/api/video/generate.php", {
        method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload)
      });
      var d = await r.json().catch(function () { return {}; });
      if (r.status === 401) { try { localStorage.removeItem(SESSION); } catch (e) {} stageError("Session expired. Reloading…"); setTimeout(function () { location.reload(); }, 1400); return; }
      if (!r.ok || !d.ok) { stageError(d.error || "Could not start the video."); resetBtn(); return; }
      stageLoading("Rendering your video… this takes 1 to 4 minutes.");
      poll(d.id, prompt);
    } catch (err) {
      stageError("Couldn't reach the server. The studio needs the live site (coachruthjackson.com).");
      resetBtn();
    }
  }

  function resetBtn() { var b = $("vs-generate"); b.disabled = false; b.textContent = "🎬 Generate video"; }

  function poll(id, prompt) {
    var tries = 0;
    clearInterval(state.pollTimer);
    state.pollTimer = setInterval(async function () {
      tries++;
      if (tries > 120) { clearInterval(state.pollTimer); stageError("This is taking longer than expected. Check the Recent videos list in a moment, or try again."); resetBtn(); return; }
      try {
        var r = await fetch("/api/video/status.php?id=" + encodeURIComponent(id) + "&token=" + encodeURIComponent(token));
        var d = await r.json().catch(function () { return {}; });
        if (d.status === "succeeded" && d.output) {
          clearInterval(state.pollTimer);
          stageVideo(d.output, prompt);
          resetBtn();
        } else if (d.status === "failed" || d.status === "canceled") {
          clearInterval(state.pollTimer);
          stageError(d.error || "The video generation failed. Try a simpler description or a different engine.");
          resetBtn();
        }
        // otherwise keep waiting (starting / processing)
      } catch (e) { /* transient — keep polling */ }
    }, 3000);
  }

  /* ---------- Init ---------- */
  function init() {
    $("vs-root").style.display = "block";

    // top bar
    $("vs-theme").onclick = function () {
      var dark = document.documentElement.getAttribute("data-theme") === "dark";
      if (dark) { document.documentElement.removeAttribute("data-theme"); try { localStorage.setItem("rj_theme", "light"); } catch (e) {} }
      else { document.documentElement.setAttribute("data-theme", "dark"); try { localStorage.setItem("rj_theme", "dark"); } catch (e) {} }
    };
    $("vs-logout").onclick = function () { try { localStorage.removeItem(SESSION); } catch (e) {} location.href = "index.html"; };
    $("vs-adv-toggle").onclick = function () {
      var a = $("vs-adv"); var open = a.style.display !== "none";
      a.style.display = open ? "none" : "block";
      $("vs-adv-toggle").textContent = (open ? "▸" : "▾") + " Advanced: things to avoid (optional)";
    };

    renderPurposes();
    renderStyles();
    renderModelSelect();
    $("vs-aspect").addEventListener("change", stageShape);
    $("vs-generate").onclick = generate;
    renderHistory();
    stageShape();
  }

  if (!token) { showLogin(); }
  else { init(); }
})();
