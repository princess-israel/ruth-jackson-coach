# Partnership outreach email

Cold-outreach template for university/TVET registrars, deans and principals,
offering the Women in Digital Business partnership. Built from the approved
copy in the project handover — do not lengthen the body copy.

## Files

- `partnership-outreach-email.html` — the mail-merge-ready HTML email.
  Table-based layout, inline CSS only (no `<style>` block, no flexbox), capped
  at 600px, so it survives Gmail/Outlook stripping and reads on a phone.
- `partnership-outreach-email.txt` — plain-text alternative (first line is
  `Subject: ...`, the rest is the body). Many university mail servers strip
  HTML, and sending a text alternative alongside HTML improves deliverability.
- `ruth-jackson-email-signature.html` — the signature block on its own, for
  reuse in other emails. Deep teal `#0F6156`, ink `#16282E`, name in Georgia,
  body in Arial, 3px left accent rule, three small logo images (Microsoft,
  ILO, ITC) hosted on `coachruthjackson.com` (already live under
  `assets/img/`).
- `contacts.sample.csv` — expected spreadsheet format: `Institution,
  Contact Name, Email`.
- `merge_emails.py` — reads a contacts CSV and writes one `.eml` file per row
  to `outreach/output/` (gitignored — review-only, not for committing).

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
