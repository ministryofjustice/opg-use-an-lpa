<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AbstractApiClient
{
    /**
     * ActorCodes Constructor
     *
     * @param ClientInterface         $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface  $streamFactory
     * @param RequestSignerFactory    $requestSignerFactory
     * @param string                  $apiBaseUri
     */
    public function __construct(
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly RequestSignerFactory $requestSignerFactory,
        protected readonly string $apiBaseUri,
    ) {
    }

    protected function attachHeaders(RequestInterface $request): RequestInterface
    {
        $headerLines = [
            'Accept'       => 'application/vnd.opg-data.v1+json,application/json',
            'Content-Type' => 'application/json',
        ];

        foreach ($headerLines as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request;
    }
}
