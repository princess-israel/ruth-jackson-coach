#!/usr/bin/env python3
"""
Scan a mailbox for "STOP" replies to the partnership outreach campaign and
automatically add each sender to suppression.csv. merge_emails.py reads
suppression.csv and skips those addresses on every future batch, so once
this has run, a contact who replied STOP is never sent to again without
anyone having to remember to remove them by hand.

Run this before generating each day's batch of .eml files, e.g.:

    python3 check_stop_replies.py --host imap.zoho.com --user info@coachruthjackson.com

Credentials are never hardcoded or committed — pass them as flags, or set
the IMAP_HOST / IMAP_USER / IMAP_PASSWORD environment variables. For Gmail,
use an app password (not the account password) and host imap.gmail.com;
for Zoho Mail, host imap.zoho.com.

Stdlib only, no dependencies.
"""
import argparse
import csv
import email
import imaplib
import os
import re
import sys
from datetime import datetime, timezone
from email.header import decode_header
from pathlib import Path

HERE = Path(__file__).resolve().parent
SUPPRESSION_PATH = HERE / "suppression.csv"

STOP_PATTERN = re.compile(r"\bstop\b", re.IGNORECASE)
EMAIL_PATTERN = re.compile(r"[\w.+-]+@[\w-]+\.[\w.-]+")


def load_suppressed():
    if not SUPPRESSION_PATH.exists():
        return set()
    with SUPPRESSION_PATH.open(newline="", encoding="utf-8") as f:
        return {row["Email"].strip().lower() for row in csv.DictReader(f) if row.get("Email")}


def append_suppressed(email_addr, reason):
    is_new = not SUPPRESSION_PATH.exists()
    with SUPPRESSION_PATH.open("a", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        if is_new:
            writer.writerow(["Email", "Date", "Reason"])
        writer.writerow([email_addr, datetime.now(timezone.utc).isoformat(), reason])


def decode_subject(raw_subject):
    parts = decode_header(raw_subject or "")
    return "".join(
        part.decode(enc or "utf-8", errors="ignore") if isinstance(part, bytes) else part
        for part, enc in parts
    )


def extract_sender(msg):
    match = EMAIL_PATTERN.search(msg.get("From", ""))
    return match.group(0).lower() if match else None


def get_text_body(msg):
    if msg.is_multipart():
        for part in msg.walk():
            if part.get_content_type() == "text/plain":
                charset = part.get_content_charset() or "utf-8"
                payload = part.get_payload(decode=True) or b""
                return payload.decode(charset, errors="ignore")
        return ""
    charset = msg.get_content_charset() or "utf-8"
    payload = msg.get_payload(decode=True) or b""
    return payload.decode(charset, errors="ignore")


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--host", default=os.environ.get("IMAP_HOST"))
    parser.add_argument("--user", default=os.environ.get("IMAP_USER"))
    parser.add_argument("--password", default=os.environ.get("IMAP_PASSWORD"))
    parser.add_argument("--mailbox", default="INBOX")
    parser.add_argument("--mark-seen", action="store_true", help="Mark processed STOP replies as read")
    args = parser.parse_args()

    if not (args.host and args.user and args.password):
        sys.exit(
            "Missing IMAP credentials. Pass --host/--user/--password, or set "
            "IMAP_HOST / IMAP_USER / IMAP_PASSWORD in your environment."
        )

    already_suppressed = load_suppressed()

    conn = imaplib.IMAP4_SSL(args.host)
    try:
        conn.login(args.user, args.password)
        conn.select(args.mailbox)

        status, data = conn.search(None, "UNSEEN")
        if status != "OK":
            sys.exit("Could not search mailbox.")

        ids = data[0].split()
        new_count = 0

        for msg_id in ids:
            status, msg_data = conn.fetch(msg_id, "(RFC822)")
            if status != "OK" or not msg_data or not msg_data[0]:
                continue
            msg = email.message_from_bytes(msg_data[0][1])

            subject = decode_subject(msg.get("Subject", ""))
            body = get_text_body(msg)

            if STOP_PATTERN.search(subject) or STOP_PATTERN.search(body):
                sender = extract_sender(msg)
                if sender and sender not in already_suppressed:
                    append_suppressed(sender, "replied STOP")
                    already_suppressed.add(sender)
                    new_count += 1
                    print(f"Suppressed: {sender}")
                if args.mark_seen:
                    conn.store(msg_id, "+FLAGS", "\\Seen")
    finally:
        conn.logout()

    print(f"Done. {new_count} new address(es) added to {SUPPRESSION_PATH}.")


if __name__ == "__main__":
    main()
