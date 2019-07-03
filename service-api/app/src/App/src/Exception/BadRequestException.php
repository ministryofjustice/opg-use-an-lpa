<?php

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class BadRequestException
 * @package App\Exception
 */
class BadRequestException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Bad Request';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_BAD_REQUEST;

    /**
     * BadRequestException constructor.
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
