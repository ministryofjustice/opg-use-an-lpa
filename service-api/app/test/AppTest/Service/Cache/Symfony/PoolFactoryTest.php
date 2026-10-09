<?php

declare(strict_types=1);

namespace AppTest\Service\Cache\Symfony;

use App\Service\Cache\Symfony\PoolFactory;
use DI\Factory\RequestedEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use UnexpectedValueException;

#[CoversClass(PoolFactory::class)]
final class PoolFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_an_array_pool_for_a_phpdi_entry_and_injects_the_logger(): void
    {
        $entry = $this->createMock(RequestedEntry::class);
        $entry->method('getName')->willReturn('cache.test');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $pool = (new PoolFactory())($this->container($this->config(), $logger), $entry);

        self::assertInstanceOf(ArrayAdapter::class, $pool);
        self::assertTrue($pool->save($pool->getItem('example')->set('value')));
        self::assertSame('value', $pool->getItem('example')->get());
        self::assertFalse($pool->save($pool->getItem('unserializable')->set(static fn () => null)));
    }

    #[Test]
    public function it_creates_an_apcu_pool_or_reports_that_apcu_is_disabled(): void
    {
        $config                               = $this->config('apcu');
        $config['symfony_cache']['namespace'] = 'factory-test-' . bin2hex(random_bytes(8));

        if (!ApcuAdapter::isSupported() || !apcu_enabled()) {
            $this->expectException(UnexpectedValueException::class);
            $this->expectExceptionMessage('APCu cache adapter requires APCu to be enabled for this runtime');
        }

        $pool = (new PoolFactory())($this->container($config), 'cache.test');

        self::assertInstanceOf(ApcuAdapter::class, $pool);
        self::assertTrue($pool->save($pool->getItem('example')->set('value')));
        self::assertSame('value', $pool->getItem('example')->get());
        self::assertTrue($pool->clear());
    }

    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function it_rejects_invalid_configuration(array $config, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        (new PoolFactory())($this->container($config), 'cache.test');
    }

    public static function invalidConfigurations(): array
    {
        $config = [
            'symfony_cache' => [
                'namespace' => 'test',
                'version'   => '1',
                'pools'     => [
                    'cache.test' => [
                        'adapter'          => 'array',
                        'default_lifetime' => 60,
                    ],
                ],
            ],
        ];

        return [
            'missing cache settings' => [
                [],
                'Missing Symfony cache configuration',
            ],
            'missing pool'           => [
                array_replace_recursive($config, ['symfony_cache' => ['pools' => ['cache.test' => null]]]),
                'Missing Symfony cache configuration',
            ],
            'invalid settings'       => [
                array_replace_recursive($config, ['symfony_cache' => ['namespace' => '']]),
                'Invalid Symfony cache configuration',
            ],
            'unsupported adapter'    => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => 'unknown']]],
                ]),
                'Unsupported Symfony cache adapter: unknown',
            ],
        ];
    }

    #[Test]
    public function it_rejects_an_invalid_logger(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Symfony cache logger must implement LoggerInterface');

        (new PoolFactory())($this->container($this->config(), new stdClass()), 'cache.test');
    }

    private function config(string $adapter = 'array'): array
    {
        return [
            'symfony_cache' => [
                'namespace' => 'test',
                'version'   => '1',
                'pools'     => [
                    'cache.test' => [
                        'adapter'          => $adapter,
                        'default_lifetime' => 60,
                    ],
                ],
            ],
        ];
    }

    private function container(array $config, ?object $logger = null): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(LoggerInterface::class)->willReturn($logger !== null);
        $container->method('get')->willReturnMap([
            [
                'config',
                $config,
            ],
            [
                LoggerInterface::class,
                $logger,
            ],
        ]);

        return $container;
    }
}
