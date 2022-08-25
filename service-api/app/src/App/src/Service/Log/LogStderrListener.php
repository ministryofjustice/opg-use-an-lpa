<?php

declare(strict_types=1);

namespace App\Service\Log;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LogStderrListener
{

    public function __construct(public LoggerInterface $logger, public bool $includeTrace = false) {}

    /**
     * Style and output errors to STDERR (For use with Docker)
     *
     * @param Throwable $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $data = [
            'message' => $error->getMessage(),
            'line' => $error->getLine(),
            'file' => $error->getFile(),
            'code' => $error->getCode(),
        ];

        if ($this->includeTrace) {
            $data['trace'] = $error->getTraceAsString();
        }

        $this->logger->error(
            '{message} on line {line} in {file}',
            $data
        );
    }
}
