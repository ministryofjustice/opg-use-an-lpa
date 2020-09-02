<?php

declare(strict_types=1);

namespace CommonTest\Middleware\I18n;

use Acpr\I18n\Translator;
use Common\Middleware\I18n\SetLocaleMiddleware;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetLocaleMiddlewareTest extends TestCase
{
    /** @test */
    public function it_sets_a_default_locale_of_en_GB_if_none_provided(): void
    {
        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        $translatorProphecy = $this->prophesize(Translator::class);

        $uriInterfaceProphecy = $this->prophesize(UriInterface::class);
        $uriInterfaceProphecy
            ->getPath()
            ->willReturn('/home');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getUri()
            ->willReturn($uriInterfaceProphecy->reveal());
        $requestProphecy
            ->withAttribute('locale', 'en_GB')
            ->willReturn($requestProphecy->reveal());

        $handlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $handlerProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        // --

        $middleware = new SetLocaleMiddleware($urlHelperProphecy->reveal(), $translatorProphecy->reveal());

        $response = $middleware->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function it_sets_a_locale_of_cy_GB_if_requested_to(): void
    {
        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $urlHelperProphecy
            ->setBasePath('cy')
            ->shouldBeCalled();

        $translatorProphecy = $this->prophesize(Translator::class);

        $uriInterfaceProphecy = $this->prophesize(UriInterface::class);
        $uriInterfaceProphecy
            ->getPath()
            ->willReturn('/cy/home');
        $uriInterfaceProphecy
            ->withPath('/home')
            ->willReturn($uriInterfaceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getUri()
            ->willReturn($uriInterfaceProphecy->reveal());
        $requestProphecy
            ->withAttribute('locale', 'cy')
            ->willReturn($requestProphecy->reveal());
        $requestProphecy
            ->withUri($uriInterfaceProphecy->reveal())
            ->willReturn($requestProphecy->reveal());

        $handlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $handlerProphecy
            ->handle($requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        // --

        $middleware = new SetLocaleMiddleware($urlHelperProphecy->reveal(), $translatorProphecy->reveal());

        $response = $middleware->process($requestProphecy->reveal(), $handlerProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
