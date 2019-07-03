<?php

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class NotFoundException
 * @package App\Exception
 */
class NotFoundException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Not found';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_NOT_FOUND;

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
