<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\CSPNonceMiddleware;
use Common\Service\Security\CSPNonce;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @coversDefaultClass \Common\Middleware\Security\CSPNonceMiddleware
 */
class CSPNonceMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers ::__construct
     * @covers ::process
     */
    public function it_correctly_attaches_a_csp_nonce_header(): void
    {
        $testNonce = new CSPNonce('test');

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->withAttribute(CSPNonceMiddleware::NONCE_ATTRIBUTE, $testNonce)
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy
            ->withAddedHeader('X-CSP-Nonce', 'nonce-test')
            ->shouldBeCalled()
            ->willReturn($responseProphecy->reveal());

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($responseProphecy->reveal());

        $sut = new CSPNonceMiddleware($testNonce);

        $sut->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
