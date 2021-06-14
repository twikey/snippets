<?php

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Twikey\Api\TwikeyException;

class LinkGateway extends BaseGateway
{
    public function __construct(ClientInterface $httpClient, string $endpoint, string $apikey)
    {
        parent::__construct($httpClient, $endpoint, $apikey);
    }

    /**
     * @throws TwikeyException
     */
    public function create($data, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/payment/link", ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Creating a new paymentlink!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get($linkid, $ref, $lang = 'en')
    {
        if (empty($ref)) {
            $item = "id=" . $linkid;
        } else {
            $item = "ref=" . $ref;
        }
        $response = $this->request('POST', sprintf("/creditor/payment/link?%s", $item), [], $lang);
        $server_output = $this->checkResponse($response, "Verifying a paymentlink ");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed($lang = 'en')
    {
        $response = $this->request('GET', "/creditor/payment/link/feed", [], $lang);
        $server_output = $this->checkResponse($response, "Retrieving paymentlink feed!");
        return json_decode($server_output);
    }
}
