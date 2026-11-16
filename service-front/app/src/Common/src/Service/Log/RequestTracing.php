<?php

declare(strict_types=1);

namespace Common\Service\Log;

final class RequestTracing
{
    /**
     * The http header that amazon attaches to requests that come through its LB
     */
    public const string TRACE_HEADER_NAME = 'x-amzn-trace-id';
}
