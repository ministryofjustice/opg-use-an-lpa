<?php

declare(strict_types=1);

namespace Common\Handler;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;

interface UserAware
{
    public function setAuthenticator(AuthenticationInterface $authenticator): void;
    public function getUser(ServerRequestInterface $request): ?UserInterface;
}
