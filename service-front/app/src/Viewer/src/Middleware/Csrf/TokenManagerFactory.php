<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Zend\Expressive\Session\SessionMiddleware;

class TokenManagerFactory implements TokenManagerFactoryInterface
{
    /**
     * @var string
     */
    private $attributeKey;

    public function __construct(string $attributeKey = SessionMiddleware::SESSION_ATTRIBUTE)
    {
        $this->attributeKey = $attributeKey;
    }

    /**
     * This will provide a correctly built TokenManager but because we rely on
     * the request pipeline to make it fully functional we actually rely on middleware
     * to create a working instance. The middleware uses the createTokenManagerFromGuard()
     * function below.
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