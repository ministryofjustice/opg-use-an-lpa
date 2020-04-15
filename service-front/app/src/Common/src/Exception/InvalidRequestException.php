<?php

declare(strict_types=1);

namespace Common\Exception;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class InvalidRequestException extends Exception
{
    /**
     * InvalidRequestException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message, int $code = StatusCodeInterface::STATUS_BAD_REQUEST, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
