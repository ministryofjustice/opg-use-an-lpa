<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class ApiException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    // A safe bet for an exception is a 500 error response
    public const DEFAULT_ERROR = 500;

    // The title is suitably generic, further details (from previous Throwables) will be
    // encapsulated in the stacktrace.
    public const DEFAULT_TITLE = 'An API exception has occurred';

    /**
     * ApiException constructor
     *
     * @param string                 $message
     * @param int                    $code
     * @param ResponseInterface|null $response
     * @param Throwable|null         $previous
     * @throws RuntimeException
     */
    public function __construct(
        string $message,
        int $code = self::DEFAULT_ERROR,
        protected ?ResponseInterface $response = null,
        ?Throwable $previous = null,
    ) {
        $this->code = $code;

        parent::__construct(self::DEFAULT_TITLE, $message, [], $previous);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Returns the body content of the response decoded from JSON into an
     * associative array.
     *
     * @return array
     * @throws RuntimeException
     */
    public function getAdditionalData(): array
    {
        return $this->getResponse() !== null
            ? json_decode($this->getResponse()->getBody()->getContents(), true)
            : [];
    }

    public function getAdditionalDataForLogging(): array
    {
        return $this->getAdditionalData();
    }

    public static function create(
        ?string $message = null,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null,
    ): ApiException {
        $code = self::DEFAULT_ERROR;

        if (! is_null($response)) {
            $body = json_decode($response->getBody()->getContents(), true);
            $code = $response->getStatusCode();

            //  If no message was provided create one from the response data
            if (is_null($message)) {
                //  Try to get the message from the details section of the body
                if (is_array($body) && isset($body['details'])) {
                    $message = $body['details'];
                }

                //  If there is still no message then compose a standard message
                if (is_null($message)) {
                    $message = sprintf(
                        'HTTP: %d - %s',
                        $response->getStatusCode(),
                        is_array($body) ? print_r($body, true) : 'Unexpected API response'
                    );
                }
            }
        }

        return new self($message, $code, $response, $previous);
    }
}
