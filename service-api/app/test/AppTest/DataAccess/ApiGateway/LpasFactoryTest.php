<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\Lpas;
use App\DataAccess\ApiGateway\LpasFactory;
use App\Service\Log\RequestTracing;
use GuzzleHttp\Client as GuzzleHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Exception;

class LpasFactoryTest extends TestCase
{
    /** @test */
    public function can_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(GuzzleHttpClient::class)->willReturn(
            $this->prophesize(GuzzleHttpClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn(
            [
                'sirius_api' => [
                    'endpoint' => 'http://test'
                ]
            ]
        );

        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)->willReturn('');

        $factory = new LpasFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(Lpas::class, $repo);
    }

    /** @test */
    public function cannot_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(GuzzleHttpClient::class)->willReturn(
            $this->prophesize(GuzzleHttpClient::class)->reveal()
        );

        $containerProphecy->get('config')->willReturn([]);

        $factory = new LpasFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sirius API Gateway endpoint is not set');

        $factory($containerProphecy->reveal());
    }
}
