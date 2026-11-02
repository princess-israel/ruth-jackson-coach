<?php
/**
 * LMS helpers: lesson queries, progress maths, safe video embeds.
 * Progress is always computed server-side from lesson_progress.
 */
require_once __DIR__ . '/_db.php';

/** Convert a YouTube/Vimeo link into a safe embed URL, or '' if unsupported. */
function lms_embed_url($url) {
  $url = trim((string)$url);
  if ($url === '') return '';
  if (preg_match('~^https://(?:www\.)?(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m))
    return 'https://www.youtube-nocookie.com/embed/' . $m[1];
  if (preg_match('~^https://(?:www\.)?vimeo\.com/(?:video/)?(\d+)~', $url, $m))
    return 'https://player.vimeo.com/video/' . $m[1];
  return '';
}

/** Only allow http(s) links for resources. */
function lms_safe_link($url) {
  $url = trim((string)$url);
  return preg_match('~^https?://~i', $url) ? $url : '';
}

/** Admin-authored HTML: drop script tags and inline event handlers. */
function lms_clean_html($html) {
  $html = preg_replace('~<\s*(script|style|iframe|object|embed)\b.*?<\s*/\s*\1\s*>~is', '', (string)$html);
  $html = preg_replace('~\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)~i', '', $html);
  $html = preg_replace('~(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2~i', '$1="#"', $html);
  return $html;
}

function lms_active_enrollment($userId, $programId) {
  $s = db()->prepare('SELECT * FROM enrollments WHERE user_id = ? AND program_id = ? AND status = "active" LIMIT 1');
  $s->execute([$userId, $programId]);
  return $s->fetch() ?: null;
}

function lms_lessons($programId, $publishedOnly = true) {
  $sql = 'SELECT * FROM lessons WHERE program_id = ?' . ($publishedOnly ? ' AND published = 1' : '') . ' ORDER BY position, created_at';
  $s = db()->prepare($sql);
  $s->execute([$programId]);
  return $s->fetchAll();
}

function lms_completed_ids($userId, $programId) {
  $s = db()->prepare(
    'SELECT lp.lesson_id FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id
      WHERE lp.user_id = ? AND lp.program_id = ? AND l.published = 1');
  $s->execute([$userId, $programId]);
  return array_column($s->fetchAll(), 'lesson_id');
}

/** Recompute enrollments.progress from lesson completion. Returns the percentage. */
function lms_recompute($userId, $programId) {
  $t = db()->prepare('SELECT COUNT(*) c FROM lessons WHERE program_id = ? AND published = 1');
  $t->execute([$programId]);
  $total = (int)$t->fetch()['c'];
  if ($total === 0) return null;
  $done = count(lms_completed_ids($userId, $programId));
  $pct = (int)round(min($done, $total) / $total * 100);
  db()->prepare('UPDATE enrollments SET progress = ? WHERE user_id = ? AND program_id = ?')
      ->execute([$pct, $userId, $programId]);
  return $pct;
}

/** Shape a lesson for a student (never leaks unpublished data). */
function lms_public_lesson($l) {
  return [
    'id' => $l['id'], 'module' => $l['module_title'], 'title' => $l['title'], 'kind' => $l['kind'],
    'embed' => $l['kind'] === 'video' ? lms_embed_url($l['video_url']) : '',
    'content' => lms_clean_html($l['content']),
    'resource_url' => lms_safe_link($l['resource_url']), 'resource_label' => $l['resource_label'],
  ];
}
