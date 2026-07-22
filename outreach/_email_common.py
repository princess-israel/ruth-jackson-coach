"""
Shared mail-merge logic used by merge_emails.py and push_to_drafts.py.
Not a standalone script — imported by both.
"""
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


def build_message(subject_tpl, text_tpl, html_tpl, institution, contact_name, to_email, from_name, from_email):
    subject = merge(subject_tpl, institution, contact_name)
    text_body = merge(text_tpl, institution, contact_name)
    html_body = merge(html_tpl, institution, contact_name)

    msg = MIMEMultipart("alternative")
    msg["Subject"] = subject
    msg["From"] = formataddr((from_name, from_email))
    msg["To"] = formataddr((contact_name, to_email)) if contact_name else to_email
    msg.attach(MIMEText(text_body, "plain", "utf-8"))
    msg.attach(MIMEText(html_body, "html", "utf-8"))
    return msg


def iter_contacts(csv_path):
    """Yield (institution, contact_name, to_email) tuples, validating required columns."""
    with csv_path.open(newline="", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        required = {"Institution", "Contact Name", "Email"}
        missing = required - set(reader.fieldnames or [])
        if missing:
            sys.exit(f"CSV is missing column(s): {', '.join(sorted(missing))}")
        for row in reader:
            yield row["Institution"].strip(), row["Contact Name"].strip(), row["Email"].strip()
