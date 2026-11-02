<?php
/**
 * Admin lesson manager (admin token).
 *   GET  ?program=ID                      -> { lessons:[...] } including drafts
 *   POST { action:"save", lesson:{...} }  -> create/update
 *   POST { action:"delete", id }
 */
require __DIR__ . '/../_lms.php';
require __DIR__ . '/../_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$b = read_body();
if (!admin_token_valid(bearer_token($b))) json_out(401, ['error' => 'Unauthorized']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $pid = (string)($_GET['program'] ?? '');
  if ($pid === '') json_out(400, ['error' => 'Missing program.']);
  json_out(200, ['lessons' => lms_lessons($pid, false)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$action = $b['action'] ?? '';
if ($action === 'delete') {
  $id = (string)($b['id'] ?? '');
  db()->prepare('DELETE FROM lesson_progress WHERE lesson_id = ?')->execute([$id]);
  db()->prepare('DELETE FROM lessons WHERE id = ?')->execute([$id]);
  json_out(200, ['ok' => true]);
}
if ($action !== 'save') json_out(400, ['error' => 'Unknown action.']);

$l = $b['lesson'] ?? [];
$title = trim((string)($l['title'] ?? ''));
$pid   = trim((string)($l['program_id'] ?? ''));
if ($title === '' || $pid === '') json_out(400, ['error' => 'Title and course are required.']);
$kind  = ($l['kind'] ?? 'text') === 'video' ? 'video' : 'text';
$video = trim((string)($l['video_url'] ?? ''));
if ($kind === 'video' && lms_embed_url($video) === '') json_out(400, ['error' => 'Use a YouTube or Vimeo link (https).']);
$res   = trim((string)($l['resource_url'] ?? ''));
if ($res !== '' && lms_safe_link($res) === '') json_out(400, ['error' => 'Resource link must start with http:// or https://']);

$vals = [
  $pid, mb_substr(trim((string)($l['module_title'] ?? '')) ?: 'Module 1', 0, 120), mb_substr($title, 0, 190), $kind,
  $kind === 'video' ? $video : null, lms_clean_html($l['content'] ?? ''),
  $res ?: null, mb_substr(trim((string)($l['resource_label'] ?? '')), 0, 120) ?: null,
  (int)($l['position'] ?? 0), empty($l['published']) ? 0 : 1,
];
if (!empty($l['id'])) {
  $vals[] = $l['id'];
  db()->prepare('UPDATE lessons SET program_id=?, module_title=?, title=?, kind=?, video_url=?, content=?, resource_url=?, resource_label=?, position=?, published=? WHERE id=?')
      ->execute($vals);
  $id = $l['id'];
} else {
  $id = uuid();
  array_unshift($vals, $id);
  db()->prepare('INSERT INTO lessons (id, program_id, module_title, title, kind, video_url, content, resource_url, resource_label, position, published) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
      ->execute($vals);
}
json_out(200, ['ok' => true, 'id' => $id]);
