<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Secrets\CachedSecretManager;
use App\Service\Secrets\Secret;
use App\Service\Secrets\SecretManagerInterface;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

class CachedSecretManagerTest extends TestCase
{
    use ProphecyTrait;

    private const CACHE_NAME = 'lpa-data-store';

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
        $secret     = new Secret(new HiddenString($mockSecret));

        $cache = $this->prophesize(CacheInterface::class);
        $cache->has(self::CACHE_NAME)->willReturn(true);
        $cache->get(self::CACHE_NAME)->willReturn($secret);

        $cachedSecretManager = new CachedSecretManager(
            $cache->reveal(),
            $this->prophesize(SecretManagerInterface::class)->reveal(),
        );

        $this->assertInstanceOf(Secret::class, $cachedSecretManager->getSecret());
        $this->assertEquals($mockSecret, $cachedSecretManager->getSecret()->secret);
    }

    #[Test]
    public function it_caches_secret_if_not_cached(): void
    {
        $mockSecret = 'my-secret';
        $secret     = new Secret(new HiddenString($mockSecret));

        $cache = $this->prophesize(CacheInterface::class);
        $cache->has(self::CACHE_NAME)->willReturn(false);
        $cache->set(self::CACHE_NAME, $secret, 3600)->shouldBeCalled();

        $secretManager = $this->prophesize(SecretManagerInterface::class);
        $secretManager->getSecret()->willReturn($secret);

        $cachedSecretManager = new CachedSecretManager(
            $cache->reveal(),
            $secretManager->reveal(),
        );

        $this->assertInstanceOf(Secret::class, $cachedSecretManager->getSecret());
        $this->assertEquals($mockSecret, $cachedSecretManager->getSecret()->secret);
    }

    #[Test]
    public function has_the_correct_algorithm(): void
    {
        $secretManager = $this->prophesize(SecretManagerInterface::class);
        $secretManager->getAlgorithm()->willReturn('HS256');

        $cachedSecretManager = new CachedSecretManager(
            $this->prophesize(CacheInterface::class)->reveal(),
            $secretManager->reveal(),
        );

        $alg = $cachedSecretManager->getAlgorithm();

        $this->assertSame('HS256', $alg);
    }
}
