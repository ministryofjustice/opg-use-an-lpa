<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use App\Service\Log\LogStderrListener;
use App\Service\Log\LogStderrListenerDelegatorFactory;
use Zend\Stratigility\Middleware\ErrorHandler;

/**
 * Class UserServiceTest
 * @package AppTest\Service\User
 */
class LogStderrListenerDelegatorFactoryTest extends TestCase
{

    public function test_invoke()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new LogStderrListenerDelegatorFactory();

        $errorHandler = $this->prophesize(ErrorHandler::class);
        $errorHandler->attachListener(Argument::type(LogStderrListener::class))->shouldBeCalled();

        $result = $factory($containerProphecy->reveal(),
            null,
            function() use ($errorHandler){
                return $errorHandler->reveal();
            }
        );

        $this->assertEquals($errorHandler->reveal(), $result);
    }

}
