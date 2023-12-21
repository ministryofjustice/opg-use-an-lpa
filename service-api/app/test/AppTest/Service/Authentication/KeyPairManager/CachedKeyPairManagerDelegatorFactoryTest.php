<?php

declare(strict_types=1);

namespace AppTest\Service\Authentication\KeyPairManager;

use App\Service\Authentication\KeyPairManager\CachedKeyPairManager;
use App\Service\Authentication\KeyPairManager\CachedKeyPairManagerDelegatorFactory;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use App\Service\Cache\CacheFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @coversDefaultClass \App\Service\Authentication\KeyPairManager\CachedKeyPairManagerDelegatorFactory
 */
class CachedKeyPairManagerDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers ::__invoke
     */
    public function it_creates_a_delegated_cachedKeyPairManager(): void
    {
        $cacheFactory = $this->prophesize(CacheFactory::class);
        $cacheFactory
            ->__invoke('one-login')
            ->willReturn($this->prophesize(CacheInterface::class)->reveal());

        $containerInterface = $this->prophesize(ContainerInterface::class);
        $containerInterface->get(CacheFactory::class)->willReturn($cacheFactory->reveal());

        $sut = new CachedKeyPairManagerDelegatorFactory();

        $result = $sut(
            $containerInterface->reveal(),
            'fakeName',
            fn () => $this->prophesize(KeyPairManagerInterface::class)->reveal()
        );

        $this->assertInstanceOf(CachedKeyPairManager::class, $result);
    }
}
