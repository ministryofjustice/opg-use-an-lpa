<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class RequestSignerFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_an_request_signer_without_config(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new RequestSignerFactory($containerProphecy->reveal());

        $signer = $factory();

        $this->assertInstanceOf(RequestSigner::class, $signer);
    }

    #[Test]
    public function it_creates_an_actor_codes_configured_signer(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [
                        'static_auth_token' => 'test',
                    ],
                ]
            );

        $factory = new RequestSignerFactory($containerProphecy->reveal());

        $signer = $factory(SignatureType::ActorCodes);

        $this->assertInstanceOf(RequestSigner::class, $signer);
    }

    #[Test]
    public function it_creates_an_data_store_lpas_configured_signer(): void
    {
        $this->markTestIncomplete();
    }
}
