<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Repository;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\DataStoreLpasFactory;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\DataAccess\Repository\DataSanitiserStrategy;
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

class DataStoreLpasFactoryTest extends TestCase
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
        $requestSignerFactory->__invoke()->willReturn($this->prophesize(RequestSignerFactory::class)->reveal());

        $containerProphecy->get(RequestSignerFactory::class)->willReturn(
            $requestSignerFactory->reveal()
        );

        $containerProphecy->get('config')->willReturn(
            [
                'lpa_data_store_api' => [
                    'endpoint' => 'http://test',
                ],
            ]
        );

        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)->willReturn('');

        $containerProphecy->get(SiriusLpaSanitiser::class)->willReturn(
            $this->prophesize(SiriusLpaSanitiser::class)->reveal()
        );

        $containerProphecy->get(DataSanitiserStrategy::class)->willReturn(
            $this->prophesize(DataSanitiserStrategy::class)->reveal()
        );

        $factory = new DataStoreLpasFactory();
        $repo    = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(DataStoreLpas::class, $repo);
    }
    #[Test]
    public function cannot_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(ClientInterface::class)->willReturn(
            $this->prophesize(GuzzleHttpClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new DataStoreLpasFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA data store API endpoint is not set');

        $factory($containerProphecy->reveal());
    }
}
