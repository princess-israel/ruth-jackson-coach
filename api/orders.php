<?php
/**
 * Customer orders API.
 *   GET (customer Bearer) -> { orders: [...] }  the signed-in user's own orders.
 * Lets the dashboard show a payment that is still PENDING (no enrollment yet).
 */
require __DIR__ . '/_db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(405, ['error' => 'Method not allowed']);

$u = user_from_token(bearer_token());
if (!$u) json_out(401, ['error' => 'Not signed in.']);

// Match by user_id, plus any guest orders paid under this email before login.
$s = db()->prepare(
  'SELECT id, merchant_reference, program_id, amount, currency, status, created_at
   FROM orders
   WHERE user_id = ? OR (email IS NOT NULL AND email = ?)
   ORDER BY created_at DESC LIMIT 50');
$s->execute([$u['id'], strtolower($u['email'])]);
json_out(200, ['orders' => $s->fetchAll()]);
