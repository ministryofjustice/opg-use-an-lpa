<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class SessionExpiryMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SessionExpiryMiddleware
    {
        $config = $container->get('config');

        if (!array_key_exists('expires', $config['session'])) {
            throw new RuntimeException('Missing session configuration: expires');
        }

        return new SessionExpiryMiddleware($config['session']['expires']);
    }
}
