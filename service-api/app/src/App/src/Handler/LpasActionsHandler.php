<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\AddOlderLpa;
use App\Service\Lpa\OlderLpaService;
use DateTime;
use Exception;
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
    private AddOlderLpa $addOlderLpa;
    private OlderLpaService $olderLpaService;
    private FeatureEnabled $featureEnabled;

    public function __construct(
        OlderLpaService $olderLpaService,
        AddOlderLpa $addOlderLpa,
        FeatureEnabled $featureEnabled
    )
    {
        $this->olderLpaService = $olderLpaService;
        $this->addOlderLpa = $addOlderLpa;
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId = $request->getAttribute('actor-id');

        if (
            !isset($requestData['reference_number']) ||
            !isset($requestData['dob']) ||
            !isset($requestData['first_names']) ||
            !isset($requestData['last_name']) ||
            !isset($requestData['postcode'])
        ) {
            throw new BadRequestException('Required data missing to request an activation key');
        }

        //UML-1578 Check if activation key already requested by actor for LPA
        if (($this->featureEnabled)('allow_older_lpas')) {
           if (!$requestData['force_activation_key']) {
                $this->olderLpaService->checkIfActivationKeyAlreadyRequested(
                    $userId,
                    (string)$requestData['reference_number']
                );
           }
        }

        // Check LPA with user provided reference number
        $lpaMatchResponse = ($this->addOlderLpa)->validateRequest($userId, $requestData);

        if (!isset($lpaMatchResponse['lpa-id'])) {
            throw new BadRequestException('The lpa-id is missing from the data match response');
        }

        if (!isset($lpaMatchResponse['actor-id'])) {
            throw new BadRequestException('The actor-id is missing from the data match response');
        }
        // Checks if the actor already has an active activation key. If forced ignore
        if (!$requestData['force_activation_key']) {
            $hasActivationCode = $this->olderLpaService->hasActivationCode(
                $lpaMatchResponse['lpa-id'],
                $lpaMatchResponse['actor-id']
            );

            if ($hasActivationCode instanceof DateTime) {
                throw new BadRequestException(
                    'LPA has an activation key already',
                    [
                        'donor'         => $lpaMatchResponse['donor'],
                        'caseSubtype'   => $lpaMatchResponse['caseSubtype']
                    ]
                );
            }
        }

        //If all criteria pass, request letter with activation key
        $this->olderLpaService->requestAccessByLetter(
            $lpaMatchResponse['lpa-id'],
            $lpaMatchResponse['actor-id'],
            $userId
        );


        return new EmptyResponse();
    }
}
