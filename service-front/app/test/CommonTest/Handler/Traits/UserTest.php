<?php

declare(strict_types=1);

namespace CommonTest\Handler\Traits;

use Common\Handler\Traits\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;

class UserTest extends TestCase
{
    /** @test */
    public function can_get_user_from_request_pipeline()
    {
        $userInterfaceProphecy = $this->prophesize(UserInterface::class);

        $authenticationProhphecy = $this->prophesize(AuthenticationInterface::class);
        $authenticationProhphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn($userInterfaceProphecy->reveal());

        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $userTrait = $this->getMockForTrait(User::class);

        $userTrait->setAuthenticator($authenticationProhphecy->reveal());

        $user = $userTrait->getUser($requestProphecy->reveal());

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    /** @test */
    public function gets_null_when_user_is_not_logged_in()
    {
        $authenticationProhphecy = $this->prophesize(AuthenticationInterface::class);
        $authenticationProhphecy->authenticate(Argument::type(ServerRequestInterface::class))
            ->willReturn(null);

        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $userTrait = $this->getMockForTrait(User::class);

        $userTrait->setAuthenticator($authenticationProhphecy->reveal());

        $user = $userTrait->getUser($requestProphecy->reveal());

        $this->assertNull($user);
    }
}
