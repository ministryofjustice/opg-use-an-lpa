<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Exception\RateLimitExceededException;
use Common\Service\Security\RateLimitServiceFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Queries the registered rate limit storage to block incoming requests that have exceeded the specified limit.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private RateLimitServiceFactory $rateLimitServiceFactory)
    {
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws RateLimitExceededException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null !== $identity = $request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE)) {
            $rateLimiters = $this->rateLimitServiceFactory->all();

            foreach ($rateLimiters as $limiter) {
                if ($limiter->isLimited($identity)) {
                    throw new RateLimitExceededException(
                        $limiter->getName() . ' rate limit exceeded for identity ' . $identity
                    );
                }
            }
        }

        return $handler->handle($request);
    }
}
