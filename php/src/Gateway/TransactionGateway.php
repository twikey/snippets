<?php

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientInterface;
use Twikey\Api\TwikeyException;

class TransactionGateway extends BaseGateway
{
    public function __construct(ClientInterface $httpClient, string $endpoint, string $apikey)
    {
        parent::__construct($httpClient, $endpoint, $apikey);
    }

    /**
     * @param $data
     * @return array|mixed|object
     * @throws TwikeyException
     */
    public function create($data, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/transaction", ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Creating a new transaction!");
        return json_decode($server_output);
    }

    /**
     * Note: This is rate limited
     * @throws TwikeyException
     */
    public function get($txid, $ref, $lang = 'en')
    {
        if (empty($ref)) {
            $item = "id=" . $txid;
        } else {
            $item = "ref=" . $ref;
        }

        $response = $this->request('GET', sprintf("/creditor/transaction/detail?%s", $item), [], $lang);
        $server_output = $this->checkResponse($response, "Retrieving payments!");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     */
    public function feed($lang = 'en')
    {
        $response = $this->request('GET', "/creditor/transaction", [], $lang);
        $server_output = $this->checkResponse($response, "Retrieving transaction feed!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     */
    public function sendPending(int $ct, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/collect", ['form_params' => ["ct" => $ct]], $lang);
        $server_output = $this->checkResponse($response, "Retrieving transaction feed!");
        return json_decode($server_output);
    }
}
