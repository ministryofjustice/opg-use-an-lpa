<?php

declare(strict_types=1);

namespace CommonTest\Service\Cache;

use Common\Service\Cache\RedisFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Redis;

#[CoversClass(RedisFactory::class)]
final class RedisFactoryTest extends TestCase
{
    #[Test]
    public function it_should_create_a_new_redis_instance_each_time(): void
    {
        $factory = new RedisFactory();
        $first   = $factory();
        $second  = $factory();

        self::assertInstanceOf(Redis::class, $first);
        self::assertInstanceOf(Redis::class, $second);
        self::assertNotSame($first, $second);
    }
}
