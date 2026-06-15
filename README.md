# Ruth Jackson — AI Coach Website

A modern, dark-blue marketing + e-learning site for **Ruth Wanjohi ("Ruth Jackson")**, a Microsoft-certified AI coach and WIDB Lead Trainer. Built as a fast static site (HTML/CSS/vanilla JS + GSAP) and deployed on GitHub Pages.

## What it does
- **Sells $79 self-paced certificate courses** (AI, digital marketing, website building, SEO, data analysis, e-commerce).
- **Free / contact-only "Customer Service Excellence"** signature training, personally delivered by Ruth (banking background).
- **Buy → customer dashboard** flow: enroll → land in a private dashboard where Ruth shares the access link/instructions and they chat directly.
- **Admin dashboard** for Ruth: KPIs, enrollments, grant access, reply to every student.
- **AI chat assistant ("Ruthie")** — a conversion-focused widget on every page.
- **SEO**: meta tags, JSON-LD, sitemap, robots, 3 articles.
- **Design**: GSAP scroll reveals, parallax, 3D portrait tilt, count-ups, glassmorphism.

## Tech
- No build step. Static front end + thin PHP API on cPanel.
- GSAP + ScrollTrigger via CDN.
- **Real backend: MySQL/MariaDB** via `api/*.php`. Accounts, sessions, orders,
  enrollments and messages live in the database. Passwords are hashed
  (`password_hash`). `assets/js/store.js` is a thin async wrapper over the API —
  no business data lives in the browser anymore.

## Backend setup (one time, on cPanel)
1. **Create a database + user** in cPanel → MySQL Databases (e.g. `irelandc_ruth`);
   add the user to the DB with ALL PRIVILEGES.
2. **Import the schema:** phpMyAdmin → select the DB → Import → `db/schema.sql`.
3. **Configure:** copy `api/pesapal/config.sample.php` → `api/pesapal/config.php`,
   fill in the Pesapal keys, `site_url`, `admin_email`, `admin_token`, and the `db`
   block. `config.php` is gitignored.
4. **Register the IPN once:** visit `/api/pesapal/setup.php`, paste the returned
   `ipn_id` into `config.php`.
5. Test a sandbox payment end-to-end (see PESAPAL-SETUP.md).

## Access
- **Customers** self-register at `login.html` (localStorage demo store).
- **Admin** is a single, exclusive login at `admin.html`, verified server-side against
  `admin_token` in `api/pesapal/config.php` (via `api/admin-login.php`). There is no admin
  account in the client store and no signup path to admin. The Programs/Articles write APIs
  also require this secret. Set it once in `config.php`.

## Structure
```
index.html              Home
programs.html           Course catalog
program.html?id=...     Course detail + checkout/enroll
customer-service.html   Signature training enquiry
about.html              Bio + badge + certificate
blog.html, blog/*.html  Articles (SEO)
login.html              Auth (login + signup)
dashboard.html          Customer dashboard
admin.html              Admin dashboard
assets/css, assets/js, assets/img
```

## Run locally
Any static server, e.g.:
```bash
npx serve site
```

## Going to production (real payments & messaging)
The checkout and messaging are demo-grade (localStorage). To go live, swap:
- **Payments** → Paystack / Flutterwave / Stripe checkout.
- **Auth + data** → a real backend (Supabase / Firebase) instead of `store.js`.
- **Messaging** → the same backend, or connect WhatsApp Business API.
Contact: **+254 714 458530**.
