<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Log\LogStderrListener;
use Common\Service\Log\LogStderrListenerDelegatorFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(LogStderrListenerDelegatorFactory::class)]
class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function creates_and_attaches_configured_logging_delegator_no_tracing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
        $containerProphecy->get('config')
            ->willReturn([]);

        $errorHandlerProphecy = $this->prophesize(ErrorHandler::class);

        // We're expecting an instance of `LogStderrListener` to be passed via `attachListener()`.
        $errorHandlerProphecy
            ->attachListener(Argument::type(LogStderrListener::class))
            ->shouldBeCalled();

        $callable = function () use ($errorHandlerProphecy) {
            return $errorHandlerProphecy->reveal();
        };

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $factory(
            $containerProphecy->reveal(),
            ErrorHandler::class,
            $callable
        );

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }

    #[Test]
    public function creates_and_attaches_configured_logging_delegator_with_tracing(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
        $containerProphecy->get('config')
            ->willReturn(['debug' => true]);

        $errorHandlerProphecy = $this->prophesize(ErrorHandler::class);

        // We're expecting an instance of `LogStderrListener` to be passed via `attachListener()`.
        $errorHandlerProphecy
            ->attachListener(Argument::type(LogStderrListener::class))
            ->shouldBeCalled();

        $callable = function () use ($errorHandlerProphecy) {
            return $errorHandlerProphecy->reveal();
        };

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $factory(
            $containerProphecy->reveal(),
            ErrorHandler::class,
            $callable
        );

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }
}
