<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\RequestTracing;
use App\Service\Log\RequestTracingLogProcessor;
use DI\NotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class RequestTracingLogProcessorTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_adds_the_tracing_parameter(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willReturn('abc');

        $processor = new RequestTracingLogProcessor($containerProphecy->reveal());

        $result = $processor([]);

        $this->assertEquals(['extra' => [RequestTracing::TRACE_PARAMETER_NAME => 'abc']], $result);
    }

    #[Test]
    public function it_adds_a_default_tracing_parameter_if_none_found(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(RequestTracing::TRACE_PARAMETER_NAME)
            ->willThrow(new NotFoundException('no'));

        $processor = new RequestTracingLogProcessor($containerProphecy->reveal());

        $result = $processor([]);

        $this->assertEquals(['extra' => [RequestTracing::TRACE_PARAMETER_NAME => 'NO-TRACE-ID-DISCOVERED']], $result);
    }
}
