<?php

declare(strict_types=1);

namespace Common\Service\ApiClient;

use Common\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\HttpException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package Common\Service\ApiClient
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
    private $token;

    /**
     * Client constructor
     *
     * @param ClientInterface $httpClient
     * @param string $apiBaseUri
     * @param string|null $token
     */
    public function __construct(ClientInterface $httpClient, string $apiBaseUri)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUri = $apiBaseUri;
    }

    /**
     * Sets up the client object to attach authentication headers
     * to outgoing requests.
     *
     * @param string $token
     */
    public function setUserTokenHeader(string $token) : void
    {
        $this->token = $token;
    }

    /**
     * Performs a GET against the API
     *
     * @param string $path
     * @param array $query
     * @return array
     * @throws ApiException
     */
    public function httpGet(string $path, array $query = []) : ?array
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        //  Can throw RuntimeException if there is a problem
        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http GET request', $response, $ex);
        }
    }

    /**
     * Performs a POST against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPost(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('POST', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                case StatusCodeInterface::STATUS_CREATED:
                case StatusCodeInterface::STATUS_ACCEPTED:
                case StatusCodeInterface::STATUS_NO_CONTENT:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http POST request', $response, $ex);
        }
    }

    /**
     * Performs a PUT against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPut(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PUT', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                case StatusCodeInterface::STATUS_CREATED:
                case StatusCodeInterface::STATUS_ACCEPTED:
                case StatusCodeInterface::STATUS_NO_CONTENT:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http PUT request', $response, $ex);
        }
    }

    /**
     * Performs a PATCH against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPatch(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                case StatusCodeInterface::STATUS_CREATED:
                case StatusCodeInterface::STATUS_ACCEPTED:
                case StatusCodeInterface::STATUS_NO_CONTENT:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http PATCH request', $response, $ex);
        }
    }

    /**
     * Performs a DELETE against the API
     *
     * @param string $path
     * @return array
     * @throws ApiException
     */
    public function httpDelete(string $path) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders());

        try {
            $response = $this->httpClient->sendRequest($request);

            switch ($response->getStatusCode()) {
                case StatusCodeInterface::STATUS_OK:
                case StatusCodeInterface::STATUS_CREATED:
                case StatusCodeInterface::STATUS_ACCEPTED:
                case StatusCodeInterface::STATUS_NO_CONTENT:
                    return $this->handleResponse($response);
                default:
                    throw ApiException::create(null, $response);
            }
        } catch (ClientExceptionInterface $ex) {
            $response = ($ex instanceof HttpException) ? $ex->getResponse() : null;

            throw ApiException::create('Error whilst making http DELETE request', $response, $ex);
        }
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

        //  If the logged in user has an auth token already then set that in the header
        if (isset($this->token)) {
            $headerLines['User-Token'] = $this->token;
        }

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
            throw ApiException::create('Malformed JSON response from server', $response);
        }

        return $body;
    }
}
