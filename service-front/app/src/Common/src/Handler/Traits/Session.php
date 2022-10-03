<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-require-implements Common\Handler\SessionAware
 */
trait Session
{
    public function getSession(ServerRequestInterface $request, string $name): ?SessionInterface
    {
        return $request->getAttribute($name);
    }
}
