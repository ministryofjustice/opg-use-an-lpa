<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;

class LpaDetailsDoNotMatchException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    public const MESSAGE = 'LPA details do not match';
    public const TITLE   = 'Bad Request';
    public const CODE    = StatusCodeInterface::STATUS_BAD_REQUEST;

    public function __construct(array $additionalData = [])
    {
        parent::__construct(self::TITLE, self::MESSAGE, self::CODE, $additionalData);
    }

    public function getAdditionalDataForLogging(): array
    {
        $data = $this->getAdditionalData();

        // choose to be explicit about what is being logged to avoid leakage.
        return [
            'lpaRegDate' => $data['lpaRegDate'] ?? '',
        ];
    }
}
