<?php
/**
 * GET  /api/programs.php            -> { programs: [...] }   (public; used by the site)
 * POST /api/programs.php            -> add/edit/delete a program (requires admin token)
 *   body: { token, action: "save"|"delete", program?: {...}, id?: "..." }
 *
 * The admin token is read from api/pesapal/config.php ('admin_token').
 */
require __DIR__ . '/_catalog.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode(['programs' => catalog_load()]);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;

// --- auth ---
$adminToken = '';
$cfgFile = __DIR__ . '/pesapal/config.php';
if (file_exists($cfgFile)) {
  $cfg = require $cfgFile;
  if (is_array($cfg) && !empty($cfg['admin_token'])) $adminToken = (string)$cfg['admin_token'];
}
if ($adminToken === '') {
  http_response_code(403);
  echo json_encode(['error' => "No admin token configured. Add  'admin_token' => 'your-secret'  to api/pesapal/config.php."]);
  exit;
}
$given = isset($body['token']) ? (string)$body['token'] : '';
if (!hash_equals($adminToken, $given)) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid admin token.']);
  exit;
}

// --- mutate ---
$programs = catalog_load();
$action = isset($body['action']) ? $body['action'] : 'save';

if ($action === 'delete') {
  $id = isset($body['id']) ? $body['id'] : '';
  $programs = array_values(array_filter($programs, function ($p) use ($id) {
    return !isset($p['id']) || $p['id'] !== $id;
  }));
} else {
  $p = isset($body['program']) ? $body['program'] : null;
  if (!$p || empty($p['id'])) {
    http_response_code(400); echo json_encode(['error' => 'A program id and details are required.']); exit;
  }
  // normalise
  $p['id']      = preg_replace('/[^a-z0-9\-]/', '', strtolower($p['id']));
  $p['price']   = isset($p['price']) ? round((float)$p['price'], 2) : 0;
  $p['tags']    = isset($p['tags']) && is_array($p['tags']) ? array_values($p['tags']) : [];
  $p['outcomes']= isset($p['outcomes']) && is_array($p['outcomes']) ? array_values($p['outcomes']) : [];
  if (empty($p['icon'])) $p['icon'] = '📘';

  $found = false;
  foreach ($programs as $i => $existing) {
    if (isset($existing['id']) && $existing['id'] === $p['id']) {
      $programs[$i] = array_merge($existing, $p);
      $found = true; break;
    }
  }
  if (!$found) $programs[] = $p;
}

if (!catalog_save($programs)) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not write catalog file. Check that the data/ folder is writable.']);
  exit;
}
echo json_encode(['ok' => true, 'programs' => $programs]);
