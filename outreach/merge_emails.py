#!/usr/bin/env python3
"""
Mail-merge the partnership outreach email into one .eml file per contact.

Reads a CSV with columns: Institution, Contact Name, Email
Reads the subject line + plain-text body from partnership-outreach-email.txt
Reads the HTML body from partnership-outreach-email.html
Writes review-ready .eml files to outreach/output/ (open them in any mail
client, e.g. double-click on macOS Mail, or "Import" in Outlook/Thunderbird,
to check formatting before sending).

Any address in suppression.csv (built automatically by
check_stop_replies.py from real STOP replies) is skipped automatically —
run check_stop_replies.py before each batch to keep it current.

If your mailbox is webmail-only (no desktop client to open .eml files in),
use push_to_drafts.py instead — it puts each merged email directly into
your mailbox's Drafts folder, ready to open and send.

Usage:
    python3 merge_emails.py contacts.csv \
        --from-email info@coachruthjackson.com \
        --from-name "Ruth Jackson"

Stdlib only, no dependencies.
"""
import argparse
import sys
from pathlib import Path

from _email_common import build_message, iter_contacts, load_suppressed, load_templates, safe_filename

HERE = Path(__file__).resolve().parent
OUTPUT_DIR = HERE / "output"


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("csv_path", help="CSV with columns: Institution, Contact Name, Email")
    parser.add_argument("--from-email", default="info@coachruthjackson.com")
    parser.add_argument("--from-name", default="Ruth Jackson")
    args = parser.parse_args()

    subject_tpl, text_tpl, html_tpl = load_templates()

    csv_path = Path(args.csv_path)
    if not csv_path.exists():
        sys.exit(f"CSV not found: {csv_path}")

    OUTPUT_DIR.mkdir(exist_ok=True)
    suppressed = load_suppressed()

    count = 0
    skipped = 0
    for institution, contact_name, to_email in iter_contacts(csv_path):
        if not to_email:
            print(f"Skipping row with no email: {institution!r}", file=sys.stderr)
            continue
        if to_email.lower() in suppressed:
            print(f"Skipping (opted out via STOP): {to_email}", file=sys.stderr)
            skipped += 1
            continue

        msg = build_message(subject_tpl, text_tpl, html_tpl, institution, contact_name, to_email,
                             args.from_name, args.from_email)

        out_path = OUTPUT_DIR / f"{safe_filename(institution, contact_name)}.eml"
        out_path.write_bytes(msg.as_bytes())
        count += 1

    print(f"Wrote {count} .eml file(s) to {OUTPUT_DIR}/")
    if skipped:
        print(f"Skipped {skipped} address(es) that already opted out (see suppression.csv).")
    print("Open a few in your mail client to review before sending in batches of 20-30/day.")


if __name__ == "__main__":
    main()
