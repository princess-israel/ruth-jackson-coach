<?php
/**
 * Enrollments API (server-authoritative).
 *   GET  (customer Bearer)            -> { enrollments: [...] }  (mine)
 *   GET  ?all=1 (admin token)         -> { enrollments: [...] }  (all, with user info)
 *   POST (admin token) { action:"status"|"progress", id, value }
 */
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$b = read_body();
$token = bearer_token($b);
$isAdmin = admin_secret() !== '' && admin_token_valid($token);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['all']) && $isAdmin) {
    $rows = db()->query(
      'SELECT e.*, u.name AS user_name, u.email AS user_email
       FROM enrollments e JOIN users u ON u.id = e.user_id ORDER BY e.created_at DESC')->fetchAll();
    json_out(200, ['enrollments' => $rows]);
  }
  $u = user_from_token($token);
  if (!$u) json_out(401, ['error' => 'Not signed in.']);
  $s = db()->prepare('SELECT * FROM enrollments WHERE user_id = ? ORDER BY created_at DESC');
  $s->execute([$u['id']]);
  json_out(200, ['enrollments' => $s->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);
if (!$isAdmin) json_out(401, ['error' => 'Not authorised.']);

$action = $b['action'] ?? '';
$id     = $b['id'] ?? '';
if ($action === 'status') {
  $val = in_array($b['value'] ?? '', ['active','pending','revoked'], true) ? $b['value'] : 'active';
  db()->prepare('UPDATE enrollments SET status = ? WHERE id = ?')->execute([$val, $id]);
} elseif ($action === 'progress') {
  $val = max(0, min(100, (int)($b['value'] ?? 0)));
  db()->prepare('UPDATE enrollments SET progress = ? WHERE id = ?')->execute([$val, $id]);
} else {
  json_out(400, ['error' => 'Unknown action.']);
}
json_out(200, ['ok' => true]);
