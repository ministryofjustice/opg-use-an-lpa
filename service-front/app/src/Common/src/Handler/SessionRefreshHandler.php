<?php

declare(strict_types=1);

namespace Common\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionRefreshHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Simply by accessing this handler the session is refreshed further up in the pipeline
        return new JsonResponse(['session_refreshed' => true],201);
    }
}
