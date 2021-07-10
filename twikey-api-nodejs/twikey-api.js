const DEFAULT_USER_AGENT = "twikey/api-node-v0.1.0";
const { URLSearchParams } = require('url');
const https = require('https');

module.exports = function(key,options){
  options = options || 
  let USER_AGENT = options.userAgent || DEFAULT_USER_AGENT;
  
  var fetch = require('node-fetch'),
      errorHandler = function(response){
        if(response.ok){
          return response;
        }
        let error = response.headers.get('apierror') || "Unknown error";
        if(response.status !== 200){
          throw `Error in ${response.url} returned ${response.status} (${error})`;
        }
        throw error;
      },
      credentials,
      generateOtp = function (privateKey) {
          if (!privateKey)
              return '';
          const jsSHA = require('jsSHA');

          function timeAsHex() {
              const leftpad = function (str, len, pad) {
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

          function hex2dec(s) {
              return parseInt(s, 16);
          }

          function keyAsHex() {
              return new Buffer('own', "UTF-8").toString('hex') + privateKey;
          }

          var shaObj = new jsSHA("SHA-256", "HEX");
          shaObj.setHMACKey(keyAsHex(), "HEX");
          shaObj.update(timeAsHex());

          var hmac = shaObj.getHMAC("HEX"),
              offset = parseInt(hmac.substring(38, 40), 16) & 0xf,
              otp = (hex2dec(hmac.substr(offset * 2, 8)) & hex2dec('7fffffff')) + '';

          otp = otp.substr(otp.length - 8, 8);
          console.debug('otp', otp);
          return otp;
      },
      getSessionToken = function (apiKey, privateKey) {
        if (credentials && ((credentials.at + 23 * 3600 * 1000) < Date.now()))
          return credentials.auth;

        var params = new URLSearchParams();
        params.append('apiToken', apiKey);
        if (privateKey) {
          params.append('otp', generateOtp(privateKey));
        }

        return fetch(url + '/creditor', {
          headers: {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded"
          },
          method: 'POST',
          body: params
        }).then(function (res) {
          var auth = res.headers.get('authorization');
          if (auth) {
            credentials = {auth: auth, at: Date.now()};
            return auth;
          }
          throw res.headers.get('apierror') || 'Twikey login failed';
        });
      }
  ;
  if(!url){
    url = 'https://api.twikey.com';
  }
  return {
    template: function(auth){
      return fetch(url+'/creditor/template',{
        method: 'GET',
        headers: {
          "User-Agent": USER_AGENT,
          "Content-Type": "application/x-www-form-urlencoded",
          "Authorization": auth
        }
      })
      .then(errorHandler)
      .then(res => res.json());
    },
    customerLogin: function(auth,mandate){
      var params = new URLSearchParams();
      params.append("mndtId",mandate);
      return fetch(url+'/creditor/customeraccess',{
        method: 'POST',
        headers: {
          "User-Agent": USER_AGENT,
          "Content-Type": "application/x-www-form-urlencoded",
          "Authorization": auth
        },
        body: params
      })
          .then(errorHandler)
          .then(res => res.json());
    },
    decodeAccount: function(encAccount,mandateId,privateKey){
      var crypto = require('crypto');
      var keybytes = Buffer.from(mandateId+privateKey, "utf8");
      var iv =crypto.createHash('md5').update(keybytes).digest();
      var decipher = crypto.createDecipheriv("aes-128-cbc", iv,iv).setAutoPadding(false);
      var dec = decipher.update(encAccount, 'hex', 'utf8');
      dec += decipher.final('utf8');
      let items = dec.split('/');
      return {
        "iban":items[0],
        "bic":items[1]
      };
    }
  };
};
