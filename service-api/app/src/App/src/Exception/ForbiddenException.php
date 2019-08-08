<?php

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class ForbiddenException
 * @package App\Exception
 */
class ForbiddenException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Forbidden';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_FORBIDDEN;

    /**
     * NotFoundException constructor.
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
