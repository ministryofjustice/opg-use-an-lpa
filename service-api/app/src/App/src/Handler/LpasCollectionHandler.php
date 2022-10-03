<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\LpaService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LpasCollectionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasCollectionHandler implements RequestHandlerInterface
{
    public function __construct(
        private LpaService $lpaService,
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

        $result = $this->lpaService->getAllForUser($user);

        return new JsonResponse($result);
    }
}
