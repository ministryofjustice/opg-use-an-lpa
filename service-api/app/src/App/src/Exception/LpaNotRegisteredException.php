<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaNotRegisteredException extends AbstractApiException
{
    public const string MESSAGE = 'LPA status is not registered';
    public const string TITLE   = 'Bad Request';
    public const int    CODE    = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, self::MESSAGE, self::CODE, $additionalData);
    }
}
