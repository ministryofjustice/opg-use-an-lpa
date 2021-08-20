<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DataObject\ExpiringUserLpaActorMapData;
use App\DataAccess\DataObject\UserLpaActorMapData;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\LpasInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class OlderLpaService
{
    private ActivationKeyAlreadyRequested $activationKeyAlreadyRequested;
    private ActorCodes $actorCodes;
    private AddOlderLpa $addOlderLpa;
    private LoggerInterface $logger;
    private LpasInterface $lpaRepository;

    public function __construct(
        ActorCodes $actorCodes,
        ActivationKeyAlreadyRequested $activationKeyAlreadyRequested,
        AddOlderLpa $addOlderLpa,
        LpasInterface $lpaRepository,
        LoggerInterface $logger
    ) {
        $this->actorCodes = $actorCodes;
        $this->activationKeyAlreadyRequested = $activationKeyAlreadyRequested;
        $this->addOlderLpa = $addOlderLpa;
        $this->lpaRepository = $lpaRepository;
        $this->logger = $logger;
    }

    /**
     * Checks if an actor already has an active activation key
     *
     * @param string $lpaId
     * @param string $actorId
     *
     * @return DateTime|null
     */
    public function hasActivationCode(string $lpaId, string $actorId): ?DateTime
    {
        $response = $this->actorCodes->checkActorHasCode($lpaId, $actorId);
        if (is_null($response->getData()['Created'])) {
            return null;
        }

        $createdDate = DateTime::createFromFormat('Y-m-d', $response->getData()['Created']);

        $this->logger->notice(
            'Activation key exists for actor {actorId} on LPA {lpaId}',
            [
                'actorId'   => $lpaId,
                'lpaId'     => $actorId,
            ]
        );

        return $createdDate;
    }

    //UML-1578
    /**
     * Checks if the activation key already requested by actor for lpa
     *
     * @param string $userId
     * @param string $lpaId
     */
    public function checkIfActivationKeyAlreadyRequested(string $userId, string $lpaId): void
    {
        if (null !== $lpaAddedData = ($this->activationKeyAlreadyRequested)($userId, $lpaId)) {
            $this->logger->notice(
                'An activation key already requested for the LPA {uId} for user {id}',
                [
                    'id' => $userId,
                    'uId' => $lpaId,
                ]
            );
            throw new BadRequestException('LPA has an activation key already', $lpaAddedData);
        }
    }

    /**
     * Gets LPA by Uid, checks registration date and identifies the actor
     *
     * @param string $userId
     * @param array  $dataToMatch
     *
     * @return array
     * @throws Exception
     *
     * @deprecated
     */
    public function checkLPAMatchAndGetActorDetails(string $userId, array $dataToMatch): array
    {
        return ($this->addOlderLpa)($userId, $dataToMatch);
    }

    private function removeLpa(string $requestId)
    {
        $this->userLpaActorMap->delete($requestId);

        $this->logger->notice(
            'Removing request from UserLPAActorMap {id}',
            [
                'id' => $requestId
            ]
        );
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * @param string $uid      Sirius uId for an LPA
     * @param string $actorUid uId of an actor on that LPA
     */
    public function requestAccessByLetter(string $uid, string $actorUid, string $userId): void
    {
        $requestId = null;
        if (($this->featureEnabled)('save_older_lpa_requests')) {
            $requestId = $this->storeLPARequest(
                $uid,
                $userId,
                $actorUid
            );
        }

        $uidInt = (int)$uid;
        $actorUidInt = (int)$actorUid;

        $this->logger->info(
            'Requesting an access code letter for attorney {attorney} on LPA {lpa}',
            [
                'attorney' => $actorUidInt,
                'lpa' => $uidInt,
            ]
        );

        try {
            $this->lpaRepository->requestLetter($uidInt, $actorUidInt);
        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter for attorney {attorney} on LPA {lpa}',
                [
                    'attorney' => $actorUidInt,
                    'lpa' => $uidInt,
                ]
            );
            if ($requestId !== null) {
                $this->removeLpa($requestId);
            }
            throw $apiException;
        }
    }

    /**
     * Stores an Entry in UserLPAActorMap for the request of an older lpa
     * @param string $lpaId
     * @param string $userId
     * @param string $actorId
     *
     * @return string       The lpaActorToken
     * @throws Exception    throws an ApiException
     */
    public function storeLPARequest(string $lpaId, string $userId, string $actorId): string
    {
        do {
            $id = Uuid::uuid4()->toString();
            try {
                $this->lpaRepository->create($id, $userId, $lpaId, $actorId, 'P1Y');
                return $id;
            } catch (KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }
        } while (true);
    }
}
