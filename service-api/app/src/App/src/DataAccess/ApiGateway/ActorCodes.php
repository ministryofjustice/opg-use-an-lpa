<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\Response\ActorCode;
use App\Exception\ApiException;
use App\Service\Log\RequestTracing;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class ActorCodes
{
    private string $apiBaseUri;

    /**
     * ActorCodes Constructor
     *
     * @param HttpClient $httpClient
     * @param RequestSigner $awsSignature
     * @param string $apiUrl
     * @param string $traceId An amazon trace id to pass to subsequent services
     */
    public function __construct(private HttpClient $httpClient, private RequestSigner $awsSignature, string $apiUrl, private string $traceId)
    {
        $this->apiBaseUri = $apiUrl;
    }

    /**
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return ActorCode
     * @throws ApiException|Exception
     */
    public function validateCode(string $code, string $uid, string $dob): ActorCode
    {
        $response = $this->makePostRequest(
            'v1/validate',
            [
                'lpa'  => $uid,
                'dob'  => $dob,
                'code' => $code,
            ]
        );

        return new ActorCode(
            json_decode((string) $response->getBody(), true),
            new DateTime($response->getHeaderLine('Date'))
        );
    }

    /**
     * @param string $code
     * @throws ApiException
     */
    public function flagCodeAsUsed(string $code): void
    {
        $this->makePostRequest('v1/revoke', ['code' => $code]);
    }

    public function checkActorHasCode(string $lpaId, string $actorId): ActorCode
    {
        $response = $this->makePostRequest(
            'v1/exists',
            [
                'lpa'   => $lpaId,
                'actor' => $actorId,
            ]
        );

        return new ActorCode(
            json_decode((string) $response->getBody(), true),
            new DateTime($response->getHeaderLine('Date'))
        );
    }

    /**
     * @param string $url
     * @param array $body
     * @return ResponseInterface
     * @throws ApiException
     */
    private function makePostRequest(string $url, array $body): ResponseInterface
    {
        $url  = sprintf('%s/%s', $this->apiBaseUri, $url);
        $body = json_encode($body);

        $request = new Request('POST', $url, $this->buildHeaders(), $body);
        $request = $this->awsSignature->sign($request);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $ge) {
            throw ApiException::create('Error whilst communicating with actor codes service', null, $ge);
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw ApiException::create('Actor codes service returned non-ok response', $response);
        }

        return $response;
    }

    private function buildHeaders(): array
    {
        $headerLines = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->traceId)) {
            $headerLines[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        return $headerLines;
    }
}
