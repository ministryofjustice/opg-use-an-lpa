<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

interface TokenManagerFactoryInterface
{
    /**
     * Creates a TokenManager instance using Generator and Storage instances.
     *
     * @param TokenGeneratorInterface $generator
     * @param TokenStorageInterface $storage
     * @return TokenManager
     */
    public function createTokenManager(TokenGeneratorInterface $generator, TokenStorageInterface $storage) : TokenManager;
}