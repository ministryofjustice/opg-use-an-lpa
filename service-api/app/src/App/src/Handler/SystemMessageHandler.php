<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\SystemMessage\SystemMessageService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SystemMessageHandler implements RequestHandlerInterface
{
    public function __construct(
        private SystemMessageService $systemMessageService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->systemMessageService->getSystemMessages());
    }
}
