<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Zend\Expressive\Session\SessionInterface;

class SessionTokenStorage implements TokenStorageInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session = null)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function getToken($tokenId) : string
    {
        if ( ! $this->session->has($tokenId)) {
            throw new TokenNotFoundException();
        }

        return $this->session->get($tokenId);
    }

    /**
     * @inheritDoc
     */
    public function setToken($tokenId, $token)
    {
        $this->session->set($tokenId, $token);
    }

    /**
     * @inheritDoc
     */
    public function removeToken($tokenId) : ?string
    {
        $token = $this->session->get($tokenId, null);
        $this->session->unset($tokenId);

        return $token;
    }

    /**
     * @inheritDoc
     */
    public function hasToken($tokenId) : bool
    {
        return $this->session->has($tokenId);
    }
}