<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\ApiGateway\SiriusLpasFactory;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaDataFormatter;
use Aws\EventBridge\EventBridgeClient;
use Exception;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(SiriusLpasFactory::class)]
final class SiriusLpasFactoryTest extends TestCase
{
    #[Test]
    public function can_instantiate(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(10))->method('get')->willReturnMap([
            [
                'config',
                [
                    'sirius_api'           => ['endpoint' => 'http://test'],
                    'eventbridge_bus_name' => 'my test bus',
                ],
            ],
            [
                ClientInterface::class,
                $this->createStub(Client::class),
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
                SiriusLpaSanitiser::class,
                $this->createStub(SiriusLpaSanitiser::class),
            ],
            [
                LoggerInterface::class,
                $this->createStub(LoggerInterface::class),
            ],
            [
                FeatureEnabled::class,
                $this->createStub(FeatureEnabled::class),
            ],
            [
                LpaDataFormatter::class,
                $this->createStub(LpaDataFormatter::class),
            ],
            [
                EventBridgeClient::class,
                $this->createStub(EventBridgeClient::class),
            ],
        ]);

        self::assertInstanceOf(SiriusLpas::class, (new SiriusLpasFactory())($container));
    }

    #[Test]
    public function cannot_instantiate(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn([]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sirius API Gateway endpoint is not set');

        (new SiriusLpasFactory())($container);
    }

    #[Test]
    public function cannot_instantiate_a_non_guzzle_client(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))->method('get')->willReturnMap([
            [
                'config',
                ['sirius_api' => ['endpoint' => 'http://test']],
            ],
            [
                ClientInterface::class,
                $this->createStub(ClientInterface::class),
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            SiriusLpas::class . ' requires a Guzzle implementation of ' . ClientInterface::class
        );

        (new SiriusLpasFactory())($container);
    }
}
