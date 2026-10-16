<?php

/**
 * PhpStorm code completion
 */

namespace PHPSTORM_META {

    use Mezzio\Session\SessionInterface;
    use Mezzio\Session\SessionMiddleware;
    use Psr\Container\ContainerInterface;
    use Psr\Http\Message\ServerRequestInterface;

    // PSR11 container
    override(ContainerInterface::get(0),
        map([
            '' => '@',
        ])
    );

    // PSR-7 requests attributes; e.g. PSR-7 Storage-less HTTP Session
    override(ServerRequestInterface::getAttribute(0),
        map([
            SessionMiddleware::SESSION_ATTRIBUTE instanceof SessionInterface,
            UserInterface::class instanceof UserInterface,
        ])
    );
}
