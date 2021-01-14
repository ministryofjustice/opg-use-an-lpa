<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class LpasActionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasActionsHandler implements RequestHandlerInterface
{
    /**
     * @var LpaService
     */
    private $lpaService;

    private ActorCodeService $actorCodeService;

    public function __construct(LpaService $lpaService, ActorCodeService $actorCodeService)
    {
        $this->lpaService = $lpaService;
        $this->actorCodeService = $actorCodeService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handlePatch(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getAttribute('user-id');

        if (
            !isset($actorId) ||
            !isset($requestData['reference_number']) ||
            !isset($requestData['dob']) ||
            !isset($requestData['first_names']) ||
            !isset($requestData['last_name']) ||
            !isset($requestData['postcode'])
        ) {
            throw new BadRequestException("Required data missing!");
        }
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->lpaService->checkLPAMatchAndGetActorDetails($requestData);
        // Checks if the actor already has an active activation key
        // TODO: I've had to hardcode the actor id being passed in for the
        //  time being since we have not retrieved the correct actor Id yet.
        //  Please update this once completed
        $hasActivationCode = $this->actorCodeService->hasActivationCode($requestData['reference_number'], '700000116322');

        if (is_null($lpaMatchResponse)) {
            throw new NotFoundException('LPA not found');
        }

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException("'lpa-id' missing.");
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException("'actor-id' missing.");
        }

        $this->lpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);

        return new EmptyResponse();
    }
}
