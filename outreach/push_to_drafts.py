#!/usr/bin/env python3
"""
Push the mail-merged partnership outreach emails directly into your
mailbox's Drafts folder over IMAP — for when there's no desktop mail
client to open .eml files in (webmail-only setups like Zoho or Gmail in
a browser). Each contact becomes one ready-to-send draft; open Drafts on
your phone or in the browser and hit Send on each one yourself.

This only ever creates drafts. It never sends anything — sending stays a
manual, deliberate action you take from inside your own mailbox.

Credentials are never hardcoded or committed — pass them as flags, or set
the IMAP_HOST / IMAP_USER / IMAP_PASSWORD environment variables. For Gmail,
use an app password (not the account password) and host imap.gmail.com;
for Zoho Mail, host imap.zoho.com. The mailbox you log into (--user) is
also used as the "From" address on each draft, since that's who's actually
sending — override with --from-email only if you have a send-as alias set
up on that account.

Use --limit to cap how many drafts get created in one run, matching the
20-30/day batch pace.

Usage:
    python3 push_to_drafts.py contacts.csv \
        --host imap.zoho.com --user info@coachruthjackson.com --limit 25

Stdlib only, no dependencies.
"""
import argparse
import imaplib
import os
import sys
from pathlib import Path

from _email_common import build_message, iter_contacts, load_suppressed, load_templates

CANDIDATE_DRAFTS_FOLDERS = ["Drafts", "INBOX.Drafts", "INBOX/Drafts", "[Gmail]/Drafts", "[Google Mail]/Drafts"]


def find_drafts_folder(conn, override=None):
    if override:
        return override

    status, folders = conn.list()
    if status != "OK" or not folders:
        return "Drafts"

    decoded = [f.decode(errors="ignore") if isinstance(f, bytes) else f for f in folders]

    for line in decoded:
        if "\\Drafts" in line:
            return line.rsplit(' "', 1)[-1].strip('"')

    names = []
    for line in decoded:
        if '"' in line:
            names.append(line.rsplit(' "', 1)[-1].strip('"'))

    for candidate in CANDIDATE_DRAFTS_FOLDERS:
        if candidate in names:
            return candidate

    return "Drafts"


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("csv_path", help="CSV with columns: Institution, Contact Name, Email")
    parser.add_argument("--host", default=os.environ.get("IMAP_HOST"))
    parser.add_argument("--user", default=os.environ.get("IMAP_USER"))
    parser.add_argument("--password", default=os.environ.get("IMAP_PASSWORD"))
    parser.add_argument("--from-name", default="Ruth Jackson")
    parser.add_argument("--from-email", default=None, help="Defaults to --user")
    parser.add_argument("--mailbox", default=None, help="Drafts folder name, if auto-detection picks the wrong one")
    parser.add_argument("--limit", type=int, default=None, help="Cap how many drafts to create this run")
    args = parser.parse_args()

    if not (args.host and args.user and args.password):
        sys.exit(
            "Missing IMAP credentials. Pass --host/--user/--password, or set "
            "IMAP_HOST / IMAP_USER / IMAP_PASSWORD in your environment."
        )

    from_email = args.from_email or args.user

    csv_path = Path(args.csv_path)
    if not csv_path.exists():
        sys.exit(f"CSV not found: {csv_path}")

    subject_tpl, text_tpl, html_tpl = load_templates()
    suppressed = load_suppressed()

    conn = imaplib.IMAP4_SSL(args.host)
    try:
        conn.login(args.user, args.password)
        drafts_folder = find_drafts_folder(conn, args.mailbox)

        created = 0
        skipped = 0
        for institution, contact_name, to_email in iter_contacts(csv_path):
            if args.limit is not None and created >= args.limit:
                print(f"Reached --limit {args.limit}; stopping for today's batch.")
                break
            if not to_email:
                print(f"Skipping row with no email: {institution!r}", file=sys.stderr)
                continue
            if to_email.lower() in suppressed:
                print(f"Skipping (opted out via STOP): {to_email}", file=sys.stderr)
                skipped += 1
                continue

            msg = build_message(subject_tpl, text_tpl, html_tpl, institution, contact_name, to_email,
                                 args.from_name, from_email)

            status, _ = conn.append(drafts_folder, "(\\Draft)", None, msg.as_bytes())
            if status != "OK":
                print(f"Failed to create draft for {to_email}", file=sys.stderr)
                continue
            created += 1
            print(f"Drafted: {institution} <{to_email}>")
    finally:
        conn.logout()

    print(f"Done. {created} draft(s) added to '{drafts_folder}'.")
    if skipped:
        print(f"Skipped {skipped} address(es) that already opted out (see suppression.csv).")
    print("Open Drafts in your mailbox and send each one yourself — nothing has been sent automatically.")


if __name__ == "__main__":
    main()
