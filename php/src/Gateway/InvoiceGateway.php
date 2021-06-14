<?php

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Twikey\Api\TwikeyException;

class InvoiceGateway extends BaseGateway
{
    public function __construct(ClientInterface $httpClient, string $endpoint, string $apikey)
    {
        parent::__construct($httpClient, $endpoint, $apikey);
    }

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/invoice", ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Creating a new invoice!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get($id, $lang = 'en')
    {
        $response = $this->request('GET', sprintf("/creditor/invoice/%s", $id), [], $lang);
        $server_output = $this->checkResponse($response, "Verifying a invoice ");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed($lang = 'en')
    {
        $response = $this->request('GET', "/creditor/invoice", [], $lang);
        $server_output = $this->checkResponse($response, "Retrieving invoice feed!");
        return json_decode($server_output);
    }
}
