<?php
/** POST /api/pesapal/pay.php  { programId, email, name, phone }  (optional Bearer token to tie the order to a logged-in user)
 *  -> { redirect_url, order_tracking_id, merchant_reference } */
require __DIR__ . '/_pesapal.php';
require __DIR__ . '/../_catalog.php';
require __DIR__ . '/../_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

try {
  $body = read_body();

  $programId = isset($body['programId']) ? $body['programId'] : '';
  $email     = isset($body['email']) ? trim($body['email']) : '';
  $name      = isset($body['name']) ? trim($body['name']) : 'Customer';
  $phone     = isset($body['phone']) ? trim($body['phone']) : '';

  $program = catalog_find($programId);
  if (!$program) json_out(400, ['error' => 'Unknown program.']);
  $program['price'] = isset($program['price']) ? (float)$program['price'] : 0;
  if ($program['price'] <= 0) json_out(400, ['error' => 'This program is not available for online purchase.']);
  if (!isset($program['title'])) $program['title'] = $programId;
  if ($email === '' && $phone === '') json_out(400, ['error' => 'An email or phone number is required for payment.']);

  // Tie the order to a logged-in user when a session token is supplied.
  $user = user_from_token(bearer_token($body));
  if ($user && $email === '') $email = $user['email'];

  $cfg   = pesapal_config();
  $base  = site_url();
  $token = pesapal_token($cfg);
  $ipnId = pesapal_register_ipn($cfg, $token, $base);

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
    'callback_url'    => $base . '/payment-callback.html',
    'notification_id' => $ipnId,
    'billing_address' => $billing,
  ];

  $result = pesapal_http('POST', rtrim($cfg['base_url'], '/') . '/api/Transactions/SubmitOrderRequest',
    ['Authorization: Bearer ' . $token], $order);

  if (empty($result['redirect_url'])) json_out(502, ['error' => 'Could not create payment.', 'detail' => $result]);

  $trackingId = isset($result['order_tracking_id']) ? $result['order_tracking_id'] : null;

  // Persist the PENDING order — the authoritative record that this checkout happened.
  $ins = db()->prepare(
    'INSERT INTO orders (id, merchant_reference, order_tracking_id, user_id, email, phone, program_id, amount, currency, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, "USD", "PENDING")');
  $ins->execute([
    uuid(), $merchantRef, $trackingId,
    $user ? $user['id'] : null,
    $email !== '' ? strtolower($email) : null,
    $phone !== '' ? $phone : null,
    $programId, $program['price'],
  ]);

  json_out(200, [
    'redirect_url'       => $result['redirect_url'],
    'order_tracking_id'  => $trackingId,
    'merchant_reference' => $merchantRef,
  ]);
} catch (Exception $e) {
  json_out(500, ['error' => $e->getMessage()]);
}
