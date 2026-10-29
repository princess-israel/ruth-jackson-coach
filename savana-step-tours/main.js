// Savana Step Tours: set your real details here.
const CONFIG = {
  email: "info@savanasteptours.com", // portfolio placeholder
  whatsapp: "254700123456",           // portfolio placeholder (not a working number)
  address: "Westlands, Nairobi, Kenya", // portfolio placeholder
  phone: "+254 700 123 456",
  video: "video/safari-film.mp4"
};

// Sample tours: replace titles, prices and copy with your real packages.
const TOURS = [
  {t:"Classic Masai Mara Safari",d:"5 Days",c:"Kenya",cat:"classic",g:"g1",p:1450,s:"Big cats, river crossings and sundowner drives."},
  {t:"Serengeti & Ngorongoro Explorer",d:"7 Days",c:"Tanzania",cat:"classic",g:"g2",p:2450,s:"The endless plains and the crater floor in one trip."},
  {t:"Kenya & Tanzania Grand Circuit",d:"10 Days",c:"Kenya \u2022 Tanzania",cat:"classic",g:"g3",p:3650,s:"Amboseli, Mara and Serengeti at an unhurried pace."},
  {t:"Private Tented Camp Honeymoon",d:"6 Days",c:"Kenya",cat:"luxury",g:"g4",p:3200,s:"Bush dinners, balloon flights and a private guide."},
  {t:"Photographer's Mara Week",d:"7 Days",c:"Kenya",cat:"custom",g:"g5",p:2900,s:"Photo vehicle, golden-hour drives and a pro-minded guide."},
  {t:"Nairobi & Amboseli Getaway",d:"3 Days",c:"Kenya",cat:"short",g:"g6",p:690,s:"Elephants under Kilimanjaro, easy from Nairobi."}
];

// Sample reviews: replace with real guest reviews before launch.
const REVIEWS = [
  {q:"Sample review: our guide spotted a leopard before we had even finished breakfast.",n:"Guest Name",t:"Masai Mara, 5 days"},
  {q:"Sample review: every detail was handled and we never felt rushed.",n:"Guest Name",t:"Serengeti, 7 days"},
  {q:"Sample review: the best family trip we have taken. The kids still talk about it.",n:"Guest Name",t:"Amboseli, 3 days"}
];

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const money = n => "$" + n.toLocaleString("en-US");

// contact links
$("#yr").textContent = new Date().getFullYear();
$("#mailLink").textContent = CONFIG.email;
$("#mailLink").removeAttribute("href");
$("#waLink").href = "https://wa.me/" + CONFIG.whatsapp;
$("#addr").textContent = CONFIG.phone + " | " + CONFIG.address;

// mobile menu
const menuBtn = $(".menu-btn"), nav = $("#nav");
menuBtn.addEventListener("click", () => {
  const open = nav.classList.toggle("open");
  menuBtn.setAttribute("aria-expanded", open);
});
$$("#nav a").forEach(a => a.addEventListener("click", () => nav.classList.remove("open")));

// tours
let filter = "all", place = "";
function renderTours() {
  const list = TOURS.filter(x => (filter === "all" || x.cat === filter) && (!place || x.c.includes(place)));
  $("#tourGrid").innerHTML = list.length ? list.map(x => `
    <article class="card">
      <div class="img ${x.g}"><span class="badge">${x.d}</span></div>
      <div class="body">
        <span class="tags">${x.c}</span>
        <h3>${x.t}</h3>
        <p>${x.s}</p>
        <div class="foot"><span class="price">From ${money(x.p)}</span>
        <button class="btn btn-primary btn-sm" data-inquire="${x.t}">View Safari</button></div>
      </div>
    </article>`).join("") : "<p>No trips match yet. Send us an enquiry and we will build one.</p>";
}
$$(".chip").forEach(b => b.addEventListener("click", () => {
  $$(".chip").forEach(c => c.classList.remove("active"));
  b.classList.add("active");
  filter = b.dataset.filter; place = "";
  renderTours();
}));
$$(".dest").forEach(d => d.addEventListener("click", () => {
  place = d.dataset.pick; filter = "all";
  $$(".chip").forEach(c => c.classList.toggle("active", c.dataset.filter === "all"));
  renderTours();
}));
renderTours();

// modals
function openModal(el) { el.hidden = false; document.body.style.overflow = "hidden"; }
function closeModals() {
  $$(".modal").forEach(m => m.hidden = true);
  $("#videoSlot").innerHTML = "";
  document.body.style.overflow = "";
}
document.addEventListener("click", e => {
  const inq = e.target.closest("[data-inquire]");
  if (inq) {
    $("#inqFor").textContent = "About: " + inq.dataset.inquire;
    $("#inqForm").dataset.topic = inq.dataset.inquire;
    $("#inqForm").hidden = false; $("#inqDone").hidden = true; $("#inqCopy").textContent = "Copy message";
    openModal($("#inquiry"));
  }
  if (e.target.closest("[data-close]") || e.target.classList.contains("modal")) closeModals();
});
document.addEventListener("keydown", e => { if (e.key === "Escape") closeModals(); });

// inquiry: shows the message to copy or send on WhatsApp (nothing is sent automatically)
$("#inqForm").addEventListener("submit", e => {
  e.preventDefault();
  const f = new FormData(e.target);
  const text = `Enquiry: ${e.target.dataset.topic}\nName: ${f.get("name")}\nEmail: ${f.get("email")}\nDates: ${f.get("dates")}\nTravellers: ${f.get("pax")}\n\n${f.get("msg")}`;
  $("#inqText").textContent = text;
  $("#inqEmail").textContent = CONFIG.email;
  $("#inqWa").href = "https://wa.me/" + CONFIG.whatsapp + "?text=" + encodeURIComponent(text);
  e.target.hidden = true;
  $("#inqDone").hidden = false;
});
$("#inqCopy").addEventListener("click", async () => {
  const t = $("#inqText").textContent;
  try { await navigator.clipboard.writeText(t); $("#inqCopy").textContent = "Copied"; }
  catch (_) { const r = document.createRange(); r.selectNodeContents($("#inqText")); const s = getSelection(); s.removeAllRanges(); s.addRange(r); $("#inqCopy").textContent = "Selected, press copy"; }
});

// video
$("#playBtn").addEventListener("click", () => {
  $("#videoSlot").innerHTML = `<video controls autoplay muted playsinline src="${CONFIG.video}"></video>`;
  openModal($("#videoModal"));
});

// reviews slider
const slider = $("#slider"), dots = $("#dots");
slider.innerHTML = REVIEWS.map(r => `<div class="review"><div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><blockquote>${r.q}</blockquote><small>${r.n} &bull; ${r.t}</small></div>`).join("");
dots.innerHTML = REVIEWS.map((_, i) => `<button aria-label="Review ${i + 1}" data-i="${i}"></button>`).join("");
let cur = 0;
function go(i) {
  cur = (i + REVIEWS.length) % REVIEWS.length;
  slider.scrollTo({left: slider.clientWidth * cur});
  $$("button", dots).forEach((b, n) => b.classList.toggle("on", n === cur));
}
dots.addEventListener("click", e => { if (e.target.dataset.i) go(+e.target.dataset.i); });
setInterval(() => go(cur + 1), 6000);
go(0);

// newsletter (front end only; connect to your email provider)
$("#news").addEventListener("submit", e => {
  e.preventDefault();
  $("#newsMsg").textContent = "Sign-ups are not connected yet. Please contact us by email or WhatsApp.";
  e.target.reset();
});

// hero slides
const slides = $$(".slide"), hdots = $$("#heroDots button");
let hs = 0;
function showSlide(i) {
  hs = (i + slides.length) % slides.length;
  slides.forEach((s, n) => s.classList.toggle("on", n === hs));
  hdots.forEach((d, n) => d.classList.toggle("on", n === hs));
}
hdots.forEach((d, n) => d.addEventListener("click", () => showSlide(n)));
setInterval(() => showSlide(hs + 1), 6000);
