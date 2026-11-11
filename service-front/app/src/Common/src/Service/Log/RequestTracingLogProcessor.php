<?php

declare(strict_types=1);

namespace Common\Service\Log;

use DI\NotFoundException;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use Psr\Container\ContainerInterface;

final class RequestTracingLogProcessor implements ProcessorInterface
{
    /**
     * The container supplies the original incoming header for access-log correlation only.
     * The full X-Ray tracing header is generated from the active OTEL context for each record.
     */
    public function __construct(private ContainerInterface $container)
    {
    }

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

        try {
            $header = $this->container->get(RequestTracing::TRACE_PARAMETER_NAME);
        } catch (NotFoundException) {
            // Logs emitted before request middleware runs have no incoming header.
            $header = null;
        }

        if (is_string($header) && $header !== '') {
            $record['extra']['alb_trace_id'] = $header;
        }

        return $record;
    }
}
