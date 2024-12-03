<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class ConflictException extends AbstractApiException
{
    public const TITLE = 'Conflict';
    public const CODE  = StatusCodeInterface::STATUS_CONFLICT;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }
}
