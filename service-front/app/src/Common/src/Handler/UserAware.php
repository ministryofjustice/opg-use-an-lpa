<?php

declare(strict_types=1);

namespace Common\Handler;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ServerRequestInterface;

interface UserAware
{
    public function setAuthenticator(AuthenticationInterface $authenticator): void;

    public function getUser(ServerRequestInterface $request): ?UserInterface;
}
