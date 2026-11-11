<?php

declare(strict_types=1);

namespace Common\Service\Log;

use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\Context\Context;
use Telemetry\IncomingTraceHeader;

final class RequestTracingLogProcessor implements ProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(array $record): array
    {
        $headers = [];

        (new Propagator())->inject($headers);

        if (isset($headers[RequestTracing::TRACE_HEADER_NAME])) {
            $record['extra']['trace_id'] = $headers[RequestTracing::TRACE_HEADER_NAME];
        }

        $header = Context::getCurrent()->get(IncomingTraceHeader::key());

        if (is_string($header) && $header !== '') {
            $record['extra']['alb_trace_id'] = $header;
        }

        return $record;
    }
}
