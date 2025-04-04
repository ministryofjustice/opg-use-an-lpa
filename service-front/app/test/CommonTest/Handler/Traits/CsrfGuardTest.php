<?php

declare(strict_types=1);

namespace CommonTest\Handler\Traits;

use PHPUnit\Framework\Attributes\Test;
use Common\Handler\Traits\CsrfGuard;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;

class CsrfGuardTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_get_guard_from_request_pipeline(): void
    {
        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($this->prophesize(CsrfGuardInterface::class)->reveal());

        $guardTrait = $this->getMockForTrait(CsrfGuard::class);

        $guard = $guardTrait->getCsrfGuard($requestProphecy->reveal());

        $this->assertInstanceOf(CsrfGuardInterface::class, $guard);
    }
}
