<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\CachedSiriusLpas;
use App\DataAccess\ApiGateway\CachedSiriusLpasDelegatorFactory;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Service\Cache\CacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(CachedSiriusLpasDelegatorFactory::class)]
final class CachedSiriusLpasDelegatorFactoryTest extends TestCase
{
    #[Test]
    public function it_should_wrap_the_sirius_lpas_with_its_cache(): void
    {
        $lpa        = $this->createStub(LpaInterface::class);
        $siriusLpas = $this->createMock(SiriusLpas::class);
        $siriusLpas
            ->expects($this->once())
            ->method('get')
            ->with('700000000001')
            ->willReturn($lpa);

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

        $factory = new CachedSiriusLpasDelegatorFactory();
        $result  = $factory($container, SiriusLpas::class, fn (): SiriusLpas => $siriusLpas);

        self::assertInstanceOf(CachedSiriusLpas::class, $result);
        self::assertSame($lpa, $result->get('700000000001'));
    }
}
