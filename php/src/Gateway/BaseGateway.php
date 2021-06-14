<?php
namespace Twikey\Api\Gateway;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Twikey\Api\Twikey;
use Twikey\Api\TwikeyException;
use const Twikey\Api\TWIKEY_DEBUG;

class BaseGateway
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    private string $endpoint;
    private string $apikey;

    protected function __construct(ClientInterface $httpClient, string $endpoint, string $apikey)
    {
        $this->httpClient = $httpClient;
        $this->endpoint = $endpoint;
        $this->apikey = $apikey;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function request(string $method, string $uri = '', array $options = [], string $lang = 'en'): ResponseInterface
    {
        $fulluri = sprintf("%s/%s", $this->endpoint, $uri);
        $headers = $options['headers'] ?? [];
        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            "User-Agent" => "twikey-php/v" . Twikey::VERSION,
            "Accept-Language" => $lang,
            "Authorization" => 'Bearer ' . $this->apikey
        ]);
        $options['headers'] = $headers;
        return $this->httpClient->request($method, $fulluri, $options);
    }

    /**
     * @throws TwikeyException
     */
    protected function checkResponse($response, $context = "No context") : ?string
    {
        if ($response) {
            $http_code = $response->getStatusCode();
            $server_output = (string)$response->getBody();
            if ($http_code == 400) { // normal user error
                try {
                    $jsonError = json_decode($server_output);
                    $translatedError = $jsonError->message;
                    error_log(sprintf("%s : Error = %s [%d]", $context, $translatedError, $http_code), 0);
                } catch (Exception $e) {
                    $translatedError = "General error";
                    error_log(sprintf("%s : Error = %s [%d]", $context, $server_output, $http_code), 0);
                }
                throw new TwikeyException($translatedError);
            } else if ($http_code > 400) {
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
}
