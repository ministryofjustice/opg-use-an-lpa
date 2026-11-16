<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\ApiGateway\PaperVerificationCodesFactory;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(PaperVerificationCodesFactory::class)]
final class PaperVerificationCodesFactoryTest extends TestCase
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

        self::assertInstanceOf(PaperVerificationCodes::class, (new PaperVerificationCodesFactory())($container));
    }

    #[Test]
    public function it_fails_with_exception_when_config_missing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('config')->willReturn(['codes_api' => []]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Paper verification codes API Gateway endpoint is not set');

        (new PaperVerificationCodesFactory())($container);
    }
}
