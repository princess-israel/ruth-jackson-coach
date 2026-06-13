// GET /api/pesapal/setup
// One-time helper: registers the IPN URL and returns the ipn_id.
// Copy the returned ipn_id into a PESAPAL_IPN_ID env var in Vercel so it isn't re-registered.
const { getToken, ensureIpnId } = require("../../lib/pesapal");

module.exports = async (req, res) => {
  try {
    const host = req.headers["x-forwarded-host"] || req.headers.host;
    const token = await getToken();
    const ipnId = await ensureIpnId(token, host);
    return res.status(200).json({
      ok: true,
      ipn_id: ipnId,
      ipn_url: `https://${host}/api/pesapal/ipn`,
      note: "Set PESAPAL_IPN_ID to this ipn_id in your Vercel env vars, then redeploy."
    });
  } catch (e) {
    return res.status(500).json({ ok: false, error: e.message });
  }
};
