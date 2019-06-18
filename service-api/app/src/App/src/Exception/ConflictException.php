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
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_CONFLICT;

    /**
     * ConflictException constructor.
     *
     * @param string $message
     * @param string $title
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, string $title = 'Conflict', array $additionalData = [], Throwable $previous = null)
    {
        parent::__construct($message, $title, $additionalData, $previous);
    }
}
