<?php
/** Pesapal IPN endpoint — acknowledges status-change notifications. */
$orderTrackingId        = isset($_GET['OrderTrackingId']) ? $_GET['OrderTrackingId'] : (isset($_GET['orderTrackingId']) ? $_GET['orderTrackingId'] : '');
$orderMerchantReference = isset($_GET['OrderMerchantReference']) ? $_GET['OrderMerchantReference'] : (isset($_GET['orderMerchantReference']) ? $_GET['orderMerchantReference'] : '');
$orderNotificationType  = isset($_GET['OrderNotificationType']) ? $_GET['OrderNotificationType'] : 'IPNCHANGE';

// In a full system with a database you would verify and persist the order here.
header('Content-Type: application/json');
echo json_encode([
  'orderNotificationType'  => $orderNotificationType,
  'orderTrackingId'        => $orderTrackingId,
  'orderMerchantReference' => $orderMerchantReference,
  'status'                 => 200,
]);
