<?php

declare(strict_types=1);

namespace Common\Service\Log;

use DI\NotFoundException;
use Monolog\Processor\ProcessorInterface;
use Psr\Container\ContainerInterface;

class RequestTracingLogProcessor implements ProcessorInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * RequestTracingLogProcessor constructor.
     *
     * This log process needs access to the latest trace-id that has been discovered. Because this
     * process happens in a middleware and because this processor may be instantiated before that
     * middleware we need to be able to query that traceId at runtime - and so for that we need
     * to be given the container.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array $record): array
    {
        try {
            $traceId = $this->container->get(RequestTracing::TRACE_PARAMETER_NAME);
        } catch(NotFoundException $ex) {
            // we tried our best
            $traceId = 'NO-TRACE-ID-DISCOVERED';
        }

        $record['extra'][RequestTracing::TRACE_PARAMETER_NAME] = $traceId;
        return $record;
    }
}