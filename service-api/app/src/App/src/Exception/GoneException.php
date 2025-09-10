<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class GoneException extends AbstractApiException
{
    public const string TITLE = 'Gone';
    public const int    CODE  = StatusCodeInterface::STATUS_GONE;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }
}
