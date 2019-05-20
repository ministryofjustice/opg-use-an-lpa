<?php

namespace App\Exception;

use Throwable;

/**
 * Class NotFoundException
 * @package App\Exception
 */
class NotFoundException extends AbstractApiException
{
    /**
     * @var int
     */
    protected $code = 404;

    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param string $title
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, string $title = 'Not found', array $additionalData = [], Throwable $previous = null)
    {
        parent::__construct($message, $title, $additionalData, $previous);
    }
}
