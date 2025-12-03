<?php

declare(strict_types=1);

namespace CommonTest\Handler\Traits;

use Common\Handler\Traits\User;
use Mezzio\Authentication\UserInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

class UserTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_get_user_from_request_pipeline(): void
    {
        $userInterfaceProphecy = $this->prophesize(UserInterface::class);

        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute(UserInterface::class)->willReturn($userInterfaceProphecy->reveal());

        $userTrait = new class {
            use User;
        };

        $user = $userTrait->getUser($requestProphecy->reveal());

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    #[Test]
    public function gets_null_when_user_is_not_logged_in(): void
    {
        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $userTrait = new class {
            use User;
        };

        $user = $userTrait->getUser($requestProphecy->reveal());

        $this->assertNull($user);
    }
}
