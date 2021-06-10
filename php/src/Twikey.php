<?php
namespace Twikey\Api;

use Exception;
use Psr\Http\Client\ClientInterface;

const TWIKEY_DEBUG = false;

class Twikey {

    const VERSION = '3.0.0';

    public $endpoint = "https://api.twikey.com";
    protected $apiKey;
    protected $lang = 'en';

    protected $auth;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(ClientInterface $httpClient) {
        $this->httpClient = $httpClient;
    }

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

    public function setApiKey($apiKey){
        $this->apiKey = trim($apiKey);
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

        $response = $this->httpClient->request('POST', sprintf("%s/creditor", $this->endpoint), [
            'headers' => [
                "USER-AGENT", "twikey-php/v".Twikey::VERSION
            ],
            'form_params' => [
                'apiToken' => $this->apiKey
            ]
        ]);

        $body = $this->checkResponse($response, "Authentication");

        $result = json_decode($body);
        if(isset($result->{'Authorization'})){
            $this->auth = $result->{'Authorization'};
            //error_log("Set ".$this->auth, 0);
        }
        else if(isset($result->{'message'})){
            error_log("Error ".$body, 0);
            throw new TwikeyException($result->{'message'});
        }
        else {
            error_log("Twikey unreachable: ".$body, 0);
            throw new TwikeyException($body);
        }
        return $this->auth;
    }

    /**
     * @throws TwikeyException
     */
    public function createNew($data) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('POST', sprintf("%s/creditor/prepare", $this->endpoint), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ],
            'form_params' => $data
        ]);
        $server_output = $this->checkResponse($response, "Creating a new mandate!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function updateMandate($data) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('POST', sprintf("%s/creditor/mandate/update", $this->endpoint), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ],
            'form_params' => $data
        ]);
        $server_output = $this->checkResponse($response, "Update a mandate!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function cancelMandate($mndtId) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('DELETE', sprintf("%s/creditor/mandate?mndtId=%s", $this->endpoint, $mndtId), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ]
        ]);
        $server_output = $this->checkResponse($response, "Cancel a mandate!");
        return json_decode($server_output);
    }

    /**
     * @param $data
     * @return array|mixed|object
     * @throws TwikeyException
     */
    public function newTransaction($data) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('POST', sprintf("%s/creditor/transaction", $this->endpoint), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ],
            'form_params' => $data
        ]);
        $server_output = $this->checkResponse($response, "Creating a new transaction!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function newLink($data) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('POST', sprintf("%s/creditor/payment/link", $this->endpoint), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ],
            'form_params' => $data
        ]);
        $server_output = $this->checkResponse($response, "Creating a new paymentlink!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws TwikeyException
     */
    public function verifyStatusLink($linkid,$ref) {
        $this->auth = $this->authenticate();
        if(empty($ref)){
            $item = "id=".$linkid;
        }
        else {
            $item = "ref=".$ref;
        }
        $response = $this->httpClient->request('POST', sprintf("%s/creditor/payment/link?%s", $this->endpoint, $item), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ]
        ]);
        $server_output = $this->checkResponse($response, "Verifying a paymentlink ");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function getPayments($id, $detail) {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('GET', sprintf("%s/creditor/payment?id=%s&detail=%s", $this->endpoint, $id, $detail), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ]
        ]);
        $server_output = $this->checkResponse($response, "Retrieving payments!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function getPaymentStatus($txid,$ref) {
        $this->auth = $this->authenticate();
        if(empty($ref)){
            $item = "id=".$txid;
        }
        else {
            $item = "ref=".$ref;
        }

        $response = $this->httpClient->request('GET', sprintf("%s/creditor/transaction/detail?%s", $this->endpoint, $item), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ]
        ]);
        $server_output = $this->checkResponse($response, "Retrieving payments!");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     */
    public function getTransactionFeed() {
        $this->auth = $this->authenticate();
        $response = $this->httpClient->request('GET', sprintf("%s/creditor/transaction", $this->endpoint), [
            'headers' => [
                "Authorization" => $this->auth,
                "User-Agent" => "twikey-php/v".Twikey::VERSION,
                "Accept-Language" => $this->lang
            ]
        ]);
        $server_output = $this->checkResponse($response, "Retrieving transaction feed!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function checkResponse($response, $context = "No context") {
        if ($response) {
            $http_code = $response->getStatusCode();
            $server_output = (string)$response->getBody();
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
            if (TWIKEY_DEBUG) {
                error_log(sprintf("Response %s : %s", $context, $server_output), 0);
            }
            return $server_output;
        }
        error_log(sprintf("Response was strange %s : %s", $context, $response), 0);
        return null;
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

    /**
     * @param $queryString $_SERVER['QUERY_STRING']
     * @param $signatureHeader $_SERVER['HTTP_X_SIGNATURE']
     * @throws TwikeyException
     */
    public function validateWebhook($queryString,$signatureHeader){
        $calculated = strtoupper(hash_hmac('sha256', urldecode($queryString), $this -> apiKey));

        error_log("Calculated: ".$calculated);
        error_log("Given: ".$signatureHeader);
        error_log("Message: ".$queryString);
        error_log("Same: ".($calculated==$signatureHeader));

        return hash_equals($calculated,$signatureHeader);
    }
}

class TwikeyException extends Exception { }
