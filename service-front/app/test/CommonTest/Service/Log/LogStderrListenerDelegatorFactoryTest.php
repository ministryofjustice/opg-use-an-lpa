<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\LogStderrListener;
use Common\Service\Log\LogStderrListenerDelegatorFactory;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    /** @test */
    public function creates_ands_attaches_configured_logging_delegator()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $errorHandlerProphecy = $this->prophesize(ErrorHandler::class);

        // We're expecting an instance of `LogStderrListener` to be passed via `attachListener()`.
        $errorHandlerProphecy
            ->attachListener(Argument::type(LogStderrListener::class))
            ->shouldBeCalled();

        $callable = function () use ($errorHandlerProphecy) {
            return $errorHandlerProphecy->reveal();
        };

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $factory($containerProphecy->reveal(), null, $callable, null);

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }
}
