<?php

declare(strict_types=1);

namespace CommonTest\Service\Cache\Symfony;

use Common\Service\Cache\Symfony\TagAwareFactory;
use DI\Factory\RequestedEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use UnexpectedValueException;

#[CoversClass(TagAwareFactory::class)]
final class TagAwareFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('poolNames')]
    public function it_should_wrap_the_selected_pool(string $name, bool $phpdi): void
    {
        $pool      = $this->createStub(AdapterInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with($name)->willReturn($pool);
        $requestedName = $name . '.taggable';
        if ($phpdi) {
            $entry = $this->createStub(RequestedEntry::class);
            $entry->method('getName')->willReturn($requestedName);
            $requestedName = $entry;
        }

        $cache = (new TagAwareFactory())($container, $requestedName);

        self::assertInstanceOf(TagAwareAdapter::class, $cache);
    }

    public static function poolNames(): array
    {
        return [
            'ServiceManager request pool' => [
                'cache.request',
                false,
            ],
            'PHP-DI application pool'     => [
                'cache.app',
                true,
            ],
        ];
    }

    #[Test]
    public function it_should_reject_an_unknown_service_name(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())->method('get');
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported tag-aware Symfony cache service');

        (new TagAwareFactory())($container, 'unknown');
    }

    #[Test]
    public function it_should_reject_a_pool_that_is_not_an_adapter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('cache.app')->willReturn(new stdClass());
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cache.app must implement AdapterInterface');

        (new TagAwareFactory())($container, 'cache.app.taggable');
    }
}
