<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class CreationException extends AbstractApiException
{
    public const string TITLE = 'Creation error';
    public const int    CODE  = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }
}
