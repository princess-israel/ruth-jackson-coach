# Ruth Jackson — Coaching & Courses Website

Project context for Claude Code. Read this before making changes.

## What this is
A static **HTML/CSS/vanilla-JS** front end plus a **PHP** back end (JSON-file + MySQL data),
hosted on **cPanel** at **coachruthjackson.com**. It sells $79 self-paced certificate
courses with **Pesapal** payments, and provides customer + admin dashboards.

There is no build step. Edit files directly; the browser loads them as-is.

## Layout
- `index.html`, `programs.html`, `program.html`, `customer-service.html`, `about.html`,
  `blog.html`, `affiliates.html`, `dashboard.html`, `login.html`, `admin.html`,
  `onboarding.html`, `payment-callback.html`, policy pages.
- `assets/css/styles.css` — all styling (CSS variables; light default + `[data-theme=dark]`).
- `assets/js/` — `main.js` (nav, footer, language switcher, GSAP, renderers),
  `store.js` (customer session/data layer), `data.js` (catalog data), `chat.js` (AI widget).
- `api/` — PHP endpoints. `api/pesapal/` handles payments. `api/admin/` is admin-only.
  `data/*.default.json` are committed defaults; runtime `data/*.json` are gitignored.

## Critical rules
- **Never commit `api/pesapal/config.php`** — it holds Pesapal keys, the admin password,
  and DB credentials. It lives only on the server and is gitignored.
- Runtime data (`data/programs.json`, `articles.json`, `affiliates.json`, `ipn_id.txt`)
  is gitignored — do not commit or overwrite it.
- **No em-dashes** in any copy (house style). Match the surrounding code style.
- Payments are server-authoritative: an enrollment is created **only** when Pesapal
  reports COMPLETED (`api/_orders.php`). Never grant access from the client side.

## Deploying changes (now automatic)
Deployment is automated by GitHub Actions (`.github/workflows/deploy.yml`): every push
to `main` uploads the changed files to the live server over FTP. So:
1. `git add` + `git commit` + `git push` to GitHub (`origin main`).
2. The workflow deploys within ~1 minute (watch the repo's **Actions** tab). Then
   hard-refresh the site. HTML/JS/CSS are `no-cache`; images cache 30 days (rename to bust).

After pushing, tell the user it will be live in ~1 minute and to hard-refresh.
The deploy never touches `api/pesapal/config.php` or runtime `data/*.json` (excluded).
First-time wiring of the FTP secrets is documented in `SETUP-CONNECT.md`.
The older manual path (cPanel Git "Update from Remote → Deploy HEAD Commit" via
`.cpanel.yml`) still works as a fallback if Actions is ever disabled.

## Local preview
A static server is enough: `npx serve site -l 4321` (PHP endpoints won't run locally;
test PHP on the live server). Use the preview tools to verify front-end changes.

## Common tasks
- **Edit courses/prices/articles:** done by the site owner in `admin.html` (writes to the
  server `data/*.json`); code defaults live in `data/*.default.json` + `assets/js/data.js`.
- **Phone/WhatsApp number:** `+254729384374` / `wa.me/254729384374` site-wide.
- **Languages:** EN/SW/AR/ES/HI via the Google-Translate-backed switcher in `main.js`.

See also `README.md`, `CPANEL-SETUP.md`, `PESAPAL-SETUP.md`, and `CLIENT-HANDOFF.md`.
