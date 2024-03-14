<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class AddLpaConfirmationHandler implements RequestHandlerInterface
{
    public function __construct(
        private ActorCodeService $actorCodeService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $userId = $request->getHeader('user-token')[0];

        if (empty($data['actor-code']) || empty($data['uid']) || empty($data['dob'])) {
            throw new BadRequestException("'actor-code', 'uid' and 'dob' are required fields");
        }

        $response = $this->actorCodeService->confirmDetails(
            $data['actor-code'],
            $data['uid'],
            $data['dob'],
            $userId,
        );

        // We deliberately don't return details of why the (validated) code was not found.
        if (!is_string($response)) {
            throw new NotFoundException();
        }

        return new JsonResponse(
            [
                'user-lpa-actor-token' => $response,
            ],
            StatusCodeInterface::STATUS_CREATED,
        );
    }
}
