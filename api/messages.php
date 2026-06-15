<?php
/**
 * Messages API (customer <-> Ruth).
 *   GET  (customer Bearer)               -> { messages: [...] }  (my thread; marks Ruth's msgs read)
 *   GET  ?threads=1 (admin)              -> { threads: [...] }   (one row per customer)
 *   GET  ?userId=.. (admin)              -> { messages: [...] }  (that thread; marks customer msgs read)
 *   POST (customer Bearer) { body }      -> add a customer message
 *   POST (admin) { userId, body }        -> add a message from Ruth
 */
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$b = read_body();
$token = bearer_token($b);
$isAdmin = admin_secret() !== '' && admin_token_valid($token);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['threads']) && $isAdmin) {
    $rows = $pdo->query(
      'SELECT m.user_id, u.name, u.email,
              SUBSTRING_INDEX(GROUP_CONCAT(m.body ORDER BY m.created_at DESC SEPARATOR "\\n"), "\\n", 1) AS last_body,
              MAX(m.created_at) AS last_at,
              SUM(m.sender = "customer" AND m.read_flag = 0) AS unread
       FROM messages m JOIN users u ON u.id = m.user_id
       GROUP BY m.user_id, u.name, u.email ORDER BY last_at DESC')->fetchAll();
    json_out(200, ['threads' => $rows]);
  }
  if (isset($_GET['userId']) && $isAdmin) {
    $uid = $_GET['userId'];
    $pdo->prepare('UPDATE messages SET read_flag = 1 WHERE user_id = ? AND sender = "customer"')->execute([$uid]);
    $s = $pdo->prepare('SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC');
    $s->execute([$uid]);
    json_out(200, ['messages' => $s->fetchAll()]);
  }
  $u = user_from_token($token);
  if (!$u) json_out(401, ['error' => 'Not signed in.']);
  $pdo->prepare('UPDATE messages SET read_flag = 1 WHERE user_id = ? AND sender = "ruth"')->execute([$u['id']]);
  $s = $pdo->prepare('SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC');
  $s->execute([$u['id']]);
  json_out(200, ['messages' => $s->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$body = trim((string)($b['body'] ?? ''));
if ($body === '') json_out(400, ['error' => 'Message is empty.']);

if ($isAdmin) {
  $uid = $b['userId'] ?? '';
  if ($uid === '') json_out(400, ['error' => 'userId required.']);
  $pdo->prepare('INSERT INTO messages (id, user_id, sender, body, read_flag) VALUES (?, ?, "ruth", ?, 0)')
      ->execute([uuid(), $uid, $body]);
  json_out(200, ['ok' => true]);
}

$u = user_from_token($token);
if (!$u) json_out(401, ['error' => 'Not signed in.']);
$pdo->prepare('INSERT INTO messages (id, user_id, sender, body, read_flag) VALUES (?, ?, "customer", ?, 0)')
    ->execute([uuid(), $u['id'], $body]);
json_out(200, ['ok' => true]);
