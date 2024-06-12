<?php

declare(strict_types=1);

namespace CommonTest\Middleware\ErrorHandling;

use Common\Middleware\ErrorHandling\GoneHandler;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GoneHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function returns_gone_response_for_gone_uris(): void
    {
        $rendererProphecy        = $this->prophesize(TemplateRendererInterface::class);
        $responseProphecy        = $this->prophesize(ResponseInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $rendererProphecy->render('error::410')->willReturn('Gone');

        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->write(Argument::any())->willReturn(0);

        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->withStatus(StatusCodeInterface::STATUS_GONE)->willReturn($responseProphecy->reveal());
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $goneHandler = new GoneHandler(
            $responseFactoryProphecy->reveal(),
            $rendererProphecy->reveal()
        );

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/reset-password');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $handlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $response = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($responseProphecy->reveal(), $response);
    }

    /**
     * @test
     */
    public function returns_gone_status_for_specified_routes(): void
    {
        $templateRendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $responseProphecy         = $this->prophesize(ResponseInterface::class);
        $streamProphecy           = $this->prophesize(StreamInterface::class);

        $streamProphecy->write('Page gone')->shouldBeCalled();
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());
        $responseProphecy->withStatus(410)->willReturn($responseProphecy->reveal());

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $goneHandler = new GoneHandler($responseFactoryProphecy->reveal(), $templateRendererProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $handlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $templateRendererProphecy->render('error::410')->willReturn('Page gone');

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/reset-password');

        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $responseProphecy->withStatus(StatusCodeInterface::STATUS_GONE)->shouldBeCalledTimes(1);
        $templateRendererProphecy->render('error::410')->shouldBeCalledTimes(1);

        $result = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertSame($responseProphecy->reveal(), $result);
    }

    /**
     * @test
     */
    public function passes_control_to_the_next_middleware_for_other_uris(): void
    {
        $rendererProphecy        = $this->prophesize(TemplateRendererInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $goneHandler = new GoneHandler(
            $responseFactoryProphecy->reveal(),
            $rendererProphecy->reveal()
        );

        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy->getPath()->willReturn('/some-other-uri');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        $nextResponseProphecy = $this->prophesize(ResponseInterface::class);
        $handlerProphecy      = $this->prophesize(RequestHandlerInterface::class);
        $handlerProphecy->handle($requestProphecy->reveal())->willReturn($nextResponseProphecy->reveal());

        $response = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertSame($nextResponseProphecy->reveal(), $response);
    }
}
