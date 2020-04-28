<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Service\ApiClient\Client;
use App\Service\ApiClient\ClientFactory;
use App\Service\Log\RequestTracing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

class ClientFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_an_instance_of_a_client()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')
            ->willReturn(
                [
                    'sirius_api' => [
                        'endpoint' => 'test_endpoint'
                    ],
                    'aws' => [
                        'region' => 'test_region'
                    ]
                ]
            );

        $httpClientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy->get(ClientInterface::class)
            ->willReturn($httpClientProphecy->reveal());

        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('trace-id');

        $factory = new ClientFactory();

        $client = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * @test
     */
    public function throws_exception_when_missing_sirius_api_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn(
                [
                    'aws' => [
                        'region' => 'test_region'
                    ]
                ]
            );

        $factory = new ClientFactory();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sirius API configuration not present');
        $factory($containerProphecy->reveal());
    }

    /**
     * @test
     */
    public function throws_exception_when_missing_aws_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn(
                [
                    'sirius_api' => [
                        'endpoint' => 'test_endpoint'
                    ],
                ]
            );

        $factory = new ClientFactory();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AWS configuration not present');
        $factory($containerProphecy->reveal());
    }
}
