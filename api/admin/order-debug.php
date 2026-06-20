<?php
/**
 * GET /api/admin/order-debug.php?orderTrackingId=...  (admin token)
 * Returns the RAW Pesapal GetTransactionStatus plus our stored order row,
 * so we can see exactly why a payment is/ isn't being marked COMPLETED.
 * If no orderTrackingId is given, uses the most recent PENDING/INVALID order.
 */
require __DIR__ . '/../_orders.php';
require __DIR__ . '/../_admin.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (admin_secret() === '' || !admin_token_valid(bearer_token(read_body())))
  json_out(401, ['error' => 'Not authorised.']);

$id = $_GET['orderTrackingId'] ?? '';
if ($id === '') {
  $row = db()->query(
    "SELECT order_tracking_id FROM orders
     WHERE status IN ('PENDING','INVALID') AND order_tracking_id IS NOT NULL
     ORDER BY created_at DESC LIMIT 1")->fetch();
  $id = $row['order_tracking_id'] ?? '';
}
if ($id === '') json_out(404, ['error' => 'No pending order to diagnose.']);

try {
  $cfg   = pesapal_config();
  $token = pesapal_token($cfg);
  $data  = pesapal_http('GET',
    rtrim($cfg['base_url'], '/') . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($id),
    ['Authorization: Bearer ' . $token]);
  $order = order_find_by_tracking($id);
  json_out(200, [
    'orderTrackingId' => $id,
    'pesapal_says'    => $data,
    'our_order'       => $order ? [
      'merchant_reference' => $order['merchant_reference'],
      'program_id'         => $order['program_id'],
      'amount'             => $order['amount'],
      'currency'           => $order['currency'],
      'status'             => $order['status'],
    ] : null,
  ]);
} catch (Exception $e) {
  json_out(500, ['error' => $e->getMessage()]);
}
