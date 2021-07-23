<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Lpa\OlderLpaService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use DateTime;

/**
 * Class OlderLpaVerificationHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class OlderLpaVerificationHandler implements RequestHandlerInterface
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
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->olderLpaService->checkLPAMatchAndGetActorDetails($userId, $requestData);

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

        return new JsonResponse($lpaMatchResponse, 200);
    }
}
