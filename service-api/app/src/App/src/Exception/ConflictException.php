<?php

namespace App\Exception;

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
    protected $code = 409;

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
