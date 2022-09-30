<?php

declare(strict_types=1);

namespace Common\Exception;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class RateLimitExceededException extends Exception
{
    public function __construct(string $message, int $code = StatusCodeInterface::STATUS_TOO_MANY_REQUESTS, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
