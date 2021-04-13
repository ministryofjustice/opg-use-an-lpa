<?php

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\{LpasInterface,
    Response\Lpa,
    Response\LpaInterface,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface
};
use App\Exception\GoneException;
use DateTime;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class LpaService
 * @package App\Service\Lpa
 */
class LpaService
{
    private const ACTIVE_ATTORNEY = 0;

    private ViewerCodesInterface $viewerCodesRepository;
    private ViewerCodeActivityInterface $viewerCodeActivityRepository;
    private LpasInterface $lpaRepository;
    private UserLpaActorMapInterface $userLpaActorMapRepository;
    private LoggerInterface $logger;
    private ActorCodes $actorCodes;
    private ResolveActor $resolveActor;
    private GetAttorneyStatus $getAttorneyStatus;

    public function __construct(
        ViewerCodesInterface $viewerCodesRepository,
        ViewerCodeActivityInterface $viewerCodeActivityRepository,
        LpasInterface $lpaRepository,
        UserLpaActorMapInterface $userLpaActorMapRepository,
        LoggerInterface $logger,
        ActorCodes $actorCodes,
        ResolveActor $resolveActor,
        GetAttorneyStatus $getAttorneyStatus
    ) {
        $this->viewerCodesRepository = $viewerCodesRepository;
        $this->viewerCodeActivityRepository = $viewerCodeActivityRepository;
        $this->lpaRepository = $lpaRepository;
        $this->userLpaActorMapRepository = $userLpaActorMapRepository;
        $this->logger = $logger;
        $this->actorCodes = $actorCodes;
        $this->resolveActor = $resolveActor;
        $this->getAttorneyStatus = $getAttorneyStatus;
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
                return ($this->getAttorneyStatus)($attorney) === self::ACTIVE_ATTORNEY;
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
        $actor = ($this->resolveActor)($lpaData, $map['ActorId']);
        unset($lpaData['original_attorneys']);

        // If an active attorney is not found then we should not return an lpa
        if (is_null($actor)) {
            return null;
        }

        $result = [];
        //Extract and return only LPA's where status is Registered or Cancelled
        if (strtolower($lpaData['status']) === 'registered' ||
            strtolower($lpaData['status']) === 'cancelled') {
            $result = [
                'user-lpa-actor-token' => $map['Id'],
                'date' => $lpa->getLookupTime()->format('c'),
                'actor' => $actor,
                'lpa' => $lpaData,
            ];
        }

        return $result;
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
            $actor = ($this->resolveActor)($lpaData, $item['ActorId']);
            $added = $item['Added']->format('Y-m-d H:i:s');
            unset($lpaData['original_attorneys']);

            //Extract and return only LPA's where status is Registered or Cancelled
            if (strtolower($lpaData['status']) === 'registered' ||
                strtolower($lpaData['status']) === 'cancelled') {

                $result[$item['Id']] = [
                    'user-lpa-actor-token' => $item['Id'],
                    'date' => $lpa->getLookupTime()->format('c'),
                    'actor' => $actor,
                    'lpa' => $lpaData,
                    'added' => $added
                ];
            }
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
            'date' => $lpa->getLookupTime()->format('c'),
            'expires' => $viewerCodeData['Expires']->format('c'),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa' => $lpaData,
        ];

        if (isset($viewerCodeData['Cancelled'])) {
            $lpaData['cancelled'] = $viewerCodeData['Cancelled']->format('c');
        }

        return $lpaData;
    }
}
