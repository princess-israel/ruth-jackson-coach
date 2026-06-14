/* ============================================================
   main.js, navigation, GSAP scroll/parallax/3D, renderers
   ============================================================ */
(function () {
  "use strict";

  /* ---------- Navbar ---------- */
  const nav = document.querySelector(".nav");
  if (nav) {
    const onScroll = () => nav.classList.toggle("scrolled", window.scrollY > 30);
    onScroll(); window.addEventListener("scroll", onScroll, { passive: true });
    const burger = nav.querySelector(".hamburger");
    burger && burger.addEventListener("click", () => nav.classList.toggle("menu-open"));
    nav.querySelectorAll(".nav-links a").forEach(a => a.addEventListener("click", () => nav.classList.remove("menu-open")));
  }

  /* Reflect logged-in state in nav (Dashboard link) */
  const acctSlot = document.querySelector("[data-account-cta]");
  if (acctSlot && window.Store) {
    const u = Store.currentUser();
    if (u) {
      const dest = u.role === "admin" ? "admin.html" : "dashboard.html";
      acctSlot.innerHTML = `<a class="btn btn-gold btn-sm" href="${dest}">${u.role === "admin" ? "Admin" : "My Dashboard"}</a>`;
    } else {
      acctSlot.innerHTML = `<a class="btn btn-ghost btn-sm" href="login.html">Sign in</a>`;
    }
  }

  const yr = document.querySelector("[data-year]"); if (yr) yr.textContent = new Date().getFullYear();

  /* ---------- GSAP animations ---------- */
  function initGSAP() {
    if (!window.gsap) return;
    gsap.registerPlugin(ScrollTrigger);

    // Generic reveal
    gsap.utils.toArray(".reveal").forEach((el) => {
      gsap.to(el, {
        opacity: 1, y: 0, duration: 0.9, ease: "power3.out",
        scrollTrigger: { trigger: el, start: "top 86%" }
      });
    });

    // Staggered groups
    gsap.utils.toArray("[data-stagger]").forEach((grp) => {
      const kids = grp.children;
      gsap.set(kids, { opacity: 0, y: 30 });
      gsap.to(kids, {
        opacity: 1, y: 0, duration: 0.7, ease: "power3.out", stagger: 0.1,
        scrollTrigger: { trigger: grp, start: "top 82%" }
      });
    });

    // Parallax layers (data-parallax = speed factor)
    gsap.utils.toArray("[data-parallax]").forEach((el) => {
      const speed = parseFloat(el.dataset.parallax) || 0.2;
      gsap.to(el, {
        yPercent: speed * 100, ease: "none",
        scrollTrigger: { trigger: el.closest("section") || el, start: "top bottom", end: "bottom top", scrub: true }
      });
    });

    // Hero headline rise
    const hero = document.querySelector(".hero");
    if (hero) {
      gsap.from(hero.querySelectorAll("[data-hero]"), {
        opacity: 0, y: 36, duration: 1, ease: "power3.out", stagger: 0.12, delay: 0.15
      });
    }

    // Count-up stats
    gsap.utils.toArray("[data-count]").forEach((el) => {
      const target = parseFloat(el.dataset.count);
      const obj = { v: 0 };
      ScrollTrigger.create({
        trigger: el, start: "top 90%", once: true,
        onEnter: () => gsap.to(obj, {
          v: target, duration: 1.6, ease: "power2.out",
          onUpdate: () => { el.textContent = Math.round(obj.v).toLocaleString() + (el.dataset.suffix || ""); }
        })
      });
    });

    // Section pin/scale for any [data-zoom]
    gsap.utils.toArray("[data-zoom]").forEach((el) => {
      gsap.fromTo(el, { scale: 0.92 }, {
        scale: 1, ease: "none",
        scrollTrigger: { trigger: el, start: "top bottom", end: "top center", scrub: true }
      });
    });
  }

  /* ---------- Hero 3D tilt (pointer) ---------- */
  const tiltEl = document.querySelector("[data-tilt]");
  if (tiltEl) {
    const wrap = tiltEl.parentElement;
    wrap.addEventListener("mousemove", (e) => {
      const r = wrap.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width - 0.5;
      const py = (e.clientY - r.top) / r.height - 0.5;
      tiltEl.style.transform = `rotateY(${px * 12}deg) rotateX(${-py * 12}deg) translateZ(0)`;
    });
    wrap.addEventListener("mouseleave", () => { tiltEl.style.transform = "rotateY(0) rotateX(0)"; });
  }

  /* ---------- Program card renderer ---------- */
  window.programCardHTML = function (p, opts = {}) {
    const isFree = !p.price || Number(p.price) <= 0;
    const tags = Array.isArray(p.tags) ? p.tags : [];
    const meta = isFree
      ? `<span>👥 ${p.level || "All levels"}</span>`
      : `<span>⏱ ${p.hours || 0}h</span><span>📚 ${p.lessons || 0} lessons</span><span>📈 ${p.level || "Beginner"}</span>`;
    const price = isFree
      ? `<div class="price-tag">Custom<small>by enquiry</small></div>`
      : `<div class="price-tag">$${p.price}<small>certificate included</small></div>`;
    const cta = isFree
      ? `<a class="btn btn-ghost btn-block" href="customer-service.html">Talk to Ruth</a>`
      : `<a class="btn btn-gold btn-block" href="program.html?id=${p.id}">Enroll, $${p.price}</a>`;
    return `
      <article class="card program-card">
        <span class="card-glow"></span>
        <div class="program-top">
          <div class="program-ico">${p.icon || "📘"}</div>
          ${price}
        </div>
        <h3>${p.title || ""}</h3>
        <p class="desc">${p.short || ""}</p>
        <div class="tags">${tags.map(t => `<span class="tag">${t}</span>`).join("")}</div>
        <div class="program-meta">${meta}</div>
        ${cta}
      </article>`;
  };

  // Render into [data-programs] grids (+ optional [data-signature])
  function renderPrograms(animate) {
    const grid = document.querySelector("[data-programs]");
    if (grid && window.RJ_PROGRAMS) {
      const limit = parseInt(grid.dataset.limit) || RJ_PROGRAMS.length;
      grid.innerHTML = RJ_PROGRAMS.slice(0, limit).map(p => programCardHTML(p)).join("");
      if (animate && window.gsap) {
        gsap.from(grid.querySelectorAll(".program-card"),
          { opacity: 0, y: 22, duration: 0.5, ease: "power2.out", stagger: 0.08, clearProps: "all" });
      }
    }
    const sig = document.querySelector("[data-signature]");
    if (sig && window.RJ_SIGNATURE) sig.innerHTML = programCardHTML(window.RJ_SIGNATURE);
  }
  renderPrograms(false);

  // Refresh the catalog from the server (so admin-added programs appear).
  // Falls back silently to the bundled defaults if the API isn't reachable.
  (function loadServerCatalog() {
    if (!document.querySelector("[data-programs]")) return;
    const sig = list => JSON.stringify((list || []).map(p => [p.id, p.price]));
    fetch("/api/programs.php", { cache: "no-store" })
      .then(r => r.ok ? r.json() : null)
      .then(d => {
        if (!d || !Array.isArray(d.programs) || !d.programs.length) return;
        const paid = d.programs.filter(p => Number(p.price) > 0);
        if (paid.length && sig(paid) !== sig(window.RJ_PROGRAMS)) {
          window.RJ_PROGRAMS = paid;
          renderPrograms(true);
        }
      })
      .catch(() => {});
  })();

  // Partner marquee
  const mq = document.querySelector("[data-partners]");
  if (mq && window.RJ_PARTNERS) {
    const item = n => `<div class="logo-item">${partnerLogo(n)}<span>${n}</span></div>`;
    const set = RJ_PARTNERS.map(item).join("");
    mq.innerHTML = set + set; // duplicate for seamless loop
  }
  function partnerLogo(name) {
    if (/microsoft/i.test(name)) return `<svg width="22" height="22" viewBox="0 0 23 23"><rect width="10" height="10" fill="#f25022"/><rect x="12" width="10" height="10" fill="#7fba00"/><rect y="12" width="10" height="10" fill="#00a4ef"/><rect x="12" y="12" width="10" height="10" fill="#ffb900"/></svg>`;
    if (/itc|labour|ilo/i.test(name)) return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4ea8ff" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>`;
    if (/ey/i.test(name)) return `<svg width="22" height="22" viewBox="0 0 24 24"><rect width="24" height="24" rx="3" fill="#ffe600"/><text x="12" y="17" font-size="11" font-weight="800" text-anchor="middle" fill="#111">EY</text></svg>`;
    return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e8b65a" stroke-width="1.6"><path d="M4 18l4-9 4 6 4-10 4 13"/></svg>`;
  }

  document.addEventListener("DOMContentLoaded", initGSAP);
  if (document.readyState !== "loading") initGSAP();
})();
