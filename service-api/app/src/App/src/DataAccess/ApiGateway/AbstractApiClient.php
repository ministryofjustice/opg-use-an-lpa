<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\Service\Log\RequestTracing;
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
     * @param string                  $traceId An amazon trace id to pass to subsequent services
     */
    public function __construct(
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly RequestSignerFactory $requestSignerFactory,
        protected readonly string $apiBaseUri,
        protected readonly string $traceId,
    ) {
    }

    protected function attachHeaders(RequestInterface $request): RequestInterface
    {
        $headerLines = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->traceId)) {
            $headerLines[RequestTracing::TRACE_HEADER_NAME] = $this->traceId;
        }

        foreach ($headerLines as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request;
    }
}
