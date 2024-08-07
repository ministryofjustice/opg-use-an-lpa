<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Cache\CacheFactory;
use App\Service\Secrets\CachedKeyPairManager;
use App\Service\Secrets\CachedKeyPairManagerDelegatorFactory;
use App\Service\Secrets\KeyPairManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CachedKeyPairManagerDelegatorFactory::class)]
class CachedKeyPairManagerDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
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
