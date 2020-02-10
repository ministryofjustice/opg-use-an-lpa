<?php

declare(strict_types=1);

namespace Common\Handler;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\SessionInterface;

interface SessionAware
{
    public function getSession(ServerRequestInterface $request, string $name): ?SessionInterface;
}
