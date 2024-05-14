<?php

declare(strict_types=1);

namespace AppTest\Service\SystemMessage;

use App\Service\Cache\CacheFactory;
use App\Service\SystemMessage\CachedSystemMessage;
use App\Service\SystemMessage\CachedSystemMessageDelegatorFactory;
use App\Service\SystemMessage\SystemMessageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CachedSystemMessageDelegatorFactory::class)]
class CachedSystemMessageDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_delegated_cachedSystemMessageService(): void
    {
        $cacheFactory = $this->prophesize(CacheFactory::class);

        $cacheFactory
            ->__invoke('system-message')
            ->willReturn($this->prophesize(CacheInterface::class)->reveal());

        $containerInterface = $this->prophesize(ContainerInterface::class);
        $containerInterface->get(CacheFactory::class)->willReturn($cacheFactory->reveal());

        $sut = new CachedSystemMessageDelegatorFactory();

        $result = $sut(
            $containerInterface->reveal(),
            'fakeName',
            fn () => $this->prophesize(SystemMessageService::class)->reveal()
        );

        $this->assertInstanceOf(CachedSystemMessage::class, $result);
    }
}
