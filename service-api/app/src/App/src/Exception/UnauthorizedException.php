<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class UnauthorizedException extends AbstractApiException
{
    public const TITLE = 'Unauthorized';

    protected $code = StatusCodeInterface::STATUS_UNAUTHORIZED;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
