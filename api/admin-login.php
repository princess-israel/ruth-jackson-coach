<?php
/** POST /api/admin-login.php { password } -> { ok:true, token } when the password matches the single admin secret. */
require __DIR__ . '/_admin.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;
$pw = isset($body['password']) ? (string)$body['password'] : '';
$email = isset($body['email']) ? strtolower(trim((string)$body['email'])) : '';

$secret = admin_secret();
if ($secret === '') {
  http_response_code(403);
  echo json_encode(['error' => "Admin not set up. Add  'admin_token' => 'your-secret'  to api/pesapal/config.php."]);
  exit;
}
// tiny delay to blunt brute-forcing
usleep(300000);
$emailOk = hash_equals(admin_email(), $email);
$pwOk = hash_equals($secret, $pw);
if (!$emailOk || !$pwOk) {
  http_response_code(401);
  echo json_encode(['error' => 'Incorrect admin email or password.']);
  exit;
}
echo json_encode(['ok' => true, 'token' => admin_session_token()]);
