<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use App\DataAccess\ApiGateway\ActorCodes;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\LpaService;
use Psr\Log\LoggerInterface;

class CodesApiValidationStrategy implements CodeValidationStrategyInterface
{
    private ActorCodes $actorCodesApi;

    private LoggerInterface $logger;

    private LpaService $lpaService;

    public function __construct(
        ActorCodes $actorCodesApi,
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        $this->actorCodesApi = $actorCodesApi;
        $this->lpaService = $lpaService;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function validateCode(string $code, string $uid, string $dob): string
    {
        try {
            $actorCode = $this->actorCodesApi->validateCode($code, $uid, $dob);

            $actorUid = $actorCode->getData()['actor'] ?? null;
            if ($actorUid !== null) {
                return $actorUid;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to validate actor code when communicating with codes service',
                [
                    'uid' => $uid
                ]
            );

            // This is a serious error that needs reporting up the call stack
            throw $e;
        }

        $this->logger->notice(
            'Actor code validation failed for lpa {uid}',
            [
                'uid' => $uid
            ]
        );

        throw new ActorCodeValidationException('Actor code has not been validated by the codes service.');
    }

    /**
     * @inheritDoc
     */
    public function flagCodeAsUsed(string $code)
    {
        $this->actorCodesApi->flagCodeAsUsed($code);
    }
}
