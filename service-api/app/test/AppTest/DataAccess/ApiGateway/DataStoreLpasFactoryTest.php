<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\DataStoreLpasFactory;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\Service\Lpa\LpaDataFormatter;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(DataStoreLpasFactory::class)]
final class DataStoreLpasFactoryTest extends TestCase
{
    #[Test]
    public function can_instantiate(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(6))->method('get')->willReturnMap([
            [
                'config',
                ['lpa_data_store_api' => ['endpoint' => 'http://test']],
            ],
            [
                ClientInterface::class,
                $this->createStub(ClientInterface::class),
            ],
            [
                RequestFactoryInterface::class,
                $this->createStub(RequestFactoryInterface::class),
            ],
            [
                StreamFactoryInterface::class,
                $this->createStub(StreamFactoryInterface::class),
            ],
            [
                RequestSignerFactory::class,
                $this->createStub(RequestSignerFactory::class),
            ],
            [
                LpaDataFormatter::class,
                $this->createStub(LpaDataFormatter::class),
            ],
        ]);

        self::assertInstanceOf(DataStoreLpas::class, (new DataStoreLpasFactory())($container));
    }

    #[Test]
    public function cannot_instantiate(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn([]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA data store API endpoint is not set');

        (new DataStoreLpasFactory())($container);
    }
}
