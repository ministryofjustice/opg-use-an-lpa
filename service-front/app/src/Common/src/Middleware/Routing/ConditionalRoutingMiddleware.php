<?php

declare(strict_types=1);

namespace Common\Middleware\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

class ConditionalRoutingMiddleware implements MiddlewareInterface
{
    private ContainerInterface $middlewareContainer;

    /**
     * @param ContainerInterface $container       It is necessary to pass the container so we can resolve the feature flag
     *                                      at runtime. Passing just the FeatureEnabled component results in having to
     *                                      re-initialise a number of container items to facilitate testing
     *                                      - increasing complexity.
     * @param string             $featureFlagName The name of the feature flag that will be used to determine the
     *                                      correct route
     * @param \Closure           $trueRoute       The route taken if the feature flag is true
     * @param string             $falseRoute      The route taken if the feature flag is false Or undefined.
     */
    public function __construct(
        ContainerInterface $container,
        private string $featureFlagName,
        private \Closure $trueRoute,
        private string $falseRoute,
    ) {
        $this->middlewareContainer = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->middlewareContainer->get('config');

        if (!isset($config['feature_flags']) || !is_array($config['feature_flags'])) {
            throw new UnexpectedValueException('Missing feature flags configuration');
        }

        $flagEnabled = $config['feature_flags'][$this->featureFlagName] ?? false;

        $middleware = $this->middlewareContainer->get($flagEnabled ? $this->trueRoute : $this->falseRoute);

        return $middleware->handle($request, $handler);
    }
}
