<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\Response\ActorCode;
use App\Exception\ApiException;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class ActorCodes extends AbstractApiClient
{
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
     * @throws ApiException|Exception
     */
    public function flagCodeAsUsed(string $code): void
    {
        $this->makePostRequest('v1/revoke', ['code' => $code]);
    }

    /**
     * @throws ApiException
     */
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
        $url = sprintf('%s/%s', $this->apiBaseUri, $url);

        $request = $this->requestFactory->createRequest('POST', $url);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($body)));

        $request = $this->attachHeaders($request);
        $request = ($this->requestSignerFactory)(SignatureType::ActorCodes)->sign($request);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create('Error whilst communicating with actor codes service', null, $ce);
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw ApiException::create('Actor codes service returned non-ok response', $response);
        }

        return $response;
    }
}
