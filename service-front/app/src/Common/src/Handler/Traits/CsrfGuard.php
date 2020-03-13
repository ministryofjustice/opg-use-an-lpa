<?php

declare(strict_types=1);

namespace Common\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;

trait CsrfGuard
{
    public function getCsrfGuard(ServerRequestInterface $request): ?CsrfGuardInterface
    {
        return $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
    }
}
