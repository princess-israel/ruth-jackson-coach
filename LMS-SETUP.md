# Timshi Learning Management System (LMS)

In-site course delivery for enrolled students, built on the existing enrollments.

## One-time setup (cPanel)
1. phpMyAdmin: select the site database, then import `db/lms-migration.sql` (adds `lessons` and `lesson_progress`).
2. Push to `main` so the deploy workflow uploads the new files.

## Add lessons
Admin dashboard, **Lessons (LMS)**: pick a course, **+ Add lesson**. Reading lessons take HTML; video lessons take a YouTube or Vimeo link. Unpublished lessons stay hidden from students.

## What students get
- `learn.html?course=<program-id>`: lesson list, video/reading player, **Mark complete**.
- Progress is stored server-side and written to `enrollments.progress` (shown on the dashboard and in admin).
- `certificate.html?course=<program-id>`: printable certificate, issued by the server only when every published lesson is complete.

## Files
`api/_lms.php`, `api/lms.php` (student), `api/admin/lessons.php` (admin), `learn.html`, `certificate.html`, `db/lms-migration.sql`.
Access requires an **active** enrollment, which is only created after Pesapal confirms payment.
