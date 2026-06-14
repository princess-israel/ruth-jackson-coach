<?php
/** POST /api/pesapal/pay.php  { programId, email, name, phone }  -> { redirect_url, order_tracking_id, merchant_reference } */
require __DIR__ . '/_pesapal.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

try {
  $raw  = file_get_contents('php://input');
  $body = json_decode($raw, true);
  if (!is_array($body)) $body = $_POST;

  $programId = isset($body['programId']) ? $body['programId'] : '';
  $email     = isset($body['email']) ? trim($body['email']) : '';
  $name      = isset($body['name']) ? trim($body['name']) : 'Customer';
  $phone     = isset($body['phone']) ? trim($body['phone']) : '';

  $programs = pesapal_programs();
  if (!isset($programs[$programId])) json_out(400, ['error' => 'Unknown program.']);
  if ($email === '' && $phone === '') json_out(400, ['error' => 'An email or phone number is required for payment.']);

  $program = $programs[$programId];
  $cfg     = pesapal_config();
  $host    = $_SERVER['HTTP_HOST'];
  $token   = pesapal_token($cfg);
  $ipnId   = pesapal_register_ipn($cfg, $token, $host);

  $merchantRef = $programId . '-' . bin2hex(random_bytes(5));
  $parts = preg_split('/\s+/', $name);
  $first = $parts[0] !== '' ? $parts[0] : 'Customer';
  $last  = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '-';

  $billing = ['first_name' => $first, 'last_name' => $last, 'country_code' => 'KE'];
  if ($email !== '') $billing['email_address'] = $email;
  if ($phone !== '') $billing['phone_number'] = $phone;

  $order = [
    'id'              => $merchantRef,
    'currency'        => 'USD',
    'amount'          => $program['price'],
    'description'     => substr('Enrollment: ' . $program['title'], 0, 100),
    'callback_url'    => 'https://' . $host . '/payment-callback.html',
    'notification_id' => $ipnId,
    'billing_address' => $billing,
  ];

  $result = pesapal_http('POST', rtrim($cfg['base_url'], '/') . '/api/Transactions/SubmitOrderRequest',
    ['Authorization: Bearer ' . $token], $order);

  if (empty($result['redirect_url'])) json_out(502, ['error' => 'Could not create payment.', 'detail' => $result]);

  json_out(200, [
    'redirect_url'      => $result['redirect_url'],
    'order_tracking_id' => isset($result['order_tracking_id']) ? $result['order_tracking_id'] : null,
    'merchant_reference'=> $merchantRef,
  ]);
} catch (Exception $e) {
  json_out(500, ['error' => $e->getMessage()]);
}
