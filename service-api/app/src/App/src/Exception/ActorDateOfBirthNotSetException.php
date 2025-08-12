<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class ActorDateOfBirthNotSetException extends AbstractApiException
{
    public const string MESSAGE = 'Actor date of birth is not set';
    public const string TITLE   = 'DOB Not Found';
    public const int    CODE    = StatusCodeInterface::STATUS_NOT_FOUND;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }
}
