<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\LogStderrListener;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Common\Service\Log\LogStderrListener
 */
class LogStderrListenerTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::__invoke
     */
    public function creates_and_pushes_test_message_without_trace()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(
            Argument::type('string'),
            Argument::that(function ($exceptionArray) {
                $this->assertArrayHasKey('message', $exceptionArray);
                $this->assertArrayHasKey('code', $exceptionArray);
                $this->assertArrayHasKey('line', $exceptionArray);
                $this->assertArrayHasKey('file', $exceptionArray);
                $this->assertArrayNotHasKey('trace', $exceptionArray);

                $this->assertEquals('It is an error!', $exceptionArray['message']);
                $this->assertIsInt($exceptionArray['line']);
                $this->assertStringContainsString('LogStderrListenerTest.php', $exceptionArray['file']);
                $this->assertEquals(40, $exceptionArray['code']);

                return true;
            })
        )
        ->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $anonClass = new class () extends \Exception {
        };

        $exception = new $anonClass('It is an error!', 40, new \Exception());

        $logStderrListener = new LogStderrListener($loggerProphecy->reveal());
        $logStderrListener($exception, $requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::__invoke
     */
    public function creates_and_pushes_test_message_with_trace()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(
            Argument::type('string'),
            Argument::that(function ($exceptionArray) {
                $this->assertArrayHasKey('message', $exceptionArray);
                $this->assertArrayHasKey('code', $exceptionArray);
                $this->assertArrayHasKey('line', $exceptionArray);
                $this->assertArrayHasKey('file', $exceptionArray);
                $this->assertArrayHasKey('trace', $exceptionArray);

                $this->assertEquals('It is an error!', $exceptionArray['message']);
                $this->assertIsInt($exceptionArray['line']);
                $this->assertStringContainsString('LogStderrListenerTest.php', $exceptionArray['file']);
                $this->assertEquals(40, $exceptionArray['code']);
                $this->assertStringContainsString('LogStderrListenerTest', $exceptionArray['trace']);

                return true;
            })
        )
            ->shouldBeCalled();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $anonClass = new class () extends \Exception {
        };

        $exception = new $anonClass('It is an error!', 40, new \Exception());

        $logStderrListener = new LogStderrListener($loggerProphecy->reveal(), true);
        $logStderrListener($exception, $requestProphecy->reveal(), $responseProphecy->reveal());
    }
}
