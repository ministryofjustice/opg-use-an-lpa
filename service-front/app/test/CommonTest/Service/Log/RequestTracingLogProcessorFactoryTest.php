<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\RequestTracingLogProcessor;
use Common\Service\Log\RequestTracingLogProcessorFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class RequestTracingLogProcessorFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_constructs_a_request_tracing_log_processor(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new RequestTracingLogProcessorFactory();
        $factory->setContainer($containerProphecy->reveal());

        $result = $factory([]);

        $this->assertInstanceOf(RequestTracingLogProcessor::class, $result);
    }
}
