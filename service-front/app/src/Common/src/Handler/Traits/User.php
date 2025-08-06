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
    public function getUser(ServerRequestInterface $request): ?UserInterface
    {
        return $request->getAttribute(UserInterface::class);
    }
}
