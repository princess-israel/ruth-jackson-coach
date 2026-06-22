# Managing this website with Claude Code

This guide gets you set up to manage **coachruthjackson.com** from your own computer
using **Claude Code** (Anthropic's AI coding assistant). Follow it once; after that,
you can just open the project and ask Claude to make changes.

> Most day-to-day work (prices, courses, articles, replying to students, viewing orders)
> is done in the **admin dashboard** at `coachruthjackson.com/admin.html` — you do NOT
> need Claude Code for that. Use Claude Code for bigger changes: new pages, design
> tweaks, new features, or fixes.

---

## 1. Get the things you need (one-time)

| You need | How to get it |
|---|---|
| **An Anthropic account** with Claude Code access | Sign up at https://www.anthropic.com → Claude Code. A paid plan (Claude Pro/Max or API billing) is required. |
| **Git** | https://git-scm.com/downloads |
| **Node.js** (for the local preview) | https://nodejs.org (LTS version) |
| **Claude Code** | Install per https://docs.claude.com/claude-code (e.g. `npm install -g @anthropic-ai/claude-code`) |
| **A GitHub account** | https://github.com — so you own the code going forward |

## 2. Take ownership of the code (one-time)

The site's code lives in a GitHub repository. Pick ONE:

- **Recommended — transfer ownership to you:** the developer goes to the repo →
  Settings → *Transfer ownership* → enters your GitHub username. The repo becomes yours.
- **Or — be added as a collaborator:** the developer adds your GitHub username under
  repo → Settings → Collaborators (you can edit, they keep ownership).

Either way you'll then have the repo URL, e.g. `https://github.com/<owner>/ruth-jackson-coach`.

## 3. Get the code onto your computer (one-time)

Open a terminal and run:

```bash
git clone https://github.com/<owner>/ruth-jackson-coach.git
cd ruth-jackson-coach
```

## 4. Start Claude Code

From inside that folder:

```bash
claude
```

Claude Code automatically reads `CLAUDE.md` in this project, so it already knows how the
site is built and how to deploy it. Just describe what you want in plain English, e.g.:

- "Change the homepage headline to ..."
- "Add a new course called ... priced at $X"
- "The contact form button colour should be ..."

Claude edits the files and explains what it did.

## 5. Publish your changes (every time)

Claude can save and push the code, but **going live needs two manual clicks** (this is a
safety feature of the hosting):

1. Let Claude commit & push, or run `git push` yourself.
2. In **cPanel → Git Version Control →** this repo → **Update from Remote**, then
   **Manage → Deploy HEAD Commit**.
3. Hard-refresh the site (Ctrl/Cmd + Shift + R). If something looks stale, toggle the
   domain's document root in cPanel and refresh again.

Claude will remind you of these steps after each change.

---

## Important: secrets are NOT in the code

For security, the payment keys, database login, and admin password live in a single file
on the server only: `api/pesapal/config.php`. It is never stored in GitHub. If you ever
move hosting, you'll recreate it from `api/pesapal/config.sample.php`.

## Your admin login
- URL: `coachruthjackson.com/admin.html`
- Email + password: provided in your handover document. Keep them private.

## Where to learn more (already in this repo)
- `README.md` — project summary
- `CLAUDE.md` — technical context (Claude reads this automatically)
- `CPANEL-SETUP.md` — hosting & deployment details
- `PESAPAL-SETUP.md` — payment configuration

## Getting help
For anything you're unsure about, you can ask Claude Code directly ("how do I ...?"), or
contact your developer at **kendesigners.com**.
