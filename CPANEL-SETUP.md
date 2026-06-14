# Deploy to cPanel (site + live Pesapal payments)

Everything runs natively on cPanel — static site + **PHP** Pesapal endpoints. No Node, no Vercel.
Payments are **live Pesapal**, charged in **USD ($79)**. Your keys stay on your server in
`config.php` (never committed, never sent to anyone).

## Requirements
- cPanel with **PHP 7.4+** and the **cURL** extension (both are on by default).
- **HTTPS** on your domain (cPanel → *SSL/TLS Status* → run AutoSSL). Pesapal callbacks need https.

## Step 1 — Get the files onto the server
**Option A — Upload a zip (simplest):**
1. On GitHub open `kenikiara/ruth-jackson-coach` → **Code ▾ → Download ZIP**.
2. cPanel → **File Manager** → open **public_html**.
3. **Upload** the zip, then **Extract** it. Move the extracted files so that `index.html` sits
   **directly inside `public_html`** (not in a sub-folder). Delete the leftover zip.

**Option B — cPanel Git Version Control:**
1. cPanel → **Git™ Version Control → Create**.
2. Clone URL: `https://github.com/kenikiara/ruth-jackson-coach.git`, repository path e.g.
   `repositories/ruth`. Then use *Manage → Pull or Deploy* and set the deploy target to
   `public_html` (or copy the files there).

## Step 2 — Add your Pesapal keys
1. In **public_html/api/pesapal/**, copy **`config.sample.php`** to **`config.php`**
   (File Manager → right-click `config.sample.php` → Copy → name it `config.php`).
2. **Edit `config.php`** and fill in:
   ```php
   'consumer_key'    => 'your_real_consumer_key',
   'consumer_secret' => 'your_real_consumer_secret',
   'base_url'        => 'https://pay.pesapal.com/v3',
   'ipn_id'          => '',
   ```
   Save. (`config.php` is gitignored and blocked from the web by `.htaccess`.)

## Step 3 — Register the IPN (one time)
1. Visit **`https://yourdomain.com/api/pesapal/setup.php`** in a browser.
2. It returns an `ipn_id`. Copy it into `config.php` → `'ipn_id' => 'that-value'` and save.

## Step 4 — Test the live flow
1. Go to `https://yourdomain.com/programs.html`, pick a course → **Enroll → Pay $79 with Pesapal**
   (you'll sign in / create an account first).
2. Complete a real Pesapal payment (card / M-Pesa).
3. You return to `payment-callback.html`, which verifies the payment server-side and, only if it's
   **Completed**, enrolls you and opens your dashboard. Ruth then grants course access from the
   admin dashboard.

## Notes
- The files `api/pesapal/*.js` and `vercel.json` are the Node/Vercel version — harmless on cPanel
  (Apache won't run them). You can delete them if you like; the **PHP** files are what run here.
- Admin/customer accounts still use the browser's `localStorage` demo store. For real cross-device
  accounts and a saved record of every payment, the next upgrade is a small MySQL table written from
  `ipn.php` (cPanel has MySQL built in) — ask and I'll wire it up.
- Need help pointing your domain or running AutoSSL? Tell me your host and I'll give exact clicks.
