<?php

declare(strict_types=1);

namespace CommonTest\Service\Log;

use Common\Service\Log\RequestTracingLogProcessor;
use Common\Service\Log\RequestTracingLogProcessorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestTracingLogProcessorFactory::class)]
final class RequestTracingLogProcessorFactoryTest extends TestCase
{
    #[Test]
    public function it_constructs_a_request_tracing_log_processor(): void
    {
        $factory = new RequestTracingLogProcessorFactory();

        $result = $factory([]);

        $this->assertInstanceOf(RequestTracingLogProcessor::class, $result);
    }
}
