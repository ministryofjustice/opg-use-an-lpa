<?php

declare(strict_types=1);

namespace App\Middleware\Session;

use App\Service\Session\Cookie as CookieSessionPersistence;

use Zend\Expressive\Session\SessionMiddleware;

class General extends SessionMiddleware
{
    #public const SESSION_ATTRIBUTE = 'cookie';

    /**
     * General constructor.
     * @param CookieSessionPersistence $persistence
     */
    public function __construct(CookieSessionPersistence $persistence)
    {
        parent::__construct($persistence);

        # Apply config
    }
}
