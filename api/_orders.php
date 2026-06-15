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

  // Amount-tampering guard: a COMPLETED payment must match the order amount.
  if ($label === 'COMPLETED' && $paidAmount !== null && abs($paidAmount - (float)$order['amount']) > 0.01) {
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
  $body = '🎉 Payment received' . ($confirmation ? " (ref: {$confirmation})" : '') .
          ". Thank you for enrolling! I'm preparing your private access link and getting-started guide — you'll receive it right here shortly. Reply anytime with questions!";
  $msg->execute([uuid(), $userId, $body]);
}
