<?php

declare(strict_types=1);

namespace AppTest\Handler\Factory;

use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\ActorUsersInterface;
use App\Handler\Factory\HealthcheckHandlerFactory;
use App\Handler\HealthcheckHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HealthcheckHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItCreatesAHealthcheckHandler(): void
    {
        $factory = new HealthcheckHandlerFactory();

        $container              = $this->prophesize(ContainerInterface::class);
        $actorUsers             = $this->prophesize(ActorUsersInterface::class);
        $clientProphecy         = $this->prophesize(ClientInterface::class);
        $requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
        $requestSignerProphecy  = $this->prophesize(RequestSignerFactory::class);

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
        $container->get(ClientInterface::class)->willReturn($clientProphecy->reveal());
        $container->get(RequestFactoryInterface::class)->willReturn($requestFactoryProphecy->reveal());
        $container->get(RequestSignerFactory::class)->willReturn($requestSignerProphecy->reveal());

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(HealthcheckHandler::class, $handler);
    }
}
