<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Secrets\CachedSecretManager;
use App\Service\Secrets\SecretManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

class CachedSecretManagerTest extends TestCase
{
    use ProphecyTrait;

    const CACHE_NAME = 'lpa-data-store';

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $cachedSecretManager = new CachedSecretManager(
            $this->prophesize(CacheInterface::class)->reveal(),
            $this->prophesize(SecretManagerInterface::class)->reveal(),
        );

        $this->assertInstanceOf(SecretManagerInterface::class, $cachedSecretManager);
    }

    #[Test]
    public function it_returns_cached_secret_if_present(): void
    {
        $mockSecret = 'my-cached-secret';

        $cache = $this->prophesize(CacheInterface::class);
        $cache->has(self::CACHE_NAME)->willReturn(true);
        $cache->get(self::CACHE_NAME)->willReturn($mockSecret);

        $cachedSecretManager = new CachedSecretManager(
            $cache->reveal(),
            $this->prophesize(SecretManagerInterface::class)->reveal(),
        );

        $this->assertEquals($mockSecret, $cachedSecretManager->getSecret());
    }

    #[Test]
    public function it_caches_secret_if_not_cached(): void
    {
        $mockSecret = 'my-secret';

        $cache = $this->prophesize(CacheInterface::class);
        $cache->has(self::CACHE_NAME)->willReturn(false);
        $cache->set(self::CACHE_NAME, $mockSecret, 3600)->shouldBeCalled();

        $secretManager = $this->prophesize(SecretManagerInterface::class);
        $secretManager->getSecret()->willReturn($mockSecret);

        $cachedSecretManager = new CachedSecretManager(
            $cache->reveal(),
            $secretManager->reveal(),
        );

        $this->assertEquals($mockSecret, $cachedSecretManager->getSecret());
    }
}