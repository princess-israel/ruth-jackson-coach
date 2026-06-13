// Pesapal Instant Payment Notification endpoint.
// Pesapal calls this (GET) when a transaction status changes.
// We acknowledge in the exact shape Pesapal expects so the IPN is marked delivered.
// (In a full system with a database you would also verify status and persist the order here.)
module.exports = async (req, res) => {
  const q = req.method === "GET" ? (req.query || {}) : (req.body || {});
  const orderTrackingId = q.OrderTrackingId || q.orderTrackingId || "";
  const orderMerchantReference = q.OrderMerchantReference || q.orderMerchantReference || "";
  const orderNotificationType = q.OrderNotificationType || q.orderNotificationType || "IPNCHANGE";

  console.log("Pesapal IPN:", { orderTrackingId, orderMerchantReference, orderNotificationType });

  return res.status(200).json({
    orderNotificationType,
    orderTrackingId,
    orderMerchantReference,
    status: 200
  });
};
