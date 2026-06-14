<?php
/** GET /api/pesapal/status.php?orderTrackingId=...  -> verified transaction status */
require __DIR__ . '/_pesapal.php';

try {
  $id = isset($_GET['orderTrackingId']) ? $_GET['orderTrackingId'] : '';
  if ($id === '') json_out(400, ['error' => 'Missing orderTrackingId']);

  $cfg   = pesapal_config();
  $token = pesapal_token($cfg);
  $data  = pesapal_http('GET',
    rtrim($cfg['base_url'], '/') . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($id),
    ['Authorization: Bearer ' . $token]);

  // status_code: 0 INVALID, 1 COMPLETED, 2 FAILED, 3 REVERSED
  json_out(200, [
    'status_code'               => isset($data['status_code']) ? $data['status_code'] : null,
    'payment_status_description'=> isset($data['payment_status_description']) ? $data['payment_status_description'] : null,
    'amount'                    => isset($data['amount']) ? $data['amount'] : null,
    'currency'                  => isset($data['currency']) ? $data['currency'] : null,
    'confirmation_code'         => isset($data['confirmation_code']) ? $data['confirmation_code'] : null,
    'payment_method'            => isset($data['payment_method']) ? $data['payment_method'] : null,
    'merchant_reference'        => isset($data['merchant_reference']) ? $data['merchant_reference'] : null,
    'message'                   => isset($data['message']) ? $data['message'] : null,
  ]);
} catch (Exception $e) {
  json_out(500, ['error' => $e->getMessage()]);
}
