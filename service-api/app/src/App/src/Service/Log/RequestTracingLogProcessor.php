<?php

declare(strict_types=1);

namespace App\Service\Log;

use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;

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

        return $record;
    }
}
