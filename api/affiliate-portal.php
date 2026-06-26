<?php
/**
 * Affiliate self-service portal. Authenticated with the user's normal account
 * session (Authorization: Bearer <token>). The affiliate is the logged-in user.
 *   POST { action: "login" | "stats" | "payout" }
 */
require __DIR__ . '/_affiliates.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['error' => 'Method not allowed']);

$b      = read_body();
$action = (string)($b['action'] ?? '');

$user = user_from_token(bearer_token($b));
if (!$user) json_out(401, ['error' => 'Please sign in to view your affiliate dashboard.', 'need_auth' => true]);

$aff = aff_find_by_user($user);
if (!$aff) json_out(200, ['ok' => true, 'affiliate' => null, 'not_affiliate' => true]);

$code = strtoupper((string)$aff['code']);
$profile = [
  'name'    => $aff['name'] ?? '',
  'email'   => $aff['email'] ?? '',
  'phone'   => $aff['phone'] ?? '',
  'code'    => $code,
  'status'  => $aff['status'] ?? 'pending',
  'link'    => site_url() . '/?ref=' . $code,
  'payment' => aff_payment($aff),
];

if ($action === 'login') {
  json_out(200, ['ok' => true, 'affiliate' => $profile]);
}

if ($action === 'save_payment') {
  $method = (($b['method'] ?? '') === 'bank') ? 'bank' : 'mpesa';
  $fields = ['payment_method' => $method];
  if ($method === 'mpesa') {
    $mp = trim((string)($b['mpesa_phone'] ?? ''));
    if ($mp === '') json_out(422, ['error' => 'Enter your M-Pesa phone number.']);
    $fields['mpesa_phone'] = $mp;
  } else {
    $bn = trim((string)($b['bank_name'] ?? ''));
    $ba = trim((string)($b['bank_account'] ?? ''));
    $bh = trim((string)($b['bank_holder'] ?? ''));
    if ($bn === '' || $ba === '') json_out(422, ['error' => 'Enter your bank name and account number.']);
    $fields['bank_name'] = $bn; $fields['bank_account'] = $ba; $fields['bank_holder'] = $bh;
  }
  aff_update_by_code($code, $fields);
  json_out(200, ['ok' => true, 'payment' => aff_payment(aff_find_by_code($code))]);
}

if ($action === 'stats') {
  json_out(200, ['ok' => true, 'affiliate' => $profile] + aff_stats($code));
}

if ($action === 'payout') {
  // One outstanding request at a time, so a payout maps cleanly to its commissions.
  $pend = db()->prepare("SELECT COUNT(*) c FROM affiliate_payouts WHERE code = ? AND status = 'requested'");
  $pend->execute([$code]);
  if ((int)$pend->fetch()['c'] > 0) {
    json_out(400, ['error' => 'You already have a payout request being processed. Ruth will send it shortly.']);
  }
  if (!aff_payment($aff)['set']) {
    json_out(400, ['error' => 'Please add your payment details (M-Pesa or bank) before requesting a payout.', 'need_payment' => true]);
  }
  $s = aff_stats($code);
  $available = (float)$s['totals']['available'];
  if ($available < 0.01) {
    json_out(400, ['error' => 'You have no available balance to request yet.']);
  }
  try {
    db()->beginTransaction();
    $ins = db()->prepare('INSERT INTO affiliate_payouts (id, code, amount, status) VALUES (?, ?, ?, "requested")');
    $ins->execute([uuid(), $code, $available]);
    // Move those commissions out of "available" so they cannot be requested twice.
    $upd = db()->prepare("UPDATE orders SET commission_status = 'requested'
      WHERE affiliate_code = ? AND status = 'COMPLETED' AND commission_status = 'pending'");
    $upd->execute([$code]);
    db()->commit();
  } catch (Exception $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_out(500, ['error' => 'Could not submit your payout request. Please try again.']);
  }
  json_out(200, ['ok' => true, 'requested' => round($available, 2)] + ['affiliate' => $profile] + aff_stats($code));
}

json_out(400, ['error' => 'Unknown action.']);

/** Aggregate clicks, sales and earnings for one affiliate code. */
function aff_stats($code) {
  $clicks = (int)db()->query(
    'SELECT COUNT(*) c FROM affiliate_clicks WHERE code = ' . db()->quote($code)
  )->fetch()['c'];

  $os = db()->prepare(
    "SELECT created_at, program_id, amount, currency, commission, commission_status, status
       FROM orders
      WHERE affiliate_code = ? AND status = 'COMPLETED'
      ORDER BY created_at DESC");
  $os->execute([$code]);
  $orders = $os->fetchAll();

  $earned = 0; $available = 0; $requested = 0; $paid = 0;
  foreach ($orders as $o) {
    $c = (float)$o['commission'];
    $earned += $c;
    if ($o['commission_status'] === 'pending')   $available += $c;
    if ($o['commission_status'] === 'requested') $requested += $c;
    if ($o['commission_status'] === 'paid')      $paid += $c;
  }

  $ps = db()->prepare('SELECT amount, status, requested_at, paid_at FROM affiliate_payouts WHERE code = ? ORDER BY requested_at DESC');
  $ps->execute([$code]);

  return [
    'totals'   => [
      'clicks'    => $clicks,
      'sales'     => count($orders),
      'earned'    => round($earned, 2),
      'available' => round($available, 2),
      'requested' => round($requested, 2),
      'paid'      => round($paid, 2),
    ],
    'orders'   => $orders,
    'payouts'  => $ps->fetchAll(),
  ];
}
