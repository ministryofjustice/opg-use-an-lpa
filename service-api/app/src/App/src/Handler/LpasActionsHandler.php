<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        if (
            !isset($requestData['reference_number']) ||
            !isset($requestData['dob']) ||
            !isset($requestData['first_names']) ||
            !isset($requestData['last_name']) ||
            !isset($requestData['postcode'])
        ) {
            throw new BadRequestException("Required data missing to request an activation key");
        }
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->lpaService->checkLPAMatchAndGetActorDetails($requestData);

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException("The lpa-id is missing from the data match response");
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException("The actor-id is missing from the data match response");
        }

        // Checks if the actor already has an active activation key
        $hasActivationCode = $this->actorCodeService->hasActivationCode(
            $lpaMatchResponse['lpa-id'],
            $lpaMatchResponse['actor-id']
        );

        if ($hasActivationCode) {
            throw new BadRequestException("LPA not eligible as an activation key already exists");
        }

        //If all criteria pass, request letter with activation key
        $this->lpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);

        return new EmptyResponse();
    }
}
