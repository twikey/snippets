let querystring = require('querystring'),
    https = require('https');

//from_creditor_env
let API_TOKEN = "API_TOKEN",
    PRIVATE_KEY = "Priv-key";

function generateOtp(){
  if(!PRIVATE_KEY)
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
  function keyAsHex() { return new Buffer('own', "UTF-8").toString('hex') + PRIVATE_KEY; }
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

var apitoken = "your_api_token",
    ct = "your_contracttemplate_id",
    authorization = null,
    otp = generateOtp(),
    req = https.request({
      host: 'api.twikey.com',
      port: '443',
      path: '/creditor',
      method: 'POST',
      data: querystring.encode({apiToken: apitoken, otp: otp}),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }, function (res) {
        authorization = res.headers.authorization;
        console.debug("authorization : ",authorization);
        options.path = '/creditor/prepare';
        options.data = querystring.encode({
          ct: ct,
          email: "info@twikey.com",
          firstname: "Info",
          lastname: "Twikey",
          l: "en",
          address: "Abby road",
          city: "Liverpool",
          zip: "1526",
          country: "BE",
          mobile: "",
          iban: "",
          bic: "",
          mandateNumber: "",
          contractNumber: ""
        });

        https.request(options, function (res) {
          res.on('data', function (chunk) {
            console.log("Redirect to : "+chunk)
          });
        });
    });
