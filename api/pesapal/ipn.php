<?php
/**
 * Pesapal IPN endpoint — Pesapal calls this on every status change.
 * We verify the transaction server-side, persist the result, and fulfill
 * (create the enrollment) when COMPLETED. Then we ACK in the format Pesapal expects.
 */
require __DIR__ . '/../_orders.php';

$orderTrackingId        = $_GET['OrderTrackingId']        ?? $_GET['orderTrackingId']        ?? '';
$orderMerchantReference = $_GET['OrderMerchantReference'] ?? $_GET['orderMerchantReference'] ?? '';
$orderNotificationType  = $_GET['OrderNotificationType']  ?? 'IPNCHANGE';

if ($orderTrackingId !== '') {
  try { order_verify_and_fulfill($orderTrackingId); }
  catch (Exception $e) { /* swallow — still ACK so Pesapal stops retrying this notice */ }
}

header('Content-Type: application/json');
echo json_encode([
  'orderNotificationType'  => $orderNotificationType,
  'orderTrackingId'        => $orderTrackingId,
  'orderMerchantReference' => $orderMerchantReference,
  'status'                 => 200,
]);
