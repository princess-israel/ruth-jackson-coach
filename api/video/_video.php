<?php
/**
 * AI Video Studio — SERVER SIDE ONLY (PHP / cPanel).
 *
 * Talks to Replicate (https://replicate.com) which hosts the top text-to-video
 * models. Pay-per-use: you only pay for videos you actually generate.
 *
 * The API token lives in api/pesapal/config.php ('replicate_token') alongside
 * the other secrets, so it is never committed. See VIDEO-STUDIO-SETUP.md.
 */

if (!function_exists('json_out')) {
  function json_out($code, $arr) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
  }
}

/** Read the Replicate token from config.php, or the REPLICATE_API_TOKEN env var. */
function video_token() {
  $cfgFile = __DIR__ . '/../pesapal/config.php';
  if (file_exists($cfgFile)) {
    $cfg = require $cfgFile;
    if (is_array($cfg) && !empty($cfg['replicate_token'])) return trim((string)$cfg['replicate_token']);
  }
  return getenv('REPLICATE_API_TOKEN') ?: '';
}

/**
 * The allow-list of models the studio may call. Keeping it server-side means a
 * tampered request can never point Replicate (and your bill) at an arbitrary
 * model. Each entry says which inputs that model understands so we only send
 * fields it accepts.
 */
function video_models() {
  return [
    'seedance' => [
      'label'       => 'Seedance 1 Pro — crisp & cinematic',
      'path'        => 'bytedance/seedance-1-pro',
      'aspect'      => ['16:9', '9:16', '1:1'],
      'durations'   => [5, 10],
      'resolutions' => ['480p', '720p', '1080p'],
      'def_res'     => '1080p',
    ],
    'hailuo' => [
      'label'       => 'Hailuo 02 — lifelike motion',
      'path'        => 'minimax/hailuo-02',
      'aspect'      => [],
      'durations'   => [6, 10],
      'resolutions' => ['768p', '1080p'],
      'def_res'     => '1080p',
      'optimizer'   => true,
    ],
    'kling' => [
      'label'       => 'Kling v2.1 Master — photoreal',
      'path'        => 'kwaivgi/kling-v2.1-master',
      'aspect'      => ['16:9', '9:16', '1:1'],
      'durations'   => [5, 10],
      'resolutions' => [],
    ],
    'veo' => [
      'label'       => 'Google Veo 3 Fast — premium (with sound)',
      'path'        => 'google/veo-3-fast',
      'aspect'      => ['16:9', '9:16'],
      'durations'   => [],
      'resolutions' => ['720p', '1080p'],
      'def_res'     => '1080p',
    ],
  ];
}

/**
 * Build the model-specific input object from a validated request. Only fields
 * the chosen model supports are included.
 */
function video_build_input($model, $req) {
  $prompt = trim((string)($req['prompt'] ?? ''));
  $input  = ['prompt' => $prompt];

  // Aspect ratio
  if (!empty($model['aspect'])) {
    $ar = (string)($req['aspect_ratio'] ?? '');
    $input['aspect_ratio'] = in_array($ar, $model['aspect'], true) ? $ar : $model['aspect'][0];
  }
  // Duration (seconds)
  if (!empty($model['durations'])) {
    $d = (int)($req['duration'] ?? 0);
    $input['duration'] = in_array($d, $model['durations'], true) ? $d : $model['durations'][0];
  }
  // Resolution
  if (!empty($model['resolutions'])) {
    $r = (string)($req['resolution'] ?? '');
    $input['resolution'] = in_array($r, $model['resolutions'], true) ? $r : ($model['def_res'] ?? $model['resolutions'][0]);
  }
  // Let the model rewrite terse prompts into richer ones where supported.
  if (!empty($model['optimizer'])) {
    $input['prompt_optimizer'] = true;
  }
  // Optional negative prompt (ignored by models that don't read it).
  $neg = trim((string)($req['negative_prompt'] ?? ''));
  if ($neg !== '') $input['negative_prompt'] = $neg;

  return $input;
}

/** Minimal cURL wrapper for the Replicate REST API. */
function video_http($method, $url, $token, $body = null) {
  if (!function_exists('curl_init')) {
    throw new Exception('PHP cURL extension is not enabled on this server.');
  }
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_TIMEOUT, 40);
  $hdr = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
  ];
  if ($body !== null) {
    $hdr[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
  $resp = curl_exec($ch);
  if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new Exception('Network error contacting the video service: ' . $err);
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return ['code' => $code, 'json' => json_decode($resp, true), 'raw' => $resp];
}

/** Normalise a Replicate prediction into the small shape the front end needs. */
function video_shape($p) {
  if (!is_array($p)) return ['status' => 'failed', 'error' => 'Unexpected response from the video service.'];
  $out = $p['output'] ?? null;
  // Text-to-video models return either a single URL or an array of URLs.
  if (is_array($out)) $out = end($out) ?: null;
  return [
    'id'      => $p['id'] ?? null,
    'status'  => $p['status'] ?? 'unknown',   // starting | processing | succeeded | failed | canceled
    'output'  => $out,
    'error'   => $p['error'] ?? null,
  ];
}
