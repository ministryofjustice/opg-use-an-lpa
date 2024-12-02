<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\AbstractApiException;
use App\Exception\LoggableAdditionalDataInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ProblemDetailsMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (AbstractApiException $ex) {
            //  Translate this exception type into response JSON
            $problem = [
                'title'   => $ex->getTitle(),
                'details' => $ex->getMessage(),
                'data'    => $ex->getAdditionalData(),
            ];

            $this->logger->info(
                $ex->getMessage(),
                $ex instanceof LoggableAdditionalDataInterface ? $ex->getAdditionalDataForLogging() : [],
            );

            $previous = $ex->getPrevious();
            if ($previous instanceof Exception) {
                $this->logger->debug(
                    $ex->getMessage(),
                    [
                        'previous' => $previous->getMessage(),
                        'trace'    => $previous->getTrace(),
                    ]
                );
            }

            return new JsonResponse(
                $problem,
                $ex->getCode(),
                ['Content-Type' => 'application/problem+json']
            );
        }
    }
}
