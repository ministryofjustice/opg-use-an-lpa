<?php

declare(strict_types=1);

namespace Common\Exception;

use Exception;

class RateLimitExceededException extends Exception
{
    const RATE_LIMIT_EXCEEDED_CODE = 429;

    /**
     * RateLimitExceededException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message, int $code = self::RATE_LIMIT_EXCEEDED_CODE, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}