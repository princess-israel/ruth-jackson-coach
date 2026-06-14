<?php
/**
 * Copy this file to  config.php  (same folder) and fill in your real Pesapal keys.
 * config.php is gitignored so your secrets are never committed.
 */
return [
  'consumer_key'    => 'YOUR_PESAPAL_CONSUMER_KEY',
  'consumer_secret' => 'YOUR_PESAPAL_CONSUMER_SECRET',

  // Live (default). For testing use: https://cybqa.pesapal.com/pesapalv3
  'base_url'        => 'https://pay.pesapal.com/v3',

  // Leave blank. After visiting /api/pesapal/setup.php once, paste the
  // returned ipn_id here so the IPN isn't re-registered on every payment.
  'ipn_id'          => '',
];
