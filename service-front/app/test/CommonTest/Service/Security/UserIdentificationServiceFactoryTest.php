<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\UserIdentificationService;
use Common\Service\Security\UserIdentificationServiceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class UserIdentificationServiceFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_configured_uid_service()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new UserIdentificationServiceFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(UserIdentificationService::class, $instance);
    }
}
