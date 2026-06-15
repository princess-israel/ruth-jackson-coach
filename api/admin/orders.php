<?php
/**
 * GET /api/admin/orders.php (admin token) -> { orders, users, stats }
 * Real data Ruth can see: who paid, who's pending, who enrolled.
 */
require __DIR__ . '/../_db.php';
require __DIR__ . '/../_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (admin_secret() === '' || !admin_token_valid(bearer_token(read_body())))
  json_out(401, ['error' => 'Not authorised.']);

$pdo = db();
$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
$users  = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$stats = [
  'customers'      => count($users),
  'orders'         => count($orders),
  'paid'           => count(array_filter($orders, fn($o) => $o['status'] === 'COMPLETED')),
  'revenue'        => array_sum(array_map(fn($o) => $o['status'] === 'COMPLETED' ? (float)$o['amount'] : 0, $orders)),
];

json_out(200, ['orders' => $orders, 'users' => $users, 'stats' => $stats]);
