<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaNeedsCleansingException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    public const string MESSAGE = 'LPA needs cleansing';
    public const string TITLE   = 'Bad Request';
    public const int    CODE    = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, self::MESSAGE, self::CODE, $additionalData);
    }

    public function getAdditionalDataForLogging(): array
    {
        $data = $this->getAdditionalData();

        // choose to be explicit about what is being logged to avoid leakage.
        return [
            'actor_id' => $data['actor_id'] ?? '',
        ];
    }
}
