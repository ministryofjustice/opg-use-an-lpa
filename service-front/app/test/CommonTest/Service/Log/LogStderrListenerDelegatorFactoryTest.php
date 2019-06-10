<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\LogStderrListener;
use Common\Service\Log\LogStderrListenerDelegatorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Prophecy\Argument;
use Zend\Stratigility\Middleware\ErrorHandler;

class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        //---

        $errorHandlerProphecy = $this->prophesize(ErrorHandler::class);

        // We're expecting an instance of `LogStderrListener` to be passed via `attachListener()`.
        $errorHandlerProphecy
            ->attachListener(Argument::type(LogStderrListener::class))
            ->shouldBeCalled();

        $callable = function () use ($errorHandlerProphecy) {
            return $errorHandlerProphecy->reveal();
        };

        //---

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $factory($containerProphecy->reveal(), null, $callable, null);

        $this->assertInstanceOf(ErrorHandler::class, $errorHandler);
    }
}
