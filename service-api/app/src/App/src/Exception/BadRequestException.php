<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class BadRequestException extends AbstractApiException
{
    public const TITLE = 'Bad Request';
    public const CODE  = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }
}
