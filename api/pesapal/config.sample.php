<?php
/**
 * Copy this file to  config.php  (same folder) and fill in your real values.
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

  // Canonical public URL of the site. Used to build the callback + IPN URLs
  // instead of trusting the (spoofable) Host header.
  'site_url'        => 'https://coachruthjackson.com',

  // Admin login (admin.html / write APIs).
  'admin_email'     => 'info@coachruthjackson.com',
  'admin_token'     => 'change-this-to-a-strong-secret',

  // MySQL (cPanel -> MySQL Databases). Use the prefixed names cPanel shows you,
  // e.g. irelandc_ruth. The DB user must be added to the DB with ALL PRIVILEGES.
  'db' => [
    'host' => 'localhost',
    'name' => 'irelandc_ruth',
    'user' => 'irelandc_ruth',
    'pass' => 'STRONG_DB_PASSWORD',
  ],
];
