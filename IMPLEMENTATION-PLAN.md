# Ruth Jackson Site — Backend Hardening Plan (MySQL on cPanel)

Goal: make the **server** the source of truth for accounts, payments, and enrollments.
Today all of this lives in browser `localStorage`, so payments are unverifiable, customers
lose access across devices, and Ruth's admin can't see her own paying customers.

Stack decision: **MySQL (cPanel) + thin PHP endpoints**. No framework, no build step —
matches the existing `api/*.php` style. Keeps current hosting.

---

## 1. Database schema

Create one database in cPanel (MySQL Databases). Tables:

```sql
CREATE TABLE users (
  id            CHAR(36) PRIMARY KEY,          -- uuid
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,         -- password_hash(), NEVER plain text
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
  id                 CHAR(36) PRIMARY KEY,
  merchant_reference VARCHAR(80) NOT NULL UNIQUE,   -- programId-<random>
  order_tracking_id  VARCHAR(80) NULL,              -- from Pesapal
  user_id            CHAR(36) NULL,                 -- nullable: guest checkout
  email              VARCHAR(190) NULL,
  phone              VARCHAR(40) NULL,
  program_id         VARCHAR(80) NOT NULL,
  amount             DECIMAL(10,2) NOT NULL,        -- server-set price, authoritative
  currency           VARCHAR(8) NOT NULL DEFAULT 'USD',
  status             ENUM('PENDING','COMPLETED','FAILED','REVERSED','INVALID')
                       NOT NULL DEFAULT 'PENDING',
  confirmation_code  VARCHAR(80) NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
  INDEX (order_tracking_id), INDEX (user_id), INDEX (status)
);

CREATE TABLE enrollments (
  id          CHAR(36) PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  program_id  VARCHAR(80) NOT NULL,
  order_id    CHAR(36) NOT NULL,                 -- proof of payment
  status      ENUM('active','pending','revoked') NOT NULL DEFAULT 'active',
  progress    TINYINT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_program (user_id, program_id),
  INDEX (user_id)
);

CREATE TABLE messages (
  id        CHAR(36) PRIMARY KEY,
  user_id   CHAR(36) NOT NULL,                   -- the customer thread
  sender    ENUM('customer','ruth') NOT NULL,
  body      TEXT NOT NULL,
  read_flag TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, created_at)
);

CREATE TABLE sessions (
  token      CHAR(64) PRIMARY KEY,               -- random, sha256-length
  user_id    CHAR(36) NOT NULL,
  expires_at DATETIME NOT NULL,
  INDEX (user_id)
);
```

`programs` / `articles` stay as JSON files for now (admin-only, low write volume) — but
add file-locking on write (`flock`) to prevent corruption. Move to tables later if needed.

---

## 2. New / changed PHP files

```
api/_db.php            NEW   PDO connection (reads creds from config.php), helpers
api/auth/signup.php    NEW   hash password, insert user, create session
api/auth/login.php     NEW   verify password_hash, create session
api/auth/me.php        NEW   return current user from session token
api/auth/logout.php    NEW   delete session
api/enrollments.php    NEW   GET (mine) — reads server enrollments, gated by session
api/messages.php       NEW   GET/POST thread messages (customer + admin)
api/admin/orders.php   NEW   admin: list orders/customers (real data Ruth can see)

api/pesapal/pay.php    EDIT  insert PENDING order row before redirect
api/pesapal/ipn.php    EDIT  on notification: call GetTransactionStatus, persist
                             verified status, and on COMPLETED create the enrollment
api/pesapal/status.php EDIT  read from orders table first; only the SERVER fulfills
```

Key rule: **enrollment is created server-side inside the IPN/status handler when, and
only when, Pesapal returns `status_code === 1` (COMPLETED) AND the order amount matches.**
The browser callback page becomes display-only — it polls `status.php`, never grants access.

---

## 3. Config additions (`api/pesapal/config.php`, already gitignored)

```php
'db' => [
  'host' => 'localhost',
  'name' => 'cpaneluser_ruth',
  'user' => 'cpaneluser_ruth',
  'pass' => 'STRONG_DB_PASSWORD',
],
'site_url' => 'https://coachruthjackson.com',  // replaces HTTP_HOST everywhere
```

---

## 4. Frontend changes

- `store.js` → replace the localStorage data layer with `fetch` calls to the new APIs.
  Keep the same method names (`Store.login`, `Store.enroll`, `Store.messages`…) so the
  HTML pages barely change — they just become async.
- `login.html` → submit to `api/auth/login.php` / `signup.php`; store only the session
  token, never the password.
- `payment-callback.html` → poll `status.php`; show success only when the **server** says
  COMPLETED. Remove client-side `Store.enroll()`.
- `admin.html` → read from `api/admin/orders.php` + `api/messages.php` so Ruth sees real
  paying customers, not her own browser.

---

## 5. Security hardening (ship alongside)

`.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

- Hardcode `site_url` instead of `HTTP_HOST` in `pay.php` / `_pesapal.php`.
- Admin login: add real throttling (track failed attempts per IP in a table; lock 15 min
  after 5 fails). Keep `hash_equals`. Consider moving admin to an unguessable path.
- Hash all passwords with `password_hash(PASSWORD_DEFAULT)`; verify with `password_verify`.
- All DB access via PDO **prepared statements** (no string-built SQL).
- Escape any admin-authored article HTML on output, or sanitize on input.

---

## 6. SEO add-ons (separate, low-risk, can run in parallel)

- Add `Course` + `Offer` JSON-LD (with $79 price) to `program.html`.
- Add `Organization` schema to `index.html`.
- Generate indexable per-course URLs (server-rendered `program.php?id=` is fine if each
  emits unique title/description/canonical) and list them in `sitemap.xml`.
- Activate the real Google Search Console verification tag (currently a placeholder).

---

## 7. Phased rollout & verification

| Phase | Scope | Verify by |
|-------|-------|-----------|
| 0 | orders table + IPN persistence + status verification + HTTPS/headers + hardcode domain | Make a real sandbox payment → row appears, status flips to COMPLETED via IPN, enrollment auto-created |
| 1 | server auth (signup/login/sessions), enrollments + messages APIs, store.js rewrite | Pay on phone, log in on laptop → enrollment still there; admin sees the customer |
| 2 | admin throttling, security headers audit, Course/Org schema, GSC, email receipt | Brute-force is locked out; Rich Results test passes; receipt email arrives |

**Definition of done for "safe to take real money":** Phase 0 + Phase 1 complete. A paid
enrollment exists in MySQL, is created only after Pesapal-verified COMPLETED, survives a
browser/device change, and is visible in Ruth's admin. Free console enrollment no longer
grants dashboard access.

---

## 8. Risks / open items

- **cPanel MySQL must be available** (confirm in cPanel → MySQL Databases). If the plan is
  PHP-only with no DB, fall back to SQLite file (still a real ledger), but MySQL preferred.
- Decide **guest checkout vs. require account before pay.** Recommended: require account
  first → every order is tied to a `user_id`, simpler fulfillment, cleaner dashboard.
- Pesapal sandbox credentials needed to test end-to-end before going live.
- Pick ONE deploy target (cPanel). Remove `vercel.json` / GitHub-Pages artifacts so nobody
  deploys the static-only build and silently disables the PHP payment APIs.
