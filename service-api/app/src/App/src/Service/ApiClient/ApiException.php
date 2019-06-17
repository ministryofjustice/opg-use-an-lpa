<?php declare(strict_types=1);

namespace App\Service\ApiClient;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Class ApiException
 * @package App\Service\ApiClient
 */
class ApiException extends RuntimeException
{
    const DEFAULT_ERROR = 500;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * ApiException constructor
     *
     * @param string $message
     * @param int $code
     * @param ResponseInterface|null $response
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = self::DEFAULT_ERROR, ResponseInterface $response = null, Throwable $previous = null)
    {
        $this->response = $response;

        parent::__construct($message, $code, $previous);
    }

    public function getResponse() : ResponseInterface
    {
        return $this->response;
    }

    public static function create(string $message = null, ResponseInterface $response = null, Throwable $previous = null) : ApiException
    {
        $code = null;

        if (! is_null($response)) {
            $body = json_decode($response->getBody(), true);
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
