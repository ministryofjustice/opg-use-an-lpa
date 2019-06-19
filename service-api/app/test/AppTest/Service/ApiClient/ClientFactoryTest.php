<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Service\ApiClient\ClientFactory;
use App\Service\ApiClient\SignedRequestClient;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use App\Service\ApiClient\ClientInterface as ApiClient;

class ClientFactoryTest extends TestCase
{
    public function testReturnsASignedClient()
    {
        $clientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn([
                'sirius_api' => [
                    'endpoint' => 'test'
                ],
                'aws' => [
                    'region' => 'test'
                ]
            ]);
        $containerProphecy->get(ClientInterface::class)
            ->willReturn($clientProphecy->reveal());


        $factory = new ClientFactory();

        $client = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(ApiClient::class, $client);
        $this->assertInstanceOf(SignedRequestClient::class, $client);
    }

    public function testThrowsExceptionWhenNotConfigured()
    {
        $this->expectException(\Exception::class);

        $clientProphecy = $this->prophesize(ClientInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('config')
            ->willReturn([
                'aws' => [
                ]
            ]);
        $containerProphecy->get(ClientInterface::class)
            ->willReturn($clientProphecy->reveal());


        $factory = new ClientFactory();

        $client = $factory($containerProphecy->reveal());
    }
}