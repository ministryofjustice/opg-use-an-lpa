<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\LpasInterface;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Log\EventCodes;
use DateTime;
use Psr\Log\LoggerInterface;

class OlderLpaService
{
    private LpaService $lpaService;
    private LpasInterface $lpaRepository;
    private LoggerInterface $logger;
    private ActorCodes $actorCodes;
    private GetAttorneyStatus $getAttorneyStatus;
    private ValidateOlderLpaRequirements $validateLpaRequirements;

    public function __construct(
        LpaService $lpaService,
        LpasInterface $lpaRepository,
        LoggerInterface $logger,
        ActorCodes $actorCodes,
        GetAttorneyStatus $getAttorneyStatus,
        ValidateOlderLpaRequirements $validateLpaRequirements
    ) {
        $this->lpaService = $lpaService;
        $this->lpaRepository = $lpaRepository;
        $this->logger = $logger;
        $this->actorCodes = $actorCodes;
        $this->getAttorneyStatus = $getAttorneyStatus;
        $this->validateLpaRequirements = $validateLpaRequirements;
    }

    /**
     * Formats data attributes for comparison in the older lpa journey
     *
     * @param array $data
     *
     * @return array|null
     * @throws \Exception
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
            'lpa-id' => $lpaId,
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
     * Gets LPA by Uid, checks registration date and identifies the actor
     *
     * @param array $dataToMatch
     *
     * @return array
     * @throws \Exception
     */
    public function checkLPAMatchAndGetActorDetails(array $dataToMatch): array
    {
        // Cleanse user provided data
        $dataToMatch = $this->cleanseUserData($dataToMatch);

        //Get LPA by reference number
        $lpaMatchResponse = $this->lpaService->getByUid((string)$dataToMatch['reference_number']);

        if (is_null($lpaMatchResponse)) {
            $this->logger->info(
                'The LPA {uId} entered by user is not found in Sirius',
                [
                    'uId' => $dataToMatch['reference_number'],
                ]
            );
            throw new NotFoundException('LPA not found');
        }

        if (!($this->validateLpaRequirements)($lpaMatchResponse->getData())) {
            throw new BadRequestException('LPA not eligible due to registration date');
        }

        //Check and compare user provided data with lpa data and return actor details

        $actorMatch = $this->compareAndLookupActiveActorInLpa($lpaMatchResponse->getData(), $dataToMatch);

        if (is_null($actorMatch)) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $dataToMatch['reference_number'],
                ]
            );
            throw new BadRequestException('LPA details do not match');
        }

        $actorMatch['donor_name'] = [
            $lpaMatchResponse->getData()['donor']['firstname'],
            $lpaMatchResponse->getData()['donor']['middlenames'],
            $lpaMatchResponse->getData()['donor']['surname'],
        ];
        $actorMatch['lpa_type'] = $lpaMatchResponse->getData()['caseSubtype'];

        return $actorMatch;
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * @param string $uid      Sirius uId for an LPA
     * @param string $actorUid uId of an actor on that LPA
     */
    public function requestAccessByLetter(string $uid, string $actorUid): void
    {
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
                    'attorney'  => $actorUidInt,
                    'lpa'       => $uidInt,
                ]
            );

            throw $apiException;
        }
    }
}
