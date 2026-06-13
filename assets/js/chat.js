/* ============================================================
   chat.js — "Ruthie" AI Assistant widget (conversion helper)
   Rule-based, self-contained. Self-injects on every page.
   ============================================================ */
(function () {
  const WHATSAPP = "https://wa.me/254714458530";
  const PHONE = "+254 714 458530";
  const BASE = location.pathname.includes("/blog/") ? "../" : "";

  // --- inject markup ---
  const fab = document.createElement("button");
  fab.className = "chat-fab";
  fab.setAttribute("aria-label", "Chat with Ruth's AI assistant");
  fab.innerHTML = `<span class="badge">1</span>
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>`;

  const panel = document.createElement("div");
  panel.className = "chat-panel";
  panel.innerHTML = `
    <div class="chat-head">
      <div class="av"><img src="${BASE}assets/img/ruth-photo.jpg" alt="Ruth"></div>
      <div>
        <h4>Ruthie · AI Assistant</h4>
        <div class="status">Online — replies instantly</div>
      </div>
      <button class="chat-close" aria-label="Close">×</button>
    </div>
    <div class="chat-body" id="rj-chat-body"></div>
    <div class="chat-quick" id="rj-chat-quick"></div>
    <form class="chat-input" id="rj-chat-form">
      <input type="text" id="rj-chat-input" placeholder="Ask about courses, price, certificates…" autocomplete="off">
      <button type="submit" aria-label="Send">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </button>
    </form>`;

  document.body.appendChild(fab);
  document.body.appendChild(panel);

  const body = panel.querySelector("#rj-chat-body");
  const quick = panel.querySelector("#rj-chat-quick");
  const form = panel.querySelector("#rj-chat-form");
  const input = panel.querySelector("#rj-chat-input");
  let greeted = false;

  const QUICK = [
    "What courses do you offer?",
    "How much do they cost?",
    "Do I get a certificate?",
    "Tell me about customer service training",
    "How do I enroll?"
  ];
  function renderQuick() {
    quick.innerHTML = QUICK.map(q => `<button type="button">${q}</button>`).join("");
    quick.querySelectorAll("button").forEach(b =>
      b.addEventListener("click", () => { addUser(b.textContent); respond(b.textContent); }));
  }

  function open() {
    panel.classList.add("open");
    fab.querySelector(".badge")?.remove();
    if (!greeted) {
      greeted = true;
      botSeq([
        "Hi there 👋 I'm Ruthie, Ruth Jackson's AI assistant.",
        "Ruth is a Microsoft-certified AI coach and WIDB Lead Trainer who helps women entrepreneurs and youth grow with practical digital skills.",
        "What can I help you with today?"
      ]);
      renderQuick();
    }
    setTimeout(() => input.focus(), 300);
  }
  function close() { panel.classList.remove("open"); }

  fab.addEventListener("click", () => panel.classList.contains("open") ? close() : open());
  panel.querySelector(".chat-close").addEventListener("click", close);

  function scroll() { body.scrollTop = body.scrollHeight; }
  function addUser(t) {
    const d = document.createElement("div"); d.className = "msg user"; d.textContent = t;
    body.appendChild(d); scroll();
  }
  function addBot(html) {
    const d = document.createElement("div"); d.className = "msg bot"; d.innerHTML = html;
    body.appendChild(d); scroll();
  }
  function typing() {
    const d = document.createElement("div"); d.className = "msg bot typing";
    d.innerHTML = "<span></span><span></span><span></span>";
    body.appendChild(d); scroll(); return d;
  }
  function botSeq(arr, i = 0) {
    if (i >= arr.length) return;
    const t = typing();
    setTimeout(() => { t.remove(); addBot(arr[i]); botSeq(arr, i + 1); }, 500 + Math.min(arr[i].length * 12, 900));
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const v = input.value.trim(); if (!v) return;
    addUser(v); input.value = ""; respond(v);
  });

  // --- the "brain": keyword intent matching ---
  function respond(text) {
    const q = text.toLowerCase();
    let reply;

    const has = (...w) => w.some(x => q.includes(x));

    if (has("hi", "hello", "hey", "hallo")) {
      reply = "Hello! 😊 Happy to help. Are you interested in a specific skill — like AI, digital marketing, SEO, or our customer service training?";
    } else if (has("price", "cost", "how much", "fee", "pay", "$")) {
      reply = `Each self-paced certificate course is just <b>$79</b> — that includes lifetime access and a certificate on completion. The <b>Customer Service Excellence</b> training is custom-priced because Ruth tailors it to your team. Want me to point you to a course? <a href="${BASE}programs.html">See all programs →</a>`;
    } else if (has("certificate", "certified", "accredit")) {
      reply = `Yes! Every $79 course gives you a <b>certificate of completion</b> you can share on LinkedIn or with employers. Ruth herself is Microsoft-certified and a certified WIDB Lead Trainer. 🎓`;
    } else if (has("customer service", "banking", "service training", "team training")) {
      reply = `Great choice 💬 Customer Service Excellence is Ruth's signature, personally delivered. She spent years in <b>banking customer service</b>, so this is her strongest craft. It's custom-built for you or your team — no fixed price. <a href="${BASE}customer-service.html">Request custom training →</a> or message her on <a href="${WHATSAPP}" target="_blank">WhatsApp</a>.`;
    } else if (has("course", "program", "learn", "offer", "what do you", "topics")) {
      reply = `Ruth offers 6 self-paced certificate courses ($79 each): <br>🤖 AI for Women Entrepreneurs<br>📣 Digital Marketing & Social Media<br>🌐 Build Your Business Website<br>🔎 SEO & Online Visibility<br>📊 Data Analysis for Growth<br>🛒 E-Commerce & Selling Online<br><a href="${BASE}programs.html">Explore all →</a>`;
    } else if (has("enroll", "sign up", "buy", "register", "start", "how do i join")) {
      reply = `It's simple: pick a course → click <b>Enroll ($79)</b> → create your account → you'll land in your <b>customer dashboard</b>, where Ruth sends your private access link and instructions, and you can message her directly. <a href="${BASE}programs.html">Choose a course →</a>`;
    } else if (has("dashboard", "access", "after i buy", "instructions")) {
      reply = `After enrolling you get a personal <b>customer dashboard</b>. That's where Ruth shares your course access link, getting-started instructions, and where you two chat directly. <a href="${BASE}login.html">Go to dashboard →</a>`;
    } else if (has("ai", "artificial intelligence", "chatgpt")) {
      reply = `Ruth's flagship is <b>Artificial Intelligence for Women Entrepreneurs</b> — practical AI tools for marketing, admin and growth, no tech background needed. $79, certificate included. <a href="${BASE}program.html?id=ai-women-entrepreneurs">View course →</a>`;
    } else if (has("ruth", "who is", "about", "experience", "qualif")) {
      reply = `Ruth Wanjohi — known as <b>Ruth Jackson</b> — is an AI coach, Microsoft-certified, and a certified <b>WIDB Lead Trainer</b> (Women in Digital Business / ITC–ILO). She empowers women in business and youth, and previously built deep expertise in banking customer service. <a href="${BASE}about.html">Read her story →</a>`;
    } else if (has("contact", "phone", "call", "whatsapp", "email", "reach")) {
      reply = `You can reach Ruth directly:<br>📞 ${PHONE}<br>💬 <a href="${WHATSAPP}" target="_blank">Chat on WhatsApp</a><br>Or just enroll and message her inside your dashboard.`;
    } else if (has("self", "pace", "time", "long", "duration")) {
      reply = `All courses are <b>100% self-paced</b> — start anytime, learn on your schedule, keep lifetime access. Most take 6–8 hours total. 🎯`;
    } else if (has("thank", "thanks", "asante", "great", "ok", "cool")) {
      reply = `You're welcome! 🌟 Ready when you are — <a href="${BASE}programs.html">browse the courses</a> or ask me anything else.`;
    } else if (has("women", "youth", "empower")) {
      reply = `Absolutely — Ruth is passionate about empowering <b>women in business and youth</b>. Her programs are designed to be beginner-friendly and immediately practical. 💪 <a href="${BASE}programs.html">See how →</a>`;
    } else {
      reply = `Good question! I can help with our <b>$79 certificate courses</b>, the custom <b>customer service training</b>, enrollment, or contacting Ruth. You can also reach her on <a href="${WHATSAPP}" target="_blank">WhatsApp</a> (${PHONE}). What would you like to know?`;
    }

    const t = typing();
    setTimeout(() => { t.remove(); addBot(reply); }, 650);
  }

  // Allow other pages to open chat with a preset (e.g. "Talk to Ruthie" buttons)
  window.openRuthie = function (preset) {
    open();
    if (preset) setTimeout(() => { addUser(preset); respond(preset); }, 400);
  };
  document.querySelectorAll("[data-open-chat]").forEach(el =>
    el.addEventListener("click", (e) => { e.preventDefault(); window.openRuthie(el.dataset.openChat || ""); }));
})();
