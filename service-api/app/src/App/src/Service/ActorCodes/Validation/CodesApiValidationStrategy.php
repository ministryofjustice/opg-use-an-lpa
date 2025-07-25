<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use App\DataAccess\ApiGateway\ActorCodes;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\ResolveActor;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use Exception;

class CodesApiValidationStrategy implements CodeValidationStrategyInterface
{
    public function __construct(
        private ActorCodes $actorCodesApi,
        private LpaManagerInterface $lpaManager,
        private LoggerInterface $logger,
        private ResolveActor $resolveActor,
    ) {
    }

    public function validateCode(string $code, string $uid, string $dob): string
    {
        try {
            $actorCode = $this->actorCodesApi->validateCode($code, $uid, $dob);

            $actorUid = $actorCode->getData()->actorUid;

            if ($actorUid !== null && $this->verifyAgainstLpa($uid, $actorUid, $dob)) {
                return $actorUid;
            }
        } catch (ActorCodeValidationException $acve) {
            throw $acve;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to validate actor code when communicating with codes service',
                [
                    'uid' => $uid,
                ]
            );

            // This is a serious error that needs reporting up the call stack
            throw $e;
        }

        $this->logger->notice(
            'Actor code validation failed for lpa {uid}',
            [
                'uid' => $uid,
            ]
        );

        throw new ActorCodeValidationException('Actor code has not been validated by the codes service.');
    }

    public function flagCodeAsUsed(string $code): void
    {
        try {
            $this->actorCodesApi->flagCodeAsUsed($code);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to revoke actor code {code} when communicating with codes service',
                [
                    'code' => new HiddenString($code),
                ]
            );

            // This is a serious error that needs reporting up the call stack
            throw new ActorCodeMarkAsUsedException(
                'Code has not been marked as used by the codes service',
                500,
                $e
            );
        }
    }

    /**
     * Attempts further validation against sirius lpa data so that we can be sure that we're using
     * the authoritative source.
     *
     * @param string $uid
     * @param string $actorUid
     * @param string $dob
     * @return bool
     * @throws ActorCodeValidationException
     */
    private function verifyAgainstLpa(string $uid, string $actorUid, string $dob): bool
    {
        $lpa = $this->lpaManager->getByUid($uid);

        if ($lpa === null) {
            $this->logger->error(
                'Could not find LPA for SiriusUid {SiriusUid} when validating actor code',
                [
                    'SiriusUid' => $uid,
                ]
            );
            throw new ActorCodeValidationException('LPA not found');
        }

        $actor = ($this->resolveActor)($lpa->getData(), $actorUid);

        if ($actor === null) {
            $this->logger->error(
                'Could not find actor {ActorLpaId} in LPA for SiriusUid {SiriusUid} when validating actor code',
                [
                    'ActorLpaId' => $actorUid,
                    'SiriusUid'  => $uid,
                ]
            );
            throw new ActorCodeValidationException('Actor not in LPA');
        }

        if (
            (
                $actor->actorType !== ResolveActor\ActorType::TRUST_CORPORATION
                && $dob !== $actor->actor->getDob()->format('Y-m-d')
            ) || (
                $actor->actorType === ResolveActor\ActorType::TRUST_CORPORATION
                && $dob !== $lpa->getData()->getDonor()->getDob()->format('Y-m-d')
            )
        ) {
            $this->logger->error(
                'Provided dob {dob} did not match the expected when validating actor code',
                [
                    'dob' => $dob,
                ]
            );
            throw new ActorCodeValidationException('Bad date of birth');
        }

        return true;
    }
}
