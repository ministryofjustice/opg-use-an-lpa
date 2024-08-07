<?php

declare(strict_types=1);

namespace AppTest\Service\Secrets;

use App\Service\Authentication\KeyPairManager\CachedKeyPairManager;
use App\Service\Authentication\KeyPairManager\CachedKeyPairManagerDelegatorFactory;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use App\Service\Cache\CacheFactory;
use App\Service\Secrets\CachedSecretManager;
use App\Service\Secrets\CachedSecretManagerDelegatorFactory;
use App\Service\Secrets\SecretManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CachedSecretManagerDelegatorFactory::class)]
class CachedSecretManagerDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_delegated_cachedSecretManager(): void
    {
        $cacheFactory = $this->prophesize(CacheFactory::class);
        $cacheFactory
            ->__invoke('lpa-data-store')
            ->willReturn($this->prophesize(CacheInterface::class)->reveal());

        $containerInterface = $this->prophesize(ContainerInterface::class);
        $containerInterface->get(CacheFactory::class)->willReturn($cacheFactory->reveal());

        $sut = new CachedSecretManagerDelegatorFactory();

        $result = $sut(
            $containerInterface->reveal(),
            'fakeName',
            fn () => $this->prophesize(SecretManagerInterface::class)->reveal()
        );

        $this->assertInstanceOf(CachedSecretManager::class, $result);
    }
}
