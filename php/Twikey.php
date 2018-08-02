<?php

class Twikey {

    const VERSION = '2.1.0';

    public $templateId;
    public $endpoint = "https://api.twikey.com";
    protected $apiToken;
    protected $lang = 'en';

    protected $auth;

    public function setEndpoint($endpoint){
        $this->endpoint = trim($endpoint);
    }

    public function setTestmode($testMode){
        if($testMode){
            $this->endpoint = "https://api.beta.twikey.com";
        }
        else {
            $this->endpoint = "https://api.twikey.com";
        }
    }

    public function setApiToken($apiToken){
        $this->apiToken = trim($apiToken);
    }

    public function setTemplateId($templateId){
        $this->templateId = trim($templateId);
    }

    public function setLang($lang){
        $this->lang = $lang;
    }

    /**
     * @throws TwikeyException
     */
    function authenticate() {

        if ($this->auth != "")
            return $this->auth;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf("apiToken=%s", $this->apiToken));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);

        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Authentication");
        curl_close($ch);

        $result = json_decode($server_output);
        if(isset($result->{'Authorization'})){
            error_log("Set ".$server_output, 0);
            $this->auth = $result->{'Authorization'};
        }
        else if(isset($result->{'message'})){
            error_log("Error ".$server_output, 0);
            throw new TwikeyException($result->{'message'});
        }
        else {
            error_log("Twikey unreachable: ".$server_output, 0);
            throw new TwikeyException($server_output);
        }
        return $this->auth;
    }

    /**
     * @throws TwikeyException
     */
    public function createNew($data) {
        $this->auth = $this->authenticate();

        $payload = http_build_query($data);

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/prepare", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);

        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new mandate!");
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function updateMandate($data) {
        $this->auth = $this->authenticate();

        $payload = http_build_query($data);

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/mandate/update", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Update mandate");
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function cancelMandate($mndtId) {
        $this->auth = $this->authenticate();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/mandate?mndtId=".$mndtId, $this->endpoint));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Cancelled mandate");
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @param $data
     * @return array|mixed|object
     * @throws TwikeyException
     */
    public function newTransaction($data)
    {
        $this->auth = $this->authenticate();

        $payload = http_build_query($data);

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/transaction", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new transaction!");
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function newLink($data) {
        $this->auth = $this->authenticate();
        $payload = http_build_query($data);
        $this->debugRequest($payload);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/payment/link", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new paymentlink!");
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function verifyLink($linkid,$ref) {
        $this->auth = $this->authenticate();

        if(empty($ref)){
            $payload = http_build_query(array("id" => $linkid));
        }
        else {
            $payload = http_build_query(array("ref" => $ref));
        }

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/payment/link?%s", $this->endpoint,$payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);

        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Verifying a paymentlink ".$payload);
        curl_close($ch);
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function getPayments($id, $detail) {
        $this->auth = $this->authenticate();

        $payload = http_build_query(array(
            "id" => $id,
            "detail" => $detail
        ));

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/payment?%s", $this->endpoint, $payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth"/*, "X-RESET: true"*/,"Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults();
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving payments!");
        curl_close($ch);

        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function getPaymentStatus($txid,$ref) {
        $this->auth = $this->authenticate();

        if(empty($ref)){
            $payload = http_build_query(array("id" => $txid));
        }
        else {
            $payload = http_build_query(array("ref" => $ref));
        }

        $this->debugRequest($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/transaction/detail?%s", $this->endpoint, $payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth"/*, "X-RESET: true"*/,"Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);

        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving payments!");
        curl_close($ch);

        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function getTransactionFeed() {
        $this->auth = $this->authenticate();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/transaction", $this->endpoint));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth"/*, "X-RESET: true"*/,"Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        self::setCurlDefaults($ch);

        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving transaction feed!");
        curl_close($ch);

        return json_decode($server_output);
    }

    private static function setCurlDefaults($ch){
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        if(DEBUG){
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
    }

    /**
     * @throws TwikeyException
     */
    public function checkResponse($curlHandle, $server_output, $context = "No context") {
        if (!curl_errno($curlHandle)) {
            $http_code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            if ($http_code == 400) { // normal user error
                try {
                    $jsonError = json_decode($server_output);
                    $translatedError = $jsonError->message;
                    error_log(sprintf("%s : Error = %s [%d] (%s)", $context, $translatedError, $http_code, $this->endpoint), 0);
                } catch (Exception $e) {
                    $translatedError = "General error";
                    error_log(sprintf("%s : Error = %s [%d] (%s)", $context, $server_output, $http_code, $this->endpoint), 0);
                }
                throw new TwikeyException($translatedError);
            }
            else if ($http_code > 400) {
                error_log(sprintf("%s : Error = %s (%s)", $context, $server_output, $this->endpoint), 0);
                throw new TwikeyException("General error");
            }
        }
        if (TWIKEY_DEBUG) {
            error_log(sprintf("Response %s : %s", $context, $server_output), 0);
        }
        return $server_output;
    }

    /**
     * @throws TwikeyException
     */
    public static function validateSignature($website_key,$mandateNumber,$status,$token,$signature){
        $calculated = hash_hmac('sha256', sprintf("%s/%s/%s",$mandateNumber,$status,$token), $website_key);
        $sig_valid = hash_equals($calculated,$signature);
        if(!$sig_valid){
            error_log("Invalid signature : expected=".$calculated.' was='.$signature, 0);
            throw new TwikeyException('Invalid signature');
        }
        return $sig_valid;
    }

    public function debugRequest($msg){
        if (TWIKEY_DEBUG) {
            error_log('Request : '.$msg, 0);
        }
    }
}

class TwikeyException extends Exception { }