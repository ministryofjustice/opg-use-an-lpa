<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class ForbiddenException extends AbstractApiException
{
    public const TITLE = 'Forbidden';

    protected $code = StatusCodeInterface::STATUS_FORBIDDEN;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
