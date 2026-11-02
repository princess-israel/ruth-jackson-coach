<?php
/**
 * Student LMS API (customer Bearer token; active enrollment required).
 *   GET  ?program=ID      -> { lessons:[...], completed:[ids], progress }
 *   GET  ?certificate=ID  -> { certificate:{...} } only when every lesson is complete
 *   POST { action:"complete", lesson_id, done:true|false } -> { progress, completed }
 */
require __DIR__ . '/_lms.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$b = read_body();
$u = user_from_token(bearer_token($b));
if (!$u) json_out(401, ['error' => 'Not signed in.']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $pid = (string)($_GET['program'] ?? $_GET['certificate'] ?? '');
  if ($pid === '') json_out(400, ['error' => 'Missing course.']);
  $en = lms_active_enrollment($u['id'], $pid);
  if (!$en) json_out(403, ['error' => 'You do not have active access to this course.']);

  $lessons = lms_lessons($pid);
  $done = lms_completed_ids($u['id'], $pid);

  if (isset($_GET['certificate'])) {
    if (!$lessons || count($done) < count($lessons)) json_out(403, ['error' => 'Finish every lesson to earn your certificate.']);
    $s = db()->prepare('SELECT MAX(completed_at) d FROM lesson_progress WHERE user_id = ? AND program_id = ?');
    $s->execute([$u['id'], $pid]);
    json_out(200, ['certificate' => [
      'name' => $u['name'], 'program_id' => $pid,
      'date' => substr((string)$s->fetch()['d'], 0, 10),
      'code' => 'TUI-' . strtoupper(substr(sha1($en['id']), 0, 10)),
    ]]);
  }

  json_out(200, [
    'lessons'   => array_map('lms_public_lesson', $lessons),
    'completed' => $done,
    'progress'  => (int)$en['progress'],
  ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);
if (($b['action'] ?? '') !== 'complete') json_out(400, ['error' => 'Unknown action.']);

$s = db()->prepare('SELECT program_id FROM lessons WHERE id = ? AND published = 1 LIMIT 1');
$s->execute([(string)($b['lesson_id'] ?? '')]);
$lesson = $s->fetch();
if (!$lesson) json_out(404, ['error' => 'Lesson not found.']);
$pid = $lesson['program_id'];
if (!lms_active_enrollment($u['id'], $pid)) json_out(403, ['error' => 'You do not have active access to this course.']);

if (!empty($b['done'])) {
  db()->prepare('INSERT IGNORE INTO lesson_progress (user_id, lesson_id, program_id) VALUES (?, ?, ?)')
      ->execute([$u['id'], $b['lesson_id'], $pid]);
} else {
  db()->prepare('DELETE FROM lesson_progress WHERE user_id = ? AND lesson_id = ?')
      ->execute([$u['id'], $b['lesson_id']]);
}
$pct = lms_recompute($u['id'], $pid);
json_out(200, ['progress' => $pct, 'completed' => lms_completed_ids($u['id'], $pid)]);
