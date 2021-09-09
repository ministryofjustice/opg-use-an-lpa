<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\DataObject\ExpiringUserLpaActorMapData;
use App\DataAccess\DataObject\UserLpaActorMapData;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class OlderLpaService
{
    private FeatureEnabled $featureEnabled;
    private LpaAlreadyAdded $lpaAlreadyAdded;
    private LpaService $lpaService;
    private LpasInterface $lpaRepository;
    private LoggerInterface $logger;
    private ActorCodes $actorCodes;
    private GetAttorneyStatus $getAttorneyStatus;
    private ValidateOlderLpaRequirements $validateLpaRequirements;
    private UserLpaActorMapInterface $userLpaActorMap;
    private ResolveActor $resolveActor;

    public function __construct(
        LpaAlreadyAdded $lpaAlreadyAdded,
        LpaService $lpaService,
        LpasInterface $lpaRepository,
        LoggerInterface $logger,
        ActorCodes $actorCodes,
        GetAttorneyStatus $getAttorneyStatus,
        ValidateOlderLpaRequirements $validateLpaRequirements,
        UserLpaActorMapInterface $userLpaActorMap,
        FeatureEnabled $featureEnabled,
        ResolveActor $resolveActor
    ) {
        $this->lpaAlreadyAdded = $lpaAlreadyAdded;
        $this->lpaService = $lpaService;
        $this->lpaRepository = $lpaRepository;
        $this->logger = $logger;
        $this->actorCodes = $actorCodes;
        $this->getAttorneyStatus = $getAttorneyStatus;
        $this->validateLpaRequirements = $validateLpaRequirements;
        $this->userLpaActorMap = $userLpaActorMap;
        $this->featureEnabled = $featureEnabled;
        $this->resolveActor = $resolveActor;
    }

    public function validateOlderLpaRequest(string $userId, array $requestData): array
    {
        // Check LPA with user provided reference number
        $lpaMatchResponse = $this->checkLPAMatchAndGetActorDetails($userId, $requestData);

        // Checks if the actor already has an active activation key. If forced ignore
        if (!$requestData['force_activation_key']) {
            $hasActivationCode = $this->hasActivationCode(
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

        return $lpaMatchResponse;
    }

    /**
     * Formats data attributes for comparison in the older lpa journey
     *
     * @param array $data
     *
     * @return array|null
     * @throws Exception
     */
    public function cleanseUserData(array $data): ?array
    {
        $data['first_names'] = strtolower(explode(' ', $data['first_names'])[0]);
        $data['last_name'] = strtolower($data['last_name']);
        $data['postcode'] = strtolower(str_replace(' ', '', $data['postcode']));

        return $data;
    }

    /**
     * Compares LPA data retrieved from Sirius to the data provided by
     * the user to check if it matches
     *
     * @param array $actor           The actor details being compared against
     * @param array $userDataToMatch The user provided data we're searching for a match against
     *
     * @return ?array A data structure containing the matched actor id and lpa id
     */

    public function checkDataMatch(array $actor, array $userDataToMatch): ?array
    {
        // Check if the actor has more than one address
        if (count($actor['addresses']) > 1) {
            $this->logger->notice(
                'Data match failed for actor {id} as more than 1 address found',
                [
                    'id' => $actor['uId'],
                ]
            );
            return null;
        }

        $actorData = $this->cleanseUserData(
            [
                'first_names'   => $actor['firstname'],
                'last_name'     => $actor['surname'],
                'postcode'      => $actor['addresses'][0]['postcode'],
            ]
        );

        $this->logger->debug(
            'Doing actor data comparison against actor with id {actor_id}',
            [
                'actor_id'      => $actor['uId'],
                'to_match'      => $userDataToMatch,
                'actor_data'    => array_merge($actorData, ['dob' => $actor['dob']]),
            ]
        );

        if (
            $actor['dob'] === $userDataToMatch['dob'] &&
            $actorData['first_names'] === $userDataToMatch['first_names'] &&
            $actorData['last_name'] === $userDataToMatch['last_name'] &&
            $actorData['postcode'] === $userDataToMatch['postcode']
        ) {
            $this->logger->info(
                'User entered data matches for LPA {uId}',
                [
                    'uId' => $userDataToMatch['reference_number'],
                ]
            );
            return $actor;
        }
        return null;
    }

    public function compareAndLookupActiveActorInLpa(array $lpa, array $userDataToMatch): ?array
    {
        $actorId = null;
        $lpaId = $lpa['uId'];

        if (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if (($this->getAttorneyStatus)($attorney) === GetAttorneyStatus::ACTIVE_ATTORNEY) {
                    $actorMatchResponse = $this->checkDataMatch($attorney, $userDataToMatch);
                    // if not null, an actor match has been found
                    if (!is_null($actorMatchResponse)) {
                        $actorId = $actorMatchResponse['uId'];
                        break;
                    }
                } else {
                    $this->logger->info(
                        'Actor {id} status is not active for LPA {uId}',
                        [
                            'id' => $attorney['uId'],
                            'uId' => $lpaId,
                        ]
                    );
                }
            }
        }

        // If not an attorney, check if they're the donor.
        if (is_null($actorId) && isset($lpa['donor']) && is_array($lpa['donor'])) {
            $donorMatchResponse = $this->checkDataMatch($lpa['donor'], $userDataToMatch);

            if (!is_null($donorMatchResponse)) {
                $actorId = $donorMatchResponse['uId'];
            }
        }

        if (is_null($actorId)) {
            return null;
        }

        return [
            'actor-id' => $actorId,
            'lpa-id' => $lpaId
        ];
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

    /**
     * Checks if the LPA has already been added to the users account
     *
     * @param string $userId
     * @param string $lpaId
     */
    public function checkIfLpaAlreadyAdded(string $userId, string $lpaId): void
    {
        if (null !== $lpaAddedData = ($this->lpaAlreadyAdded)($userId, $lpaId)) {
            $this->logger->notice(
                'User {id} attempted to request a key for the LPA {uId} which already exists in their account',
                [
                    'id' => $userId,
                    'uId' => $lpaId,
                ]
            );
            throw new BadRequestException('LPA already added', $lpaAddedData);
        }
    }

    /**
     * @param string $lpaId
     *
     * @return Lpa
     */
    public function getLpaByUid(string $lpaId): Lpa
    {
        $lpa = $this->lpaService->getByUid($lpaId);

        if (is_null($lpa)) {
            $this->logger->info(
                'The LPA {uId} entered by user is not found in Sirius',
                [
                    'uId' => $lpaId,
                ]
            );
            throw new NotFoundException('LPA not found');
        }

        return $lpa;
    }

    /**
     * Compares user provided data with lpa data to return matched actor details
     *
     * @param array $lpa
     * @param array $dataToMatch
     *
     * @return array
     * @throws Exception
     */
    public function lookupActorInLpa(array $lpa, array $dataToMatch): array
    {
        $actorMatch = $this->compareAndLookupActiveActorInLpa($lpa, $dataToMatch);

        if (is_null($actorMatch)) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $dataToMatch['reference_number'],
                ]
            );
            throw new BadRequestException('LPA details do not match');
        }

        $actor = ($this->resolveActor)($lpa, $actorMatch['actor-id']);

        if ($actor['type'] !== 'donor') {
            $actorMatch['attorney'] = [
                'uId'           => $actor['details']['uId'],
                'firstname'     => $actor['details']['firstname'],
                'middlenames'   => $actor['details']['middlenames'],
                'surname'       => $actor['details']['surname'],
            ];
        }

        $actorMatch['caseSubtype'] = $lpa['caseSubtype'];
        $actorMatch['donor'] = [
            'uId'           => $lpa['donor']['uId'],
            'firstname'     => $lpa['donor']['firstname'],
            'middlenames'   => $lpa['donor']['middlenames'],
            'surname'       => $lpa['donor']['surname'],
        ];

        return $actorMatch;
    }

    /**
     * Gets LPA by Uid, checks registration date and identifies the actor
     *
     * @param string $userId
     * @param array  $dataToMatch
     *
     * @return array
     * @throws Exception
     */
    public function checkLPAMatchAndGetActorDetails(string $userId, array $dataToMatch): array
    {
        $this->checkIfLpaAlreadyAdded($userId, (string) $dataToMatch['reference_number']);

        $dataToMatch = $this->cleanseUserData($dataToMatch);

        $lpaMatch = $this->getLpaByUid((string) $dataToMatch['reference_number']);

        ($this->validateLpaRequirements)($lpaMatch->getData());

        return $this->lookupActorInLpa($lpaMatch->getData(), $dataToMatch);
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
     * @param string $userId
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
                $this->userLpaActorMap->create($id, $userId, $lpaId, $actorId, 'P1Y');
                return $id;
            } catch (KeyCollisionException $e) {
                // Allows the loop to repeat with a new ID.
            }
        } while (true);
    }
}
