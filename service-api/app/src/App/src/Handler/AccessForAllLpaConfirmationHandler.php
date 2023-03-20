<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\EventCodes;
use App\Service\Lpa\AddAccessForAllLpa;
use App\Service\Lpa\CheckLpaCleansed;
use App\Service\Lpa\AccessForAllLpaService;
use Exception;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class AccessForAllLpaConfirmationHandler implements RequestHandlerInterface
{
    public function __construct(
        private AddAccessForAllLpa $addAccessForAllLpa,
        private AccessForAllLpaService $accessForAllLpaService,
        private FeatureEnabled $featureEnabled,
        private CheckLpaCleansed $checkLpaCleansed,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException|Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();
        $userId      = $request->getHeader('user-token')[0];

        if (
            empty($requestData['reference_number']) ||
            empty($requestData['dob']) ||
            empty($requestData['first_names']) ||
            empty($requestData['last_name']) ||
            empty($requestData['postcode'])
        ) {
            throw new BadRequestException('Required data missing to request an activation key');
        }

        $lpaMatchResponse = $this->addAccessForAllLpa->validateRequest($userId, $requestData);

        if (($this->featureEnabled)('allow_older_lpas')) {
            ($this->checkLpaCleansed)($userId, $lpaMatchResponse);
        }

        $this->accessForAllLpaService->requestAccessByLetter(
            (string) $requestData['reference_number'],
            $lpaMatchResponse['actor']['uId'],
            $userId,
            $lpaMatchResponse['lpaActorToken'] ?? null,
        );

        $this->logger->notice(
            'Successfully matched data for LPA {uId} and requested letter for account {id} ',
            [
                'event_code' => $lpaMatchResponse['caseSubtype'] === 'hw'
                    ? EventCodes::FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_HW
                    : EventCodes::FULL_MATCH_KEY_REQUEST_SUCCESS_LPA_TYPE_PFA,
                'id'         => $userId,
                'uId'        => (string)$requestData['reference_number'],
                'lpaType'    => $lpaMatchResponse['caseSubtype'],
            ],
        );

        return new EmptyResponse();
    }
}
