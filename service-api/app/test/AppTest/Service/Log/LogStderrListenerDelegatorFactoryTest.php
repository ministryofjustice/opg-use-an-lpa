<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\LogStderrListener;
use App\Service\Log\LogStderrListenerDelegatorFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_correctly_attaches_a_listener_to_the_error_handler(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $this->prophesize(ErrorHandler::class);
        $errorHandler->attachListener(Argument::type(LogStderrListener::class))->shouldBeCalled();

        $result = $factory(
            $containerProphecy->reveal(),
            null,
            function () use ($errorHandler) {
                return $errorHandler->reveal();
            }
        );

        $this->assertEquals($errorHandler->reveal(), $result);
    }
}
