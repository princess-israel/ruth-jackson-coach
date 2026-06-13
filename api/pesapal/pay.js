// POST /api/pesapal/pay  { programId, email, name, phone }
// Creates a Pesapal order and returns the redirect_url to send the buyer to.
const { getToken, ensureIpnId, submitOrder } = require("../../lib/pesapal");
const PROGRAMS = require("../../lib/programs");

module.exports = async (req, res) => {
  if (req.method !== "POST") return res.status(405).json({ error: "Method not allowed" });
  try {
    const body = typeof req.body === "string" ? JSON.parse(req.body || "{}") : (req.body || {});
    const { programId, email, name, phone } = body;

    const program = PROGRAMS[programId];
    if (!program) return res.status(400).json({ error: "Unknown program." });
    if (!email && !phone) return res.status(400).json({ error: "An email or phone number is required for payment." });

    const host = req.headers["x-forwarded-host"] || req.headers.host;
    const token = await getToken();
    const ipnId = await ensureIpnId(token, host);

    const merchantRef = `${programId}-${Date.now()}`;
    const [first, ...rest] = String(name || "Customer").trim().split(/\s+/);

    const order = {
      id: merchantRef,
      currency: "USD",
      amount: program.price,
      description: `Enrollment: ${program.title}`.slice(0, 100),
      callback_url: `https://${host}/payment-callback.html`,
      notification_id: ipnId,
      billing_address: {
        email_address: email || undefined,
        phone_number: phone || undefined,
        first_name: first || "Customer",
        last_name: rest.join(" ") || "-",
        country_code: "KE"
      }
    };

    const result = await submitOrder(token, order);
    if (!result || !result.redirect_url) {
      return res.status(502).json({ error: "Could not create payment.", detail: result });
    }
    return res.status(200).json({
      redirect_url: result.redirect_url,
      order_tracking_id: result.order_tracking_id,
      merchant_reference: merchantRef
    });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
