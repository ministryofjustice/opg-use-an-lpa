<?php

namespace Common\Middleware\Routing;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalRoutingMiddleware implements MiddlewareInterface
{
    private ContainerInterface $middlewareContainer;
    private string $featureFlagName;
    private string $trueRoute;
    private string $falseRoute;

    public function __construct(
        ContainerInterface $container,
        string $featureFlagName,
        string $trueRoute,
        string $falseRoute
    ) {
        $this->middlewareContainer = $container;
        $this->featureFlagName = $featureFlagName;
        $this->trueRoute = $trueRoute;
        $this->falseRoute = $falseRoute;
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
