# Connect everything: GitHub + Auto-Deploy + Claude Code

One-time setup so the site auto-publishes on every change, and your client can manage
it from Claude Code. Do these in order. After this, **a push goes live by itself** — no
manual cPanel clicks.

---

## A. Put the code on her GitHub account

1. Sign in as her on GitHub.
2. Create a new repo (e.g. `ruth-jackson-coach`) **or** have the current owner
   *Transfer ownership* of the existing repo to her account.
3. If it's a brand-new empty repo, point this project at it and push:
   ```bash
   git remote set-url origin https://github.com/HER-USERNAME/ruth-jackson-coach.git
   git push -u origin main
   ```

## B. Add the FTP secrets (this is what makes auto-deploy work)

In **her GitHub repo → Settings → Secrets and variables → Actions → New repository secret**,
add these four (get the values from cPanel → FTP Accounts):

| Secret name | Value / example |
|---|---|
| `FTP_SERVER` | the FTP host, e.g. `ftp.irelandcollection.com` |
| `FTP_USERNAME` | the FTP user, e.g. `claude@coachruthjackson.com` |
| `FTP_PASSWORD` | that FTP account's password |
| `FTP_SERVER_DIR` | the folder that IS the website root, **ending in `/`**. Often `./` for a domain-scoped FTP user, or a path like `/coachruthjackson.com/`. |

> Security: create a **dedicated FTP account** in cPanel just for deploys, and if the old
> `claude@…` password was ever shared, reset it first. The password only lives inside
> GitHub Secrets — never in the code.

### How to know `FTP_SERVER_DIR` is right
The deploy must land where `index.html` is served. In cPanel → File Manager, open the
folder that contains the live `index.html` (for this site it's
`/home/irelandc/coachruthjackson.com`). If your FTP login starts you *inside* that folder,
use `./`. If it starts one level up, use `/coachruthjackson.com/`.

## C. Test auto-deploy

1. Make any tiny change and push (or open the repo → **Actions** tab → run
   **Deploy to cPanel** manually with *Run workflow*).
2. Watch the run go green in the **Actions** tab.
3. Hard-refresh `coachruthjackson.com` (Ctrl/Cmd + Shift + R) — your change is live.

If the run fails on connection, switch `protocol: ftps` to `protocol: ftp` in
`.github/workflows/deploy.yml` (some hosts don't offer FTPS) and push again.

## D. Connect her Claude Code

On her computer (you said you have access):

```bash
git clone https://github.com/HER-USERNAME/ruth-jackson-coach.git
cd ruth-jackson-coach
git config user.name  "Ruth Jackson"
git config user.email "her-github-email@example.com"
claude
```

Claude Code reads `CLAUDE.md` automatically, so it already understands the site. Now the
loop is simply: **ask Claude → it edits & pushes → GitHub Actions deploys → live.**
She no longer touches cPanel for normal changes.

## E. Protect the server secret (must do once)

The payment keys, database login and admin password live ONLY in
`api/pesapal/config.php` on the server (never in GitHub). The deploy is configured to
**never overwrite or delete it**. Make sure she also has **cPanel access** so she can
edit that file if hosting ever moves. Recreate it from `api/pesapal/config.sample.php`.

---

## What stays manual (rare)
- Editing `api/pesapal/config.php` (secrets) — done in cPanel, not via Git.
- Anything to do with the domain, email, or hosting account itself.

## Day-to-day, she has two tools
- **Admin dashboard** (`/admin.html`) — prices, courses, articles, orders, students,
  messages, affiliates. No code, no deploy.
- **Claude Code** — bigger changes, new pages/features. Push = auto-live.
