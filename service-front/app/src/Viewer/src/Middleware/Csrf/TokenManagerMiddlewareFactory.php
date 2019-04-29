<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class TokenManagerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new TokenManagerMiddleware(
            $container->get(UriSafeTokenGenerator::class),
            $container->get(SessionTokenStorageFactory::class),
            $container->get(TokenManagerFactory::class)
        );
    }
}