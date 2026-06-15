<?php
/** POST /api/auth/login.php { email, password } -> { user, token } */
require __DIR__ . '/../_db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$b = read_body();
$email = isset($b['email']) ? strtolower(trim($b['email'])) : '';
$pass  = isset($b['password']) ? (string)$b['password'] : '';
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($email === '' || $pass === '') json_out(400, ['error' => 'Email and password are required.']);

// Throttle: max 8 failed attempts per IP per 15 minutes.
$cnt = db()->prepare('SELECT COUNT(*) c FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
$cnt->execute([$ip]);
if ((int)$cnt->fetch()['c'] >= 8) json_out(429, ['error' => 'Too many attempts. Please wait 15 minutes and try again.']);

$stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || !password_verify($pass, $u['password_hash'])) {
  db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
  json_out(401, ['error' => 'Incorrect email or password.']);
}

$token = session_create($u['id']);
json_out(200, ['user' => public_user($u), 'token' => $token]);
