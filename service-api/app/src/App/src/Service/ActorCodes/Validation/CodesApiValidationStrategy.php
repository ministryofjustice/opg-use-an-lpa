<?php

declare(strict_types=1);

namespace App\Service\ActorCodes\Validation;

use App\DataAccess\ApiGateway\ActorCodes;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\LpaService;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;

class CodesApiValidationStrategy implements CodeValidationStrategyInterface
{
    private ActorCodes $actorCodesApi;

    private LoggerInterface $logger;

    private LpaService $lpaService;

    private ResolveActor $resolveActor;

    public function __construct(
        ActorCodes $actorCodesApi,
        LpaService $lpaService,
        LoggerInterface $logger,
        ResolveActor $resolveActor
    ) {
        $this->actorCodesApi = $actorCodesApi;
        $this->lpaService = $lpaService;
        $this->logger = $logger;
        $this->resolveActor = $resolveActor;
    }

    /**
     * @inheritDoc
     */
    public function validateCode(string $code, string $uid, string $dob): string
    {
        try {
            $actorCode = $this->actorCodesApi->validateCode($code, $uid, $dob);

            $actorUid = !empty($actorCode->getData()['actor']) ? $actorCode->getData()['actor'] : null;
            if ($actorUid !== null && $this->verifyAgainstLpa($uid, $actorUid, $dob)) {
                return $actorUid;
            }
        } catch (ActorCodeValidationException $acve) {
            throw $acve;
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
        try {
            $this->actorCodesApi->flagCodeAsUsed($code);
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to revoke actor code {code} when communicating with codes service',
                [
                    'code' => new HiddenString($code)
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
        $lpa = $this->lpaService->getByUid($uid);

        if ($lpa === null) {
            $this->logger->error(
                'Could not find LPA for SiriusUid {SiriusUid} when validating actor code',
                [
                    'SiriusUid' => $uid
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
                    'SiriusUid' => $uid,
                ]
            );
            throw new ActorCodeValidationException('Actor not in LPA');
        }

        if ($dob !== $actor['details']['dob']) {
            $this->logger->error(
                'Dob {dob} did not match {expected} when validating actor code',
                [
                    'dob' => $dob,
                    'expected' => $actor['details']['dob'],
                ]
            );
            throw new ActorCodeValidationException('Bad date of birth');
        }

        return true;
    }
}
