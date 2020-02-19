<?php

declare(strict_types=1);

namespace App\Service\Log;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LogStderrListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Style and output errors to STDERR (For use with Docker)
     *
     * @param Throwable $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->logger->error(
            '{message} on line {line} in {file}',
            [
                'message' => $error->getMessage(),
                'line' => $error->getLine(),
                'file' => $error->getFile(),
                'code' => $error->getCode(),
                'trace' => $error->getTraceAsString(),
            ]
        );
    }
}
