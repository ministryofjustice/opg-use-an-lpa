<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatuses;
use App\DataAccess\Repository\{InstructionsAndPreferencesImagesInterface,
    LpasInterface,
    Response\Lpa,
    Response\LpaInterface,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use App\Exception\GoneException;
use App\Service\Features\FeatureEnabled;
use DateTime;
use Psr\Log\LoggerInterface;
use RuntimeException;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;

class SiriusLpaManager implements LpaManagerInterface
{
    private const ACTIVE_TC         = 0;

    public function __construct(
        private UserLpaActorMapInterface $userLpaActorMapRepository,
        private LpasInterface $lpaRepository,
        private ViewerCodesInterface $viewerCodesRepository,
        private ViewerCodeActivityInterface $viewerCodeActivityRepository,
        private InstructionsAndPreferencesImagesInterface $iapRepository,
        private ResolveActor $resolveActor,
        private GetAttorneyStatus $getAttorneyStatus,
        private IsValidLpa $isValidLpa,
        private GetTrustCorporationStatus $getTrustCorporationStatus,
        private FeatureEnabled $featureEnabled,
        private LoggerInterface $logger,
    ) {
    }

    public function getByUid(string $uid): ?LpaInterface
    {
        $lpa = $this->lpaRepository->get($uid);
        if ($lpa === null) {
            return null;
        }

        $lpaData = $lpa->getData();

        if ($lpaData['attorneys'] !== null) {
            $lpaData['original_attorneys'] = $lpaData['attorneys'];
            $lpaData['activeAttorneys']    = array_values(
                array_filter($lpaData['attorneys'], function ($attorney) {
                    return ($this->getAttorneyStatus)($attorney) === AttorneyStatus::ACTIVE_ATTORNEY;
                })
            );
        }

        if ($lpaData['attorneys'] !== null) {
            $lpaData['original_attorneys'] = $lpaData['attorneys'];
            $lpaData['inactiveAttorneys']  = array_values(
                array_filter($lpaData['attorneys'], function ($attorney) {
                    return ($this->getAttorneyStatus)($attorney) === AttorneyStatus::INACTIVE_ATTORNEY;
                })
            );
        }

        if ($lpaData['trustCorporations'] !== null) {
                $lpaData['trustCorporations'] = array_values(
                    array_filter($lpaData['trustCorporations'], function ($trustCorporation) {
                        return ($this->getTrustCorporationStatus)($trustCorporation)
                            === TrustCorporationStatuses::ACTIVE_TC->value;
                    })
                );
        }

        return $lpa;
    }

    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        $map = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $map['UserId']) {
            return null;
        }

        $lpa = $this->getByUid($map['SiriusUid']);

        if ($lpa === null) {
            return null;
        }

        $lpaData = $lpa->getData();
        unset($lpaData['original_attorneys']);

        $result = [
            'user-lpa-actor-token' => $map['Id'],
            'date'                 => $lpa->getLookupTime()->format('c'),
            'lpa'                  => $lpaData,
            'activationKeyDueDate' => $map['DueBy'] ?? null,
        ];

        // If an actor has been stored against an LPA then attempt to resolve it from the API return
        if (isset($map['ActorId'])) {
            // If an active attorney is not found then this is null
            $result['actor'] = ($this->resolveActor)($lpaData, (string) $map['ActorId']);
        }

        // Extract and return only LPA's where status is Registered or Cancelled
        if (($this->isValidLpa)($lpaData)) {
            return $result;
        }

        // LPA was found but is not valid for use.
        return [];
    }

    public function getAllForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getByUserId($userId);

        $lpaActorMaps = array_filter($lpaActorMaps, function ($item) {
            return !array_key_exists('ActivateBy', $item);
        });

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

    public function getAllLpasAndRequestsForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getByUserId($userId);

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

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
        // at this point as we only want to acknowledge if a code has expired if donor surname matched.

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

        $lpaData = $lpa->getData();
        unset($lpaData['original_attorneys']);

        $result = [
            'date'         => $lpa->getLookupTime()->format('c'),
            'expires'      => $viewerCodeData['Expires']->format('c'),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa'          => $lpaData,
        ];

        if (
            ($this->featureEnabled)('instructions_and_preferences') &&
            (($lpaData['applicationHasGuidance'] ?? false) || ($lpaData['applicationHasRestrictions'] ?? false))
        ) {
            $this->logger->info('The LPA has instructions and/or preferences. Fetching images');
            $result['iap'] = $this->iapRepository->getInstructionsAndPreferencesImages((int) $lpaData['uId']);
        }

        if (!is_null($organisation)) {
            // Record the lookup in the activity table
            // We only do this if the organisation is provided
            $this->viewerCodeActivityRepository->recordSuccessfulLookupActivity(
                $viewerCodeData['ViewerCode'],
                $organisation
            );
        }

        return $result;
    }

    /**
     * @param $lpaActorMaps array Map of LPAs from Dynamo
     * @return array an array with formatted LPA results
     */
    private function lookupAndFormatLpas(array $lpaActorMaps): array
    {
        $lpaUids = array_column($lpaActorMaps, 'SiriusUid');

        if (empty($lpaUids)) {
            return [];
        }

        // Return all the LPA details based on the Sirius Ids.
        $lpas = $this->lpaRepository->lookup($lpaUids);

        $result = [];

        // Map the results...
        foreach ($lpaActorMaps as $item) {
            $lpa = $lpas[$item['SiriusUid']] ?? null;

            if ($lpa === null) {
                continue;
            }

            $lpaData = $lpa->getData();
            $actor   = ($this->resolveActor)($lpaData, (string) $item['ActorId']);

            $added = $item['Added']->format('Y-m-d H:i:s');
            unset($lpaData['original_attorneys']);

            //Extract and return only LPA's where status is Registered or Cancelled
            if (($this->isValidLpa)($lpaData)) {
                $result[$item['Id']] = [
                    'user-lpa-actor-token' => $item['Id'],
                    'date'                 => $lpa->getLookupTime()->format('c'),
                    'actor'                => $actor,
                    'lpa'                  => $lpaData,
                    'added'                => $added,
                ];
            }
        }

        return $result;
    }
}
