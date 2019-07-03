<?php

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class ConflictException
 * @package App\Exception
 */
class ConflictException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Conflict';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_CONFLICT;

    /**
     * ConflictException constructor.
     *
     * @param string $message
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, array $additionalData = [], Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
