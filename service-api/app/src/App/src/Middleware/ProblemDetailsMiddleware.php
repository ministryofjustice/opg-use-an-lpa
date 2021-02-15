<?php

namespace App\Middleware;

use App\Exception\AbstractApiException;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as DelegateInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ProblemDetailsMiddleware
 *
 * @package App\Middleware
 */
class ProblemDetailsMiddleware implements MiddlewareInterface
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
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface|JsonResponse
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        try {
            $response = $delegate->handle($request);

            return $response;
        } catch (AbstractApiException $ex) {
            //  Translate this exception type into response JSON
            $problem = [
                'title' => $ex->getTitle(),
                'details' => $ex->getMessage(),
                'data' => $ex->getAdditionalData(),
            ];

            $this->logger->info($ex->getMessage(), $ex->getAdditionalData());

            $previous = $ex->getPrevious();
            if ($previous instanceof Exception) {
                $this->logger->debug(
                    $ex->getMessage(),
                    [
                        'previous' => $previous->getMessage(),
                        'trace' => $previous->getTrace()
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
