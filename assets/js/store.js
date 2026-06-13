/* ============================================================
   RJ Store — localStorage data layer (demo backend)
   Users, sessions, enrollments, messages.
   Note: passwords are stored in plain text for DEMO only.
   ============================================================ */
(function () {
  const K = {
    users: "rj_users",
    session: "rj_session",
    enroll: "rj_enrollments",
    msgs: "rj_messages",
  };
  const read = (k, d) => { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch { return d; } };
  const write = (k, v) => localStorage.setItem(k, JSON.stringify(v));
  const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

  function seed() {
    let users = read(K.users, null);
    if (!users) {
      users = [
        { id: "admin-ruth", name: "Ruth Jackson", email: "ruth@ruthjackson.coach", password: "ruth123", role: "admin", createdAt: Date.now() },
        { id: "demo-user", name: "Amina Demo", email: "demo@student.com", password: "demo123", role: "customer", createdAt: Date.now() }
      ];
      write(K.users, users);
    }
    if (!read(K.enroll, null)) {
      write(K.enroll, [
        { id: uid(), userId: "demo-user", programId: "ai-women-entrepreneurs", status: "active", progress: 35, purchasedAt: Date.now() - 86400000 * 4 },
        { id: uid(), userId: "demo-user", programId: "digital-marketing-social", status: "pending", progress: 0, purchasedAt: Date.now() - 3600000 }
      ]);
    }
    if (!read(K.msgs, null)) {
      write(K.msgs, [
        { id: uid(), userId: "demo-user", from: "ruth", text: "Welcome aboard, Amina! 🎉 Your AI for Women Entrepreneurs course is ready. I'll send your access link here within the hour.", ts: Date.now() - 86400000 * 4 + 3600000, read: true },
        { id: uid(), userId: "demo-user", from: "customer", text: "Thank you Ruth! Looking forward to starting.", ts: Date.now() - 86400000 * 4 + 5400000, read: true }
      ]);
    }
  }
  seed();

  const Store = {
    KEYS: K,
    /* ---- auth ---- */
    signup({ name, email, password }) {
      const users = read(K.users, []);
      if (users.some(u => u.email.toLowerCase() === email.toLowerCase()))
        return { error: "An account with that email already exists. Try logging in." };
      const user = { id: uid(), name, email, password, role: "customer", createdAt: Date.now() };
      users.push(user); write(K.users, users);
      this.setSession(user.id);
      return { user };
    },
    login({ email, password }) {
      const users = read(K.users, []);
      const u = users.find(x => x.email.toLowerCase() === email.toLowerCase() && x.password === password);
      if (!u) return { error: "Incorrect email or password." };
      this.setSession(u.id);
      return { user: u };
    },
    setSession(id) { write(K.session, id); },
    logout() { localStorage.removeItem(K.session); },
    currentUser() {
      const id = read(K.session, null);
      if (!id) return null;
      return read(K.users, []).find(u => u.id === id) || null;
    },
    users() { return read(K.users, []); },
    userById(id) { return read(K.users, []).find(u => u.id === id) || null; },

    /* ---- enrollments ---- */
    enrollments(userId) {
      const all = read(K.enroll, []);
      return userId ? all.filter(e => e.userId === userId) : all;
    },
    enroll(userId, programId) {
      const all = read(K.enroll, []);
      if (all.some(e => e.userId === userId && e.programId === programId))
        return { error: "You are already enrolled in this program." };
      const rec = { id: uid(), userId, programId, status: "pending", progress: 0, purchasedAt: Date.now() };
      all.push(rec); write(K.enroll, all);
      // auto welcome message from Ruth
      this.addMessage(userId, "ruth",
        "🎉 Thank you for enrolling! Your payment is confirmed. I'm preparing your private access link and getting-started guide — you'll receive it right here in this chat shortly. Reply anytime with questions!");
      return { rec };
    },
    setEnrollStatus(id, status) {
      const all = read(K.enroll, []);
      const e = all.find(x => x.id === id); if (e) { e.status = status; if (status === "active" && e.progress === 0) e.progress = 5; }
      write(K.enroll, all);
    },
    setProgress(id, progress) {
      const all = read(K.enroll, []);
      const e = all.find(x => x.id === id); if (e) e.progress = Math.max(0, Math.min(100, progress));
      write(K.enroll, all);
    },

    /* ---- messages ---- */
    messages(userId) { return read(K.msgs, []).filter(m => m.userId === userId).sort((a, b) => a.ts - b.ts); },
    threads() {
      const msgs = read(K.msgs, []);
      const map = {};
      msgs.forEach(m => { (map[m.userId] ??= []).push(m); });
      return Object.entries(map).map(([userId, list]) => {
        list.sort((a, b) => a.ts - b.ts);
        return { userId, user: this.userById(userId), last: list[list.length - 1], unread: list.filter(x => x.from === "customer" && !x.read).length };
      });
    },
    addMessage(userId, from, text) {
      const all = read(K.msgs, []);
      all.push({ id: uid(), userId, from, text, ts: Date.now(), read: from === "ruth" });
      write(K.msgs, all);
    },
    markRead(userId) {
      const all = read(K.msgs, []);
      all.forEach(m => { if (m.userId === userId) m.read = true; });
      write(K.msgs, all);
    },

    /* ---- helpers ---- */
    programById(id) {
      const list = (window.RJ_PROGRAMS || []).concat(window.RJ_SIGNATURE ? [window.RJ_SIGNATURE] : []);
      return list.find(p => p.id === id);
    },
    reset() { Object.values(K).forEach(k => localStorage.removeItem(k)); seed(); }
  };
  window.Store = Store;
})();
