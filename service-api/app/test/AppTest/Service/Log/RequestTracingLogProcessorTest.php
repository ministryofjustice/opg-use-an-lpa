<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use DI\NotFoundException;
use App\Service\Log\RequestTracing;
use App\Service\Log\RequestTracingLogProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RequestTracingLogProcessorTest extends TestCase
{
    /** @test */
    public function it_adds_the_tracing_parameter()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('abc');

        $processor = new RequestTracingLogProcessor($containerProphecy->reveal());

        $result = $processor([]);

        $this->assertEquals(['extra' => [RequestTracing::TRACE_PARAMETER_NAME => 'abc']], $result);
    }

    /** @test */
    public function it_adds_a_default_tracing_parameter_if_none_found()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willThrow(new NotFoundException('no'));

        $processor = new RequestTracingLogProcessor($containerProphecy->reveal());

        $result = $processor([]);

        $this->assertEquals(['extra' => [RequestTracing::TRACE_PARAMETER_NAME => 'NO-TRACE-ID-DISCOVERED']], $result);
    }
}
