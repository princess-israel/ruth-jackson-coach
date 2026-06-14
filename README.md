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
- No build step. Pure static files.
- GSAP + ScrollTrigger via CDN.
- Demo "backend" is `localStorage` (`assets/js/store.js`) — users, enrollments, messages. **Passwords are plain-text for demo only.**

## Demo logins
| Role | Email | Password |
|------|-------|----------|
| Customer | `demo@student.com` | `demo123` |
| Admin (Ruth) | `ruth@coachruthjackson.com` | `ruth123` |

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
