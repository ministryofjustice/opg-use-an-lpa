<?php

declare(strict_types=1);

namespace CommonTest\Handler\Traits;

use PHPUnit\Framework\Attributes\Test;
use Common\Handler\Traits\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;

class UserTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_get_user_from_request_pipeline(): void
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

    #[Test]
    public function gets_null_when_user_is_not_logged_in(): void
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
