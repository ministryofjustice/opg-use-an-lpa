<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

trait User
{
    private ?AuthenticationInterface $authenticator = null;

    public function setAuthenticator(AuthenticationInterface $authenticator): void
    {
        $this->authenticator = $authenticator;
    }

    public function getUser(ServerRequestInterface $request): ?UserInterface
    {
        if ($this->authenticator === null) {
            throw new RuntimeException(
                'Authentication interface property not initialised before attempt to fetch'
            );
        }

        return $this->authenticator->authenticate($request);
    }
}
