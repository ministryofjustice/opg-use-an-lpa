<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\RemoveLpa;
use App\Service\Lpa\LpaService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class LpaSearchHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasResourceHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var RemoveLpa
     */
    private $removeLpa;

    public function __construct(LpaService $lpaService, RemoveLpa $removeLpa)
    {
        $this->lpaService = $lpaService;
        $this->removeLpa = $removeLpa;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            case 'DELETE':
                return $this->handleDelete($request);
            default:
                return $this->handleGet($request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($request->getAttribute('actor-id'))) {
            throw new BadRequestException("'actor-id' missing.");
        }

        if (is_null($request->getAttribute('user-lpa-actor-token'))) {
            throw new BadRequestException("'user-lpa-actor-token' missing.");
        }

        $result = $this->lpaService->getByUserLpaActorToken(
            $request->getAttribute('user-lpa-actor-token'),
            $request->getAttribute('actor-id')
        );

        if (is_null($result)) {
            throw new NotFoundException();
        }

        return new JsonResponse($result);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handleDelete(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getAttribute('user-lpa-actor-token');
        $userToken =  $request->getAttribute('actor-id');

        if (!isset($actorLpaToken)) {
            throw new BadRequestException('User actor LPA token must be provided for lpa removal');
        }

        $removedLpaData = ($this->removeLpa)($userToken, $actorLpaToken);

        return new JsonResponse(['lpa' => $removedLpaData]);
    }
}
