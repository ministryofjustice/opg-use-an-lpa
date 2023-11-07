<?php

declare(strict_types=1);

namespace Common\Exception;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class InvalidRequestException extends Exception
{
    public function __construct(
        string $message,
        int $code = StatusCodeInterface::STATUS_BAD_REQUEST,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
