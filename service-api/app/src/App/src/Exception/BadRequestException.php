<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

class BadRequestException extends AbstractApiException
{
    /**
     * Exception title
     */
    public const TITLE = 'Bad Request';

    protected $code = StatusCodeInterface::STATUS_BAD_REQUEST;

    /**
     * @param string $message
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(?string $message = null, array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
