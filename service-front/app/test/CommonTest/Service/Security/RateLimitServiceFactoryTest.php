<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\RateLimiterInterface;
use Common\Service\Security\RateLimitServiceFactory;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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
    public function it_throws_an_exception_with_an_invalid_limiter_service_type()
    {
        $cacheAdapterProphecy = $this->prophesize(StorageAdapterFactoryInterface::class);
        $cacheAdapterProphecy
            ->createFromArrayConfiguration(Argument::type('array'))
            ->willReturn($this->prophesize(StorageInterface::class)->reveal());

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
        $containerProphecy
            ->get(StorageAdapterFactoryInterface::class)
            ->shouldBeCalled()
            ->willReturn($cacheAdapterProphecy->reveal());

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No class available for rate limit type does-not-exist');
        $rateLimiter = $factory->factory('a-rate-limiter');
    }

    /** @test */
    public function it_creates_a_configured_keyed_rate_limiter_service()
    {
        $cacheAdapterProphecy = $this->prophesize(StorageAdapterFactoryInterface::class);
        $cacheAdapterProphecy
            ->createFromArrayConfiguration(Argument::type('array'))
            ->willReturn($this->prophesize(StorageInterface::class)->reveal());

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
        $containerProphecy
            ->get(StorageAdapterFactoryInterface::class)
            ->shouldBeCalled()
            ->willReturn($cacheAdapterProphecy->reveal());

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $rateLimiter = $factory->factory('a-rate-limiter');

        $this->assertInstanceOf(RateLimiterInterface::class, $rateLimiter);
    }

    /** @test */
    public function it_creates_multiple_configured_rate_limiters()
    {
        $cacheAdapterProphecy = $this->prophesize(StorageAdapterFactoryInterface::class);
        $cacheAdapterProphecy
            ->createFromArrayConfiguration(Argument::type('array'))
            ->willReturn($this->prophesize(StorageInterface::class)->reveal());

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
                        ],
                        'a-second-limiter' => [
                            'type' => 'keyed',
                            'storage' => [
                                'adapter' => 'memory'
                            ]
                        ],
                        'a-third-limiter' => [
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
        $containerProphecy
            ->get(StorageAdapterFactoryInterface::class)
            ->shouldBeCalledTimes(3)
            ->willReturn($cacheAdapterProphecy->reveal());

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $limiters = $factory->all();

        $this->assertIsArray($limiters);
        $this->assertCount(3, $limiters);
        foreach ($limiters as $limiter) {
            $this->assertInstanceOf(RateLimiterInterface::class, $limiter);
        }
    }

    /** @test */
    public function it_requires_multiple_ratelimits_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new RateLimitServiceFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $rateLimiter = $factory->all();
    }

}
