<?php

namespace App\Exception;

use Throwable;

/**
 * Class PageExpiredException
 * @package App\Exception
 */
class PageExpiredException extends AbstractApiException
{
    /**
     * @var int
     */
    protected $code = 419;

    /**
     * PageExpiredException constructor.
     *
     * @param string $message
     * @param string $title
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, string $title = 'Page expired', array $additionalData = [], Throwable $previous = null)
    {
        parent::__construct($message, $title, $additionalData, $previous);
    }
}
