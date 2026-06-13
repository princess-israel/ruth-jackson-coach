// Pesapal API 3.0 helper — SERVER SIDE ONLY. Never import into browser code.
// Credentials come from environment variables set in Vercel.
//   PESAPAL_CONSUMER_KEY, PESAPAL_CONSUMER_SECRET
//   PESAPAL_BASE_URL (optional, defaults to live)
//   PESAPAL_IPN_ID   (optional — set after first registration to skip re-registering)

const BASE = process.env.PESAPAL_BASE_URL || "https://pay.pesapal.com/v3";

async function getToken() {
  const consumer_key = process.env.PESAPAL_CONSUMER_KEY;
  const consumer_secret = process.env.PESAPAL_CONSUMER_SECRET;
  if (!consumer_key || !consumer_secret) {
    throw new Error("Missing PESAPAL_CONSUMER_KEY / PESAPAL_CONSUMER_SECRET environment variables.");
  }
  const r = await fetch(`${BASE}/api/Auth/RequestToken`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ consumer_key, consumer_secret })
  });
  const data = await r.json();
  if (!data || !data.token) {
    const msg = (data && (data.error?.message || data.message)) || JSON.stringify(data);
    throw new Error("Pesapal authentication failed: " + msg);
  }
  return data.token;
}

// Warm-instance cache so we don't re-register the IPN on every request.
let cachedIpn = null;
async function ensureIpnId(token, host) {
  if (process.env.PESAPAL_IPN_ID) return process.env.PESAPAL_IPN_ID;
  if (cachedIpn) return cachedIpn;
  const url = `https://${host}/api/pesapal/ipn`;
  const r = await fetch(`${BASE}/api/URLSetup/RegisterIPN`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ url, ipn_notification_type: "GET" })
  });
  const data = await r.json();
  if (!data || !data.ipn_id) throw new Error("IPN registration failed: " + JSON.stringify(data));
  cachedIpn = data.ipn_id;
  console.log(`Pesapal IPN registered (${url}) -> ${data.ipn_id}. Set PESAPAL_IPN_ID to this value to reuse it.`);
  return data.ipn_id;
}

async function submitOrder(token, order) {
  const r = await fetch(`${BASE}/api/Transactions/SubmitOrderRequest`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(order)
  });
  return r.json();
}

async function getStatus(token, orderTrackingId) {
  const r = await fetch(
    `${BASE}/api/Transactions/GetTransactionStatus?orderTrackingId=${encodeURIComponent(orderTrackingId)}`,
    { headers: { Authorization: `Bearer ${token}`, Accept: "application/json" } }
  );
  return r.json();
}

module.exports = { BASE, getToken, ensureIpnId, submitOrder, getStatus };
