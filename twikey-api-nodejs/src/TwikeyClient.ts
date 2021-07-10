const DEFAULT_USER_HEADER: string = "twikey/java-1.0";
const PROD_ENVIRONMENT: string = "https://api.twikey.com/creditor";
const TEST_ENVIRONMENT: string = "https://api.beta.twikey.com/creditor";
const MAX_SESSION_AGE: number = 23 * 60 * 60 * 60; // max 1day, but use 23 to be safe
const SALT_OWN: string = "own";

class DocumentGateway {
    private _client: TwikeyClient;

    constructor(client: TwikeyClient) {
        this._client = client;
    }

}

class InvoiceGateway {
    private _client: TwikeyClient;

    constructor(client: TwikeyClient) {
        this._client = client;
    }
}

class TransactionGateway {
    private _client: TwikeyClient;

    constructor(client: TwikeyClient) {
        this._client = client;
    }
}

class PaylinkGateway {
    private _client: TwikeyClient;

    constructor(client: TwikeyClient) {
        this._client = client;
    }
}

class TwikeyClient {
    apiKey:string;
    privateKey:string;
    endpoint:string;
    lastLogin:number;
    sessionToken:string;
    userAgent: string = DEFAULT_USER_HEADER;
    documentGateway: DocumentGateway;
    invoiceGateway: InvoiceGateway;
    transactionGateway: TransactionGateway;
    paylinkGateway: PaylinkGateway;

    constructor(apiKey: string, test: boolean = false) {
        this.apiKey = apiKey;
        this.endpoint = test ? TEST_ENVIRONMENT : PROD_ENVIRONMENT;
        this.documentGateway = new DocumentGateway(this);
        this.invoiceGateway = new InvoiceGateway(this);
        this.transactionGateway = new TransactionGateway(this);
        this.paylinkGateway = new PaylinkGateway(this);
    }

    public withUserAgent(userAgent: string): TwikeyClient {
        this.userAgent = userAgent;
        return this;
    }

    public withPrivateKey(privateKey:string):TwikeyClient {
        this.privateKey = privateKey;
        return this;
    }

    private generateOtp = function () {
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
            return new Buffer('own', "UTF-8").toString('hex') + this.privateKey;
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
    }

    protected getSessionToken() : string {
        if ((Date.now() - this.lastLogin) > MAX_SESSION_AGE) {


            return await fetch(this.endpoint + '/creditor',{
                headers: {
                    "User-Agent": this.userAgent,
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                method: 'POST',
                formData(): Promise<FormData> {
                    var params = new URLSearchParams();
                    params.append('apiToken', this.apiKey);
                    if (this.privateKey) {
                        params.append('otp', this.generateOtp());
                    }

                }
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
        URL myurl = new URL(endpoint);
        HttpURLConnection con = (HttpURLConnection) myurl.openConnection();
        con.setRequestMethod("POST");
        con.setRequestProperty("User-Agent", userAgent);
        con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        con.setDoOutput(true);
        con.setDoInput(true);

        try (DataOutputStream output = new DataOutputStream(con.getOutputStream())) {
        if (privateKey != null) {
        long otp = generateOtp(SALT_OWN, privateKey);
        output.writeBytes(String.format("apiToken=%s&otp=%d", apiKey, otp));
    } else {
        output.writeBytes(String.format("apiToken=%s", apiKey));
    }
    output.flush();
    } catch (GeneralSecurityException e) {
        throw new IOException(e);
    }

    sessionToken = con.getHeaderField("Authorization");
    con.disconnect();

    if (sessionToken != null) {
        lastLogin = System.currentTimeMillis();
    } else {
        lastLogin = 0;
        throw new UnauthenticatedException();
    }
    }

    return sessionToken;
    }
}
