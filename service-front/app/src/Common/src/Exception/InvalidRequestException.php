<?php

declare(strict_types=1);

namespace Common\Exception;

class InvalidRequestException extends \Exception
{
    const BAD_REQUEST_CODE = 400;

    /**
     * InvalidRequestException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message, int $code = self::BAD_REQUEST_CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
