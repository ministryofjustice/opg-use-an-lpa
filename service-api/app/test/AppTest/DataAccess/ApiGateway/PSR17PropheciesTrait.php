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
    private ClientInterface|ObjectProphecy $httpClientProphecy;
    private RequestFactoryInterface|ObjectProphecy $requestFactoryProphecy;
    private RequestInterface|ObjectProphecy $requestProphecy;
    private StreamFactoryInterface|ObjectProphecy $streamFactoryProphecy;

    public function generateCleanPSR17Prophecies(): void
    {
        $this->httpClientProphecy     = $this->prophesize(ClientInterface::class);
        $this->streamFactoryProphecy  = $this->prophesize(StreamFactoryInterface::class);
        $this->requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
        $this->requestProphecy        = $this->prophesize(RequestInterface::class);
    }

    public function generatePSR17Prophecies(ResponseInterface $response, string $traceId, array $data): void
    {
        $this->generateCleanPSR17Prophecies();

        $this->httpClientProphecy->sendRequest(Argument::any())->willReturn($response);

        $this->streamFactoryProphecy
            ->createStream(json_encode($data))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

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
    }

    public function generatePSR17PropheciesWithoutAssertions(
        ResponseInterface $response,
        string $traceId,
        array $data,
    ): void {
        $this->generateCleanPSR17Prophecies();

        $this->httpClientProphecy->sendRequest(Argument::any())->willReturn($response);

        $this->streamFactoryProphecy
            ->createStream(json_encode($data))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestProphecy
            ->withBody(Argument::any())
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('Accept', 'application/json')
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('Content-Type', 'application/json')
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->withHeader('x-amzn-trace-id', $traceId)
            ->willReturn($this->requestProphecy->reveal());
    }
}
