<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\LpaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use RuntimeException;

/**
 * Class LpaSearchHandler
 * @package App\Handler
 */
class LpasCollectionHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(LpaService $lpaService)
    {
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('actor-id');

        $result = $this->lpaService->getAllForUser($user);

        return new JsonResponse($result);
    }
}
