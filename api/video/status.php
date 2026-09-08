<?php
/**
 * GET /api/video/status.php?id=<prediction_id>&token=<admin_token>
 *   -> { status, output?, error? }
 *
 * Polled by the studio every few seconds while a video renders. Admin-only.
 */
require __DIR__ . '/_video.php';
require __DIR__ . '/../_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  json_out(405, ['error' => 'Method not allowed']);
}

// --- auth (token via query string or Authorization: Bearer header) ---
$given = $_GET['token'] ?? '';
if ($given === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
  $given = trim(preg_replace('/^Bearer\s+/i', '', $_SERVER['HTTP_AUTHORIZATION']));
}
if (admin_secret() === '' || !admin_token_valid($given)) {
  json_out(401, ['error' => 'Not authorised.']);
}

$id = preg_replace('/[^a-zA-Z0-9]/', '', (string)($_GET['id'] ?? ''));
if ($id === '') {
  json_out(400, ['error' => 'Missing job id.']);
}

$token = video_token();
if ($token === '') {
  json_out(403, ['error' => 'Video service not set up.']);
}

try {
  $res = video_http('GET', 'https://api.replicate.com/v1/predictions/' . $id, $token);
} catch (Exception $e) {
  json_out(502, ['error' => $e->getMessage()]);
}

if ($res['code'] >= 400 || !is_array($res['json'])) {
  json_out(502, ['error' => 'Could not read the video status.']);
}

echo json_encode(video_shape($res['json']));
