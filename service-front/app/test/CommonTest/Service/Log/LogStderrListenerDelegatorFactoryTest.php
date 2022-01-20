<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\LogStderrListener;
use Common\Service\Log\LogStderrListenerDelegatorFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Common\Service\Log\LogStderrListenerDelegatorFactory
 */
class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     */
    public function creates_and_attaches_configured_logging_delegator_no_tracing()
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
            'Laminas\Stratigility\Middleware\ErrorHandler',
            $callable
        );

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function creates_and_attaches_configured_logging_delegator_with_tracing()
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
            'Laminas\Stratigility\Middleware\ErrorHandler',
            $callable
        );

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }
}
