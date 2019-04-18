<?php

namespace Viewer\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception as HttpException;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package Viewer\Service\ApiClient
 */
class Client
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var string
     */
    private $authToken;

    /**
     * Client constructor
     *
     * @param HttpClient $httpClient
     * @param string $apiBaseUri
     * @param string|null $authToken
     */
    public function __construct(HttpClient $httpClient, string $apiBaseUri, string $authToken = null)
    {
        $this->httpClient = $this->getMockHttpClient($apiBaseUri);//$httpClient;
        $this->apiBaseUri = $apiBaseUri;
        $this->authToken = $authToken;
    }

    /**
     * TODO - TO BE REMOVED WHEN MOCKED HTTP CLIENT IS REMOVED
     *
     * @param string $apiBaseUri
     * @return HttpClient
     */
    private function getMockHttpClient(string $apiBaseUri) : HttpClient
    {
        $prophet = new \Prophecy\Prophet();
        $httpClientProphercy = $prophet->prophesize(HttpClient::class);

        $lpaDatasets = [
            '12345' => [
                'id'      => 'A12345678901',
                'isValid' => true,
            ],
            '67890' => [
                'id'      => 'A98765432109',
                'isValid' => false,
            ],
        ];

        //  Loop through the LPA datasets and set up the mock data
        foreach ($lpaDatasets as $shareCode => $lpaDataset) {
            //  Generate the intended request with the share code parameter
            $uri = new Uri($apiBaseUri . '/path/to/lpa');
            $uri = Uri::withQueryValue($uri, 'code', $shareCode);

            $request = new Request('GET', $uri, $this->buildHeaders());

            //  Generate the mocked response
            $responseProphecy = $prophet->prophesize(ResponseInterface::class);
            $responseProphecy->getStatusCode()
                ->willReturn(200);
            $responseProphecy->getBody()
                ->willReturn(json_encode($lpaDataset));

            //  Attach the request and response to the mocked client
            $httpClientProphercy->sendRequest($request)
                ->willReturn($responseProphecy->reveal());
        }

        //  Response for not found 404
        $notFoundResponseProphecy = $prophet->prophesize(ResponseInterface::class);
        $notFoundResponseProphecy->getStatusCode()
            ->willReturn(404);

        $httpClientProphercy->sendRequest(new \Prophecy\Argument\Token\AnyValuesToken())
            ->willReturn($notFoundResponseProphecy->reveal());

        return $httpClientProphercy->reveal();
    }

    /**
     * Performs a GET against the API
     *
     * @param string $path
     * @param array $query
     * @return array
     * @throws ApiException|HttpException
     */
    public function httpGet(string $path, array $query = []) : ?array
    {
        $url = new Uri($this->apiBaseUri . $path);

        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        //  Can throw RuntimeException if there is a problem
        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
                return $this->handleResponse($response);
            case 404:
                return null;
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
     * @throws ApiException|HttpException
     */
    public function httpPost(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('POST', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
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
     * @throws ApiException|HttpException
     */
    public function httpPut(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PUT', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
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
     * @throws ApiException|HttpException
     */
    public function httpPatch(string $path, array $payload = []) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
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
     * @throws ApiException|HttpException
     */
    public function httpDelete(string $path) : array
    {
        $url = new Uri($this->apiBaseUri . $path);

        $request = new Request('DELETE', $url, $this->buildHeaders());

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $this->handleResponse($response);
            default:
                throw new ApiException($response);
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
        if (isset($this->authToken)) {
            $headerLines['token'] = $this->authToken;
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
        $body = json_decode($response->getBody(), true);

        //  If the body isn't an array now then it wasn't JSON before
        if (!is_array($body)) {
            throw new ApiException($response, 'Malformed JSON response from server');
        }

        return $body;
    }
}
