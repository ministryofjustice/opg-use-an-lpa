<?php

declare(strict_types=1);

namespace CommonTest\Middleware\ErrorHandling;

use Common\Middleware\ErrorHandling\GoneHandler;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GoneHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testReturnsGoneResponseForGoneUris(): void
    {
        $rendererProphecy        = $this->prophesize(TemplateRendererInterface::class);
        $responseProphecy        = $this->prophesize(ResponseInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $rendererProphecy->render('error::410')->willReturn('Gone');

        $responseProphecy->getBody()->willReturn(new class {
            public function write($content)
            {
            }
        });
        $responseProphecy->withStatus(StatusCodeInterface::STATUS_GONE)->willReturn($responseProphecy->reveal());
        $responseFactoryProphecy->createResponse()->willReturn($responseProphecy->reveal());

        $goneHandler = new GoneHandler(
            $responseFactoryProphecy->reveal(),
            $rendererProphecy->reveal()
        );

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getUri()->willReturn(new class {
            public function getPath()
            {
                return '/reset-password';
            }
        });

        $handlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $response = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($responseProphecy->reveal(), $response);
    }

    public function testReturnsGoneStatusForSpecifiedRoutes()
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

        $requestProphecy->getUri()->willReturn(new class {
            public function getPath()
            {
                return '/reset-password';
            }
        });

        $responseProphecy->withStatus(StatusCodeInterface::STATUS_GONE)->shouldBeCalledTimes(1);
        $templateRendererProphecy->render('error::410')->shouldBeCalledTimes(1);

        $result = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertSame($responseProphecy->reveal(), $result);
    }

    public function testPassesControlToNextMiddlewareForOtherUris(): void
    {
        $rendererProphecy        = $this->prophesize(TemplateRendererInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $goneHandler = new GoneHandler(
            $responseFactoryProphecy->reveal(),
            $rendererProphecy->reveal()
        );

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getUri()->willReturn(new class {
            public function getPath()
            {
                return '/some-other-uri';
            }
        });

        $nextResponseProphecy = $this->prophesize(ResponseInterface::class);
        $handlerProphecy      = $this->prophesize(RequestHandlerInterface::class);
        $handlerProphecy->handle($requestProphecy->reveal())->willReturn($nextResponseProphecy->reveal());

        $response = $goneHandler->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertSame($nextResponseProphecy->reveal(), $response);
    }
}
