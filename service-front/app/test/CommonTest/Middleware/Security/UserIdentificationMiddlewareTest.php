<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Security\UserIdentificationService;
use Common\Service\Security\UserIdentity;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @coversDefaultClass UserIdentificationMiddleware
 */
class UserIdentificationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $delegateProphecy;
    private ObjectProphecy $idServiceProphecy;
    private ObjectProphecy $requestProphecy;
    private ObjectProphecy $sessionProphecy;

    public function setUp(): void
    {
        $this->sessionProphecy = $this->prophesize(SessionInterface::class);
        $this->sessionProphecy
            ->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->willReturn(null);

        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->willReturn($this->sessionProphecy->reveal());
        $this->requestProphecy
            ->getAttribute(RouteResult::class)
            ->willReturn(null);
        $this->requestProphecy
            ->getHeaders()
            ->willReturn([]);

        $this->idServiceProphecy = $this->prophesize(UserIdentificationService::class);

        $this->delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $this->delegateProphecy
            ->handle($this->requestProphecy->reveal())
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function it_can_be_created(): void
    {
        $sut = new UserIdentificationMiddleware(
            $this->idServiceProphecy->reveal()
        );

        $this->assertInstanceOf(UserIdentificationMiddleware::class, $sut);
    }

    /**
     * @test
     * @covers ::process
     */
    public function it_uniquely_identifies_a_user_without_a_session(): void
    {
        $id = new UserIdentity('', '', '', '', '');

        $this->sessionProphecy
            ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled();

        $this->requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());

        $this->idServiceProphecy
            ->id([], null)
            ->shouldBeCalled()
            ->willReturn($id);

        $uim = new UserIdentificationMiddleware(
            $this->idServiceProphecy->reveal()
        );

        $response = $uim->process($this->requestProphecy->reveal(), $this->delegateProphecy->reveal());
    }

    /**
     * @test
     * @covers ::process
     */
    public function it_uniquely_identifies_a_user_with_a_session_first_visit(): void
    {
        $id = new UserIdentity('', '', '', '', '');

        $this->sessionProphecy
            ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled();

        $this->requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());
        $this->requestProphecy
            ->getAttribute(RouteResult::class)
            ->willReturn(null);

        $this->idServiceProphecy = $this->prophesize(UserIdentificationService::class);
        $this->idServiceProphecy
            ->id([], null)
            ->willReturn($id);

        $uim = new UserIdentificationMiddleware(
            $this->idServiceProphecy->reveal()
        );

        $response = $uim->process($this->requestProphecy->reveal(), $this->delegateProphecy->reveal());
    }

    /**
     * @test
     * @covers ::process
     */
    public function it_uniquely_identifies_a_user_with_a_session_subsequent_visit(): void
    {
        $id = new UserIdentity('', '', '', '', '');

        $this->sessionProphecy
            ->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->willReturn($id->hash());
        $this->sessionProphecy
            ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled();

        $this->requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());

        $this->idServiceProphecy
            ->id([], $id->hash())
            ->willReturn($id);

        $uim = new UserIdentificationMiddleware(
            $this->idServiceProphecy->reveal()
        );

        $response = $uim->process($this->requestProphecy->reveal(), $this->delegateProphecy->reveal());
    }

    /**
     * @test
     * @dataProvider javascriptRoutes
     * @covers ::process
     * @covers ::isValidRoute
     */
    public function it_does_not_update_identity_for_javascript_endpoints(string $routeName, bool $setExpected): void
    {
        $id = new UserIdentity('', '', '', '', '');

        $this->sessionProphecy
            ->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->willReturn($id->hash());

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy
            ->getMatchedRouteName()
            ->willReturn($routeName);

        $this->requestProphecy
            ->getAttribute(RouteResult::class)
            ->willReturn($routeResultProphecy->reveal());
        $this->requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
            ->shouldBeCalled()
            ->willReturn($this->requestProphecy->reveal());

        $this->idServiceProphecy = $this->prophesize(UserIdentificationService::class);
        $this->idServiceProphecy
            ->id([], $id->hash())
            ->willReturn($id);

        if ($setExpected) {
            $this->requestProphecy
                ->getHeader('accept')
                ->willReturn(['']);
            $this->sessionProphecy
                ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id->hash())
                ->shouldBeCalled();
        } else {
            $this->requestProphecy
                ->getHeader('accept')
                ->willReturn(['application/json']);
            $this->sessionProphecy
                ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, Argument::type('string'))
                ->shouldNotBeCalled();
        }

        $uim = new UserIdentificationMiddleware(
            $this->idServiceProphecy->reveal()
        );

        $response = $uim->process($this->requestProphecy->reveal(), $this->delegateProphecy->reveal());
    }

    public function javascriptRoutes(): array
    {
        return [
            'route is session-check'   => [
                'session-check',
                false,
            ],
            'route is session-refresh' => [
                'session-refresh',
                false,
            ],
            'route is home'            => [
                'home',
                true,
            ],
        ];
    }
}
