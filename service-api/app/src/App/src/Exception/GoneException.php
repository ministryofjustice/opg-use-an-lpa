<?php

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class GoneException
 * @package App\Exception
 */
class GoneException extends AbstractApiException
{
    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_GONE;

    /**
     * GoneException constructor.
     *
     * @param string $message
     * @param string $title
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, string $title = 'Gone', array $additionalData = [], Throwable $previous = null)
    {
        parent::__construct($message, $title, $additionalData, $previous);
    }
}
