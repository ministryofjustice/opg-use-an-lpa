<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RequestSignerFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_static_request_signer(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [
                        'static_auth_token' => 'test'
                    ]
                ]
            );

        $factory = new RequestSignerFactory();

        $signer = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(RequestSigner::class, $signer);
    }

    /** @test */
    public function it_creates_an_aws_request_signer_without_config(): void
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

        $factory = new RequestSignerFactory();

        $signer = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(RequestSigner::class, $signer);
    }
}
