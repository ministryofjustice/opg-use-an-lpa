<?php

declare(strict_types=1);

namespace Common\Service\Log;

class RequestTracing
{
    /**
     * The http header that amazon attaches to requests that come through its LB
     */
    const TRACE_HEADER_NAME = 'x-amzn-trace-id';

    /**
     * The name of the configuration value stored in the DI container
     */
    const TRACE_CONTAINER_NAME = 'trace-id';
}