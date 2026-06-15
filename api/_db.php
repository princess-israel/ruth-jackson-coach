<?php
/**
 * Shared DB layer + small helpers used by every API endpoint.
 * DB credentials live in api/pesapal/config.php under the 'db' key (gitignored).
 */

/** Shared JSON responder. Guarded so it can coexist with _pesapal.php's copy. */
if (!function_exists('json_out')) {
  function json_out($code, $arr) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
  }
}

/** RFC4122-ish v4 uuid. */
function uuid() {
  $d = random_bytes(16);
  $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
  $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

function app_config() {
  static $cfg = null;
  if ($cfg !== null) return $cfg;
  $file = __DIR__ . '/pesapal/config.php';
  $cfg = file_exists($file) ? require $file : [];
  if (!is_array($cfg)) $cfg = [];
  return $cfg;
}

/** Base site URL, e.g. https://coachruthjackson.com (no trailing slash). */
function site_url() {
  $cfg = app_config();
  if (!empty($cfg['site_url'])) return rtrim($cfg['site_url'], '/');
  // Fallback only if site_url isn't configured. Prefer the config value.
  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
  return 'https://' . $host;
}

function db() {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $cfg = app_config();
  $db = isset($cfg['db']) ? $cfg['db'] : null;
  if (!$db || empty($db['name'])) {
    json_out(500, ['error' => 'Database is not configured. Add a "db" block to api/pesapal/config.php.']);
  }
  $host = isset($db['host']) ? $db['host'] : 'localhost';
  $dsn = "mysql:host={$host};dbname={$db['name']};charset=utf8mb4";
  try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
  } catch (PDOException $e) {
    // TEMP DIAGNOSTIC: surface the real reason so we can fix credentials. Revert after.
    json_out(500, ['error' => 'Database connection failed.', 'detail' => $e->getMessage(), 'using' => ['host' => $host, 'name' => $db['name'], 'user' => $db['user']]]);
  }
  return $pdo;
}

/** Read the bearer token from the Authorization header or a JSON/POST body field. */
function bearer_token($body = null) {
  $hdr = '';
  if (!empty($_SERVER['HTTP_AUTHORIZATION'])) $hdr = $_SERVER['HTTP_AUTHORIZATION'];
  elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $hdr = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
  elseif (function_exists('apache_request_headers')) {
    $h = apache_request_headers();
    foreach ($h as $k => $v) if (strtolower($k) === 'authorization') $hdr = $v;
  }
  if (stripos($hdr, 'Bearer ') === 0) return trim(substr($hdr, 7));
  if (is_array($body) && !empty($body['token'])) return (string)$body['token'];
  return '';
}

/** Create a 30-day session for a user and return the opaque token. */
function session_create($userId) {
  $token = bin2hex(random_bytes(32));
  $stmt = db()->prepare('INSERT INTO sessions (token, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))');
  $stmt->execute([$token, $userId]);
  return $token;
}

/** Return the user row for a valid, unexpired session token, or null. */
function user_from_token($token) {
  if (!$token) return null;
  $stmt = db()->prepare(
    'SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id
     WHERE s.token = ? AND s.expires_at > NOW() LIMIT 1');
  $stmt->execute([$token]);
  $u = $stmt->fetch();
  return $u ?: null;
}

/** Strip the password hash before sending a user to the client. */
function public_user($u) {
  if (!$u) return null;
  return ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
}

/** Read JSON body (falls back to form POST). */
function read_body() {
  $raw = file_get_contents('php://input');
  $b = json_decode($raw, true);
  return is_array($b) ? $b : $_POST;
}
