<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class NotFoundException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Not found';

    protected int $code = StatusCodeInterface::STATUS_NOT_FOUND;

    /**
     * @param string $message
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(?string $message = null, ?array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
