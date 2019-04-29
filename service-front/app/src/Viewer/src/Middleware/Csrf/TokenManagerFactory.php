<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class TokenManagerFactory implements TokenManagerFactoryInterface
{
    /**
     * This will provide a correctly built TokenManager but because we rely on
     * the request pipeline to make it fully functional, we actually rely on middleware
     * to create a working instance. The middleware uses the createTokenManager()
     * function below.
     *
     * This function still needs to exist so the Twig CSRF extension can be initialised correctly.
     *
     * @param ContainerInterface $container
     * @return TokenManager
     */
    public function __invoke(ContainerInterface $container) : TokenManager
    {
        return new TokenManager();
    }

    /**
     * @inheritDoc
     */
    public function createTokenManager(TokenGeneratorInterface $generator, TokenStorageInterface $storage): TokenManager
    {
        return new TokenManager($generator, $storage);
    }
}