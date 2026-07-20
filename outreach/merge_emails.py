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

Usage:
    python3 merge_emails.py contacts.csv \
        --from-email info@coachruthjackson.com \
        --from-name "Ruth Jackson"

Stdlib only, no dependencies.
"""
import argparse
import csv
import re
import sys
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.utils import formataddr
from pathlib import Path

HERE = Path(__file__).resolve().parent
HTML_TEMPLATE_PATH = HERE / "partnership-outreach-email.html"
TEXT_TEMPLATE_PATH = HERE / "partnership-outreach-email.txt"
OUTPUT_DIR = HERE / "output"
SUPPRESSION_PATH = HERE / "suppression.csv"


def load_suppressed():
    if not SUPPRESSION_PATH.exists():
        return set()
    with SUPPRESSION_PATH.open(newline="", encoding="utf-8") as f:
        return {row["Email"].strip().lower() for row in csv.DictReader(f) if row.get("Email")}


def load_templates():
    html = HTML_TEMPLATE_PATH.read_text(encoding="utf-8")

    text_raw = TEXT_TEMPLATE_PATH.read_text(encoding="utf-8")
    subject_match = re.match(r"Subject:\s*(.+)\n+", text_raw)
    if not subject_match:
        sys.exit(f"Could not find a 'Subject: ...' line at the top of {TEXT_TEMPLATE_PATH}")
    subject = subject_match.group(1).strip()
    text_body = text_raw[subject_match.end():]

    return subject, text_body, html


def merge(template, institution, contact_name):
    return (
        template
        .replace("{{institution_name}}", institution)
        .replace("{{contact_name}}", contact_name)
    )


def safe_filename(*parts):
    name = "_".join(parts)
    name = re.sub(r"[^\w\-. ]+", "", name).strip().replace(" ", "-")
    return name[:120] or "contact"


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
    with csv_path.open(newline="", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        required = {"Institution", "Contact Name", "Email"}
        missing = required - set(reader.fieldnames or [])
        if missing:
            sys.exit(f"CSV is missing column(s): {', '.join(sorted(missing))}")

        for row in reader:
            institution = row["Institution"].strip()
            contact_name = row["Contact Name"].strip()
            to_email = row["Email"].strip()
            if not to_email:
                print(f"Skipping row with no email: {institution!r}", file=sys.stderr)
                continue
            if to_email.lower() in suppressed:
                print(f"Skipping (opted out via STOP): {to_email}", file=sys.stderr)
                skipped += 1
                continue

            subject = merge(subject_tpl, institution, contact_name)
            text_body = merge(text_tpl, institution, contact_name)
            html_body = merge(html_tpl, institution, contact_name)

            msg = MIMEMultipart("alternative")
            msg["Subject"] = subject
            msg["From"] = formataddr((args.from_name, args.from_email))
            msg["To"] = formataddr((contact_name, to_email)) if contact_name else to_email
            msg.attach(MIMEText(text_body, "plain", "utf-8"))
            msg.attach(MIMEText(html_body, "html", "utf-8"))

            out_path = OUTPUT_DIR / f"{safe_filename(institution, contact_name)}.eml"
            out_path.write_bytes(msg.as_bytes())
            count += 1

    print(f"Wrote {count} .eml file(s) to {OUTPUT_DIR}/")
    if skipped:
        print(f"Skipped {skipped} address(es) that already opted out (see suppression.csv).")
    print("Open a few in your mail client to review before sending in batches of 20-30/day.")


if __name__ == "__main__":
    main()
