<?php

declare(strict_types=1);

namespace CommonTest\Middleware\Security;

use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Log\EventCodes;
use Common\Service\Security\UserIdentificationService;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class UserIdentificationMiddlewareTest extends TestCase
{
    /** @test */
    public function it_uniquely_identifies_a_user_without_a_session()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn(null);
        $requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, 'a-unique-user-id')
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $idServiceProphecy = $this->prophesize(UserIdentificationService::class);
        $idServiceProphecy
            ->id($requestProphecy->reveal())
            ->willReturn('a-unique-user-id');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $uim = new UserIdentificationMiddleware(
            $idServiceProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $response = $uim->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_uniquely_identifies_a_user_with_a_session_first_visit()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy
            ->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn(null);
        $sessionProphecy
            ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, 'a-unique-user-id')
            ->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());
        $requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, 'a-unique-user-id')
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $idServiceProphecy = $this->prophesize(UserIdentificationService::class);
        $idServiceProphecy
            ->id($requestProphecy->reveal())
            ->willReturn('a-unique-user-id');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $uim = new UserIdentificationMiddleware(
            $idServiceProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $response = $uim->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }

    /** @test */
    public function it_correctly_flags_a_probably_nefarious_changed_identity()
    {
        $sessionProphecy = $this->prophesize(SessionInterface::class);
        $sessionProphecy
            ->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn('a-unique-but-different-id');
        $sessionProphecy
            ->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, 'a-unique-user-id')
            ->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)
            ->shouldBeCalled()
            ->willReturn($sessionProphecy->reveal());
        $requestProphecy
            ->withAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, 'a-unique-user-id')
            ->shouldBeCalled()
            ->willReturn($requestProphecy->reveal());

        $idServiceProphecy = $this->prophesize(UserIdentificationService::class);
        $idServiceProphecy
            ->id($requestProphecy->reveal())
            ->willReturn('a-unique-user-id');

        $delegateProphecy = $this->prophesize(RequestHandlerInterface::class);
        $delegateProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->debug(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalled();
        $loggerProphecy
            ->notice(
                Argument::type('string'),
                Argument::that(
                    function ($parameter): bool {
                        $this->assertIsArray($parameter);
                        $this->assertArrayHasKey('event_code', $parameter);
                        $this->assertEquals(EventCodes::IDENTITY_HASH_CHANGE, $parameter['event_code']);

                        return true;
                    }
                )
            )
            ->shouldBeCalled();

        $uim = new UserIdentificationMiddleware(
            $idServiceProphecy->reveal(),
            $loggerProphecy->reveal()
        );

        $response = $uim->process($requestProphecy->reveal(), $delegateProphecy->reveal());
    }
}
