<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class UnauthorizedException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Unauthorized';

    protected int $code = StatusCodeInterface::STATUS_UNAUTHORIZED;

    /**
     * @param string $message
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
