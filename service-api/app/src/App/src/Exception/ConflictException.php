<?php

declare(strict_types=1);

namespace App\Exception;

use App\Service\Log\Output\Email;
use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class ConflictException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    public const string TITLE = 'Conflict';
    public const int    CODE  = StatusCodeInterface::STATUS_CONFLICT;

    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, self::CODE, $additionalData, $previous);
    }

    public function getAdditionalDataForLogging(): array
    {
        $data = $this->getAdditionalData();

        // choose to be explicit about what is being logged to avoid leakage.
        return array_filter([
            'identity' => $data['identity'] ?? '',
            'email'    => isset($data['email']) ? new Email($data['email']) : '',
        ]);
    }
}
