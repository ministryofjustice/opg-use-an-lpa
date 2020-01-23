<?php

declare(strict_types=1);

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
     * Exception title
     */
    const TITLE = 'Gone';

    /**
     * @var int
     */
    protected $code = StatusCodeInterface::STATUS_GONE;

    /**
     * GoneException constructor.
     *
     * @param string $message
     * @param array $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, ?array $additionalData = [], ?Throwable $previous = null)
    {
        parent::__construct(self::TITLE, $message, $additionalData, $previous);
    }
}
