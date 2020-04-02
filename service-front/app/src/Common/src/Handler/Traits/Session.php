<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Session\SessionInterface;

trait Session
{
    public function getSession(ServerRequestInterface $request, string $name): ?SessionInterface
    {
        return $request->getAttribute($name);
    }
}
