<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Exception\ApiException;
use App\Exception\RequestSigningException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

trait PostRequest
{
    /**
     * @param string        $url
     * @param array         $body
     * @param SignatureType $signature
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function makePostRequest(
        string $url,
        array $body,
        SignatureType $signature = SignatureType::None,
    ): ResponseInterface {
        $url = sprintf('%s/%s', $this->apiBaseUri, $url);

        $request = $this->requestFactory->createRequest('POST', $url);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($body)));

        $request = $this->attachHeaders($request);

        try {
            $request = ($this->requestSignerFactory)($signature)->sign($request);
        } catch (RequestSigningException $e) {
            throw ApiException::create('Error whilst signing request for upstream service', null, $e);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create('Error whilst communicating with upstream service', null, $ce);
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw ApiException::create('Upstream service returned non-ok response', $response);
        }

        return $response;
    }
}
