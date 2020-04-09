<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\RateLimiterInterface;
use Common\Service\Security\RateLimitServiceFactory;
use Laminas\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use const Laminas\Cache\Exception\InvalidArgumentException;

class RateLimitServiceFactoryTest extends TestCase
{
    /** @test */
    public function it_requires_a_ratelimits_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_requires_a_ratelimits_configuration_for_individual_services()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                ['ratelimits' => []]
            );

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_requires_a_valid_ratelimits_configuration_type_for_a_service()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => []
                    ]
                ]
            );

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_requires_a_valid_adaptor_configuration_for_a_keyed_limiter_service()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => [
                            'type' => 'keyed'
                        ]
                    ]
                ]
            );

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(InvalidArgumentException::class);
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_throws_an_exception_with_an_invalid_limiter_service_type()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => [
                            'type' => 'does-not-exist',
                            'storage' => [
                                'adapter' => 'memory'
                            ]
                        ]
                    ]
                ]
            );

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No class available for rate limit type does-not-exist');
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_creates_a_configured_keyed_rate_limiter_service()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => [
                            'type' => 'keyed',
                            'storage' => [
                                'adapter' => 'memory'
                            ]
                        ]
                    ]
                ]
            );
        $containerProphecy
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $rateLimiter = $factory->factory('a-rate-limiter');

        $this->assertInstanceOf(RateLimiterInterface::class, $rateLimiter);
    }

}
