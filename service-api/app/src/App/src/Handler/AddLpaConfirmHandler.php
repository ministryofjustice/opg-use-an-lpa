<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class AddLpaConfirmHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class AddLpaConfirmHandler implements RequestHandlerInterface
{
    /**
     * @var ActorCodeService
     */
    private $actorCodeService;

    public function __construct(ActorCodeService $actorCodeService)
    {
        $this->actorCodeService = $actorCodeService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $actorId = $request->getHeader('user-token')[0];

        if (!isset($data['actor-code']) || !isset($data['uid']) || !isset($data['dob'])) {
            throw new BadRequestException("'actor-code', 'uid' and 'dob' are required fields");
        }

        $response = $this->actorCodeService->confirmDetails(
            $data['actor-code'],
            (string) $data['uid'],
            $data['dob'],
            $actorId
        );

        // We deliberately don't return details of why the (validated) code was not found.
        if (!is_string($response)) {
            throw new NotFoundException();
        }

        return new JsonResponse([
            'user-lpa-actor-token' => $response
        ], 201);
    }
}
