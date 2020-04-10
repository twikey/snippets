var request = require('request-promise-native');

module.exports = function(url){
    if(!url){
        url = 'https://api.twikey.com';
    }
    return {
        login : function(apiKey,privateKey) {
            var creds = {'apiToken':apiKey};
            if(privateKey){
                creds.otp = generateOtp(privateKey);
            }
            return request({
                uri: url+'/creditor',
                method: 'POST',
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                form: creds,
                json: true
            });
        },
        invite: function(auth, entity){
            return request({
                uri: url+'/creditor/invite',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: entity,
                json: true
            });
        },
        sign: function(auth, entity){
            return request({
                uri: url+'/creditor/sign',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: entity,
                json: true
            });
        },
        preview: function(auth, entity){
            return request({
                uri: url+'/creditor/preview',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: entity,
                json: true
            });
        },
        paylink: function(auth, entity){
            return request({
                uri: url+'/creditor/payment/link',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: entity,
                json: true
            });
        },
        inviteFlow: function(auth, entity){
            return request({
                uri: url+'/creditor/flow',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: entity,
                json: true
            });
        },
        mandateFeed: function(auth){
            return request({
                uri: url+'/creditor/mandate',
                method: 'GET',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                json: true
            });
        },
        addTransaction: function(auth,tx){
            return request({
                uri: url+'/creditor/transaction',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: tx,
                json: true
            });
        },
        customerLogin: function(auth,mandate){
            return request({
                uri: url+'/creditor/customeraccess',
                method: 'POST',
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "Authorization": auth
                },
                form: {mndtId:mandate},
                json: true
            });
        },
        invoice: function(auth,invoice){
            return request({
                uri: url+'/creditor/invoice',
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": auth
                },
                form: invoice,
                json: true
            });
        },
        signUrl: function (key, url, params) {
            var baseUrl = url.toLowerCase();
            var baseParams = require('querystring').stringify(params);
            var _url = require('url').parse(baseUrl);

            var payload = _url.host + '\n' +
                'get\n' +
                key + '\n' +
                params['ct'] + '\n' +
                '0\n';

            var keys = Object.keys(params);
            keys.sort();

            for (var i = 0; i < keys.length; i++) {
                if(['c','ct','_t'].indexOf(keys[i]) === -1)
                    payload += keys[i] + '=' + params[keys[i]] + '\n';
            }
            var crypto = require('crypto');
            var signature = crypto.createHmac('SHA256', key).update(payload).digest('hex');
            return baseUrl + '?'+ baseParams + '&s='+signature;
        },
        decodeAccount: function(encAccount,mandateId,privateKey){
            var crypto = require('crypto');
            var keybytes = Buffer.from(mandateId+privateKey, "utf8");
            var iv =crypto.createHash('md5').update(keybytes).digest();
            var decipher = crypto.createDecipheriv("aes-128-cbc", iv,iv).setAutoPadding(false);
            var dec = decipher.update(encAccount, 'hex', 'utf8');
            dec += decipher.final('utf8');
            return {
                "iban":dec.split('/')[0],
                "bic":dec.split('/')[1]
            };
        }
    };
};

function generateOtp(privateKey){
    if(!privateKey)
        return '';
    let jsSHA = require('jsSHA');
    function timeAsHex() {
        let leftpad = function (str, len, pad) {
                if (len + 1 >= str.length) {
                    str = Array(len + 1 - str.length).join(pad) + str;
                }
                return str;
            },
            dec2hex = function (s) {
                return ("0" + (Number(s).toString(16))).toUpperCase();
            },
            epoch = Math.floor(Date.now() / 30000.0);
        return leftpad(dec2hex(epoch), 16, '0');
    }
    function hex2dec(s) { return parseInt(s, 16); }
    function keyAsHex() { return new Buffer('own', "UTF-8").toString('hex') + privateKey; }
    var shaObj = new jsSHA("SHA-256", "HEX");
    shaObj.setHMACKey(keyAsHex(), "HEX");
    shaObj.update(timeAsHex());

    var hmac = shaObj.getHMAC("HEX"),
        offset = parseInt(hmac.substring(38,40),16) & 0xf,
        otp = (hex2dec(hmac.substr(offset * 2, 8)) & hex2dec('7fffffff')) + '';

    otp = otp.substr(otp.length - 8, 8);
    console.debug('otp', otp);
    return otp;
}
