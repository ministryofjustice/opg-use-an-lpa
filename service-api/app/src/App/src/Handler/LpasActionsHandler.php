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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $lpaMatchResponse = null;
        $requestData = $request->getParsedBody();
        $userId = $request->getAttribute('user-id');

        if (
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

        if (is_null($lpaMatchResponse)) {
            throw new NotFoundException('LPA not found');
        }

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException("'lpa-id' missing.");
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException("'actor-id' missing.");
        }

        // Checks if the actor already has an active activation key is_null(abc):true
        $hasActivationCode = $this->actorCodeService->hasActivationCode(
            $requestData['reference_number'],
            $lpaMatchResponse['actor-id']
        );

        if (!$hasActivationCode) {
            throw new BadRequestException("LPA not eligible");
        }

        //If all criteria pass, request letter with activation key
        $this->lpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);

        return new EmptyResponse();
    }
}
