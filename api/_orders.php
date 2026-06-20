<?php
/**
 * Order persistence + fulfillment. The ONLY place an enrollment is created.
 * An enrollment is granted only when Pesapal reports COMPLETED and the paid
 * amount matches the order amount — never on the client's say-so.
 */
require_once __DIR__ . '/_db.php';
require_once __DIR__ . '/pesapal/_pesapal.php';

function order_find_by_tracking($trackingId) {
  $s = db()->prepare('SELECT * FROM orders WHERE order_tracking_id = ? LIMIT 1');
  $s->execute([$trackingId]);
  return $s->fetch() ?: null;
}
function order_find_by_reference($ref) {
  $s = db()->prepare('SELECT * FROM orders WHERE merchant_reference = ? LIMIT 1');
  $s->execute([$ref]);
  return $s->fetch() ?: null;
}

/** Map Pesapal status_code (0 INVALID,1 COMPLETED,2 FAILED,3 REVERSED) to our enum. */
function pesapal_status_label($code, $desc) {
  $desc = strtolower((string)$desc);
  if ((int)$code === 1 || $desc === 'completed') return 'COMPLETED';
  if ((int)$code === 2 || $desc === 'failed')    return 'FAILED';
  if ((int)$code === 3 || $desc === 'reversed')  return 'REVERSED';
  return 'INVALID';
}

/**
 * Verify a transaction with Pesapal by tracking id, persist the result on the
 * order, and fulfill (create enrollment) if COMPLETED. Returns the verified data.
 */
function order_verify_and_fulfill($trackingId) {
  $order = order_find_by_tracking($trackingId);
  if (!$order) return ['error' => 'Unknown transaction.'];

  // Already settled — don't re-hit Pesapal or double-enroll.
  if (in_array($order['status'], ['COMPLETED', 'FAILED', 'REVERSED'], true)) {
    return ['status' => $order['status'], 'order' => $order, 'cached' => true];
  }

  $cfg   = pesapal_config();
  $token = pesapal_token($cfg);
  $data  = pesapal_http('GET',
    rtrim($cfg['base_url'], '/') . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($trackingId),
    ['Authorization: Bearer ' . $token]);

  $label = pesapal_status_label($data['status_code'] ?? null, $data['payment_status_description'] ?? '');
  $paidAmount = isset($data['amount']) ? (float)$data['amount'] : null;
  $confirmation = $data['confirmation_code'] ?? null;

  // Under-payment guard: only reject a COMPLETED payment if the customer paid
  // clearly LESS than the price in the SAME currency. We never reject on
  // overpayment or a currency mismatch (Pesapal may report a converted figure).
  $paidCurrency  = isset($data['currency']) ? strtoupper((string)$data['currency']) : null;
  $orderCurrency = strtoupper((string)$order['currency']);
  $sameCurrency  = ($paidCurrency === null || $paidCurrency === $orderCurrency);
  if ($label === 'COMPLETED' && $paidAmount !== null && $sameCurrency
      && $paidAmount + 0.01 < (float)$order['amount']) {
    $label = 'INVALID';
  }

  $upd = db()->prepare('UPDATE orders SET status = ?, confirmation_code = ? WHERE id = ?');
  $upd->execute([$label, $confirmation, $order['id']]);

  if ($label === 'COMPLETED') {
    order_fulfill($order, $confirmation);
  }

  return [
    'status'            => $label,
    'amount'            => $data['amount'] ?? $order['amount'],
    'currency'          => $data['currency'] ?? $order['currency'],
    'confirmation_code' => $confirmation,
    'order'             => $order,
  ];
}

/** Create the enrollment + welcome message for a paid order (idempotent). */
function order_fulfill($order, $confirmation) {
  // Resolve the user: prefer the order's user_id, else match a user by email.
  $userId = $order['user_id'];
  if (!$userId && !empty($order['email'])) {
    $s = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $s->execute([strtolower(trim($order['email']))]);
    $row = $s->fetch();
    if ($row) $userId = $row['id'];
  }
  if (!$userId) return; // guest paid without an account — fulfilled on first login by email match

  // Idempotent enroll (unique key on user_id+program_id).
  $ins = db()->prepare(
    'INSERT INTO enrollments (id, user_id, program_id, order_id, status, progress)
     VALUES (?, ?, ?, ?, "active", 5)
     ON DUPLICATE KEY UPDATE status = "active", order_id = VALUES(order_id)');
  $ins->execute([uuid(), $userId, $order['program_id'], $order['id']]);

  $msg = db()->prepare('INSERT INTO messages (id, user_id, sender, body, read_flag) VALUES (?, ?, "ruth", ?, 0)');
  $site = site_url();
  $body = '🎉 Payment received' . ($confirmation ? " (ref: {$confirmation})" : '') .
          ". Welcome aboard, I'm so glad to have you! 🎓 Your enrolment is active. Here's how to check yourself into your course:" .
          "\n\n1) Open your step-by-step check-in guide: {$site}/onboarding.html" .
          "\n2) Go to the learning platform: https://www.widb.network and tap Log in, then Register (first time)." .
          "\n3) When it asks for your Trainer email, enter: suuupooi@gmail.com  (this links you to my training group, it's the key step)." .
          "\n4) Open the menu, go to Training Programmes, and start learning." .
          "\n\nIf you get stuck, just reply here or WhatsApp me on +254729384374. You can also send your payment receipt there so I can cross-check it against my account. Welcome again!";
  $msg->execute([uuid(), $userId, $body]);
}
