<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-require-implements Common\Handler\CsrfGuardAware
 */
trait CsrfGuard
{
    public function getCsrfGuard(ServerRequestInterface $request): ?CsrfGuardInterface
    {
        return $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
    }
}
