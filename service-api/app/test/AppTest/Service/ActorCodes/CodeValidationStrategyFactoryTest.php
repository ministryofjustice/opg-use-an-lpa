<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes;

use App\DataAccess\ApiGateway\ActorCodes as ActorCodesApi;
use App\Service\ActorCodes\CodeValidationStrategyFactory;
use App\Service\ActorCodes\Validation\CodesApiValidationStrategy;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CodeValidationStrategyFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_a_codes_api_strategy(): void
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container
            ->get(ActorCodesApi::class)
            ->willReturn($this->prophesize(ActorCodesApi::class)->reveal());

        $container
            ->get(LpaService::class)
            ->willReturn($this->prophesize(LpaService::class)->reveal());

        $container
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $container
            ->get(ResolveActor::class)
            ->willReturn($this->prophesize(ResolveActor::class)->reveal());

        $factory = new CodeValidationStrategyFactory();

        $strategy = $factory($container->reveal());

        $this->assertInstanceOf(CodesApiValidationStrategy::class, $strategy);
    }
}
