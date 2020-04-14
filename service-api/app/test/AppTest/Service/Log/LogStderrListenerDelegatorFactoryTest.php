<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\LogStderrListener;
use App\Service\Log\LogStderrListenerDelegatorFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

/**
 * Class UserServiceTest
 *
 * @package AppTest\Service\User
 */
class LogStderrListenerDelegatorFactoryTest extends TestCase
{
    /** @test */
    public function it_correctly_attaches_a_listener_to_the_error_handler()
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
