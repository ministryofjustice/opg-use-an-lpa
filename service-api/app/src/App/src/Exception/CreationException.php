<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;

/**
 * Class CreationException
 * @package App\Exception
 */
class CreationException extends AbstractApiException
{
    /**
     * Exception title
     */
    const TITLE = 'Creation error';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

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
