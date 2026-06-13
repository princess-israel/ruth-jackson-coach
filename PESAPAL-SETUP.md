# Pesapal Payments — Setup (Vercel)

Payments use **Pesapal API 3.0 (live)**, charged in **USD ($79)**. Credentials live only on the
server (Vercel environment variables) and are never shipped to the browser.

## Architecture
```
Buyer clicks "Pay with Pesapal"
  → POST /api/pesapal/pay   (server gets auth token, registers IPN, submits order)
  → buyer redirected to Pesapal to pay (card / M-Pesa)
  → Pesapal redirects back to /payment-callback.html
  → page calls /api/pesapal/status (server verifies with Pesapal)
  → only if COMPLETED: enrollment is created, buyer lands in their dashboard
  → Pesapal also calls /api/pesapal/ipn (acknowledged server-side)
```
Server endpoints live in `api/pesapal/` and share `lib/pesapal.js`. The charged amount comes
from `lib/programs.js` on the server, so it can't be tampered with from the browser.

## Deploy to Vercel (one time)

1. Go to **https://vercel.com → Add New → Project → Import Git Repository**, and pick
   `kenikiara/ruth-jackson-coach`. Framework preset: **Other**. Root directory: **/** (default).
2. Before deploying, open **Environment Variables** and add:

   | Name | Value |
   |------|-------|
   | `PESAPAL_CONSUMER_KEY` | your Pesapal consumer key |
   | `PESAPAL_CONSUMER_SECRET` | your Pesapal consumer secret |
   | `PESAPAL_BASE_URL` | `https://pay.pesapal.com/v3` |

3. Click **Deploy**. You'll get a URL like `https://ruth-jackson-coach.vercel.app`.

## After first deploy (optional but recommended)

- Visit `https://<your-vercel-url>/api/pesapal/setup` once. It registers the IPN URL and returns an
  `ipn_id`. Copy it into a new env var **`PESAPAL_IPN_ID`** in Vercel, then redeploy. This stops the
  IPN being re-registered on cold starts.
- In your **Pesapal dashboard**, make sure your account is approved for live transactions and that
  your domain/callback is allowed if Pesapal requires whitelisting.

## Test the live flow
1. Open `https://<your-vercel-url>/programs.html`, pick a course, click **Enroll → Pay with Pesapal**.
   (You'll be asked to sign in / create an account first.)
2. Complete a real payment on Pesapal.
3. You return to `/payment-callback.html`, it verifies status, and on success you're enrolled and
   sent to your dashboard, where Ruth grants access.

## Notes / going further
- The GitHub Pages copy (`kenikiara.github.io/ruth-jackson-coach`) is **static only** — the `/api`
  functions don't run there, so use the **Vercel URL** for live payments.
- Accounts/enrollments still use the browser `localStorage` demo store. For real cross-device
  accounts and an audit trail of payments, move `store.js` to a database (e.g. Supabase) and have
  `/api/pesapal/ipn` persist confirmed orders there. The Pesapal pieces are already production-shaped.
