<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Features\FeatureEnabled;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;

class OlderLpaService
{
    private ActorCodes $actorCodes;
    private LoggerInterface $logger;
    private LpasInterface $lpaRepository;
    private FeatureEnabled $featureEnabled;
    private UserLpaActorMapInterface $userLpaActorMap;

    public function __construct(
        ActorCodes $actorCodes,
        LpasInterface $lpaRepository,
        UserLpaActorMapInterface $userLpaActorMap,
        FeatureEnabled $featureEnabled,
        LoggerInterface $logger
    ) {
        $this->actorCodes = $actorCodes;
        $this->lpaRepository = $lpaRepository;
        $this->userLpaActorMap = $userLpaActorMap;
        $this->featureEnabled = $featureEnabled;
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
                'actorId' => $lpaId,
                'lpaId' => $actorId,
            ]
        );

        return $createdDate;
    }

    private function removeLpa(string $requestId)
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
     * @param string $uid      Sirius uId for an LPA
     * @param string $actorUid uId of an actor on that LPA
     * @param string $userId
     *
     * @throws Exception
     */
    public function requestAccessByLetter(string $uid, string $actorUid, string $userId): void
    {
        $recordId = null;
        if (($this->featureEnabled)('save_older_lpa_requests')) {
            $recordId = $this->userLpaActorMap->create($userId, $uid, $actorUid, 'P1Y');
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
            $response = $this->lpaRepository->requestLetter($uidInt, $actorUidInt, null);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $data = json_decode((string)$response->getBody(), true);

                if ($data['queuedForCleansing']) {
                    throw new ApiException('Unexpected response received from Api Gateway when cleanse requested for Lpa');
                }
            } elseif ($response->getStatusCode() !== StatusCodeInterface::STATUS_NO_CONTENT) {
                throw new ApiException('Unexpected response received from Api Gateway when cleanse requested for Lpa');
            }

        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter for attorney {attorney} on LPA {lpa}',
                [
                    'attorney' => $actorUidInt,
                    'lpa' => $uidInt,
                ]
            );
            if ($recordId !== null) {
                $this->removeLpa($recordId);
            }
            throw $apiException;
        }
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * @param string $uid Sirius uId for an LPA
     * @param string $userId
     * @param string $actorId
     * @param string $additionalInfo
     *
     * @throws Exception
     */
    public function requestAccessAndCleanseByLetter(
        string $uid,
        string $userId,
        string $additionalInfo
    ): void {
        $uidInt = (int)$uid;
        $this->logger->info(
            'Requesting cleanse and an access code letter on LPA {lpa}',
            [
                'lpa' => $uidInt,
            ]
        );

        try {
            $response = $this->lpaRepository->requestLetter($uidInt, null, $additionalInfo);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $data = json_decode((string)$response->getBody(), true);

                if (!$data['queuedForCleansing']) {
                    throw new ApiException(
                        'Unexpected response received from Api Gateway when cleanse requested for Lpa'
                    );
                }
            } else {
                throw new ApiException('Unexpected response received from Api Gateway when cleanse requested for Lpa');
            }
            //TODO: needs to save the request once merged with changes from 1643
        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter and cleanse for LPA {lpa}',
                [
                    'lpa' => $uidInt,
                ]
            );
            throw $apiException;
        }
    }
}
