<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\RateLimiterInterface;
use Common\Service\Security\RateLimitServiceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(RateLimitServiceFactory::class)]
class RateLimitServiceFactoryTest extends TestCase
{
    #[Test]
    public function it_requires_a_ratelimits_configuration(): void
    {
        $factory = new RateLimitServiceFactory($this->createStub(ContainerInterface::class));

        $this->expectException(RuntimeException::class);
        $factory->factory('a-rate-limiter');
    }

    #[Test]
    public function it_requires_a_ratelimits_configuration_for_individual_services(): void
    {
        $factory = new RateLimitServiceFactory(
            $this->createContainer(['ratelimits' => []]),
        );

        $this->expectException(RuntimeException::class);
        $factory->factory('a-rate-limiter');
    }

    #[Test]
    public function it_requires_a_valid_ratelimits_configuration_type_for_a_service(): void
    {
        $factory = new RateLimitServiceFactory(
            $this->createContainer([
                'ratelimits' => [
                    'a-rate-limiter' => [],
                ],
            ]),
        );

        $this->expectException(RuntimeException::class);
        $factory->factory('a-rate-limiter');
    }

    #[Test]
    public function it_throws_an_exception_with_an_invalid_limiter_service_type(): void
    {
        $container = $this->createContainer([
            'ratelimits' => [
                'a-rate-limiter' => [
                    'type' => 'does-not-exist',
                ],
            ],
        ]);
        $factory   = new RateLimitServiceFactory($container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No class available for rate limit type does-not-exist');
        $factory->factory('a-rate-limiter');
    }

    #[Test]
    public function it_uses_the_cache_pool_selected_for_a_rate_limit(): void
    {
        $customPool = new ArrayAdapter();
        $factory    = new RateLimitServiceFactory(
            $this->createContainer(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => [
                            'type'       => 'keyed',
                            'cache_pool' => 'cache.ratelimits.custom',
                        ],
                    ],
                ],
                ['cache.ratelimits.custom' => $customPool],
            ),
        );

        $factory->factory('a-rate-limiter')->limit('an-identity');

        $this->assertNotEmpty($customPool->getValues());
    }

    #[Test]
    public function it_creates_a_configured_keyed_rate_limiter_service(): void
    {
        $factory = new RateLimitServiceFactory(
            $this->createContainer(
                [
                    'ratelimits' => [
                        'a-rate-limiter' => [
                            'type' => 'keyed',
                        ],
                    ],
                ],
            ),
        );

        $rateLimiter = $factory->factory('a-rate-limiter');

        $this->assertSame('a-rate-limiter', $rateLimiter->getName());
    }

    #[Test]
    public function it_creates_multiple_configured_rate_limiters(): void
    {
        $factory = new RateLimitServiceFactory(
            $this->createContainer(
                [
                    'ratelimits' => [
                        'a-rate-limiter'   => [
                            'type' => 'keyed',
                        ],
                        'a-second-limiter' => [
                            'type' => 'keyed',
                        ],
                        'a-third-limiter'  => [
                            'type' => 'keyed',
                        ],
                    ],
                ],
            ),
        );

        $limiters = $factory->all();

        $this->assertIsArray($limiters);
        $this->assertCount(3, $limiters);
        $this->assertEqualsCanonicalizing(
            [
                'a-rate-limiter',
                'a-second-limiter',
                'a-third-limiter',
            ],
            array_map(static fn (RateLimiterInterface $limiter): string => $limiter->getName(), $limiters),
        );
    }

    #[Test]
    public function it_requires_multiple_ratelimits_configuration(): void
    {
        $factory = new RateLimitServiceFactory($this->createStub(ContainerInterface::class));

        $this->expectException(RuntimeException::class);
        $factory->all();
    }

    private function createContainer(array $config, array $pools = []): ContainerInterface
    {
        $services = [
            [
                'config',
                $config,
            ],
            [
                LoggerInterface::class,
                $this->createStub(LoggerInterface::class),
            ],
        ];

        foreach ($config['ratelimits'] ?? [] as $limitName => $rateLimit) {
            $poolName   = $rateLimit['cache_pool'] ?? 'cache.ratelimits.' . $limitName;
            $services[] = [
                $poolName,
                $pools[$poolName] ?? new ArrayAdapter(),
            ];
        }

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap($services);

        return $container;
    }
}
