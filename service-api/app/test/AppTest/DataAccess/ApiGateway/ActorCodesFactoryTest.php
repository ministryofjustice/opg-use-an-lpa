<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\ActorCodesFactory;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(ActorCodesFactory::class)]
final class ActorCodesFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_an_instance(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(5))->method('get')->willReturnMap([
            [
                'config',
                ['codes_api' => ['endpoint' => 'test']],
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
        ]);

        self::assertInstanceOf(ActorCodes::class, (new ActorCodesFactory())($container));
    }

    #[Test]
    public function it_fails_with_exception_when_config_missing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn(['codes_api' => []]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Actor codes API Gateway endpoint is not set');

        (new ActorCodesFactory())($container);
    }
}
