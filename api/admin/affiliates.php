<?php
/**
 * Admin affiliate report + payout approval.
 *   GET  (admin): affiliates enriched with clicks/sales/earnings + payout requests.
 *   POST (admin): { action: "pay", payout_id }  marks a payout paid and settles its commissions.
 */
require __DIR__ . '/../_affiliates.php';
require __DIR__ . '/../_admin.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$token  = $_GET['token'] ?? bearer_token(read_body());
if (!admin_token_valid($token)) {
  // POST bodies are read once; re-read for token if needed.
  $b = read_body();
  $token = $b['token'] ?? $token;
  if (!admin_token_valid($token)) json_out(401, ['error' => 'Unauthorized']);
}

if ($method === 'GET') {
  $affs = aff_load();

  // Per-code aggregates in two grouped queries.
  $clicks = [];
  foreach (db()->query('SELECT code, COUNT(*) c FROM affiliate_clicks GROUP BY code') as $r) {
    $clicks[strtoupper($r['code'])] = (int)$r['c'];
  }
  $sales = [];
  foreach (db()->query(
    "SELECT affiliate_code code, COUNT(*) sales,
            SUM(commission) earned,
            SUM(CASE WHEN commission_status='pending'   THEN commission ELSE 0 END) available,
            SUM(CASE WHEN commission_status='requested' THEN commission ELSE 0 END) requested,
            SUM(CASE WHEN commission_status='paid'      THEN commission ELSE 0 END) paid
       FROM orders
      WHERE status='COMPLETED' AND affiliate_code IS NOT NULL
      GROUP BY affiliate_code") as $r) {
    $sales[strtoupper($r['code'])] = $r;
  }

  $out = [];
  foreach ($affs as $a) {
    $code = strtoupper((string)($a['code'] ?? ''));
    $s = $sales[$code] ?? [];
    $out[] = $a + [
      'clicks'    => $clicks[$code] ?? 0,
      'sales'     => (int)($s['sales'] ?? 0),
      'earned'    => round((float)($s['earned'] ?? 0), 2),
      'available' => round((float)($s['available'] ?? 0), 2),
      'requested' => round((float)($s['requested'] ?? 0), 2),
      'paid'      => round((float)($s['paid'] ?? 0), 2),
    ];
  }

  $payouts = db()->query(
    'SELECT * FROM affiliate_payouts ORDER BY (status="requested") DESC, requested_at DESC'
  )->fetchAll();

  json_out(200, ['affiliates' => $out, 'payouts' => $payouts]);
}

if ($method === 'POST') {
  $b = read_body();
  $action = (string)($b['action'] ?? '');

  if ($action === 'pay') {
    $payoutId = (string)($b['payout_id'] ?? '');
    $p = db()->prepare('SELECT * FROM affiliate_payouts WHERE id = ? LIMIT 1');
    $p->execute([$payoutId]);
    $payout = $p->fetch();
    if (!$payout) json_out(404, ['error' => 'Payout not found.']);
    if ($payout['status'] === 'paid') json_out(200, ['ok' => true, 'already' => true]);

    try {
      db()->beginTransaction();
      db()->prepare("UPDATE affiliate_payouts SET status='paid', paid_at=NOW() WHERE id=?")->execute([$payoutId]);
      db()->prepare("UPDATE orders SET commission_status='paid'
        WHERE affiliate_code=? AND status='COMPLETED' AND commission_status='requested'")
        ->execute([strtoupper($payout['code'])]);
      db()->commit();
    } catch (Exception $e) {
      if (db()->inTransaction()) db()->rollBack();
      json_out(500, ['error' => 'Could not mark this payout paid.']);
    }
    json_out(200, ['ok' => true]);
  }

  json_out(400, ['error' => 'Unknown action.']);
}

json_out(405, ['error' => 'Method not allowed']);
