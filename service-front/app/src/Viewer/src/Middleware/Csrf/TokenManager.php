<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class TokenManager implements CsrfTokenManagerInterface
{
    /**
     * @var TokenGeneratorInterface
     */
    private $generator;

    /**
     * @var TokenStorageInterface
     */
    private $storage;

    public function __construct(TokenGeneratorInterface $generator = null, TokenStorageInterface $storage = null)
    {
        $this->generator = $generator;
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function getToken($tokenId) : CsrfToken
    {
        if ($this->storage->hasToken($tokenId)) {
            $token = $this->storage->getToken($tokenId);
        } else {
            $token = $this->generator->generateToken();
            $this->storage->setToken($tokenId, $token);
        }

        return new CsrfToken($tokenId, $token);
    }

    /**
     * @inheritDoc
     */
    public function refreshToken($tokenId) : CsrfToken
    {
        $token = $this->generator->generateToken();
        $this->storage->setToken($tokenId, $token);

        return new CsrfToken($tokenId, $token);
    }

    /**
     * @inheritDoc
     */
    public function removeToken($tokenId) : ?string
    {
        return $this->storage->removeToken($tokenId);
    }

    /**
     * @inheritDoc
     */
    public function isTokenValid(CsrfToken $token) : bool
    {
        if ( ! $this->storage->hasToken($token->getId())) {
            return false;
        }

        return $this->storage->getToken($token->getId()) === $token->getValue();
    }
}