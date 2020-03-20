const crypto = require('crypto');

/**
 * Check Twikey Webhook
 * @param {string} signature - X-Signature header
 * @param {string} apikey - Twikey apikey
 * @param {string} qs - Query String coming from Twikey
 */
const checkHmacValidity = (apikey,signature, qs) => {

    if (!secret || !qs || !signature) {
        throw "Invalid signature or missing api key";
    }

    let hash = crypto.createHmac('sha256', apikey).update(qs).digest('hex').toUpper();
    // validate and return
    return hash === hmac
};

module.exports = checkHmacValidity;
