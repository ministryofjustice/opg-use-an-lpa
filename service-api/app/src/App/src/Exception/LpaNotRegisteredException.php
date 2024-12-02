<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaNotRegisteredException extends AbstractApiException
{
    public const MESSAGE = 'LPA status is not registered';
    public const TITLE   = 'Bad Request';

    protected $code = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, self::MESSAGE, $additionalData);
    }
}
