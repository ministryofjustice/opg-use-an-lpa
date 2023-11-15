<?php

declare(strict_types=1);

namespace AppTest\Handler\Factory;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\ActorUsersInterface;
use App\DataAccess\Repository\LpasInterface;
use App\Handler\Factory\HealthcheckHandlerFactory;
use App\Handler\HealthcheckHandler;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class HealthcheckHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItCreatesAHealthcheckHandler(): void
    {
        $factory = new HealthcheckHandlerFactory();

        $actorUsers            = $this->prophesize(ActorUsersInterface::class);
        $lpaInterface          = $this->prophesize(LpasInterface::class);
        $container             = $this->prophesize(ContainerInterface::class);
        $httpClientProphecy    = $this->prophesize(HttpClient::class);
        $requestSignerProphecy = $this->prophesize(RequestSigner::class);

        $container->get('config')->willReturn(
            [
                'version'        => 'dev',
                'sirius_api'     => [
                    'endpoint' => 'localhost',
                ],
                'codes_api'      => [
                    'endpoint' => 'localhost',
                ],
                'iap_images_api' => [
                    'endpoint' => 'localhost',
                ],
            ]
        );

        $container->get(ActorUsersInterface::class)->willReturn($actorUsers->reveal());
        $container->get(LpasInterface::class)->willReturn($lpaInterface->reveal());
        $container->get(HttpClient::class)->willReturn($httpClientProphecy->reveal());
        $container->get(RequestSigner::class)->willReturn($requestSignerProphecy->reveal());

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HealthcheckHandler::class, $handler);
    }
}
