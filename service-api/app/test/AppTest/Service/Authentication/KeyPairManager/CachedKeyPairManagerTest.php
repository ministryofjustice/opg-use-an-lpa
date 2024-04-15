<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\KeyPairManager;

use App\Service\Authentication\KeyPairManager\CachedKeyPairManager;
use App\Service\Authentication\KeyPairManager\KeyPair;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\SimpleCache\CacheInterface;

class CachedKeyPairManagerTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $sut = new CachedKeyPairManager(
            $this->prophesize(CacheInterface::class)->reveal(),
            $this->prophesize(KeyPairManagerInterface::class)->reveal(),
        );

        $this->assertInstanceOf(KeyPairManagerInterface::class, $sut);
    }

    #[Test]
    public function it_returns_the_decorated_algorithm(): void
    {
        $keyPairManager = $this->prophesize(KeyPairManagerInterface::class);
        $keyPairManager
            ->getAlgorithm()
            ->willReturn('ALGORITHM');

        $sut = new CachedKeyPairManager(
            $this->prophesize(CacheInterface::class)->reveal(),
            $keyPairManager->reveal(),
        );

        $this->assertSame('ALGORITHM', $sut->getAlgorithm());
    }

    #[Test]
    public function it_caches_a_decorated_keypair(): void
    {
        $cacheInterface = $this->prophesize(CacheInterface::class);
        $cacheInterface
            ->has(Argument::that(function (string $key) use (&$cacheKey): bool {
                $cacheKey = $key;

                return true;
            }))
            ->willReturn(false);

        $keyPair = $this->prophesize(KeyPair::class)->reveal();

        $keyPairManager = $this->prophesize(KeyPairManagerInterface::class);
        $keyPairManager
            ->getKeyPair()
            ->willReturn($keyPair);

        $cacheInterface
            ->set(Argument::that(function (string $key) use (&$cacheKey): bool {
                // checks that the key used in the cache is consistently used.
                $this->assertSame($cacheKey, $key);

                return true;
            }), $keyPair, Argument::type('int'))
            ->shouldBeCalled();

        $sut = new CachedKeyPairManager(
            $cacheInterface->reveal(),
            $keyPairManager->reveal(),
        );

        $result = $sut->getKeyPair();

        $this->assertInstanceOf(KeyPair::class, $result);
    }

    #[Test]
    public function it_caches_a_decorated_keypair_with_a_custom_ttl(): void
    {
        $cacheInterface = $this->prophesize(CacheInterface::class);
        $cacheInterface
            ->has(Argument::type('string'))
            ->willReturn(false);

        $keyPair = $this->prophesize(KeyPair::class)->reveal();

        $keyPairManager = $this->prophesize(KeyPairManagerInterface::class);
        $keyPairManager
            ->getKeyPair()
            ->willReturn($keyPair);

        $cacheInterface
            ->set(Argument::type('string'), $keyPair, 60)
            ->shouldBeCalled();

        $sut = new CachedKeyPairManager(
            $cacheInterface->reveal(),
            $keyPairManager->reveal(),
            60, #seconds
        );

        $result = $sut->getKeyPair();

        $this->assertInstanceOf(KeyPair::class, $result);
    }

    #[Test]
    public function it_returns_a_cached_keypair(): void
    {
        $cacheInterface = $this->prophesize(CacheInterface::class);
        $cacheInterface
            ->has(Argument::type('string'))
            ->willReturn(true);

        $keyPair = $this->prophesize(KeyPair::class)->reveal();

        $keyPairManager = $this->prophesize(KeyPairManagerInterface::class);
        $keyPairManager
            ->getKeyPair()
            ->shouldNotBeCalled();

        $cacheInterface
            ->get(Argument::type('string'))
            ->willReturn($keyPair);

        $sut = new CachedKeyPairManager(
            $cacheInterface->reveal(),
            $keyPairManager->reveal(),
            60, #seconds
        );

        $result = $sut->getKeyPair();

        $this->assertSame($keyPair, $result);
    }
}
