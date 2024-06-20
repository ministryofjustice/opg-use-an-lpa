<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

trait PSR17PropheciesTrait
{
    private ObjectProphecy|ClientInterface $httpClientProphecy;
    private ObjectProphecy|RequestFactoryInterface $requestFactoryProphecy;
    private ObjectProphecy|RequestInterface $requestProphecy;
    private ObjectProphecy|StreamFactoryInterface $streamFactoryProphecy;

    public function generatePSR17Prophecies(ResponseInterface $response, string $traceId, array $data): void
    {
        $this->httpClientProphecy = $this->prophesize(ClientInterface::class);
        $this->httpClientProphecy->sendRequest(Argument::any())->willReturn($response);

        $this->streamFactoryProphecy = $this->prophesize(StreamFactoryInterface::class);
        $this->streamFactoryProphecy
            ->createStream(json_encode($data))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestProphecy = $this->prophesize(RequestInterface::class);
        $this->requestProphecy
            ->withBody(Argument::any())
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('Accept', 'application/json')
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('Content-Type', 'application/json')
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('x-amzn-trace-id', $traceId)
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());

        $this->requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
    }
}