<?php

declare(strict_types=1);

namespace CommonTest\Handler\Traits;

use Common\Handler\Traits\CsrfGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;

class CsrfGuardTest extends TestCase
{
    /** @test */
    public function can_get_guard_from_request_pipeline()
    {
        $requestProphecy = $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE)
            ->willReturn($this->prophesize(CsrfGuardInterface::class)->reveal());

        $guardTrait = $this->getMockForTrait(CsrfGuard::class);

        $guard = $guardTrait->getCsrfGuard($requestProphecy->reveal());

        $this->assertInstanceOf(CsrfGuardInterface::class, $guard);
    }
}
