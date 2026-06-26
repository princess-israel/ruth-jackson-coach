<?php
/**
 * Record a click on a referral link.
 *   POST { code, path }  (public)
 * Only stores the click if the code belongs to a real affiliate. Best-effort:
 * never blocks the page, returns ok even when ignored.
 */
require __DIR__ . '/_affiliates.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$b = read_body();
$code = strtoupper(trim((string)($b['code'] ?? '')));
$path = substr(trim((string)($b['path'] ?? '')), 0, 190);

if ($code === '' || !aff_find_by_code($code)) {
  json_out(200, ['ok' => true, 'ignored' => true]);
}

try {
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ins = db()->prepare('INSERT INTO affiliate_clicks (id, code, ip, ref_path) VALUES (?, ?, ?, ?)');
  $ins->execute([uuid(), $code, $ip, $path !== '' ? $path : null]);
} catch (Exception $e) {
  // Never surface tracking errors to the visitor.
  json_out(200, ['ok' => true, 'logged' => false]);
}

json_out(200, ['ok' => true]);
