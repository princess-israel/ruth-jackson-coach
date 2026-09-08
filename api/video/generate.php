<?php
/**
 * POST /api/video/generate.php
 *   body: { token, model, prompt, aspect_ratio?, duration?, resolution?, negative_prompt? }
 *   -> { ok:true, id, status } | { error }
 *
 * Admin-only: generating a video costs money, so it is gated behind the same
 * single-admin secret used everywhere else on the site.
 */
require __DIR__ . '/_video.php';
require __DIR__ . '/../_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(405, ['error' => 'Method not allowed']);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;

// --- auth ---
if (admin_secret() === '') {
  json_out(403, ['error' => "No admin secret configured. Add  'admin_token' => 'your-secret'  to api/pesapal/config.php."]);
}
if (!admin_token_valid($body['token'] ?? '')) {
  json_out(401, ['error' => 'Not authorised. Please sign in to the studio again.']);
}

$token = video_token();
if ($token === '') {
  json_out(403, ['error' => "Video service not set up. Add  'replicate_token' => 'r8_...'  to api/pesapal/config.php. See VIDEO-STUDIO-SETUP.md."]);
}

// --- validate request ---
$prompt = trim((string)($body['prompt'] ?? ''));
if ($prompt === '') {
  json_out(400, ['error' => 'Please describe the video you want to create.']);
}
if (mb_strlen($prompt) > 2000) {
  json_out(400, ['error' => 'That description is too long. Keep it under 2000 characters.']);
}

$models = video_models();
$key = (string)($body['model'] ?? '');
if (!isset($models[$key])) {
  json_out(400, ['error' => 'Unknown video model.']);
}
$model = $models[$key];
$input = video_build_input($model, $body);

// --- kick off the generation ---
try {
  $url = 'https://api.replicate.com/v1/models/' . $model['path'] . '/predictions';
  $res = video_http('POST', $url, $token, ['input' => $input]);
} catch (Exception $e) {
  json_out(502, ['error' => $e->getMessage()]);
}

if ($res['code'] === 401 || $res['code'] === 403) {
  json_out(502, ['error' => 'The video service rejected the API token. Check replicate_token in config.php.']);
}
if ($res['code'] >= 400 || !is_array($res['json'])) {
  $detail = is_array($res['json']) && !empty($res['json']['detail']) ? $res['json']['detail'] : 'The video service could not start this generation.';
  json_out(502, ['error' => $detail]);
}

$shaped = video_shape($res['json']);
if (empty($shaped['id'])) {
  json_out(502, ['error' => 'The video service did not return a job id.']);
}

echo json_encode(['ok' => true, 'id' => $shaped['id'], 'status' => $shaped['status']]);
