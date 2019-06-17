<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Exception\ApiException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use App\Service\ApiClient\ClientInterface as ApiClientInterface;

/**
 * Class SignedRequestClient
 *
 * Signs HTTP requests made through it using HMAC and AWS API Gateway data.
 *
 * @package App\Service\ApiClient
 */
class SignedRequestClient implements ApiClientInterface
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
     * @inheritDoc
     */
    public function httpGet(string $path, array $query = []) : ?array
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        try {
            $response = $this->httpClient->sendRequest($this->signRequest($request));

            switch ($response->getStatusCode()) {
                case 200:
                    return $this->handleResponse($response);
                case 404:
                    return null;
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            throw ApiException::create('Error whilst making http GET request', null, $ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function httpPost(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('POST', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($this->signRequest($request));

            switch ($response->getStatusCode()) {
                case 200:
                case 201:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            throw ApiException::create('Error whilst making http POST request', null, $ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function httpPut(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PUT', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($this->signRequest($request));

            switch ($response->getStatusCode()) {
                case 200:
                case 201:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            throw ApiException::create('Error whilst making http POST request', null, $ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function httpPatch(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($this->signRequest($request));

            switch ($response->getStatusCode()) {
                case 200:
                case 201:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            throw ApiException::create('Error whilst making http PATCH request', null, $ex);
        }

    }

    /**
     * @inheritDoc
     */
    public function httpDelete(string $path) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders());

        try {
            $response = $this->httpClient->sendRequest($this->signRequest($request));

            switch ($response->getStatusCode()) {
                case 200:
                case 201:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            throw ApiException::create('Error whilst making http DELETE request', null, $ex);
        }
    }

    private function signRequest(RequestInterface $request) : RequestInterface
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
        $body = json_decode($response->getBody(), true);

        //  If the body isn't an array now then it wasn't JSON before
        if (!is_array($body)) {
            throw new ApiException('Malformed JSON response from server', $response);
        }

        return $body;
    }
}
