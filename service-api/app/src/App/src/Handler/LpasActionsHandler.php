<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;

/**
 * Class LpasActionHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class LpasActionsHandler implements RequestHandlerInterface
{
    private OlderLpaService $olderLpaService;

    public function __construct(OlderLpaService $olderLpaService)
    {
        $this->olderLpaService = $olderLpaService;
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
            throw new BadRequestException('Required data missing to request an activation key');
        }
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->olderLpaService->checkLPAMatchAndGetActorDetails($requestData);

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException('The lpa-id is missing from the data match response');
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException('The actor-id is missing from the data match response');
        }

        // Checks if the actor already has an active activation key. If forced ignore
        if ($requestData['force_activation_key'] == false) {
            $hasActivationCode = $this->olderLpaService->hasActivationCode($lpaMatchResponse);

            if ($hasActivationCode instanceof DateTime) {
                throw new BadRequestException(
                    'LPA has an activation key already',
                    [
                        'lpa_type' => $lpaMatchResponse['lpa_type'],
                        'donor_name' => $lpaMatchResponse['donor_name']
                    ]
                );
            }
        }

        //If all criteria pass, request letter with activation key
        $this->olderLpaService->requestAccessByLetter($lpaMatchResponse['lpa-id'], $lpaMatchResponse['actor-id']);

        return new EmptyResponse();
    }
}
