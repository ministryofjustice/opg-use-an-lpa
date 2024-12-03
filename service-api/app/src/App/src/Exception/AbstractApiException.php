<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use RuntimeException;
use Throwable;

/**
 * Custom exception that can be caught and translated into an API response
 */
abstract class AbstractApiException extends RuntimeException
{
    /**
     * AbstractApiException constructor
     *
     * Following the spirit of https://framework.zend.com/blog/2017-03-23-expressive-error-handling.html
     *
     * @param string         $title
     * @param string|null    $message
     * @param int            $code
     * @param array          $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(
        private string $title,
        ?string $message = null,
        int $code = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
        protected array $additionalData = [],
        ?Throwable $previous = null,
    ) {
        //  If no message has been provided make it equal the title
        if (empty($message)) {
            $message = $title;
        }

        //  Set the remaining data for the exception
        parent::__construct($message, $code, $previous);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }
}
