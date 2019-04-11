<?php

declare(strict_types=1);

namespace ViewerTest\Service\Log;

use Viewer\Service\Log\LogStderrListener;
use Viewer\Service\Log\LogStderrListenerDelegatorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Prophecy\Argument;
use Zend\Stratigility\Middleware\ErrorHandler;

class LogStderrListenerDelegatorFactoryTest extends TestCase
{

    public function testValidConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);

        //---

        $errorHandler = $this->prophesize(ErrorHandler::class);

        // We're expecting an instance of `LogStderrListener` to be passed via `attachListener()`.
        $errorHandler->attachListener(Argument::type(LogStderrListener::class))->shouldBeCalled();

        $callable = function () use ($errorHandler){
            return $errorHandler->reveal();
        };

        //---

        $factory = new LogStderrListenerDelegatorFactory();

        $factory($container->reveal(), null, $callable, null);
    }

}
