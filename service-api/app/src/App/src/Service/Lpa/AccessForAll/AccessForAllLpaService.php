<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\RequestLetterInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use DateInterval;
use DateTimeInterface;
use Psr\Log\LoggerInterface;

class AccessForAllLpaService
{
    private const CLEANSE_INTERVAL     = 'P6W';
    private const EXPIRY_INTERVAL      = 'P1Y';
    private const SEND_LETTER_INTERVAL = 'P2W';

    public function __construct(
        private ActorCodes $actorCodes,
        private RequestLetterInterface $requestLetterInterface,
        private UserLpaActorMapInterface $userLpaActorMap,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Checks if an actor already has an active activation key
     *
     * @throws ApiException
     */
    public function hasActivationCode(string $lpaId, string $actorId): ?DateTimeInterface
    {
        $response = $this->actorCodes->checkActorHasCode($lpaId, $actorId);
        if ($response->getData()->createdAt === null) {
            return null;
        }

        $this->logger->notice(
            'Activation key exists for actor {actorId} on LPA {lpaId}',
            [
                'actorId' => $lpaId,
                'lpaId'   => $actorId,
            ]
        );

        return $response->getData()->createdAt;
    }

    private function removeLpa(string $requestId): void
    {
        $this->userLpaActorMap->delete($requestId);

        $this->logger->info(
            'Removal request from UserLpaActorMap {id}',
            [
                'id' => $requestId,
            ]
        );
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * In order to preserve some semblance of atomicity to the required steps they're
     * carried out in a reverse order, with the one item we cannot revert carried out
     * last.
     *
     * @param string      $uid              Sirius uId for an LPA
     * @param string      $actorUid         uId of an actor on that LPA
     * @param string      $userId           The user ID of an actor in the ualpa database
     * @param string|null $existingRecordId If an existing LPA record has been stored this is the ID
     */
    public function requestAccessByLetter(
        string $uid,
        string $actorUid,
        string $userId,
        ?string $existingRecordId = null,
    ): void {
        $recordId = null;
        if ($existingRecordId === null) {
            $recordId = $this->userLpaActorMap->create(
                $userId,
                $uid,
                $actorUid,
                new DateInterval(self::EXPIRY_INTERVAL),
                new DateInterval(self::SEND_LETTER_INTERVAL)
            );
        }

        $uidInt      = (int)$uid;
        $actorUidInt = (int)$actorUid;

        $this->logger->info(
            'Requesting an access code letter for attorney {attorney} on LPA {lpa} in account {user_id}',
            [
                'user_id'  => $userId,
                'attorney' => $actorUidInt,
                'lpa'      => $uidInt,
            ]
        );

        try {
            $this->requestLetterInterface->requestLetter($uidInt, $actorUidInt, null);
        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter for attorney {attorney} on LPA {lpa} in account {user_id}',
                [
                    'user_id'  => $userId,
                    'attorney' => $actorUidInt,
                    'lpa'      => $uidInt,
                ]
            );
            if ($recordId !== null) {
                $this->removeLpa($recordId);
            }
            throw $apiException;
        }

        /**
         * This is the exception to the method documentation. We cannot easily roll this alteration
         * back so we'll do it last. The potential is that this operation could fail even though
         * the API request worked. That being the case the users record will not have
         * an up to date ActivateBy column. This isn't the end of the world.
         */
        if ($existingRecordId !== null) {
            $this->userLpaActorMap->updateRecord(
                $existingRecordId,
                new DateInterval(self::EXPIRY_INTERVAL),
                new DateInterval(self::SEND_LETTER_INTERVAL),
                $actorUid
            );
        }
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * @param string  $uid Sirius uId for an LPA
     * @param string  $userId
     * @param string  $additionalInfo
     * @param ?int    $actorId
     * @param ?string $existingRecordId
     */
    public function requestAccessAndCleanseByLetter(
        string $uid,
        string $userId,
        string $additionalInfo,
        ?int $actorId = null,
        ?string $existingRecordId = null,
    ): void {
        $recordId = null;
        if ($existingRecordId === null) {
            $recordId = $this->userLpaActorMap->create(
                $userId,
                $uid,
                $actorId ? (string)$actorId : null,
                new DateInterval(self::EXPIRY_INTERVAL),
                new DateInterval(self::CLEANSE_INTERVAL)
            );
        }

        $uidInt = (int)$uid;
        $this->logger->info(
            'Requesting cleanse and an access code letter on LPA {lpa} in account {user_id}',
            [
                'user_id' => $userId,
                'lpa'     => $uidInt,
            ]
        );

        try {
            $this->requestLetterInterface->requestLetter($uidInt, null, $additionalInfo);
        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter and cleanse for LPA {lpa} in account {user_id}',
                [
                    'user_id' => $userId,
                    'lpa'     => $uidInt,
                ]
            );

            if ($recordId !== null) {
                $this->removeLpa($recordId);
            }

            throw $apiException;
        }

        /**
         * This is the exception to the method documentation. We cannot easily roll this alteration
         * back so we'll do it last. The potential is that this operation could fail even though
         * the API request worked. That being the case the users record will not have
         * an up to date ActivateBy column. This isn't the end of the world.
         */
        if ($existingRecordId !== null) {
            $this->userLpaActorMap->updateRecord(
                $existingRecordId,
                new DateInterval(self::EXPIRY_INTERVAL),
                new DateInterval(self::CLEANSE_INTERVAL),
                $actorId ? (string)$actorId : null
            );
        }
    }
}
