<?php

declare(strict_types=1);

namespace App\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class ApiException extends AbstractApiException implements LoggableAdditionalDataInterface
{
    // The title is suitably generic, further details (from previous Throwables) will be
    // encapsulated in the stacktrace.
    public const DEFAULT_TITLE = 'An API exception has occurred';
    public const DEFAULT_CODE  = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

    public function __construct(
        string $message,
        int $code = self::DEFAULT_CODE,
        protected ?ResponseInterface $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(self::DEFAULT_TITLE, $message, $code, [], $previous);
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
     */
    public function getAdditionalData(): array
    {
        $data = null;

        if ($this->getResponse() !== null) {
            try {
                $data = json_decode($this->getResponse()->getBody()->getContents(), true);
            } catch (RuntimeException) {
                // $body->getContents() can fail and needs trapping
            }
        }

        return $data ?? [];
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
        $code = self::DEFAULT_CODE;

        if (! is_null($response)) {
            $body = null;
            $code = $response->getStatusCode();

            //  If no message was provided create one from the response data
            if (is_null($message)) {
                try {
                    $body = json_decode($response->getBody()->getContents(), true);

                    //  If no message was provided create one from the response data
                    //  Try to get the message from the details section of the body
                    if (is_array($body) && isset($body['details'])) {
                        $message = $body['details'];
                    }
                } catch (RuntimeException) {
                    // $body->getContents() can fail and needs trapping
                }
            }

            //  If there is still no message then compose a standard message
            if (is_null($message)) {
                $message = sprintf(
                    'HTTP: %d - %s',
                    $code,
                    is_array($body) ? print_r($body, true) : 'Unexpected API response'
                );
            }
        }

        return new self($message, $code, $response, $previous);
    }
}
