# Partnership outreach email

Cold-outreach template for university/TVET registrars, deans and principals,
offering the Women in Digital Business partnership.

## Files

- `partnership-outreach-email.html` — the mail-merge-ready HTML email.
  Table-based layout, inline CSS only (no `<style>` block, no flexbox), capped
  at 600px. Colors match the site's own article pages (background `#e8eef9`,
  ink `#27314f`/`#0c1633`, gold `#9a6b15`).
- `partnership-outreach-email.txt` — plain-text alternative (first line is
  `Subject: ...`, the rest is the body). Many university mail servers strip
  HTML, and sending a text alternative alongside HTML improves deliverability.
- `ruth-jackson-email-signature.html` — the signature block on its own, for
  reuse in other emails: name in Georgia, body in Arial, gold left accent
  rule, Call/WhatsApp/Email/Website each individually clickable, and a
  logo strip (Timshi, Microsoft, ILO, ITC, WIDB) hosted on
  `coachruthjackson.com` (already live under `assets/img/`).
- `contacts.sample.csv` — expected spreadsheet format: `Institution,
  Contact Name, Email`.
- `merge_emails.py` — reads a contacts CSV and writes one `.eml` file per row
  to `outreach/output/` (gitignored — review-only, not for committing).
  Automatically skips any address listed in `suppression.csv`.
- `check_stop_replies.py` — scans a mailbox for STOP replies and adds the
  sender to `suppression.csv` automatically, so `merge_emails.py` never
  emails that address again. See below.
- `push_to_drafts.py` — alternative to `merge_emails.py` for webmail-only
  setups: pushes each merged email straight into your mailbox's Drafts
  folder over IMAP instead of writing `.eml` files. See below.
- `_email_common.py` — shared template/merge logic used by both
  `merge_emails.py` and `push_to_drafts.py`. Not run directly.
- `suppression.sample.csv` — format reference only. The real
  `suppression.csv` is gitignored (it holds real recipient emails) and is
  created automatically the first time `check_stop_replies.py` runs.

Merge fields used in the templates: `{{contact_name}}`, `{{institution_name}}`.

## Generating the .eml files for review

```
cd outreach
python3 merge_emails.py contacts.csv --from-email info@coachruthjackson.com --from-name "Ruth Jackson"
```

Open a few of the resulting `.eml` files in a real mail client (double-click
on macOS Mail, or drag into Outlook/Thunderbird) to check formatting and links
before sending anything. Update `--from-email` if Ruth sends from a different
mailbox than `info@coachruthjackson.com`.

Either way, this only ever writes local files or drafts — nothing is sent
automatically. Sending is always a manual, deliberate action from inside
the real mailbox.

## Webmail-only? Push straight to Drafts instead

If there's no desktop mail client to open `.eml` files in (e.g. Zoho or
Gmail used only in a browser), use `push_to_drafts.py` instead of
`merge_emails.py`. It logs into the mailbox over IMAP and creates one
ready-to-send draft per contact — open Drafts on your phone or in the
browser afterward and hit Send on each one yourself. It never sends
anything itself.

```
cd outreach
python3 push_to_drafts.py contacts.csv --host imap.zoho.com --user info@coachruthjackson.com --limit 25
```

Same credential rules as `check_stop_replies.py` below: pass `--password`
or set `IMAP_HOST` / `IMAP_USER` / `IMAP_PASSWORD`, never hardcode it.
`--limit` caps how many drafts get created in one run, so you can match
the 20-30/day batch pace directly instead of drafting all of them at once.
It respects `suppression.csv` exactly like `merge_emails.py` does.

## Honoring STOP replies automatically

The email ends with "Reply STOP if you would rather not hear from me." To
make that actually stop future sends without anyone having to track replies
by hand:

```
cd outreach
python3 check_stop_replies.py --host imap.zoho.com --user info@coachruthjackson.com
```

You'll be prompted for nothing insecure in the repo — pass the mailbox
password as `--password`, or set `IMAP_HOST` / `IMAP_USER` / `IMAP_PASSWORD`
as environment variables instead of typing it on the command line. Common
hosts: `imap.zoho.com` (Zoho Mail), `imap.gmail.com` (Gmail — use an app
password, not the account password).

The script scans unread mail for "stop" in the subject or body, pulls out
the sender's address, and appends it to `suppression.csv` with a timestamp.
Run it once before generating each day's batch:

```
python3 check_stop_replies.py --host imap.zoho.com --user info@coachruthjackson.com
python3 merge_emails.py contacts.csv
```

`merge_emails.py` automatically skips every address already in
`suppression.csv` and prints which ones it skipped, so a contact who opted
out is never sent to again, with no manual list-editing required.

## Sending: do this in batches, not all at once

With ~150 institutional addresses:

- **Send 20-30 per day** from a normal mailbox, not all 150 at once. A sudden
  burst of near-identical messages from a new sender is exactly what spam
  filters are tuned to catch, and it can get the sending domain flagged before
  the campaign is halfway done.
- **Confirm SPF and DKIM are set up** for `coachruthjackson.com` before
  sending. University mail servers are strict about authenticating sender
  domains; a cold email from a domain without SPF/DKIM records is likely to be
  filtered or dropped silently, regardless of content.
- Vary send times across the day rather than firing all of a day's batch in
  one burst.
- Watch reply/bounce rates on the first couple of batches before sending the
  rest, so a deliverability problem is caught early rather than after all 150
  have gone out.
- Run `check_stop_replies.py` before every batch so opt-outs from earlier
  sends are excluded automatically.
