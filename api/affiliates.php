<?php
/**
 * Affiliate / marketer registration.
 *   POST (public): register a new affiliate, returns their referral code + link.
 *   GET  (admin):  list all affiliates (requires admin token).
 * Stored as a JSON file in data/affiliates.json (no DB table needed).
 */
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';

header('Content-Type: application/json');

function aff_file() { return __DIR__ . '/../data/affiliates.json'; }

function aff_load() {
  $f = aff_file();
  if (!file_exists($f)) return [];
  $a = json_decode(file_get_contents($f), true);
  return is_array($a) ? $a : [];
}

function aff_save($list) {
  return file_put_contents(
    aff_file(),
    json_encode(array_values($list), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    LOCK_EX
  ) !== false;
}

/** Build a short, human-friendly referral code from a name plus randomness. */
function aff_code($name, $existing) {
  $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
  $base = $base === '' ? 'RUTH' : substr($base, 0, 6);
  do {
    $code = $base . random_int(100, 999);
  } while (in_array($code, $existing, true));
  return $code;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // Admin-only listing.
  $token = isset($_GET['token']) ? $_GET['token'] : bearer_token();
  if (!admin_token_valid($token)) json_out(401, ['error' => 'Unauthorized']);
  json_out(200, ['affiliates' => aff_load()]);
}

if ($method === 'POST') {
  $b = read_body();
  $name  = trim((string)($b['name']  ?? ''));
  $email = strtolower(trim((string)($b['email'] ?? '')));
  $phone = trim((string)($b['phone'] ?? ''));
  $reach = trim((string)($b['reach'] ?? ''));   // where they'll promote (socials, audience)
  $audience = trim((string)($b['audience'] ?? ''));

  if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
    json_out(422, ['error' => 'Please provide your name, a valid email and a phone number.']);
  }

  $list = aff_load();
  foreach ($list as $a) {
    if (strtolower($a['email']) === $email) {
      json_out(200, ['ok' => true, 'code' => $a['code'], 'returning' => true,
        'link' => site_url() . '/?ref=' . $a['code']]);
    }
  }

  $code = aff_code($name, array_column($list, 'code'));
  $entry = [
    'id'       => uuid(),
    'name'     => $name,
    'email'    => $email,
    'phone'    => $phone,
    'reach'    => $reach,
    'audience' => $audience,
    'code'     => $code,
    'status'   => 'pending',
    'created'  => date('c'),
  ];
  $list[] = $entry;
  if (!aff_save($list)) json_out(500, ['error' => 'Could not save your registration. Please try again.']);

  json_out(200, ['ok' => true, 'code' => $code, 'link' => site_url() . '/?ref=' . $code]);
}

json_out(405, ['error' => 'Method not allowed']);
