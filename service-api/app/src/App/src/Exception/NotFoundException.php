<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class NotFoundException extends AbstractApiException
{
    public const TITLE = 'Not found';

    protected $code = StatusCodeInterface::STATUS_NOT_FOUND;

    public function __construct(?string $message = null, ?array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
