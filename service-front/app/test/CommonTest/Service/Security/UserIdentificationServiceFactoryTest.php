<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\UserIdentificationService;
use Common\Service\Security\UserIdentificationServiceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class UserIdentificationServiceFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_configured_uid_service()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'security' => [
                        'uid_hash_salt' => 'a_secure_hash_from_the_environment',
                    ],
                ]
            );

        $factory = new UserIdentificationServiceFactory();

        $instance = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(UserIdentificationService::class, $instance);
    }

    /** @test */
    public function it_throws_an_exception_if_security_config_not_found()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn([]);

        $factory = new UserIdentificationServiceFactory();

        $this->expectException(RuntimeException::class);
        $instance = $factory($containerProphecy->reveal());
    }

    /** @test */
    public function it_throws_an_exception_if_salt_config_not_found()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'security' => [],
                ]
            );

        $factory = new UserIdentificationServiceFactory();

        $this->expectException(RuntimeException::class);
        $instance = $factory($containerProphecy->reveal());
    }
}
