<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use App\DataAccess\Repository\ActorCodesInterface;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\LpaService;
use Psr\Log\LoggerInterface;
use Exception;

class DynamoCodeValidationStrategy implements CodeValidationStrategyInterface
{
    public function __construct(
        private ActorCodesInterface $actorCodesRepository,
        private LpaService $lpaService,
        private LoggerInterface $logger,
        private ResolveActor $resolveActor,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function validateCode(string $code, string $uid, string $dob): string
    {
        $details = $this->actorCodesRepository->get($code);

        if (is_null($details)) {
            $this->logger->info('Could not find details for code {code} when validating actor code', ['code' => $code]);
            throw new ActorCodeValidationException('Code not found');
        }

        if ($details['Active'] !== true) {
            $this->logger->info('{code} is inactive', ['code' => $code]);

            throw new ActorCodeValidationException('Code already used');
        }

        if ($uid !== $details['SiriusUid']) {
            $this->logger->info(
                'Uid {uid} did not match {expected} when validating actor code',
                [
                    'uid'      => $uid,
                    'expected' => $details['SiriusUid'],
                ]
            );
            throw new ActorCodeValidationException('Bad LPA uId');
        }

        $lpa = $this->lpaService->getByUid($details['SiriusUid']);

        if (is_null($lpa)) {
            $this->logger->error(
                'Could not find LPA for SiriusUid {SiriusUid} when validating actor code',
                [
                    'SiriusUid' => $details['SiriusUid'],
                ]
            );
            throw new ActorCodeValidationException('LPA not found');
        }

        $actor = ($this->resolveActor)($lpa->getData(), (int) $details['ActorLpaId']);

        if (is_null($actor)) {
            $this->logger->error(
                'Could not find actor {ActorLpaId} in LPA for SiriusUid {SiriusUid} when validating actor code',
                [
                    'ActorLpaId' => $details['ActorLpaId'],
                    'SiriusUid'  => $details['SiriusUid'],
                ]
            );
            throw new ActorCodeValidationException('Actor not in LPA');
        }

        if ($dob !== $actor['details']['dob']) {
            $this->logger->info(
                'Dob {dob} did not match {expected} when validating actor code',
                [
                    'dob'      => $dob,
                    'expected' => $actor['details']['dob'],
                ]
            );
            throw new ActorCodeValidationException('Bad date of birth');
        }

        return (string) $actor['details']['uId'];
    }

    /**
     * @inheritDoc
     */
    public function flagCodeAsUsed(string $code): void
    {
        try {
            $this->actorCodesRepository->flagCodeAsUsed($code);
        } catch (Exception $e) {
            throw new ActorCodeMarkAsUsedException('Failed to mark code as used', 500, $e);
        }
    }
}
