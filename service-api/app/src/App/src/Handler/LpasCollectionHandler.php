<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\LpaManagerInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class LpasCollectionHandler implements RequestHandlerInterface
{
    public function __construct(
        private LpaManagerInterface $lpaManager,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('actor-id');

        $result = $this->lpaManager->getAllForUser($user);

        return new JsonResponse($result);
    }
}
