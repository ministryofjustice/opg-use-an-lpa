<?php

declare(strict_types=1);

namespace Common\Middleware\Routing;

use Mezzio\MiddlewareFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

class ConditionalRoutingMiddleware implements MiddlewareInterface
{

    /**
     * @param ContainerInterface         $middlewareContainer
     * @param MiddlewareFactoryInterface $middlewareFactory
     * @param string                     $featureFlagName The name of the feature flag that will be used to determine the
     *                                                    correct route
     * @param string|callable            $trueRoute       The route taken if the feature flag is true
     * @param string|callable            $falseRoute      The route taken if the feature flag is false Or undefined.
     */
    public function __construct(
        private ContainerInterface $middlewareContainer,
        private MiddlewareFactoryInterface $middlewareFactory,
        private string $featureFlagName,
        private mixed $trueRoute,
        private mixed $falseRoute,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->middlewareContainer->get('config');

        if (!isset($config['feature_flags']) || !is_array($config['feature_flags'])) {
            throw new UnexpectedValueException('Missing feature flags configuration');
        }

        $flagEnabled = $config['feature_flags'][$this->featureFlagName] ?? false;

        $middleware = $this->middlewareFactory->prepare($flagEnabled ? $this->trueRoute : $this->falseRoute);
        return $middleware->process($request, $handler);
    }
}
