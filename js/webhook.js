const crypto = require('crypto');

/**
 * Check Twikey Webhook
 * @param {string} signature - X-Signature header
 * @param {string} apikey - Twikey apikey
 * @param {string} qs - Query String coming from Twikey
 */
const checkWebhook = (apikey,signature, qs) => {

    if (!qs || !signature) {
        throw "Invalid signature or missing api key";
    }
    let decodedQuerystring = decodeURIComponent(qs);

    let hash = crypto.createHmac('sha256', apikey).update(decodedQuerystring).digest('hex').toUpperCase();
    // validate and return
    return hash === signature
};

// var test = function(){
//     const querystring = /*http://my.company.com/webhook?*/ 'msg=dummytest&type=event';
//     const header_x_signature = '417745C0DE5DE5BFEAF.....'; // header coming from Twikey
//     const api_key = 'A03EB2.....'; // Api found in your Twikey dashboard
//     console.log("Valid ? ",checkHmacValidity(api_key,header_x_signature,querystring));
// }

/**
 * @param websitekey Provided in Settings > Website
 * @param document Mandatenumber or other
 * @param status Outcome of the request
 * @param token If provided in the initial request
 * @param signature Given in the exit url
 */
const checkExiturlSignature = (websitekey, document, status, token, signature) => {

    if (!websitekey || !signature) {
        throw "Invalid signature or missing website key";
    }

    var payload = document+"/"+status;
    if(token != null) {
        payload += "/"+token;
    }
    let hash = crypto.createHmac('sha256', websitekey).update(payload).digest('hex').toUpperCase();
    // validate and return
    return hash === signature
}

module.exports = {
    checkWebhook: checkWebhook,
    checkExiturlSignature: checkExiturlSignature
};
