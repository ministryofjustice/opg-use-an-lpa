<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\Lpas;
use App\DataAccess\ApiGateway\LpasFactory;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\Service\Log\RequestTracing;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class LpasFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get(ClientInterface::class)
            ->willReturn(
                $this->prophesize(GuzzleHttpClient::class)->reveal()
            );
        $containerProphecy
            ->get(RequestFactoryInterface::class)
            ->willReturn(
                $this->prophesize(RequestFactoryInterface::class)->reveal()
            );
        $containerProphecy
            ->get(StreamFactoryInterface::class)
            ->willReturn(
                $this->prophesize(StreamFactoryInterface::class)->reveal()
            );

        $requestSignerFactory = $this->prophesize(RequestSignerFactory::class);
        $requestSignerFactory->__invoke()->willReturn($this->prophesize(RequestSigner::class)->reveal());

        $containerProphecy->get(RequestSignerFactory::class)->willReturn(
            $requestSignerFactory->reveal()
        );

        $containerProphecy->get('config')->willReturn(
            [
                'sirius_api' => [
                    'endpoint' => 'http://test',
                ],
            ]
        );

        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)->willReturn('');

        $containerProphecy->get(SiriusLpaSanitiser::class)->willReturn(
            $this->prophesize(SiriusLpaSanitiser::class)->reveal()
        );

        $containerProphecy->get(LoggerInterface::class)->willReturn(
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $factory = new LpasFactory();
        $repo    = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(Lpas::class, $repo);
    }

    #[Test]
    public function cannot_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(ClientInterface::class)->willReturn(
            $this->prophesize(GuzzleHttpClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new LpasFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sirius API Gateway endpoint is not set');

        $factory($containerProphecy->reveal());
    }

    #[Test]
    public function cannot_instantiate_a_non_guzzle_client(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(ClientInterface::class)->willReturn(
            $this->prophesize(ClientInterface::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn(
            [
                'sirius_api' => [
                    'endpoint' => 'http://test',
                ],
            ]
        );

        $factory = new LpasFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Lpas::class . ' requires a Guzzle implementation of ' . ClientInterface::class);

        $factory($containerProphecy->reveal());
    }
}
