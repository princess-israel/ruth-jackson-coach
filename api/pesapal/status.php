<?php
/** GET /api/pesapal/status.php?orderTrackingId=...  -> verified, persisted transaction status.
 *  This is the authoritative fulfillment path the callback page polls. */
require __DIR__ . '/../_orders.php';

try {
  $id = isset($_GET['orderTrackingId']) ? $_GET['orderTrackingId'] : '';
  if ($id === '') json_out(400, ['error' => 'Missing orderTrackingId']);

  $res = order_verify_and_fulfill($id);
  if (isset($res['error'])) json_out(404, ['error' => $res['error']]);

  // status_code kept for backward-compat with the callback page (1 = COMPLETED).
  $codeMap = ['COMPLETED' => 1, 'FAILED' => 2, 'REVERSED' => 3, 'INVALID' => 0, 'PENDING' => null];
  json_out(200, [
    'status'                     => $res['status'],
    'status_code'                => $codeMap[$res['status']] ?? null,
    'payment_status_description' => $res['status'],
    'amount'                     => $res['amount'] ?? null,
    'currency'                   => $res['currency'] ?? null,
    'confirmation_code'          => $res['confirmation_code'] ?? null,
  ]);
} catch (Exception $e) {
  json_out(500, ['error' => $e->getMessage()]);
}
