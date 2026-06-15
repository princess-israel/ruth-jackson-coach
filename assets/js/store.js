/* ============================================================
   RJ Store — server-backed data layer (MySQL via /api/*).
   Replaces the old localStorage demo store. Auth uses an opaque
   session token (localStorage "rj_token"); accounts, enrollments
   and messages all live in the database now.

   Usage on a page:
     await Store.ready();          // loads session + data once
     const u = Store.currentUser();// sync getter over the cache
   ============================================================ */
(function () {
  const TOKEN_KEY = "rj_token";
  const api = (path, opts = {}) => {
    const headers = Object.assign({ "Content-Type": "application/json" }, opts.headers || {});
    const t = localStorage.getItem(TOKEN_KEY);
    if (t) headers["Authorization"] = "Bearer " + t;
    return fetch(path, Object.assign({}, opts, { headers, cache: "no-store" }))
      .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.error || "Request failed");
        return d;
      });
  };

  // in-memory cache populated by ready()/reload()
  let _user = null, _enrollments = [], _messages = [], _ready = null;

  function normalizeMsg(m) {
    // map DB shape -> the shape pages already expect
    return {
      id: m.id, userId: m.user_id, from: m.sender === "ruth" ? "ruth" : "customer",
      text: m.body, ts: new Date((m.created_at || "").replace(" ", "T")).getTime() || Date.now(),
      read: String(m.read_flag) === "1",
    };
  }

  async function refresh() {
    if (!localStorage.getItem(TOKEN_KEY)) { _user = null; _enrollments = []; _messages = []; return; }
    try {
      const me = await api("/api/auth/me.php");
      _user = me.user;
      const [en, ms] = await Promise.all([
        api("/api/enrollments.php").catch(() => ({ enrollments: [] })),
        api("/api/messages.php").catch(() => ({ messages: [] })),
      ]);
      _enrollments = en.enrollments || [];
      _messages = (ms.messages || []).map(normalizeMsg);
    } catch (e) {
      localStorage.removeItem(TOKEN_KEY);   // invalid/expired token
      _user = null; _enrollments = []; _messages = [];
    }
  }

  const Store = {
    /* ---- lifecycle ---- */
    ready() { return _ready || (_ready = refresh()); },
    async reload() { _ready = refresh(); return _ready; },

    /* ---- auth ---- */
    async signup({ name, email, password }) {
      try {
        const d = await api("/api/auth/signup.php", { method: "POST", body: JSON.stringify({ name, email, password }) });
        localStorage.setItem(TOKEN_KEY, d.token); await this.reload();
        return { user: d.user };
      } catch (e) { return { error: e.message }; }
    },
    async login({ email, password }) {
      try {
        const d = await api("/api/auth/login.php", { method: "POST", body: JSON.stringify({ email, password }) });
        localStorage.setItem(TOKEN_KEY, d.token); await this.reload();
        return { user: d.user };
      } catch (e) { return { error: e.message }; }
    },
    async logout() {
      try { await api("/api/auth/logout.php", { method: "POST" }); } catch (e) {}
      localStorage.removeItem(TOKEN_KEY); _user = null; _enrollments = []; _messages = [];
    },
    currentUser() { return _user; },

    /* ---- enrollments (read-only on the client; created server-side on payment) ---- */
    enrollments() {
      return _enrollments.map(e => ({
        id: e.id, userId: e.user_id, programId: e.program_id,
        status: e.status, progress: Number(e.progress) || 0,
      }));
    },

    /* ---- messages ---- */
    messages() { return _messages.slice().sort((a, b) => a.ts - b.ts); },
    async sendMessage(text) {
      const d = await api("/api/messages.php", { method: "POST", body: JSON.stringify({ body: text }) });
      await this.reload(); return d;
    },
    unread() { return _messages.filter(m => m.from === "ruth" && !m.read).length; },
    async markRead() { await this.reload(); }, // server marks Ruth's msgs read on GET

    /* ---- catalog helper (unchanged) ---- */
    programById(id) {
      const list = (window.RJ_PROGRAMS || []).concat(window.RJ_SIGNATURE ? [window.RJ_SIGNATURE] : []);
      return list.find(p => p.id === id);
    },

    token() { return localStorage.getItem(TOKEN_KEY); },
  };

  window.Store = Store;
})();
