<?php

declare(strict_types=1);

namespace AppTest\Service\Cache\Symfony;

use App\Service\Cache\Symfony\TagAwareFactory;
use DI\Factory\RequestedEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use UnexpectedValueException;

#[CoversClass(TagAwareFactory::class)]
final class TagAwareFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('poolNames')]
    public function it_wraps_the_selected_pool_and_supports_tag_invalidation(string $name, bool $phpdi): void
    {
        $pool      = new ArrayAdapter();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with($name)->willReturn($pool);
        $requestedName = $name . '.taggable';
        if ($phpdi) {
            $entry = $this->createMock(RequestedEntry::class);
            $entry->method('getName')->willReturn($requestedName);
            $requestedName = $entry;
        }

        $cache = (new TagAwareFactory())($container, $requestedName);

        self::assertInstanceOf(TagAwareAdapter::class, $cache);
        self::assertSame('value', $cache->get('example', static function (ItemInterface $item): string {
            $item->tag('tag');

            return 'value';
        }));
        self::assertTrue($pool->getItem('example')->isHit());
        self::assertTrue($cache->invalidateTags(['tag']));
        self::assertFalse($cache->getItem('example')->isHit());
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
    public function it_rejects_an_unknown_service_name(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())->method('get');
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported tag-aware Symfony cache service');

        (new TagAwareFactory())($container, 'unknown');
    }

    #[Test]
    public function it_rejects_a_pool_that_is_not_an_adapter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('cache.app')->willReturn(new stdClass());
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cache.app must implement AdapterInterface');

        (new TagAwareFactory())($container, 'cache.app.taggable');
    }
}
