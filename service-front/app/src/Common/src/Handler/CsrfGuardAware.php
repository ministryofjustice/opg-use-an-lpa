<?php

declare(strict_types=1);

namespace Common\Handler;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Csrf\CsrfGuardInterface;

interface CsrfGuardAware
{
    public function getCsrfGuard(ServerRequestInterface $request): ?CsrfGuardInterface;
}
