<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;

trait User
{
    /** @var AuthenticationInterface */
    private $authenticator;

    /**
     * @param AuthenticationInterface $authenticator
     */
    public function setAuthenticator(AuthenticationInterface $authenticator): void
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @param ServerRequestInterface $request
     * @return UserInterface|null
     */
    public function getUser(ServerRequestInterface $request): ?UserInterface
    {
        return $this->authenticator->authenticate($request);
    }
}