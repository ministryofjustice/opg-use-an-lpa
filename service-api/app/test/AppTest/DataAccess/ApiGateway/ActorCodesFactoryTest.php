<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\ActorCodesFactory;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\Service\Log\RequestTracing;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ActorCodesFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_an_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [
                        'endpoint' => 'test',
                    ],
                ]
            );

        $containerProphecy
            ->get(ClientInterface::class)
            ->willReturn($this->prophesize(ClientInterface::class)->reveal());

        $containerProphecy
            ->get(RequestFactoryInterface::class)
            ->willReturn($this->prophesize(RequestFactoryInterface::class)->reveal());

        $containerProphecy
            ->get(StreamFactoryInterface::class)
            ->willReturn($this->prophesize(StreamFactoryInterface::class)->reveal());

        $containerProphecy
            ->get(RequestSignerFactory::class)
            ->willReturn($this->prophesize(RequestSignerFactory::class)->reveal());

        $containerProphecy
            ->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('test-trace-id');

        $factory = new ActorCodesFactory();

        $actorCodes = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(ActorCodes::class, $actorCodes);
    }

    #[Test]
    public function it_fails_with_exception_when_config_missing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->willReturn(
                [
                    'codes_api' => [],
                ]
            );

        $factory = new ActorCodesFactory();

        $this->expectException(Exception::class);
        $actorCodes = $factory($containerProphecy->reveal());
    }
}
