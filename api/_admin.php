<?php
/** Single-admin auth helper. The one admin secret lives in api/pesapal/config.php ('admin_token'). */
function admin_secret() {
  $cfgFile = __DIR__ . '/pesapal/config.php';
  if (file_exists($cfgFile)) {
    $cfg = require $cfgFile;
    if (is_array($cfg) && !empty($cfg['admin_token'])) return (string)$cfg['admin_token'];
  }
  return '';
}
/** The single admin's email. Overridable via 'admin_email' in config.php. */
function admin_email() {
  $cfgFile = __DIR__ . '/pesapal/config.php';
  if (file_exists($cfgFile)) {
    $cfg = require $cfgFile;
    if (is_array($cfg) && !empty($cfg['admin_email'])) return strtolower(trim((string)$cfg['admin_email']));
  }
  return 'info@coachruthjackson.com';
}
/** A session token is the sha256 of the secret, so the raw password is never stored in the browser. */
function admin_session_token() { $s = admin_secret(); return $s === '' ? '' : hash('sha256', $s); }

/** Accept either the raw secret (back-compat) or the session token. */
function admin_token_valid($given) {
  $s = admin_secret();
  if ($s === '') return false;
  $given = (string)$given;
  return hash_equals($s, $given) || hash_equals(hash('sha256', $s), $given);
}
