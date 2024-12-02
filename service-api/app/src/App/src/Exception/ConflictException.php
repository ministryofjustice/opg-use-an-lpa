<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class ConflictException extends AbstractApiException
{
    public const TITLE = 'Conflict';

    protected $code = StatusCodeInterface::STATUS_CONFLICT;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
