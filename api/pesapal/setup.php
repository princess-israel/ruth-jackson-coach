<?php
/** GET /api/pesapal/setup.php — one-time: registers the IPN URL and returns the ipn_id. */
require __DIR__ . '/_pesapal.php';

try {
  $cfg   = pesapal_config();
  $host  = $_SERVER['HTTP_HOST'];
  $token = pesapal_token($cfg);

  if (!empty($cfg['ipn_id'])) {
    json_out(200, ['ok' => true, 'ipn_id' => $cfg['ipn_id'], 'note' => 'Already set in config.php — nothing to do.']);
  }
  $ipnId = pesapal_register_ipn($cfg, $token, $host);
  json_out(200, [
    'ok'      => true,
    'ipn_id'  => $ipnId,
    'ipn_url' => 'https://' . $host . '/api/pesapal/ipn.php',
    'note'    => "Paste this ipn_id into api/pesapal/config.php (the 'ipn_id' field) so it isn't re-registered.",
  ]);
} catch (Exception $e) {
  json_out(500, ['ok' => false, 'error' => $e->getMessage()]);
}
