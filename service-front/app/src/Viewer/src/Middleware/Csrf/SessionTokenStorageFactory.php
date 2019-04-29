<?php

declare(strict_types=1);

namespace Viewer\Middleware\Csrf;

use Zend\Expressive\Session\SessionInterface;

class SessionTokenStorageFactory
{
    public function createSessionTokenStorage(SessionInterface $session) : SessionTokenStorage
    {
        return new SessionTokenStorage($session);
    }
}