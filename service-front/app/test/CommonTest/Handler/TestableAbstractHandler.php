<?php

declare(strict_types=1);

namespace CommonTest\Handler;

use Common\Handler\AbstractHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestableAbstractHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new Exception('Not implemented');
    }
}
