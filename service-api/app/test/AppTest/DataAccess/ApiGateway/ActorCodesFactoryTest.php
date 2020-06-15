<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\ActorCodesFactory;
use App\DataAccess\ApiGateway\RequestSigner;
use App\Service\Log\RequestTracing;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ActorCodesFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_an_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [
                        'endpoint' => 'test'
                    ]
                ]
            );

        $containerProphecy
            ->get(HttpClient::class)
            ->willReturn($this->prophesize(HttpClient::class)->reveal());

        $containerProphecy
            ->get(RequestSigner::class)
            ->willReturn($this->prophesize(RequestSigner::class)->reveal());

        $containerProphecy
            ->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('test-trace-id');

        $factory = new ActorCodesFactory();

        $actorCodes = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(ActorCodes::class, $actorCodes);
    }

    /** @test */
    public function it_fails_with_exception_when_config_missing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [
                    ]
                ]
            );

        $factory = new ActorCodesFactory();

        $this->expectException(\Exception::class);
        $actorCodes = $factory($containerProphecy->reveal());
    }
}
