<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @psalm-require-implements \Common\Handler\UserAware
 */
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

        // TODO UML-2710 why are we reauthenticating the user when the UserInterface::class will either
        //      exist in the session or won't? If it's in the session just return it?
        return $this->authenticator->authenticate($request);
    }
}
