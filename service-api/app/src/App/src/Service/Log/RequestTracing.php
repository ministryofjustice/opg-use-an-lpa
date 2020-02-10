<?php

declare(strict_types=1);

namespace App\Service\Log;

class RequestTracing
{
    /**
     * The http header that amazon attaches to requests that come through its LB
     */
    public const TRACE_HEADER_NAME = 'x-amzn-trace-id';

    /**
     * The name of the configuration value stored in the DI container
     */
    public const TRACE_PARAMETER_NAME = 'trace-id';
}