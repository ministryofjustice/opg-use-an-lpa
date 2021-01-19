<?php

namespace App\Service\Lpa;

use App\DataAccess\Repository\{LpasInterface,
    Response\Lpa,
    Response\LpaInterface,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use App\Exception\{ApiException, BadRequestException, GoneException, NotFoundException, RuntimeException};
use DateTime;
use Psr\Log\LoggerInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    private const ACTIVE_ATTORNEY = 0;
    private const GHOST_ATTORNEY = 1;
    private const INACTIVE_ATTORNEY = 2;

    private ViewerCodesInterface $viewerCodesRepository;
    private ViewerCodeActivityInterface $viewerCodeActivityRepository;
    private LpasInterface $lpaRepository;
    private UserLpaActorMapInterface $userLpaActorMapRepository;
    private LoggerInterface $logger;

    public function __construct(
        ViewerCodesInterface $viewerCodesRepository,
        ViewerCodeActivityInterface $viewerCodeActivityRepository,
        LpasInterface $lpaRepository,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        LoggerInterface $logger
    ) {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->lpaRepository = $lpaRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->logger = $logger;
    }

    /**
     * Get an LPA using the ID value
     *
     * @param string $uid Sirius uId of LPA to fetch
     * @return ?LpaInterface A processed LPA data transfer object
     */
    public function getByUid(string $uid): ?LpaInterface
    {
        $lpa = $this->lpaRepository->get($uid);
        if ($lpa === null) {
            return null;
        }

        $lpaData = $lpa->getData();

        if ($lpaData['attorneys'] !== null) {
            $lpaData['original_attorneys'] = $lpaData['attorneys'];
            $lpaData['attorneys'] = array_values(array_filter($lpaData['attorneys'], function ($attorney) {
                return $this->attorneyStatus($attorney) === self::ACTIVE_ATTORNEY;
            }));
        }

        return new Lpa($lpaData, $lpa->getLookupTime());
    }

    /**
     * Given a user token and a user id (who should own the token), return the actor and LPA details
     *
     * @param string $token UserLpaActorToken that map an LPA to a user account
     * @param string $userId The user account ID that must correlate to the $token
     * @return ?array A structure that contains processed LPA data and metadata
     */
    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        $lpa = $this->getByUid($map['SiriusUid']);

        if (is_null($lpa)) {
            return null;
        }

        $lpaData = $lpa->getData();
        $actor = $this->lookupActiveActorInLpa($lpaData, $map['ActorId']);
        unset($lpaData['original_attorneys']);

        // If an active attorney is not found then we should not return an lpa
        if (is_null($actor)) {
            return null;
        }

        return [
            'user-lpa-actor-token' => $map['Id'],
            'date' => $lpa->getLookupTime()->format('c'),
            'actor' => $actor,
            'lpa' => $lpaData,
        ];
    }

    /**
     * Return all LPAs for the given user_id
     *
     * @param string $userId User account ID to fetch LPAs for
     * @return array An array of LPA data structures containing processed LPA data and metadata
     */
    public function getAllForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getUsersLpas($userId);

        $lpaUids = array_column($lpaActorMaps, 'SiriusUid');

        if (empty($lpaUids)) {
            return [];
        }

        // Return all the LPA details based on the Sirius Ids.
        $lpas = $this->lpaRepository->lookup($lpaUids);

        $result = [];

        // Map the results...
        foreach ($lpaActorMaps as $item) {
            $lpa = $lpas[$item['SiriusUid']];
            $lpaData = $lpa->getData();
            $actor = $this->lookupActiveActorInLpa($lpaData, $item['ActorId']);
            $added = $item['Added']->format('Y-m-d H:i:s');
            unset($lpaData['original_attorneys']);

            $result[$item['Id']] = [
                'user-lpa-actor-token' => $item['Id'],
                'date' => $lpa->getLookupTime()->format('c'),
                'actor' => $actor,
                'lpa' => $lpaData,
                'added' => $added
            ];
        }

        return $result;
    }

    /**
     * Get an LPA using the share code.
     *
     * @param string $viewerCode A code that directly maps to an LPA
     * @param string $donorSurname The surname of the donor that must correlate to the $viewerCode
     * @param ?string $organisation An organisation name that will be recorded as used against the $viewerCode
     * @return ?array A structure that contains processed LPA data and metadata
     */
    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): ?array
    {
        $viewerCodeData = $this->viewerCodesRepository->get($viewerCode);

        if (is_null($viewerCodeData)) {
            $this->logger->info('The code entered by user to view LPA is not found in the database.');
            return null;
        }

        $lpa = $this->getByUid($viewerCodeData['SiriusUid']);

        //---

        // Check donor's surname

        if (
            is_null($lpa)
            || !isset($lpa->getData()['donor']['surname'])
            || strtolower($lpa->getData()['donor']['surname']) !== strtolower($donorSurname)
        ) {
            return null;
        }

        //---
        // Whilst the checks in this section could be done before we lookup the LPA, they are done
        // at this point as we only want to acknowledge if a code has expired iff donor surname matched.

        if (!isset($viewerCodeData['Expires']) || !($viewerCodeData['Expires'] instanceof DateTime)) {
            $this->logger->info(
                'The code {code} entered by user to view LPA does not have an expiry date set.',
                ['code' => $viewerCode]
            );
            throw new RuntimeException("'Expires' field missing or invalid.");
        }

        if (new DateTime() > $viewerCodeData['Expires']) {
            $this->logger->info('The code {code} entered by user to view LPA has expired.', ['code' => $viewerCode]);
            throw new GoneException('Share code expired');
        }

        if (isset($viewerCodeData['Cancelled'])) {
            $this->logger->info('The code {code} entered by user is cancelled.', ['code' => $viewerCode]);
            throw new GoneException('Share code cancelled');
        }

        if (!is_null($organisation)) {
            // Record the lookup in the activity table
            // We only do this if the organisation is provided
            $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity(
                $viewerCodeData['ViewerCode'],
                $organisation
            );
        }

        $lpaData = $lpa->getData();
        unset($lpaData['original_attorneys']);

        $lpaData = [
            'date'         => $lpa->getLookupTime()->format('c'),
            'expires'      => $viewerCodeData['Expires']->format('c'),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa'          => $lpaData,
        ];

        if (isset($viewerCodeData['Cancelled'])) {
            $lpaData['cancelled'] = $viewerCodeData['Cancelled']->format('c');
        }

        return $lpaData;
    }

    /**
     * Provides the capability to request a letter be sent to the registered
     * address of the specified actor with a new one-time-use registration code.
     * This will allow them to add the LPA to their UaLPA account.
     *
     * @param string $uid Sirius uId for an LPA
     * @param string $actorUid uId of an actor on that LPA
     */
    public function requestAccessByLetter(string $uid, string $actorUid): void
    {
        $uidInt = (int) $uid;
        $actorUidInt = (int) $actorUid;

        $this->logger->info(
            'Requesting new access code letter for attorney {attorney} on LPA {lpa}',
            [
                'attorney' => $actorUidInt,
                'lpa' => $uidInt
            ]
        );

        try {
            $this->lpaRepository->requestLetter($uidInt, $actorUidInt);
        } catch (ApiException $apiException) {
            $this->logger->notice(
                'Failed to request access code letter for attorney {attorney} on LPA {lpa}',
                [
                    'attorney' => $actorUidInt,
                    'lpa' => $uidInt
                ]
            );

            throw $apiException;
        }
    }

    /**
     * Given an LPA and an Actor ID, this returns the actor's details, and what type of actor they are.
     *
     * This function is used by code that expects to be able to check for Sirius uId's (code validation) and
     * database id's (UserActorLpa lookup) so it checks both fields for the id. This is not ideal but we now have
     * many thousands of live data rows with database id's at this point.
     *
     * TODO: Confirm if we need to look in Trust Corporations, or if an active Trust Corporation would appear
     *       in `attorneys`.
     *
     * @param array $lpa An LPA data structure
     * @param string $actorId The actors Database ID or Sirius UId to search for within the $lpa data structure
     * @return ?array A data structure containing details of the discovered actor
     */
    public function lookupActiveActorInLpa(array $lpa, string $actorId): ?array
    {
        $actor = null;
        $actorType = null;

        // Determine if the actor is a primary attorney
        if (isset($lpa['original_attorneys']) && is_array($lpa['original_attorneys'])) {
            foreach ($lpa['original_attorneys'] as $attorney) {
                if ((string)$attorney['id'] === $actorId || $attorney['uId'] === $actorId) {
                    switch ($this->attorneyStatus($attorney)) {
                        case self::ACTIVE_ATTORNEY:
                            $actor = $attorney;
                            $actorType = 'primary-attorney';
                            break;

                        case self::GHOST_ATTORNEY:
                            $this->logger->info('Looked up attorney {id} but is a ghost', ['id' => $attorney['id']]);
                            break;

                        case self::INACTIVE_ATTORNEY:
                            $this->logger->info('Looked up attorney {id} but is inactive', ['id' => $attorney['id']]);
                            break;
                    }
                }
            }
        } elseif (isset($lpa['attorneys']) && is_array($lpa['attorneys'])) {
            foreach ($lpa['attorneys'] as $attorney) {
                if ((string)$attorney['id'] === $actorId || $attorney['uId'] === $actorId) {
                    $actor = $attorney;
                    $actorType = 'primary-attorney';
                }
            }
        }

        // If not an attorney, check if they're the donor.
        if (is_null($actor) && $this->isDonor($lpa, $actorId)) {
            $actor = $lpa['donor'];
            $actorType = 'donor';
        }

        if (is_null($actor)) {
            return null;
        }

        return [
            'type' => $actorType,
            'details' => $actor,
        ];
    }

    /**
     * Deletes an LPA from a users account
     *
     * @param string $userId The user account ID that must correlate to the $token
     * @param string $token UserLpaActorToken that map an LPA to a user account
     * @return ?array A structure that contains processed LPA data and metadata
     */
    public function removeLpaFromUserLpaActorMap(string $userId, string $token): ?array
    {
        $userActorLpa = $this->userLpaActorMapRepository->get($token);

        if (is_null($userActorLpa)) {
            $this->logger->notice(
                'User actor lpa record  not found for actor token {Id}',
                ['Id' => $token]
            );
            throw new NotFoundException('User actor lpa record  not found for actor token - ' . $token);
        }

        // Ensure the passed userId matches the passed token
        if ($userId !== $userActorLpa['UserId']) {
            $this->logger->notice(
                'User Id {userId} passed does not match the user in userActorLpaMap for actor token {actorToken}',
                ['userId' => $userId, 'actorToken' => $token]
            );
            throw new NotFoundException(
                'User Id passed does not match the user in userActorLpaMap for token - ' . $token
            );
        }

        //Get list of viewer codes to be updated
        $viewerCodes = $this->getListOfViewerCodesToBeUpdated($userActorLpa);

        //Update query to remove actor association in viewer code table
        if (!empty($viewerCodes)) {
            foreach ($viewerCodes as $key => $viewerCode) {
                $this->viewerCodesRepository->removeActorAssociation($viewerCode);
            }
        }

        return $this->userLpaActorMapRepository->delete($token);
    }

    private function isDonor(array $lpa, string $actorId): bool
    {
        if (!isset($lpa['donor']) || !is_array($lpa['donor'])) {
            return false;
        }

        // TODO: When new Sirius API has been released this property will always
        //       be present, then this `if` block can be removed.
        if (!isset($lpa['donor']['linked'])) {
            return ((string)$lpa['donor']['id'] === $actorId || $lpa['donor']['uId'] === $actorId);
        }

        foreach ($lpa['donor']['linked'] as $key => $value) {
            if ((string)$value['id'] === $actorId || $value['uId'] === $actorId) {
                return true;
            }
        }

        return false;
    }

    private function attorneyStatus(array $attorney): int
    {
        if (empty($attorney['firstname']) && empty($attorney['surname'])) {
            return self::GHOST_ATTORNEY;
        }

        if (!$attorney['systemStatus']) {
            return self::INACTIVE_ATTORNEY;
        }

        return self::ACTIVE_ATTORNEY;
    }

    private function getListOfViewerCodesToBeUpdated(array $userActorLpa): ?array
    {
        $siriusUid = $userActorLpa['SiriusUid'];

        //Lookup records in ViewerCodes table using siriusUid
        $viewerCodesData = $this->viewerCodesRepository->getCodesByLpaId($siriusUid);
        foreach ($viewerCodesData as $key => $viewerCodeRecord) {
            if (
                isset($viewerCodeRecord['UserLpaActor'])
                && ($viewerCodeRecord['UserLpaActor'] === $userActorLpa['Id'])
            ) {
                $viewerCodes[] = $viewerCodeRecord['ViewerCode'];
            }
        }
        return $viewerCodes;
    }


    /**
     * Given an LPA and an Actor ID, this returns the actor's details.
     *
     * This function is used by code that checks if a matching Sirius uId's is found and compares lpa data
     * against user provided data to find active actor details.
     *
     * TODO: Confirm if we need to look in Trust Corporations, or if an active Trust Corporation would appear
     *       in `attorneys`.
     *
     * @param array $lpaRetrieved An LPA data structure retrieved
     * @param array $userDataToMatch The actors Database ID or Sirius UId to search for within the $lpa data structure
     * @return ?array A data structure containing details of the active actor and lpa id
     */

    public function checkDataMatch(array $actorDetail, array $userDataToMatch): ?array
    {
        //cleanse lpa actor data
        $actorDOB = (new DateTime($actorDetail['dob']))->format('d/m/Y');
        $actorDOB = \DateTime::createFromFormat('d/m/Y', $actorDOB)->format('d/m/Y');;
        $actorFirstname = strtolower(explode(' ', trim($actorDetail['firstname']))[0]);
        $actorSurname = strtolower(trim($actorDetail['surname']));
        $actorPostcode = strtolower(str_replace(' ', '', $actorDetail['addresses'][0]['postcode']));

        if (
            ($userDataToMatch['dob'])->format('d/m/Y') == $actorDOB and
            $actorFirstname === $userDataToMatch['first_names'] and
            $actorSurname === $userDataToMatch['last_name'] and
            $actorPostcode === $userDataToMatch['postcode']
        ) {
            $this->logger->info(
                'User entered data matches for LPA {uId}',
                [
                    'uId' => $userDataToMatch['reference_number']
                ]
            );
            return $actorDetail;
        }
        return null;
    }

    public function compareAndLookupActiveActorInLpa($lpaRetrieved, array $userDataToMatch): ?array
    {
        $actorId =  null;
        $lpaUid =  null;

        if (isset($lpaRetrieved['attorneys']) and is_array($lpaRetrieved['attorneys'])) {
            foreach ($lpaRetrieved['attorneys'] as $attorney) {

                if ($this->attorneyStatus($attorney) === self::ACTIVE_ATTORNEY) {
                    $actorMatchResponse = $this->checkDataMatch($attorney, $userDataToMatch);

                    $actorId = $actorMatchResponse['uId'];
                    $lpaUid = $userDataToMatch['reference_number'];
                }
                $this->logger->info(
                    'Actor {id} status appears INACTIVE for LPA {uId}',
                    [
                        'uId' => $userDataToMatch['reference_number'],
                        'id'  => $actorId
                    ]
                );

            }
        }
        // If not an attorney, check if they're the donor.
        if (isset($lpaRetrieved['donor']) and is_array($lpaRetrieved['donor'])) {

            $actorMatchResponse = $this->checkDataMatch($lpaRetrieved['donor'], $userDataToMatch);

            $actorId = $actorMatchResponse['uId'];
            $lpaUid = $userDataToMatch['reference_number'];
        }
        $this->logger->info(
            'Actor details {id} received for LPA {uId}',
            [
                'uId' => $userDataToMatch['reference_number'],
                'id'  => $actorId
            ]
        );

        return [
            'actor-id' => $actorId,
            'lpa-id'   => $lpaUid
        ];
    }

    /**
     * @param array $dataToMatch
     * @return array
     *
     */
    public function cleanseUserData(array $dataToMatch): ?array
    {
        $dataToMatch['reference_number'] = trim($dataToMatch['reference_number']);
        $dataToMatch['dob'] = \DateTime::createFromFormat('d/m/Y', $dataToMatch['dob']);
        $dataToMatch['first_names'] = strtolower(explode(' ', trim($dataToMatch['first_names']))[0]);
        $dataToMatch['last_name'] = strtolower(trim($dataToMatch['last_name']));
        $dataToMatch['postcode'] = strtolower(str_replace(' ', '', $dataToMatch['postcode']));
        return $dataToMatch;
    }

    /**
     * @param string $lpaReferenceNumber
     * @param string $lpaStatus
     * @param string $lpaRegistrationDate
     * @return bool|null
     *
     */
    public function checkLpaRegistrationDetails(array $lpaRetrieved): ?bool
    {
        $expectedRegistrationDate = '01/09/2019';
        $expectedRegistrationDate = \DateTime::createFromFormat('d/m/Y', $expectedRegistrationDate);

        $lpaRegistrationDate = (new DateTime($lpaRetrieved['registrationDate']))->format('d/m/Y');
        $lpaRegistrationDate = \DateTime::createFromFormat('d/m/Y', $lpaRegistrationDate);

        //check if lpa status is Registered and registration date falls after 1 Sep 2019
        if ($lpaRetrieved['status'] !== 'Registered') {
            $this->logger->notice(
                'User entered LPA {uId} does not have the required status',
                [
                    'uId' => $lpaRetrieved[uId],
                ]
            );
            return false;
        }
        if ($lpaRegistrationDate < $expectedRegistrationDate) {
            $this->logger->notice(
                'User entered LPA {uId} has a registration date before 1 September 2019',
                [
                    'uId' => $lpaRetrieved[uId],
                ]
            );
            return false;
        }
        return true;
    }

    /**
     * Get an LPA using the lpa reference number.
     *
     * @param array $dataToMatch
     * @return ?array A structure that contains processed LPA
     */
    public function checkLPAMatchAndGetActorDetails(array $dataToMatch): ?array
    {

        $lpaAndActorMatchResponse = null;

        // Cleanse user provided data
        $dataToMatch = $this->cleanseUserData($dataToMatch);

        //Get LPA by reference number
        $lpaMatchResponse = $this->getByUid($dataToMatch['reference_number']);

        if (is_null($lpaMatchResponse)) {
            $this->logger->info(
                'The LPA {uId} entered by user is not found in Sirius',
                [
                    'uId' => $dataToMatch['reference_number']
                ]
            );

            throw new NotFoundException('LPA not found');
        }

        //Check if lpa registration date falls after 01-09-2019, else cannot send activation key
        $registrationDetailsCheckResponse = $this->checkLpaRegistrationDetails($lpaMatchResponse->getData());

        if (!$registrationDetailsCheckResponse) {
            $this->logger->info(
                'Lpa {uId} has a registration date before 1 September 2019',
                [
                    'uId' => $dataToMatch['reference_number']
                ]
            );
            throw new BadRequestException('LPA not eligible');
        }

        //Check and compare user provided data with lpa data and return actor details
        $lpaAndActorMatchResponse = $this->compareAndLookupActiveActorInLpa($lpaMatchResponse->getData(), $dataToMatch);
        if (is_null($lpaAndActorMatchResponse)) {
            $this->logger->info(
                'Actor details for LPA {uId} not found',
                [
                    'uId' => $dataToMatch['reference_number']
                ]
            );
            throw new BadRequestException('LPA details does not match');
        }

        // Check if activation key already exist for LPA

        return $lpaAndActorMatchResponse;

    }
}
