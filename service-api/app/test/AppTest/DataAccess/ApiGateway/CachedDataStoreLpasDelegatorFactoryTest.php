<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\CachedDataStoreLpas;
use App\DataAccess\ApiGateway\CachedDataStoreLpasDelegatorFactory;
use App\DataAccess\ApiGateway\DataStoreLpas;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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

        $logger    = $this->createStub(LoggerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($logger);

        $factory = new CachedDataStoreLpasDelegatorFactory();
        $result  = $factory($container, DataStoreLpas::class, fn (): DataStoreLpas => $dataStoreLpas);

        self::assertInstanceOf(CachedDataStoreLpas::class, $result);
        $result->setOriginatorId('originator');
    }
}
