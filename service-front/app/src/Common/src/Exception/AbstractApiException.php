<?php

declare(strict_types=1);

namespace Common\Exception;

use RuntimeException;
use Throwable;

/**
 * Custom exception that can be caught and translated into an API response
 *
 * Class AbstractApiException
 * @package App\Exception
 */
abstract class AbstractApiException extends RuntimeException
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $additionalData = [];

    /**
     * AbstractApiException constructor
     *
     * Following the sprint of https://framework.zend.com/blog/2017-03-23-expressive-error-handling.html
     *
     * @param string $title
     * @param string|null $message
     * @param array|null $additionalData
     * @param Throwable|null $previous
     */
    public function __construct(
        string $title,
        ?string $message = null,
        ?array $additionalData = null,
        ?Throwable $previous = null
    ) {
        // Ensure the the required data is set in the extending exception classes
        if (!is_numeric($this->getCode())) {
            throw new RuntimeException('A numeric code must be set for API exceptions');
        }

        // If no message has been provided make it equal the title
        if ($message === null) {
            $message = $title;
        }

        //  Set the remaining data for the exception
        $this->title = $title;
        $this->additionalData = $additionalData ?? [];

        parent::__construct($message, $this->getCode(), $previous);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }
}
