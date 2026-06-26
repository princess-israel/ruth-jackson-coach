<?php
/**
 * Shared affiliate helpers: the JSON-backed registry (data/affiliates.json)
 * plus the commission rate. Functions are guarded so this can be included
 * anywhere without colliding with api/affiliates.php (which has its own copies).
 */
require_once __DIR__ . '/_db.php';

if (!defined('AFF_COMMISSION_RATE')) define('AFF_COMMISSION_RATE', 0.50); // 50% of what the client paid

if (!function_exists('aff_file')) {
  function aff_file() { return __DIR__ . '/../data/affiliates.json'; }
}
if (!function_exists('aff_load')) {
  function aff_load() {
    $f = aff_file();
    if (!file_exists($f)) return [];
    $a = json_decode(file_get_contents($f), true);
    return is_array($a) ? $a : [];
  }
}

/** Find an affiliate by referral code (case-insensitive). */
function aff_find_by_code($code) {
  $code = strtoupper(trim((string)$code));
  if ($code === '') return null;
  foreach (aff_load() as $a) {
    if (isset($a['code']) && strtoupper($a['code']) === $code) return $a;
  }
  return null;
}

/** Validate an affiliate by email + referral code (their dashboard login). */
function aff_find_by_email_code($email, $code) {
  $email = strtolower(trim((string)$email));
  $code  = strtoupper(trim((string)$code));
  if ($email === '' || $code === '') return null;
  foreach (aff_load() as $a) {
    if (strtolower((string)($a['email'] ?? '')) === $email
        && strtoupper((string)($a['code'] ?? '')) === $code) return $a;
  }
  return null;
}
