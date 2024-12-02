<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaNeedsCleansingException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    public const MESSAGE = 'LPA needs cleansing';
    public const TITLE   = 'Bad Request';

    /** @var int $code */
    protected $code = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, self::MESSAGE, $additionalData);
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
