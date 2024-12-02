<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaNotRegisteredException extends AbstractApiException
{
    public const TITLE = 'Bad Request';

    /** @var int $code */
    protected $code = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, 'LPA status is not registered', $additionalData);
    }
}
