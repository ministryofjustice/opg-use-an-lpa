<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Cache;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\Cache\CachedDataStoreLpas;
use App\DataAccess\Cache\CachedDataStoreLpasDelegatorFactory;
use App\Service\Cache\CacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CachedDataStoreLpasDelegatorFactory::class)]
final class CachedDataStoreLpasDelegatorFactoryTest extends TestCase
{
    #[Test]
    public function it_should_wrap_the_data_store_lpas_with_its_cache(): void
    {
        $dataStoreLpas = $this->createMock(DataStoreLpas::class);
        $dataStoreLpas
            ->expects($this->once())
            ->method('setOriginatorId')
            ->with('originator')
            ->willReturnSelf();

        $logger       = $this->createStub(LoggerInterface::class);
        $cache        = $this->createStub(CacheInterface::class);
        $cacheFactory = $this->createMock(CacheFactory::class);
        $cacheFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with('request')
            ->willReturn($cache);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    CacheFactory::class,
                    $cacheFactory,
                ],
                [
                    LoggerInterface::class,
                    $logger,
                ],
            ]);

        $factory = new CachedDataStoreLpasDelegatorFactory();
        $result  = $factory($container, DataStoreLpas::class, fn (): DataStoreLpas => $dataStoreLpas);

        self::assertInstanceOf(CachedDataStoreLpas::class, $result);
        $result->setOriginatorId('originator');
    }
}
