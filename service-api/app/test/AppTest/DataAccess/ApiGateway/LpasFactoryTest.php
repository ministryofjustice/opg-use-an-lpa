<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\Lpas;
use App\DataAccess\ApiGateway\LpasFactory;
use App\DataAccess\ApiGateway\Sanitisers\SiriusLpaSanitiser;
use App\Service\Log\RequestTracing;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LpasFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function can_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get(GuzzleHttpClient::class)->willReturn(
            $this->prophesize(GuzzleHttpClient::class)->reveal()
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

    /** @test */
    public function cannot_instantiate(): void
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
