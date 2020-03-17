<?php

declare(strict_types=1);

namespace Common\Service\Csrf;

use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Csrf\CsrfGuardFactoryInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\Exception\MissingSessionException;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;

class SessionCsrfGuardFactory implements CsrfGuardFactoryInterface
{
    /**
     * @var string
     */
    private $attributeKey;

    public function __construct(string $attributeKey = SessionMiddleware::SESSION_ATTRIBUTE)
    {
        $this->attributeKey = $attributeKey;
    }

    public function createGuardFromRequest(ServerRequestInterface $request): CsrfGuardInterface
    {
        $session = $request->getAttribute($this->attributeKey, false);
        if (! $session instanceof SessionInterface) {
            throw MissingSessionException::create();
        }

        return new SessionCsrfGuard($session);
    }
}