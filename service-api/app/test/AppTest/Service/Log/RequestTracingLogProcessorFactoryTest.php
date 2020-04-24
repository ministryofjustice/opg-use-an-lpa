<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\RequestTracingLogProcessorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use App\Service\Log\RequestTracingLogProcessor;

class RequestTracingLogProcessorFactoryTest extends TestCase
{
    /** @test */
    public function it_constructs_a_request_tracing_log_processor()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new RequestTracingLogProcessorFactory();
        $factory->setContainer($containerProphecy->reveal());

        $result = $factory([]);

        $this->assertInstanceOf(RequestTracingLogProcessor::class, $result);
    }
}
