// GET /api/pesapal/status?orderTrackingId=...
// Server-verifies a transaction with Pesapal. The client trusts THIS, not its own state.
const { getToken, getStatus } = require("../../lib/pesapal");

module.exports = async (req, res) => {
  try {
    const id = (req.query && req.query.orderTrackingId) || (req.body && req.body.orderTrackingId);
    if (!id) return res.status(400).json({ error: "Missing orderTrackingId" });

    const token = await getToken();
    const data = await getStatus(token, id);

    // status_code: 0 INVALID, 1 COMPLETED, 2 FAILED, 3 REVERSED
    return res.status(200).json({
      status_code: data.status_code,
      payment_status_description: data.payment_status_description,
      amount: data.amount,
      currency: data.currency,
      confirmation_code: data.confirmation_code,
      payment_method: data.payment_method,
      merchant_reference: data.merchant_reference,
      message: data.message
    });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
