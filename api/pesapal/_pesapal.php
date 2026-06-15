<?php
/**
 * Pesapal API 3.0 helper — SERVER SIDE ONLY (PHP / cPanel).
 * Credentials are read from config.php (copy config.sample.php -> config.php).
 */

function pesapal_config() {
  $dir = __DIR__;
  if (file_exists($dir . '/config.php')) {
    return require $dir . '/config.php';
  }
  // Fallback to environment variables if no config.php is present.
  return [
    'consumer_key'    => getenv('PESAPAL_CONSUMER_KEY') ?: '',
    'consumer_secret' => getenv('PESAPAL_CONSUMER_SECRET') ?: '',
    'base_url'        => getenv('PESAPAL_BASE_URL') ?: 'https://pay.pesapal.com/v3',
    'ipn_id'          => getenv('PESAPAL_IPN_ID') ?: '',
  ];
}

if (!function_exists('json_out')) {
  function json_out($code, $arr) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
  }
}

function pesapal_http($method, $url, $headers = [], $body = null) {
  if (!function_exists('curl_init')) {
    throw new Exception('PHP cURL extension is not enabled on this server.');
  }
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  $hdr = array_merge(['Accept: application/json'], $headers);
  if ($body !== null) {
    $hdr[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
  $resp = curl_exec($ch);
  if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new Exception('Network error contacting Pesapal: ' . $err);
  }
  curl_close($ch);
  return json_decode($resp, true);
}

function pesapal_token($cfg) {
  if (empty($cfg['consumer_key']) || empty($cfg['consumer_secret'])) {
    throw new Exception('Missing Pesapal consumer key/secret. Edit api/pesapal/config.php.');
  }
  $data = pesapal_http('POST', rtrim($cfg['base_url'], '/') . '/api/Auth/RequestToken', [], [
    'consumer_key'    => $cfg['consumer_key'],
    'consumer_secret' => $cfg['consumer_secret'],
  ]);
  if (empty($data['token'])) {
    $msg = isset($data['error']['message']) ? $data['error']['message'] : json_encode($data);
    throw new Exception('Pesapal authentication failed: ' . $msg);
  }
  return $data['token'];
}

function pesapal_register_ipn($cfg, $token, $base) {
  if (!empty($cfg['ipn_id'])) return $cfg['ipn_id'];
  $url = rtrim($base, '/') . '/api/pesapal/ipn.php';
  $data = pesapal_http('POST', rtrim($cfg['base_url'], '/') . '/api/URLSetup/RegisterIPN',
    ['Authorization: Bearer ' . $token],
    ['url' => $url, 'ipn_notification_type' => 'GET']);
  if (empty($data['ipn_id'])) {
    throw new Exception('IPN registration failed: ' . json_encode($data));
  }
  return $data['ipn_id'];
}

/** Server-side price authority — the client cannot change the charged amount. */
function pesapal_programs() {
  return [
    'ai-women-entrepreneurs'   => ['title' => 'Artificial Intelligence for Women Entrepreneurs', 'price' => 79],
    'digital-marketing-social' => ['title' => 'Digital Marketing & Social Media Management',     'price' => 79],
    'website-development'       => ['title' => 'Build Your Business Website',                       'price' => 79],
    'seo-online-visibility'     => ['title' => 'SEO & Online Visibility',                           'price' => 79],
    'data-analysis-growth'      => ['title' => 'Data Analysis for Business Growth',                 'price' => 79],
    'ecommerce-selling-online'  => ['title' => 'E-Commerce & Selling Online',                       'price' => 79],
  ];
}
