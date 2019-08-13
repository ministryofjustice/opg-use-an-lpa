<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package App\Service\ApiClient
 */
class Client
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var string
     */
    private $awsRegion;

    /**
     * Client constructor
     *
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient, string $apiUrl, string $awsRegion)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiUrl;
        $this->awsRegion = $awsRegion;
    }

    /**
     * Performs a GET against the API
     *
     * @param string $path
     * @param array $query
     * @return array
     * @throws ApiException|ClientExceptionInterface
     */
    public function httpGet(string $path, array $query = []): ?array
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        //  Can throw RuntimeException if there is a problem
        $response = $this->httpClient->sendRequest($this->signRequest($request));

        switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
        }
    }

    /**
     * Performs a POST against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException|ClientExceptionInterface
     */
    public function httpPost(string $path, array $payload = []): array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('POST', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
            case StatusCodeInterface::STATUS_CREATED:
            case StatusCodeInterface::STATUS_ACCEPTED:
            case StatusCodeInterface::STATUS_NO_CONTENT:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
        }
    }

    /**
     * Performs a PUT against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException|ClientExceptionInterface
     */
    public function httpPut(string $path, array $payload = []): array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PUT', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
            case StatusCodeInterface::STATUS_CREATED:
            case StatusCodeInterface::STATUS_ACCEPTED:
            case StatusCodeInterface::STATUS_NO_CONTENT:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
        }
    }

    /**
     * Performs a PATCH against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException|ClientExceptionInterface
     */
    public function httpPatch(string $path, array $payload = []): array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
            case StatusCodeInterface::STATUS_CREATED:
            case StatusCodeInterface::STATUS_ACCEPTED:
            case StatusCodeInterface::STATUS_NO_CONTENT:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
        }
    }

    /**
     * Performs a DELETE against the API
     *
     * @param string $path
     * @return array
     * @throws ApiException|ClientExceptionInterface
     */
    public function httpDelete(string $path): array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders());

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case StatusCodeInterface::STATUS_OK:
            case StatusCodeInterface::STATUS_CREATED:
            case StatusCodeInterface::STATUS_ACCEPTED:
            case StatusCodeInterface::STATUS_NO_CONTENT:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
        }
    }

    private function signRequest(RequestInterface $request): RequestInterface
    {
        $provider = CredentialProvider::defaultProvider();
        $s4 = new SignatureV4('execute-api', $this->awsRegion);
        return $s4->signRequest($request, $provider()->wait());
    }

    /**
     * Generates the standard set of HTTP headers expected by the API
     *
     * @return array
     */
    private function buildHeaders() : array
    {
        $headerLines = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        return $headerLines;
    }

    /**
     * Successful response processing
     *
     * @param ResponseInterface $response
     * @return array
     * @throws ApiException
     */
    private function handleResponse(ResponseInterface $response)
    {
        $body = json_decode($response->getBody()->getContents(), true);

        //  If the body isn't an array now then it wasn't JSON before
        if (!is_array($body)) {
            throw new ApiException($response, 'Malformed JSON response from server');
        }

        return $body;
    }
}
