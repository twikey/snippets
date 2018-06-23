<?php

class Twikey {
    
    const VERSION = '2.0.1';
    
    public $templateId;
    public $debug;
    public $endpoint;
    protected $apiToken;
    protected $lang = 'en';

    protected $auth;

    public function setEndpoint($endpoint){
        $this->endpoint = trim($endpoint);
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

    function authenticate() {
        
        if ($this->auth != "")
            return $this->auth;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor", $this->endpoint));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf("apiToken=%s", $this->apiToken));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $result = json_decode($server_output);
        $this->auth = $result->{'Authorization'};
        $this->checkResponse($ch, $server_output, "Connecting to Twikey!");
        curl_close($ch);
        return $this->auth;
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new mandate!");
        curl_close($ch);
        return json_decode($server_output);
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Update mandate");
        curl_close($ch);
        return json_decode($server_output);
    }

    public function cancelMandate($mndtId) {
        $this->auth = $this->authenticate();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/mandate?mndtId=".$mndtId, $this->endpoint));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth","Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Cancelled mandate");
        curl_close($ch);
        return json_decode($server_output);
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new transaction!");
        curl_close($ch);
        return json_decode($server_output);
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Creating a new paymentlink!");
        curl_close($ch);
        return json_decode($server_output);
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving payments!");
        curl_close($ch);

        return json_decode($server_output);
    }

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
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving payments!");
        curl_close($ch);

        return json_decode($server_output);
    }

    public function getTransactionFeed() {
        $this->auth = $this->authenticate();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s/creditor/transaction", $this->endpoint));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $this->auth"/*, "X-RESET: true"*/,"Accept-Language: $this->lang"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "twikey-php/v".Twikey::VERSION);
        $server_output = curl_exec($ch);
        $this->checkResponse($ch, $server_output, "Retrieving transaction feed!");
        curl_close($ch);

        return json_decode($server_output);
    }

    public function checkResponse($curlHandle, $server_output, $context = "No context") {
        if (!curl_errno($curlHandle)) {
            $http_code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            if ($http_code == 400) { // normal user error
                try {
                    $jsonError = json_decode($server_output);
                    $translatedError = $jsonError->message;
                    error_log(sprintf("%s : Error = %s [%d]", $context, $translatedError, $http_code), 0);
                } catch (Exception $e) {
                    $translatedError = "General error";
                    error_log(sprintf("%s : Error = %s [%d]", $context, $server_output, $http_code), 0);
                }
                throw new Exception($translatedError);
            }
            else if ($http_code > 400) {
                error_log(sprintf("%s : Error = %s", $context, $server_output), 0);
                throw new Exception("General error");
            }
        } 
        if (TWIKEY_DEBUG) {
            error_log(sprintf("Response %s : %s", $context, $server_output), 0);
        }
    }
    
    public function debugRequest($msg){
        if (TWIKEY_DEBUG) {
            error_log('Request : '.$msg, 0);
        } 
    }
}